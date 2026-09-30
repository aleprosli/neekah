<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\PaymentReceipt;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->vendor = Vendor::factory()->pro()->for(Category::first())->create();
    $this->couple = User::factory()->create(['name' => 'Aina Zulkifli']);
    Sanctum::actingAs($this->vendor->user);
});

it('lists only the vendor own bookings, filtered, searched, with a count per status', function () {
    $upcoming = Booking::factory()->for($this->vendor)->for($this->couple)->confirmed()->create(['event_date' => now()->addMonth()]);
    Booking::factory()->for($this->vendor)->create(['status' => BookingStatus::PendingPayment]);
    Booking::factory()->for($this->vendor)->completed()->create();
    Booking::factory()->create();

    $this->getJson(route('api.v1.bookings.index'))
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('counts.confirmed', 1)
        ->assertJsonPath('counts.pending_payment', 1)
        ->assertJsonPath('counts.cancelled', 0)
        ->assertJsonPath('meta.total', 3);

    $this->getJson(route('api.v1.bookings.index', ['status' => 'confirmed,completed']))->assertJsonCount(2, 'data');
    $this->getJson(route('api.v1.bookings.index', ['search' => 'Aina']))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.reference', $upcoming->reference)
        ->assertJsonPath('data.0.status.value', 'confirmed')
        ->assertJsonPath('data.0.customer_name', 'Aina Zulkifli');
});

it('shows one booking with what the vendor may do to it, and never another vendor own', function () {
    $booking = Booking::factory()->for($this->vendor)->for($this->couple)->confirmed()->create(['event_date' => now()->subDay()]);
    Payment::factory()->paid()->for($booking)->create(['amount' => 500]);

    $this->getJson(route('api.v1.bookings.show', $booking))
        ->assertOk()
        ->assertJsonPath('data.customer.name', 'Aina Zulkifli')
        ->assertJsonPath('data.paid', 500)
        ->assertJsonPath('data.payments.0.can.mark_refunded', true)
        ->assertJsonPath('data.can.complete', true);

    $this->getJson(route('api.v1.bookings.show', Booking::factory()->create()))->assertForbidden();
});

it('confirms a transfer the couple recorded, which confirms the booking and sends the receipt', function () {
    Notification::fake();
    $booking = Booking::factory()->for($this->vendor)->for($this->couple)->create(['status' => BookingStatus::PendingPayment]);
    $payment = Payment::factory()->for($booking)->create(['amount' => 900]);

    $this->postJson(route('api.v1.bookings.payments.verify', [$booking, $payment]))
        ->assertOk()
        ->assertJsonPath('data.status.value', 'confirmed')
        ->assertJsonPath('data.payments.0.status.value', 'paid')
        ->assertJsonPath('message', __('flash.vendor.payment_verified', ['amount' => '900.00']));

    Notification::assertSentTo($this->couple, PaymentReceipt::class);
});

it('rejects a transfer that never arrived, and refuses to touch one already settled', function () {
    $booking = Booking::factory()->for($this->vendor)->create();
    $awaiting = Payment::factory()->for($booking)->create();
    $paid = Payment::factory()->paid()->for($booking)->create();

    $this->postJson(route('api.v1.bookings.payments.reject', [$booking, $awaiting]))->assertOk();
    $this->postJson(route('api.v1.bookings.payments.verify', [$booking, $paid]))->assertForbidden();

    expect($awaiting->fresh()->status)->toBe(PaymentStatus::Failed);
});

it('records that a paid deposit was given back', function () {
    $booking = Booking::factory()->for($this->vendor)->cancelled()->create();
    $payment = Payment::factory()->paid()->for($booking)->create();

    $this->postJson(route('api.v1.bookings.payments.refunded', [$booking, $payment]))->assertOk()->assertJsonPath('data.payments.0.status.value', 'refunded');
});

it('refuses a payment that belongs to another booking', function () {
    $booking = Booking::factory()->for($this->vendor)->create();
    $elsewhere = Payment::factory()->create();

    $this->postJson(route('api.v1.bookings.payments.verify', [$booking, $elsewhere]))->assertNotFound();
});

it('completes a booking only once its day has passed', function () {
    $ahead = Booking::factory()->for($this->vendor)->confirmed()->create(['event_date' => now()->addWeek()]);
    $passed = Booking::factory()->for($this->vendor)->confirmed()->create(['event_date' => now()->subDay()]);

    $this->postJson(route('api.v1.bookings.complete', $ahead))->assertUnprocessable()->assertJsonValidationErrors('booking');
    $this->postJson(route('api.v1.bookings.complete', $passed))->assertOk()->assertJsonPath('data.status.value', 'completed');
});

it('cancels a booking with a reason', function () {
    $booking = Booking::factory()->for($this->vendor)->confirmed()->create();

    $this->postJson(route('api.v1.bookings.cancel', $booking))->assertUnprocessable()->assertJsonValidationErrors('reason');
    $this->postJson(route('api.v1.bookings.cancel', $booking), ['reason' => 'Tarikh bertembung'])->assertOk()->assertJsonPath('data.status.value', 'cancelled');
});
