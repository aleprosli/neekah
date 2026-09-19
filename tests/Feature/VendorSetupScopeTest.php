<?php

use App\Models\Package;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
});

/**
 * A vendor with everything, then one thing taken away.
 */
function vendorMissing(array $attributes = [], int $portfolio = 3, bool $activePackage = true): Vendor
{
    $vendor = Vendor::factory()->create($attributes);

    Package::factory()->for($vendor)->create(['is_active' => $activePackage]);
    PortfolioItem::factory()->count($portfolio)->for($vendor)->create();

    return $vendor;
}

it('counts a vendor as set up only with a full profile and a full catalogue', function () {
    $complete = vendorMissing();

    expect($complete->hasCompleteProfile())->toBeTrue()
        ->and($complete->hasCompleteCatalogue())->toBeTrue()
        ->and(Vendor::setupComplete()->pluck('id')->all())->toBe([$complete->id]);
});

it('matches the SQL scope to the two methods for every way a setup falls short', function () {
    vendorMissing();
    vendorMissing(['tagline' => null]);
    vendorMissing(['tagline' => '']);
    vendorMissing(['description' => null]);
    vendorMissing(['phone' => '']);
    vendorMissing(['price_from' => 0]);
    vendorMissing(portfolio: 2);
    vendorMissing(activePackage: false);
    vendorMissing(portfolio: 0, activePackage: false);

    $byMethod = Vendor::all()
        ->filter(fn (Vendor $vendor) => $vendor->hasCompleteProfile() && $vendor->hasCompleteCatalogue())
        ->pluck('id')
        ->sort()
        ->values()
        ->all();

    expect(Vendor::setupComplete()->pluck('id')->sort()->values()->all())->toBe($byMethod)
        ->and($byMethod)->toHaveCount(1);
});

it('reads eager-loaded counts instead of querying again', function () {
    vendorMissing();

    $loaded = Vendor::withCount([
        'packages as active_packages_count' => fn ($packages) => $packages->where('is_active', true),
        'portfolioItems',
    ])->sole();

    DB::enableQueryLog();
    expect($loaded->hasCompleteCatalogue())->toBeTrue();
    expect(DB::getQueryLog())->toBeEmpty();
});
