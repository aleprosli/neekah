<?php

use App\Models\Booking;
use App\Models\Category;
use App\Models\Review;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->customer = User::factory()->create();
    $this->vendor = Vendor::factory()->for(Category::first())->create();
});

$payload = [
    'rating' => 5,
    'quality' => 5,
    'service' => 4,
    'communication' => 5,
    'value' => 4,
    'punctuality' => 5,
    'comment' => 'Hasil kerja sangat memuaskan dan cepat respon.',
];

it('lets the customer review a completed booking and updates vendor stats', function () use ($payload) {
    $booking = Booking::factory()->completed()->for($this->customer)->for($this->vendor)->create();

    $this->actingAs($this->customer)
        ->get(route('bookings.show', $booking))
        ->assertOk()
        ->assertSee('Beri review');

    $this->actingAs($this->customer)
        ->post(route('bookings.review.store', $booking), $payload)
        ->assertRedirect(route('bookings.show', $booking));

    $review = Review::sole();
    $vendor = $this->vendor->fresh();

    expect($review->vendor_id)->toBe($this->vendor->id)
        ->and($review->rating)->toBe(5)
        ->and($vendor->reviews_count)->toBe(1)
        ->and((float) $vendor->rating_avg)->toBe(5.0)
        ->and((float) $vendor->score)->toBeGreaterThan(0);

    $this->get(route('vendors.show', $vendor))->assertSee('Hasil kerja sangat memuaskan');
});

it('refuses a review before the booking is completed', function () use ($payload) {
    $booking = Booking::factory()->confirmed()->for($this->customer)->for($this->vendor)->create();

    $this->actingAs($this->customer)
        ->post(route('bookings.review.store', $booking), $payload)
        ->assertForbidden();

    expect(Review::count())->toBe(0);
});

it('refuses a second review or a review by someone else', function () use ($payload) {
    $booking = Booking::factory()->completed()->for($this->customer)->for($this->vendor)->create();

    $this->actingAs($this->customer)->post(route('bookings.review.store', $booking), $payload)->assertRedirect();
    $this->actingAs($this->customer)->post(route('bookings.review.store', $booking), $payload)->assertForbidden();
    $this->actingAs(User::factory()->create())->post(route('bookings.review.store', $booking), $payload)->assertForbidden();

    expect(Review::count())->toBe(1);
});

it('validates the rating range and comment length', function () {
    $booking = Booking::factory()->completed()->for($this->customer)->for($this->vendor)->create();

    $this->actingAs($this->customer)
        ->post(route('bookings.review.store', $booking), ['rating' => 9, 'quality' => 5, 'service' => 5, 'communication' => 5, 'value' => 5, 'punctuality' => 5, 'comment' => 'short'])
        ->assertSessionHasErrors(['rating', 'comment']);
});
