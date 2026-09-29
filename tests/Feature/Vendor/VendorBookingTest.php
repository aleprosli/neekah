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
    $this->vendor = Vendor::factory()->pro()->for(Category::first())->create();
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
        ->and((float) $booking->commission_amount)->toBe(0.0)
        ->and($booking->payments)->toBeEmpty()
        ->and($booking->status)->toBe(BookingStatus::PendingPayment);

    $props = $this->actingAs($this->customer)->get(route('bookings.show', $booking))->assertOk()->viewData('props');
    expect($props['paymentForm']['action'])->toBe(route('bookings.payments.store', $booking));
    // The list page is the shell; its rows come from the table's own endpoint.
    $this->actingAs($this->vendor->user)->get(route('vendor.bookings.index'))->assertOk()->assertSee('data-vue="data-table"', false);

    $rows = $this->actingAs($this->vendor->user)
        ->getJson(route('vendor.bookings.data'))
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->json('data');

    expect($rows[0]['customer'])->toBe($this->customer->name)
        ->and($rows[0]['url'])->toBe(route('vendor.bookings.show', $booking))
        // The money column reads total_amount; "total" is not a column at all.
        ->and($rows[0]['total'])->toBe('RM3,000.00');

    $this->actingAs($this->vendor->user)
        ->getJson(route('vendor.bookings.data', ['sort' => 'total_amount', 'direction' => 'asc']))
        ->assertOk();
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
    $otherVendor = Vendor::factory()->pro()->for(Category::first())->create();

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

it('shows no commission on a booking made while Neekah is free', function () {
    $booking = Booking::factory()->for($this->vendor)->for($this->customer)->create([
        'total_amount' => 3000,
        'commission_rate' => 0,
        'commission_amount' => 0,
    ]);

    $this->actingAs($this->vendor->user)
        ->get(route('vendor.bookings.show', $booking))
        ->assertOk()
        ->assertViewHas('props', fn (array $props): bool => $props['booking']['has_commission'] === false
            && $props['booking']['payout'] === 'RM3,000.00');

    $this->actingAs($this->vendor->user)
        ->get(route('vendor.bookings.create'))
        ->assertOk()
        ->assertViewHas('props', fn (array $props): bool => $props['commissionRate'] === 0.0);
});

it('keeps the commission a booking was made under before Neekah went free', function () {
    // The rate is stamped on each booking, so switching it off changes only
    // what comes after; an older booking still shows what it was agreed at.
    $booking = Booking::factory()->for($this->vendor)->for($this->customer)->create([
        'total_amount' => 3000,
        'commission_rate' => 8,
        'commission_amount' => 240,
    ]);

    $this->actingAs($this->vendor->user)
        ->get(route('vendor.bookings.show', $booking))
        ->assertOk()
        ->assertViewHas('props', fn (array $props): bool => $props['booking']['has_commission'] === true
            && $props['booking']['payout'] === 'RM2,760.00');
});
