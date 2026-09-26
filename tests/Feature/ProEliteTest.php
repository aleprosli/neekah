<?php

use App\Enums\BoostTokenReason;
use App\Enums\VendorTier;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBoost;
use App\Support\ProSettings;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->category = Category::query()->orderBy('id')->first();
});

function eliteVendor(Category $category, array $attributes = []): Vendor
{
    return Vendor::factory()->for($category)->pro()->tier(VendorTier::Top)->create($attributes);
}

it('makes Elite of Pro vendors at the Top or Recommended tier, and of nobody else', function () {
    $top = eliteVendor($this->category);
    $recommended = Vendor::factory()->for($this->category)->pro()->tier(VendorTier::Recommended)->create();
    $trustedPro = Vendor::factory()->for($this->category)->pro()->tier(VendorTier::Trusted)->create();
    $basicRecommended = Vendor::factory()->for($this->category)->tier(VendorTier::Recommended)->create();
    $lapsed = eliteVendor($this->category, ['pro_until' => now()->subDay()]);

    expect([$top->isElite(), $recommended->isElite(), $trustedPro->isElite(), $basicRecommended->isElite(), $lapsed->isElite()])
        ->toBe([true, true, false, false, false])
        ->and(Vendor::query()->elite()->pluck('id')->sort()->values()->all())->toBe(collect([$top->id, $recommended->id])->sort()->values()->all());

    app(ProSettings::class)->save(['elite_enabled' => false]);

    expect($top->fresh()->isElite())->toBeFalse()
        ->and(Vendor::query()->elite()->count())->toBe(0);
});

it('orders Disyorkan as boosted, then Elite, then score, and leaves other sorts alone', function () {
    app(ProSettings::class)->save(['elite_row_size' => 0]);
    Vendor::factory()->for($this->category)->create(['name' => 'Studio Skor Tinggi', 'score' => 99, 'views_30d' => 90]);
    eliteVendor($this->category, ['name' => 'Studio Elite', 'score' => 60, 'views_30d' => 10]);
    $boosted = Vendor::factory()->for($this->category)->create(['name' => 'Studio Boost', 'score' => 10]);
    VendorBoost::factory()->create(['vendor_id' => $boosted->id, 'category_id' => $this->category->id]);

    $this->get(route('vendors.index', ['category' => $this->category->slug]))
        ->assertOk()
        ->assertSeeInOrder(['Studio Boost', 'Studio Elite', 'Studio Skor Tinggi']);

    $this->get(route('vendors.index', ['category' => $this->category->slug, 'sort' => 'popular']))
        ->assertSeeInOrder(['Studio Skor Tinggi', 'Studio Elite']);
});

it('shows the Elite picks row on the first Disyorkan page only, with the badge', function () {
    eliteVendor($this->category, ['name' => 'Studio Elite']);
    Vendor::factory()->for($this->category)->create(['name' => 'Studio Biasa']);

    $this->get(route('vendors.index', ['category' => $this->category->slug]))
        ->assertOk()
        ->assertSee(__('marketplace.elite.title'))
        ->assertSee(__('marketplace.card.elite_title'))
        ->assertSeeInOrder([__('marketplace.elite.title'), 'Studio Elite', 'Studio Biasa']);

    $this->get(route('vendors.index', ['category' => $this->category->slug, 'sort' => 'rating']))
        ->assertDontSee(__('marketplace.elite.title'));

    // Nobody Elite in this category: no row.
    $other = Category::query()->orderBy('id')->skip(1)->first();
    $this->get(route('vendors.index', ['category' => $other->slug]))->assertDontSee(__('marketplace.elite.title'));
});

it('gives Elite vendors their bonus tokens every 30 days, on top of Pro', function () {
    Notification::fake();
    app(ProSettings::class)->save(['elite_bonus_tokens' => 5]);
    $elite = eliteVendor($this->category);
    $pro = Vendor::factory()->for($this->category)->pro()->tier(VendorTier::Trusted)->create();

    $this->artisan('neekah:boost-grants')->assertSuccessful();
    $this->artisan('neekah:boost-grants')->assertSuccessful();

    expect($elite->boostEntries()->where('reason', BoostTokenReason::EliteMonthly)->sum('change'))->toBe(5)
        ->and($pro->boostEntries()->where('reason', BoostTokenReason::EliteMonthly)->count())->toBe(0)
        ->and($elite->fresh()->boost_tokens - $pro->fresh()->boost_tokens)->toBe(5);
});

it('lets an admin switch Elite and tune its bonus and row', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.settings.pro'), ['enabled' => '1', 'monthly_price' => 49, 'yearly_price' => 490, 'elite_enabled' => '0', 'elite_bonus_tokens' => 8, 'elite_row_size' => 4])
        ->assertSessionHasNoErrors();

    $settings = app(ProSettings::class);
    expect($settings->eliteEnabled())->toBeFalse()
        ->and($settings->eliteBonusTokens())->toBe(8)
        ->and($settings->eliteRowSize())->toBe(4);
});

it('tells a vendor where they stand on the way to Elite', function (bool $pro, VendorTier $tier, string $expected) {
    $vendor = Vendor::factory()->for($this->category)->tier($tier)->create($pro ? ['pro_until' => now()->addMonth()] : []);

    $props = $this->actingAs($vendor->user)->get(route('vendor.dashboard'))->assertOk()->viewData('props');

    expect($props['standing']['elite'])->toBe($expected);
})->with([
    'Elite' => [true, VendorTier::Top, 'elite'],
    'Pro, tier too low' => [true, VendorTier::Trusted, 'need_tier'],
    'Basic' => [false, VendorTier::Recommended, 'need_pro'],
]);
