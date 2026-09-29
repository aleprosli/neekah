<?php

use App\Actions\CreateBooking;
use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\CancellationReason;
use App\Enums\DepositChannel;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wedding;
use App\Notifications\BookingCancelled;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    enableOnlineBooking();
    $this->vendor = Vendor::factory()->for(Category::first())->takingOnlineBookings()->create();
    $this->package = Package::factory()->for($this->vendor)->create(['price' => 3000]);
    $this->couple = User::factory()->create();
    Wedding::factory()->for($this->couple)->create();
    $this->date = now()->addMonths(2)->toDateString();
});

function book(array $overrides = []): TestResponse
{
    return test()->actingAs(test()->couple)->post(route('vendors.bookings.store', test()->vendor), [
        'package_id' => test()->package->id,
        'event_date' => test()->date,
        ...$overrides,
    ]);
}

it('keeps a basic vendor on WhatsApp: no form, and no booking endpoint', function () {
    $basic = Vendor::factory()->for(Category::first())->create();
    Package::factory()->for($basic)->create();

    $this->get(route('vendors.show', $basic))->assertOk()->assertDontSee(route('vendors.bookings.store', $basic));
    $this->actingAs($this->couple)->post(route('vendors.bookings.store', $basic), ['package_id' => 1, 'event_date' => $this->date])->assertNotFound();
});

it('shows a pro vendor taking online bookings the form, the date picker and where the money goes', function () {
    $this->get(route('vendors.show', $this->vendor))
        ->assertOk()
        ->assertSee(route('vendors.bookings.store', $this->vendor), false)
        ->assertSee('data-vue="vendor-date-picker"', false)
        ->assertSee(__('pages.online_booking.money_note'));
});

it('holds the date with the vendor deposit and shows the vendor bank details for a transfer', function () {
    book()->assertRedirect();

    $booking = Booking::sole();

    expect($booking->source)->toBe(BookingSource::Online)
        ->and($booking->payment_mode)->toBe(DepositChannel::Manual)
        ->and((float) $booking->deposit_amount)->toBe(900.0)
        ->and($booking->hold_expires_at->diffInHours(now(), true))->toBeGreaterThan(23)
        ->and($booking->status)->toBe(BookingStatus::PendingPayment);

    $this->actingAs($this->couple)->get(route('bookings.show', $booking))
        ->assertOk()
        ->assertSee('RM900.00')
        ->assertSee($this->vendor->bookingSettings->manual_instructions);
});

it('refuses a weekday the vendor does not open and a date already full', function () {
    $this->vendor->bookingSettings->update(['available_weekdays' => [6, 7]]);
    $weekday = now()->addMonths(2)->next('Wednesday')->toDateString();

    book(['event_date' => $weekday])->assertSessionHasErrors(['event_date' => __('validation.custom.date_weekday_off')]);

    $saturday = now()->addMonths(2)->next('Saturday')->toDateString();
    Booking::factory()->for($this->vendor)->create(['event_date' => $saturday]);

    book(['event_date' => $saturday])->assertSessionHasErrors('event_date');
    expect(Booking::where('source', 'online')->count())->toBe(0);
});

it('never lets two bookings take the last place, even past the form check', function () {
    $create = app(CreateBooking::class);
    $create->handle($this->couple, $this->vendor, $this->package, ['event_date' => $this->date], online: true);

    expect(fn () => $create->handle(User::factory()->create(), $this->vendor, $this->package, ['event_date' => $this->date], online: true))
        ->toThrow(ValidationException::class);

    expect(Booking::count())->toBe(1);
});

it('requires the vendor deposit terms to be accepted when there are any', function () {
    $this->vendor->bookingSettings->update(['deposit_terms' => 'Deposit tidak dikembalikan.']);

    book()->assertSessionHasErrors('terms');
    book(['terms' => '1'])->assertSessionHasNoErrors();
});

it('serves a month of statuses for the date picker, and nothing about who booked', function () {
    Booking::factory()->for($this->vendor)->create(['event_date' => $this->date]);
    $month = substr($this->date, 0, 7);

    $response = $this->getJson(route('vendors.availability', [$this->vendor, 'bulan' => $month]))->assertOk();

    expect($response->json("days.{$this->date}.status"))->toBe('full')
        ->and($response->getContent())->not->toContain('NK-');
});

it('releases an unpaid hold after it runs out, but keeps one with a receipt waiting', function () {
    Notification::fake();
    book();
    book(['event_date' => now()->addMonths(3)->toDateString()]);
    [$unpaid, $receipted] = Booking::orderBy('id')->get()->all();

    Payment::factory()->for($receipted)->create(['status' => PaymentStatus::AwaitingVerification]);

    $this->travel(25)->hours();
    $this->artisan('neekah:expire-booking-holds')->assertSuccessful();

    expect($unpaid->fresh()->status)->toBe(BookingStatus::Cancelled)
        ->and($unpaid->fresh()->cancelled_reason)->toBe(CancellationReason::Expired)
        ->and($receipted->fresh()->status)->toBe(BookingStatus::PendingPayment);

    Notification::assertSentTo($this->couple, BookingCancelled::class, fn (BookingCancelled $notice) => $notice->canceller === null);
});

it('confirms a transferred deposit when the vendor verifies it, with the booking points', function () {
    book();
    $booking = Booking::sole();

    $this->actingAs($this->couple)->post(route('bookings.payments.store', $booking), ['amount' => 900, 'paid_on' => now()->toDateString()]);
    $this->actingAs($this->vendor->user)->post(route('vendor.bookings.payments.verify', [$booking, Payment::sole()]))->assertRedirect();

    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed)
        ->and($booking->fresh()->hold_expires_at)->toBeNull()
        ->and($this->vendor->fresh()->points_total)->toBe(100 + 100);
});

it('gives the couple a fresh hold when the vendor rejects their receipt', function () {
    book();
    $booking = Booking::sole();
    $this->actingAs($this->couple)->post(route('bookings.payments.store', $booking), ['amount' => 900, 'paid_on' => now()->toDateString()]);

    $this->travel(23)->hours();
    $this->actingAs($this->vendor->user)->delete(route('vendor.bookings.payments.reject', [$booking, Payment::sole()]));

    expect($booking->fresh()->hold_expires_at->isAfter(now()->addHours(23)))->toBeTrue();
});

it('lets the vendor cancel a paid booking and record the refund', function () {
    $booking = Booking::factory()->for($this->couple)->for($this->vendor)->create(['status' => BookingStatus::Confirmed]);
    $payment = Payment::factory()->for($booking)->create(['status' => PaymentStatus::Paid, 'amount' => 900]);

    $this->actingAs($this->vendor->user)->post(route('vendor.bookings.cancel', $booking), [])->assertSessionHasErrors('reason');
    $this->actingAs($this->vendor->user)->post(route('vendor.bookings.cancel', $booking), ['reason' => 'Pasukan tidak cukup'])->assertRedirect();

    expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled)
        ->and($booking->fresh()->cancelled_reason)->toBe(CancellationReason::Vendor);

    $this->actingAs($this->vendor->user)->post(route('vendor.bookings.payments.refunded', [$booking, $payment]))->assertRedirect();
    expect($payment->fresh()->status)->toBe(PaymentStatus::Refunded);

    $other = Vendor::factory()->for(Category::first())->create();
    $this->actingAs($other->user)->post(route('vendor.bookings.cancel', $booking), ['reason' => 'x'])->assertForbidden();
});
