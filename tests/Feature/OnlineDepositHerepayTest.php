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
    Http::fake([
        'uat.herepay.org/api/v1/herepay/payment/channels' => Http::response(herepayChannels()),
        'uat.herepay.org/api/v1/herepay/initiate' => fn () => $this->herepayRefuses
            ? Http::response(['status' => 'Unauthorized', 'status_code' => '41'], 401)
            : Http::response(herepayInitiatePage()),
    ]);
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

    return test()->post(URL::signedRoute('payments.webhook', ['gateway' => 'herepay', 'ref' => $reference ?? $payment->reference], absolute: false), [
        ...$fields,
        'checksum' => hash_hmac('sha256', implode(',', $sorted), $key),
    ]);
}

it('sends the couple to confirm and pay the deposit on the vendor own Herepay account', function () {
    $response = bookOnline();
    $payment = Payment::sole();
    $response->assertRedirect(route('payments.checkout', $payment));

    expect((float) $payment->amount)->toBe(900.0)
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->merchant)->toBe('vendor')
        ->and($payment->vendor_id)->toBe($this->vendor->id)
        ->and($payment->expires_at->equalTo(Booking::sole()->hold_expires_at))->toBeTrue();

    $this->actingAs($this->couple)->get(route('payments.checkout', $payment))->assertOk()->assertSee($this->vendor->name);
    $this->actingAs($this->couple)->post(route('payments.pay', $payment), ['payment_method' => 'Online Banking', 'bank_code' => 'ABB0233'])->assertOk();

    Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://uat.herepay.org/api/v1/herepay/payment/channels'
        && $request->hasHeader('SecretKey', 'vendor-secret')
        && $request->hasHeader('XApiKey', 'vendor-api'));
    Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://uat.herepay.org/api/v1/herepay/initiate'
        && $request->hasHeader('SecretKey', 'vendor-secret')
        && $request->hasHeader('XApiKey', 'vendor-api')
        && $request['amount'] === '900.00'
        && $request['bank_prefix'] === 'ABB0233'
        && $request['checksum'] === hash_hmac('sha256', collect($request->data())->except('checksum')->sortKeys()->implode(','), 'vendor-private')
        && $request['redirect_url'] === route('payments.return', $payment)
        && str_contains($request['callback_url'], '/webhooks/herepay?ref='.$payment->reference));
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
    $url = URL::signedRoute('payments.webhook', ['gateway' => 'herepay', 'ref' => $payment->reference], absolute: false);

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

it('keeps the hold and lets the couple try again when Herepay refuses to start the payment', function () {
    $this->herepayRefuses = true;
    bookOnline();
    $payment = Payment::sole();

    $this->actingAs($this->couple)
        ->from(route('payments.checkout', $payment))
        ->post(route('payments.pay', $payment), ['payment_method' => 'Online Banking', 'bank_code' => 'ABB0233'])
        ->assertRedirect(route('payments.checkout', $payment))
        ->assertSessionHasErrors('bank_code');

    expect(Booking::sole()->isHeld())->toBeTrue()
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

it('reuses the open payment when the couple pays again', function () {
    bookOnline();
    $booking = Booking::sole();

    $this->actingAs($this->couple)->post(route('bookings.deposit.pay', $booking))->assertRedirect(route('payments.checkout', Payment::sole()));

    expect(Payment::count())->toBe(1);
    Http::assertNothingSent();
});

it('shows the couple what is known when Herepay sends them back', function () {
    bookOnline();
    $booking = Booking::sole();
    $payment = Payment::sole();

    $this->actingAs($this->couple)->get(route('bookings.payment.done', $booking))->assertRedirect(route('payments.show', $payment));
    $this->actingAs($this->couple)->get(route('payments.show', $payment))->assertOk()->assertSee(__('pages.payment_page.waiting_title'));

    depositCallback($payment);

    $this->actingAs($this->couple)->get(route('payments.show', $payment))
        ->assertOk()
        ->assertSee(__('pages.payment_page.paid_title'))
        ->assertSee(__('pages.payment_page.paid_booking', ['vendor' => $this->vendor->name, 'date' => $booking->event_date->translatedFormat('j F Y')]));
});
