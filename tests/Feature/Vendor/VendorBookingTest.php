<?php

use App\Enums\BookingStatus;
use App\Enums\EnquiryStatus;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Enquiry;
use App\Models\Package;
use App\Models\Review;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->vendor = Vendor::factory()->for(Category::first())->create();
    $this->package = Package::factory()->for($this->vendor)->create(['price' => 3000]);
    $this->customer = User::factory()->create(['email' => 'aina@example.com']);
});

it('lets a vendor record a booking for a registered customer', function () {
    $eventDate = now()->addMonths(2)->toDateString();

    $this->actingAs($this->vendor->user)
        ->post(route('vendor.bookings.store'), [
            'customer_email' => 'aina@example.com',
            'package_id' => $this->package->id,
            'event_date' => $eventDate,
            'notes' => 'Dibincang melalui WhatsApp',
        ])
        ->assertRedirect();

    $booking = Booking::sole();

    expect($booking->user_id)->toBe($this->customer->id)
        ->and((float) $booking->deposit_amount)->toBe(1200.0)
        ->and((float) $booking->commission_amount)->toBe(240.0)
        ->and($booking->status)->toBe(BookingStatus::PendingPayment);

    $this->actingAs($this->customer)->get(route('bookings.show', $booking))->assertOk()->assertSee('Bayar Deposit sekarang');
    $this->actingAs($this->vendor->user)->get(route('vendor.bookings.index'))->assertOk()->assertSee($booking->reference);
});

it('rejects a booking for an unknown customer email', function () {
    $this->actingAs($this->vendor->user)
        ->post(route('vendor.bookings.store'), ['customer_email' => 'nobody@example.com', 'package_id' => $this->package->id, 'event_date' => now()->addMonth()->toDateString()])
        ->assertSessionHasErrors('customer_email');
});

it('completes a confirmed booking after the event and refreshes vendor stats', function () {
    $booking = Booking::factory()->confirmed()->for($this->customer)->for($this->vendor)->create(['event_date' => now()->subDays(2)->toDateString()]);
    $future = Booking::factory()->confirmed()->for($this->customer)->for($this->vendor)->create(['event_date' => now()->addDays(30)->toDateString()]);

    $this->actingAs($this->vendor->user)->post(route('vendor.bookings.complete', $future))->assertSessionHasErrors('booking');

    $this->actingAs($this->vendor->user)
        ->post(route('vendor.bookings.complete', $booking))
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe(BookingStatus::Completed)
        ->and($this->vendor->fresh()->completed_bookings_count)->toBe(1)
        ->and((float) $this->vendor->fresh()->score)->toBeGreaterThan(0);
});

it('does not let another vendor complete or view the booking', function () {
    $booking = Booking::factory()->confirmed()->for($this->customer)->for($this->vendor)->create(['event_date' => now()->subDay()->toDateString()]);
    $otherVendor = Vendor::factory()->for(Category::first())->create();

    $this->actingAs($otherVendor->user)->post(route('vendor.bookings.complete', $booking))->assertForbidden();
    $this->actingAs($otherVendor->user)->get(route('vendor.bookings.show', $booking))->assertForbidden();
});

it('lists and replies to enquiries', function () {
    $enquiry = Enquiry::factory()->for($this->customer)->for($this->vendor)->create(['message' => 'Ada slot 20 Disember?']);

    $this->actingAs($this->vendor->user)->get(route('vendor.enquiries.index'))->assertOk()->assertSee('Ada slot 20 Disember?');

    $this->actingAs($this->vendor->user)
        ->put(route('vendor.enquiries.update', $enquiry), ['reply' => 'Ya, masih ada.'])
        ->assertRedirect(route('vendor.enquiries.show', $enquiry));

    expect($enquiry->fresh()->status)->toBe(EnquiryStatus::Replied)
        ->and($enquiry->fresh()->reply)->toBe('Ya, masih ada.');
});

it('shows the customer review on the vendor booking page', function () {
    $review = Review::factory()->create(['vendor_id' => $this->vendor->id, 'comment' => 'Terbaik!']);
    $review->booking->update(['vendor_id' => $this->vendor->id]);

    $this->actingAs($this->vendor->user)->get(route('vendor.bookings.show', $review->booking))->assertOk()->assertSee('Terbaik!');
});
