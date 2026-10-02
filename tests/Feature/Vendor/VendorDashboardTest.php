<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Enquiry;
use App\Models\Package;
use App\Models\Payment;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
});

/** The action keys the dashboard lists for this vendor. */
function dashboardActions(Vendor $vendor): array
{
    return collect(test()->actingAs($vendor->user)->get(route('vendor.dashboard'))->assertOk()->viewData('props')['actions'])->pluck('key')->all();
}

/** A vendor with nothing left to set up. */
function completeVendor(array $attributes = []): Vendor
{
    $vendor = Vendor::factory()->for(Category::first())->create(['cover_image' => 'vendors/cover.webp', ...$attributes]);
    Package::factory()->for($vendor)->create();
    PortfolioItem::factory()->for($vendor)->count(3)->create();

    return $vendor;
}

it('lists only the setup still missing, and says nothing once the profile is complete', function () {
    $new = Vendor::factory()->for(Category::first())->create();

    expect(dashboardActions($new))->toContain('setup_cover', 'setup_portfolio', 'setup_pakej')
        ->not->toContain('setup_profil');

    expect(dashboardActions(completeVendor()))->toBe([]);
});

it('shows Basic the enquiries waiting behind Pro, unused boost tokens, and what Pro opens', function () {
    $vendor = completeVendor();
    Enquiry::factory()->for($vendor)->create();
    $vendor->forceFill(['boost_tokens' => 7])->saveQuietly();

    $props = $this->actingAs($vendor->user)->get(route('vendor.dashboard'))->viewData('props');

    expect(collect($props['actions'])->pluck('key')->all())->toBe(['enquiries_locked', 'boost'])
        ->and($props['business'])->toBeNull()
        ->and(collect($props['locked'])->pluck('key')->all())->toBe(['calendar', 'bookings', 'enquiries', 'quotations', 'contracts', 'points']);
});

it('leaves the week\'s views and taps off the dashboard, so a quiet week after a boost does not read as a failed one', function () {
    $props = $this->actingAs(completeVendor()->user)->get(route('vendor.dashboard'))->viewData('props');

    expect($props)->not->toHaveKeys(['reach', 'reachUrl']);
});

it('puts a Pro vendor receipt to check and enquiries to answer first, with the business numbers', function () {
    $vendor = completeVendor(['pro_until' => now()->addMonths(3)]);
    Enquiry::factory()->for($vendor)->create();
    $booking = Booking::factory()->for($vendor)->create(['status' => BookingStatus::PendingPayment, 'event_date' => now()->addMonth()]);
    Payment::factory()->for($booking)->create(['status' => PaymentStatus::AwaitingVerification]);

    $props = $this->actingAs($vendor->user)->get(route('vendor.dashboard'))->viewData('props');

    expect(array_slice(collect($props['actions'])->pluck('key')->all(), 0, 2))->toBe(['receipts', 'enquiries'])
        ->and($props['business'])->toHaveCount(4)
        ->and($props['upcoming'])->toHaveCount(1)
        ->and($props['locked'])->toBe([]);
});

it('puts the rank in the hero and keeps the traffic numbers off it', function () {
    $vendor = completeVendor();

    $response = $this->actingAs($vendor->user)->get(route('vendor.dashboard'))->assertOk();

    // Owner, 2 Oct 2026: the rank is the first thing a vendor sees; a "0" view
    // count or an empty rating while traffic is low only turns them away.
    $response->assertSeeInOrder(['data-vendor-hero', 'data-rank-badge="'.$vendor->tier->value.'"', $vendor->name], false);

    preg_match('#<section data-vendor-hero.*?</section>#s', $response->getContent(), $hero);

    expect($hero[0])->not->toContain(__('ui.vendor_home.score'))
        ->and($response->viewData('props')['standing'])->not->toHaveKey('views30');
});

it('shows a Basic vendor what Basic does and how Boost works', function () {
    $props = $this->actingAs(completeVendor()->user)->get(route('vendor.dashboard'))->viewData('props');

    expect(array_column($props['tools'], 'key'))->toBe(['profile', 'packages', 'portfolio', 'reviews'])
        ->and($props['boost']['url'])->toBe(route('vendor.boost.index'))
        ->and($props['boost']['screenshot'])->toEndWith('img/boost/kad-dipromosi.jpg');
});
