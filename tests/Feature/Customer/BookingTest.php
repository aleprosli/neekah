<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
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

it('creates a pending booking with deposit and balance payments', function () {
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
        ->and((float) $booking->deposit_amount)->toBe(1000.0)
        ->and((float) $booking->commission_amount)->toBe(200.0)
        ->and($booking->status)->toBe(BookingStatus::PendingPayment)
        ->and($booking->reference)->toStartWith('NK-')
        ->and($booking->payments)->toHaveCount(2)
        ->and((float) $booking->depositPayment->amount)->toBe(1000.0)
        ->and((float) $booking->balancePayment->amount)->toBe(1500.0);

    $this->get(route('bookings.show', $booking))
        ->assertOk()
        ->assertSee($booking->reference)
        ->assertSee('Bayar Deposit sekarang');
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

it('confirms the booking when the sandbox deposit is paid, then settles the balance', function () {
    $booking = Booking::factory()->for($this->customer)->for($this->vendor)->create(['total_amount' => 2500, 'deposit_amount' => 1000]);
    $deposit = Payment::factory()->for($booking)->create(['type' => PaymentType::Deposit, 'amount' => 1000]);
    $balance = Payment::factory()->for($booking)->balance()->create(['amount' => 1500]);

    $this->actingAs($this->customer)
        ->post(route('bookings.payments.store', [$booking, $balance]))
        ->assertSessionHasErrors('payment');

    $this->actingAs($this->customer)
        ->post(route('bookings.payments.store', [$booking, $deposit]))
        ->assertRedirect(route('bookings.show', $booking));

    $booking->refresh();
    expect($booking->status)->toBe(BookingStatus::Confirmed)
        ->and($booking->confirmed_at)->not->toBeNull()
        ->and($deposit->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($deposit->fresh()->gateway_reference)->toStartWith('SBX-');

    $this->actingAs($this->customer)
        ->post(route('bookings.payments.store', [$booking, $balance]))
        ->assertRedirect(route('bookings.show', $booking));

    expect($booking->fresh()->isFullyPaid())->toBeTrue();
});

it('only lets the owning customer or the vendor view a booking', function () {
    $booking = Booking::factory()->for($this->customer)->for($this->vendor)->create();
    Payment::factory()->for($booking)->create();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->get(route('bookings.show', $booking))->assertForbidden();
    $this->actingAs($stranger)->post(route('bookings.payments.store', [$booking, $booking->payments()->first()]))->assertForbidden();
    $this->actingAs($this->vendor->user)->get(route('bookings.show', $booking))->assertRedirect(route('vendor.bookings.show', $booking));
    $this->actingAs($this->customer)->get(route('bookings.index'))->assertOk()->assertSee($booking->reference);
});
