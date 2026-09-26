<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBookingSetting;
use App\Models\Wedding;
use App\Notifications\DepositNeedsRefund;
use Database\Seeders\CategorySeeder;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    config()->set('services.herepay', ['base_url' => 'https://uat.herepay.org', 'secret_key' => 'neekah-secret', 'private_key' => 'neekah-private']);
    enableOnlineBooking();

    $this->vendor = Vendor::factory()->for(Category::first())->create(['pro_until' => now()->addYear()]);
    VendorBookingSetting::factory()->withHerepay()->for($this->vendor)->create(['manual_instructions' => null]);
    $this->package = Package::factory()->for($this->vendor)->create(['price' => 3000]);
    $this->couple = User::factory()->create(['email' => 'aina@example.com']);
    Wedding::factory()->for($this->couple)->create();

    $this->herepayRefuses = false;
    Http::fake(['uat.herepay.org/*' => fn () => $this->herepayRefuses
        ? Http::response(['status' => 'Unauthorized'], 401)
        : Http::response(['status' => 200, 'data' => ['pay_url' => 'https://uat.herepay.org/herepay/pay/DEP']])]);
});

function bookOnline(): TestResponse
{
    return test()->actingAs(test()->couple)->post(route('vendors.bookings.store', test()->vendor), [
        'package_id' => test()->package->id,
        'event_date' => now()->addMonths(2)->toDateString(),
    ]);
}

/**
 * Herepay's callback for a deposit, checksummed with the given private key.
 *
 * @param  array<string, string>  $fields
 */
function depositCallback(Payment $payment, array $fields = [], string $key = 'vendor-private', ?string $reference = null): TestResponse
{
    $fields = ['payment_code' => 'PAY-1', 'status_code' => '00', 'amount' => (string) $payment->amount, 'currency' => 'MYR', ...$fields];
    $sorted = $fields;
    ksort($sorted);

    return test()->post(URL::signedRoute('webhooks.herepay.booking', ['ref' => $reference ?? $payment->reference], absolute: false), [
        ...$fields,
        'checksum' => hash_hmac('sha256', implode(',', $sorted), $key),
    ]);
}

it('sends the couple to pay the deposit on the vendor own Herepay account', function () {
    bookOnline()->assertRedirect('https://uat.herepay.org/herepay/pay/DEP');

    $booking = Booking::sole();
    $payment = Payment::sole();

    expect((float) $payment->amount)->toBe(900.0)
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->payment_url)->toBe('https://uat.herepay.org/herepay/pay/DEP');

    Http::assertSent(fn (ClientRequest $request): bool => $request->hasHeader('SecretKey', 'vendor-secret')
        && $request['amount'] === 900.0
        && $request['usage_type'] === 'single'
        && $request['redirect_url'] === route('bookings.payment.done', $booking)
        && str_contains($request['callback_url'], '/webhooks/herepay/tempahan')
        && str_contains($request['callback_url'], 'signature='));
});

it('confirms the booking on a callback signed with the vendor key, once however often it comes', function () {
    Notification::fake();
    bookOnline();
    $payment = Payment::sole();

    depositCallback($payment)->assertOk();
    depositCallback($payment)->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($payment->booking->fresh()->status)->toBe(BookingStatus::Confirmed)
        ->and($this->vendor->fresh()->points_total)->toBe(100 + 100)
        ->and($this->vendor->bookingSettings->fresh()->herepay_verified_at)->not->toBeNull();
});

it('refuses a callback checksummed with any other key, Neekah own included', function () {
    bookOnline();
    $payment = Payment::sole();

    depositCallback($payment, key: 'neekah-private')->assertForbidden();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

it('refuses a callback whose reference was changed', function () {
    bookOnline();
    $payment = Payment::sole();
    $url = URL::signedRoute('webhooks.herepay.booking', ['ref' => $payment->reference], absolute: false);

    $this->post(str_replace($payment->reference, 'PAY-OTHER', $url), ['status_code' => '00'])->assertForbidden();
});

it('does not confirm a payment for less than the deposit', function () {
    bookOnline();
    $payment = Payment::sole();

    depositCallback($payment, ['amount' => '1.00'])->assertStatus(422);

    expect($payment->booking->fresh()->status)->toBe(BookingStatus::PendingPayment);
});

it('puts a lapsed booking back when a late deposit lands on a date still free', function () {
    bookOnline();
    $payment = Payment::sole();

    $this->travel(25)->hours();
    $this->artisan('neekah:expire-booking-holds');
    expect($payment->booking->fresh()->status)->toBe(BookingStatus::Cancelled);

    depositCallback($payment)->assertOk();

    expect($payment->booking->fresh()->status)->toBe(BookingStatus::Confirmed);
});

it('tells both sides the vendor owes a refund when a late deposit finds the date taken', function () {
    Notification::fake();
    bookOnline();
    $payment = Payment::sole();

    $this->travel(25)->hours();
    $this->artisan('neekah:expire-booking-holds');
    Booking::factory()->for($this->vendor)->create(['event_date' => $payment->booking->event_date]);

    depositCallback($payment)->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($payment->booking->fresh()->status)->toBe(BookingStatus::Cancelled);
    Notification::assertSentTo($this->couple, DepositNeedsRefund::class);
    Notification::assertSentTo($this->vendor->user, DepositNeedsRefund::class);
});

it('keeps the hold and offers to pay again when Herepay refuses the link', function () {
    $this->herepayRefuses = true;

    bookOnline()->assertRedirect(route('bookings.show', Booking::sole()));

    expect(Booking::sole()->isHeld())->toBeTrue()
        ->and(Payment::sole()->status)->toBe(PaymentStatus::Failed);
});

it('reuses the open link when the couple pays again', function () {
    bookOnline();
    $booking = Booking::sole();

    $this->actingAs($this->couple)->post(route('bookings.deposit.pay', $booking))->assertRedirect('https://uat.herepay.org/herepay/pay/DEP');

    expect(Payment::count())->toBe(1);
    Http::assertSentCount(1);
});

it('shows the couple what is known when Herepay sends them back', function () {
    bookOnline();
    $booking = Booking::sole();

    $this->actingAs($this->couple)->get(route('bookings.payment.done', $booking))->assertOk()->assertSee(__('pages.online_booking.done_waiting_title'));

    depositCallback(Payment::sole());

    $this->actingAs($this->couple)->get(route('bookings.payment.done', $booking))->assertOk()->assertSee(__('pages.online_booking.done_confirmed_title'));
});
