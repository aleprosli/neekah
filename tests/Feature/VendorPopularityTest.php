<?php

use App\Models\Category;
use App\Models\Vendor;
use App\Models\VendorDailyStat;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->category = Category::first();
});

/** Record $views profile views for a vendor, $daysAgo days back. */
function viewsFor(Vendor $vendor, int $views, int $daysAgo = 0): void
{
    VendorDailyStat::query()->create(['vendor_id' => $vendor->id, 'date' => today()->subDays($daysAgo)->toDateString(), 'profile_views' => $views]);
}

it('counts one view a day per address, even when the visitor clears their session', function () {
    $vendor = Vendor::factory()->for($this->category)->create();

    $this->get(route('vendors.show', $vendor))->assertOk();
    $this->flushSession();
    $this->get(route('vendors.show', $vendor))->assertOk();
    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.9'])->flushSession();
    $this->get(route('vendors.show', $vendor))->assertOk();

    expect((int) VendorDailyStat::query()->whereBelongsTo($vendor)->sum('profile_views'))->toBe(2);
});

it('sums the last 30 days of views and marks the most viewed of each category as trending', function () {
    $vendors = Vendor::factory()->for($this->category)->count(5)->create();
    foreach ([90, 60, 40, 30] as $index => $views) {
        viewsFor($vendors[$index], $views);
    }
    viewsFor($vendors[4], 500, daysAgo: 31);
    viewsFor($vendors[4], 5);
    $elsewhere = Vendor::factory()->for(Category::query()->whereKeyNot($this->category->id)->first())->create();
    viewsFor($elsewhere, 25);
    $vendors[3]->forceFill(['trending_at' => now()->subWeek()])->saveQuietly();

    $this->artisan('neekah:vendor-popularity')->assertSuccessful();

    expect($vendors->map(fn (Vendor $vendor) => $vendor->fresh()->views_30d)->all())->toBe([90, 60, 40, 30, 5])
        ->and($vendors->map(fn (Vendor $vendor) => $vendor->fresh()->trending_at !== null)->all())->toBe([true, true, true, false, false])
        ->and($elsewhere->fresh()->trending_at)->not->toBeNull();
});

it('orders the list by views on "Paling ramai dilihat" and shows the Trending badge', function () {
    $quiet = Vendor::factory()->for($this->category)->create(['name' => 'Studio Senyap', 'views_30d' => 3, 'score' => 90]);
    $popular = Vendor::factory()->for($this->category)->create(['name' => 'Studio Popular', 'views_30d' => 80, 'score' => 10, 'trending_at' => now()]);

    $this->get(route('vendors.index', ['sort' => 'popular']))
        ->assertOk()
        ->assertSeeInOrder([$popular->name, $quiet->name])
        ->assertSee(__('marketplace.card.trending'));
});
