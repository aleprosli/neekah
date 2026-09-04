<?php

use App\Actions\AwardVendorPoints;
use App\Actions\RecalculateVendorStats;
use App\Enums\PointReason;
use App\Enums\VendorTier;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Enquiry;
use App\Models\Package;
use App\Models\Payment;
use App\Models\PortfolioItem;
use App\Models\Review;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPoint;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->vendor = Vendor::factory()->for(Category::first())->create();
    $this->customer = User::factory()->create();
    $this->recalculate = app(RecalculateVendorStats::class);
});

it('awards the kertas kerja points across a full booking lifecycle', function () {
    $package = Package::factory()->for($this->vendor)->create(['price' => 2500]);

    $this->actingAs($this->customer)
        ->post(route('vendors.bookings.store', $this->vendor), ['package_id' => $package->id, 'event_date' => now()->addMonths(3)->toDateString()])
        ->assertRedirect();

    $booking = Booking::sole();
    expect($this->vendor->fresh()->points_total)->toBe(PointReason::PlatformBooking->points());

    $this->actingAs($this->customer)->post(route('bookings.payments.store', [$booking, $booking->depositPayment]));
    $this->actingAs($this->customer)->post(route('bookings.payments.store', [$booking, $booking->balancePayment]));

    // Booking + deposit + full payment.
    expect($this->vendor->fresh()->points_total)->toBe(100 + 100 + 150);

    $booking->update(['event_date' => now()->subDay()]);
    $this->actingAs($this->vendor->user)->post(route('vendor.bookings.complete', $booking))->assertRedirect();

    // Completing triggers a recalculation, which also awards the profile milestone.
    expect($this->vendor->fresh()->points_total)->toBe(100 + 100 + 150 + 150 + 50);

    $this->actingAs($this->customer)->post(route('bookings.review.store', $booking), [
        'rating' => 5, 'quality' => 5, 'service' => 5, 'communication' => 5, 'value' => 5, 'punctuality' => 5,
        'comment' => 'Servis yang sangat memuaskan dari awal hingga akhir.',
    ])->assertRedirect();

    expect($this->vendor->fresh()->points_total)->toBe(100 + 100 + 150 + 150 + 50 + 20);
});

it('never awards the same points twice', function () {
    $booking = Booking::factory()->for($this->vendor)->create();
    $award = app(AwardVendorPoints::class);

    expect($award->award($this->vendor, PointReason::PlatformBooking, $booking))->not->toBeNull()
        ->and($award->award($this->vendor, PointReason::PlatformBooking, $booking))->toBeNull()
        ->and($this->vendor->fresh()->points_total)->toBe(100)
        ->and(VendorPoint::count())->toBe(1);
});

it('gives no points for an enquiry, only for replying quickly', function () {
    $enquiry = Enquiry::factory()->for($this->vendor)->for($this->customer)->create();

    expect($this->vendor->fresh()->points_total)->toBe(0);

    $this->actingAs($this->vendor->user)
        ->put(route('vendor.enquiries.update', $enquiry), ['reply' => 'Ya, tarikh tersebut masih kosong.'])
        ->assertRedirect();

    expect($this->vendor->fresh()->points_total)->toBe(PointReason::FastResponse->points());
});

it('awards and revokes the profile and catalogue milestones as the vendor changes', function () {
    $bare = Vendor::factory()->for(Category::first())->create(['tagline' => null, 'description' => null, 'price_from' => 0]);

    $this->recalculate->handle($bare);
    expect($bare->fresh()->points_total)->toBe(0);

    $bare->update(['tagline' => 'Candid wedding photography', 'description' => 'Kami ada 8 tahun pengalaman.', 'price_from' => 1500]);
    Package::factory()->for($bare)->create();
    PortfolioItem::factory()->count(3)->for($bare)->create();

    $this->recalculate->handle($bare);
    expect($bare->fresh()->points_total)->toBe(PointReason::ProfileComplete->points() + PointReason::CatalogueComplete->points());

    // Losing the description takes the profile milestone back.
    $bare->update(['description' => null]);
    $this->recalculate->handle($bare);
    expect($bare->fresh()->points_total)->toBe(PointReason::CatalogueComplete->points());
});

it('promotes a vendor up the ladder as their record grows', function () {
    expect($this->recalculate->handle($this->vendor)->tier)->toBe(VendorTier::Verified);

    givePerformance($this->vendor, completed: 5, reviews: 3, rating: 4);
    expect($this->recalculate->handle($this->vendor->fresh())->tier)->toBe(VendorTier::Trusted);

    givePerformance($this->vendor, completed: 15, reviews: 8, rating: 5, responseRate: 92);
    expect($this->recalculate->handle($this->vendor->fresh())->tier)->toBe(VendorTier::Top);

    givePerformance($this->vendor, completed: 30, reviews: 15, rating: 5, responseRate: 98);
    expect($this->recalculate->handle($this->vendor->fresh())->tier)->toBe(VendorTier::Recommended);
});

it('keeps a locked tier out of the automatic engine', function () {
    $this->vendor->update(['tier' => VendorTier::Recommended, 'tier_locked' => true]);

    expect($this->recalculate->handle($this->vendor)->tier)->toBe(VendorTier::Recommended);

    $this->vendor->update(['tier_locked' => false]);
    expect($this->recalculate->handle($this->vendor->fresh())->tier)->toBe(VendorTier::Verified);
});

it('tracks the completion rate from settled bookings', function () {
    Booking::factory()->completed()->count(9)->for($this->vendor)->create();
    Booking::factory()->cancelled()->for($this->vendor)->create();

    expect($this->recalculate->handle($this->vendor)->completion_rate)->toBe(90);
});

it('shows the vendor their points, score and what the next tier needs', function () {
    givePerformance($this->vendor, completed: 5, reviews: 3, rating: 4);

    $this->actingAs($this->vendor->user)
        ->get(route('vendor.points.index'))
        ->assertOk()
        ->assertSee('Performance point')
        ->assertSee('Vendor Score')
        ->assertSee('Booking melalui platform')
        ->assertSee('Untuk naik ke Top Vendor');
});

/**
 * Give a vendor a clean record of completed bookings and reviews at a fixed rating.
 */
function givePerformance(Vendor $vendor, int $completed, int $reviews, int $rating, int $responseRate = 100): void
{
    $vendor->reviews()->delete();
    $vendor->bookings()->delete();

    $bookings = Booking::factory()->completed()->count($completed)->for($vendor)->create();

    foreach ($bookings->take($reviews) as $booking) {
        Review::factory()->create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'vendor_id' => $vendor->id,
            'rating' => $rating,
        ]);
    }

    $vendor->update(['response_rate' => $responseRate]);
}
