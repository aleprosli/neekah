<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\PointReason;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorUnavailableDate;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->customer = User::factory()->create();
    $this->vendor = Vendor::factory()->for(Category::first())->create();
    $this->package = Package::factory()->for($this->vendor)->create(['name' => 'Premium Package', 'price' => 2500]);
});

it('redirects guests to login when booking', function () {
    $this->post(route('vendors.bookings.store', $this->vendor), ['package_id' => $this->package->id, 'event_date' => now()->addMonths(3)->toDateString()])
        ->assertRedirect(route('login'));
});

it('creates a pending booking with no payments attached to it', function () {
    $eventDate = now()->addMonths(3)->toDateString();

    $this->actingAs($this->customer)
        ->post(route('vendors.bookings.store', $this->vendor), [
            'package_id' => $this->package->id,
            'event_date' => $eventDate,
            'notes' => 'Majlis di dewan, 500 tetamu',
        ])
        ->assertRedirect();

    $booking = Booking::sole();

    expect($booking->user_id)->toBe($this->customer->id)
        ->and($booking->vendor_id)->toBe($this->vendor->id)
        ->and($booking->package_name)->toBe('Premium Package')
        ->and($booking->event_date->toDateString())->toBe($eventDate)
        ->and((float) $booking->total_amount)->toBe(2500.0)
        // Neekah is free for now: a new booking carries no commission.
        ->and((float) $booking->commission_rate)->toBe(0.0)
        ->and((float) $booking->commission_amount)->toBe(0.0)
        ->and($booking->status)->toBe(BookingStatus::PendingPayment)
        ->and($booking->reference)->toStartWith('NK-')
        ->and($booking->payments)->toBeEmpty();

    $props = $this->get(route('bookings.show', $booking))->assertOk()->viewData('props');

    expect($props['booking']['reference'])->toBe($booking->reference)
        ->and($props['payments'])->toBeEmpty()
        ->and($props['paymentForm']['outstandingLabel'])->toBe('RM2,500.00');
});

it('rejects a booking on a date the vendor is unavailable or already booked', function () {
    $blocked = now()->addMonths(2)->toDateString();
    $booked = now()->addMonths(4)->toDateString();
    VendorUnavailableDate::factory()->for($this->vendor)->create(['date' => $blocked]);
    Booking::factory()->confirmed()->for($this->vendor)->create(['event_date' => $booked]);

    $this->actingAs($this->customer)
        ->post(route('vendors.bookings.store', $this->vendor), ['package_id' => $this->package->id, 'event_date' => $blocked])
        ->assertSessionHasErrors('event_date');

    $this->actingAs($this->customer)
        ->post(route('vendors.bookings.store', $this->vendor), ['package_id' => $this->package->id, 'event_date' => $booked])
        ->assertSessionHasErrors('event_date');

    expect(Booking::count())->toBe(1);
});

it('rejects a package that belongs to another vendor or a past date', function () {
    $otherPackage = Package::factory()->create();

    $this->actingAs($this->customer)
        ->post(route('vendors.bookings.store', $this->vendor), ['package_id' => $otherPackage->id, 'event_date' => now()->subDay()->toDateString()])
        ->assertSessionHasErrors(['package_id', 'event_date']);
});

it('forbids vendors from booking as customers', function () {
    $vendorUser = User::factory()->vendor()->create();

    $this->actingAs($vendorUser)
        ->post(route('vendors.bookings.store', $this->vendor), ['package_id' => $this->package->id, 'event_date' => now()->addMonth()->toDateString()])
        ->assertForbidden();
});

it('records a payment that waits for the vendor, then confirms the booking once verified', function () {
    $booking = Booking::factory()->for($this->customer)->for($this->vendor)->create(['total_amount' => 2500]);

    $this->actingAs($this->customer)
        ->post(route('bookings.payments.store', $booking), [
            'amount' => 1000,
            'paid_on' => now()->subDay()->toDateString(),
            'note' => 'Transfer Maybank2u',
        ])
        ->assertRedirect(route('bookings.show', $booking));

    $payment = Payment::sole();

    // Recording it alone changes nothing: only the vendor can see their account.
    expect($payment->status)->toBe(PaymentStatus::AwaitingVerification)
        ->and($payment->recorded_by)->toBe($this->customer->id)
        ->and($booking->fresh()->status)->toBe(BookingStatus::PendingPayment)
        ->and($booking->fresh()->paidAmount())->toBe(0.0);

    $this->actingAs($this->vendor->user)
        ->post(route('vendor.bookings.payments.verify', [$booking, $payment]))
        ->assertRedirect(route('vendor.bookings.show', $booking));

    $booking->refresh();
    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($payment->fresh()->verified_by)->toBe($this->vendor->user->id)
        ->and($booking->status)->toBe(BookingStatus::Confirmed)
        ->and($booking->confirmed_at)->not->toBeNull()
        ->and($booking->paidAmount())->toBe(1000.0);
});

it('refuses to record more than the booking is worth', function () {
    $booking = Booking::factory()->for($this->customer)->for($this->vendor)->create(['total_amount' => 2500]);

    $this->actingAs($this->customer)
        ->post(route('bookings.payments.store', $booking), ['amount' => 2600, 'paid_on' => now()->toDateString()])
        ->assertSessionHasErrors('amount');

    $this->actingAs($this->customer)
        ->post(route('bookings.payments.store', $booking), ['amount' => 2500, 'paid_on' => now()->toDateString()])
        ->assertRedirect();

    // The first record already claims the whole amount, so a second cannot.
    $this->actingAs($this->customer)
        ->post(route('bookings.payments.store', $booking), ['amount' => 100, 'paid_on' => now()->toDateString()])
        ->assertSessionHasErrors('amount');

    expect($booking->payments()->count())->toBe(1);
});

it('lets the couple cancel a booking made by mistake, but not one already paid', function () {
    $booking = Booking::factory()->for($this->customer)->for($this->vendor)->create(['total_amount' => 2500]);

    $this->actingAs($this->customer)
        ->post(route('bookings.cancel', $booking), ['reason' => 'Tersilap tekan'])
        ->assertRedirect(route('bookings.show', $booking));

    $booking->refresh();
    expect($booking->status)->toBe(BookingStatus::Cancelled)
        ->and($booking->cancelled_at)->not->toBeNull()
        ->and($booking->notes)->toContain('Tersilap tekan')
        ->and($this->vendor->points()->where('reason', PointReason::PlatformBooking)->count())->toBe(0);

    $paid = Booking::factory()->confirmed()->for($this->customer)->for($this->vendor)->create();
    Payment::factory()->for($paid)->paid()->create();

    $this->actingAs($this->customer)->post(route('bookings.cancel', $paid))->assertForbidden();
    expect($paid->fresh()->status)->toBe(BookingStatus::Confirmed);
});

it('keeps a stranger from cancelling or recording a payment', function () {
    $booking = Booking::factory()->for($this->customer)->for($this->vendor)->create();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->post(route('bookings.cancel', $booking))->assertForbidden();
    $this->actingAs($stranger)
        ->post(route('bookings.payments.store', $booking), ['amount' => 100, 'paid_on' => now()->toDateString()])
        ->assertForbidden();
});

it('only lets the owning customer or the vendor view a booking', function () {
    $booking = Booking::factory()->for($this->customer)->for($this->vendor)->create();
    Payment::factory()->for($booking)->create();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->get(route('bookings.show', $booking))->assertForbidden();
    $this->actingAs($this->vendor->user)->get(route('bookings.show', $booking))->assertRedirect(route('vendor.bookings.show', $booking));
    $this->actingAs($this->customer)->get(route('bookings.index'))->assertOk()->assertSee($booking->reference);
});
