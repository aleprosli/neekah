<?php

use App\Enums\VendorFeature;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Support\VendorFeatureSettings;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->vendor = Vendor::factory()->for(Category::first())->create();
});

/**
 * Open or close one feature for one plan, leaving the rest as they are.
 */
function setPlanFeature(string $plan, VendorFeature $feature, bool $open): void
{
    app(VendorFeatureSettings::class)->save([VendorFeatureSettings::key($plan, $feature) => $open]);
}

it('opens every feature to every plan until an admin closes one', function () {
    $this->actingAs($this->vendor->user)
        ->get(route('vendor.profile.edit'))
        ->assertSee(route('vendor.bookings.index'), false);

    $this->actingAs($this->vendor->user)->get(route('vendor.bookings.index'))->assertOk();
});

it('sends a basic vendor to the Pro page for a feature only Pro opens, and marks it Pro in the sidebar', function () {
    setPlanFeature(VendorFeatureSettings::BASIC, VendorFeature::Bookings, false);

    $this->actingAs($this->vendor->user)
        ->get(route('vendor.bookings.index'))
        ->assertRedirect(route('vendor.pro.index'))
        ->assertSessionHas('status', __('flash.vendor.feature_needs_pro', ['feature' => VendorFeature::Bookings->label()]));

    $this->actingAs($this->vendor->user)->post(route('vendor.bookings.store'), [])->assertForbidden();

    $this->actingAs($this->vendor->user)
        ->get(route('vendor.profile.edit'))
        ->assertDontSee(route('vendor.bookings.index'), false)
        ->assertSee('Pro');
});

it('opens it once the vendor is on Pro', function () {
    setPlanFeature(VendorFeatureSettings::BASIC, VendorFeature::Bookings, false);
    $this->vendor->update(['pro_until' => now()->addMonth()]);

    $this->actingAs($this->vendor->user)->get(route('vendor.bookings.index'))->assertOk();
});

it('hides a feature closed to every plan and sends the vendor to the dashboard', function () {
    setPlanFeature(VendorFeatureSettings::BASIC, VendorFeature::Points, false);
    setPlanFeature(VendorFeatureSettings::PRO, VendorFeature::Points, false);

    $this->actingAs($this->vendor->user)
        ->get(route('vendor.points.index'))
        ->assertRedirect(route('vendor.dashboard'));

    $this->actingAs($this->vendor->user)
        ->get(route('vendor.profile.edit'))
        ->assertDontSee(route('vendor.points.index'), false);
});

it('lets one vendor differ from their plan in either direction', function () {
    setPlanFeature(VendorFeatureSettings::BASIC, VendorFeature::Reviews, false);
    $other = Vendor::factory()->for(Category::first())->create(['feature_overrides' => ['reviews' => true, 'portfolio' => false]]);

    $this->actingAs($other->user)->get(route('vendor.reviews.index'))->assertOk();
    $this->actingAs($other->user)->get(route('vendor.portfolio.index'))->assertRedirect(route('vendor.dashboard'));

    $this->actingAs($this->vendor->user)->get(route('vendor.reviews.index'))->assertRedirect(route('vendor.pro.index'));
    $this->actingAs($this->vendor->user)->get(route('vendor.portfolio.index'))->assertOk();
});

it('keeps the setup page working for a vendor awaiting approval whatever the plan closes', function () {
    setPlanFeature(VendorFeatureSettings::BASIC, VendorFeature::Packages, false);
    $pending = Vendor::factory()->pending()->for(Category::first())->create();

    $this->actingAs($pending->user)
        ->post(route('vendor.packages.store'), ['name' => 'Pakej Asas', 'price' => 1000, 'features' => 'Album'])
        ->assertRedirect(route('vendor.dashboard', ['langkah' => 'pakej']));

    expect($pending->packages()->count())->toBe(1);
});

it('saves what each plan opens, an unticked box as closed', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.vendor-features.update'), [
            'features' => [
                'basic' => ['bookings' => '0', 'reviews' => '1'],
                'pro' => ['bookings' => '1', 'reviews' => '1'],
            ],
        ])
        ->assertSessionHasNoErrors();

    $settings = app(VendorFeatureSettings::class);

    expect($settings->allows('basic', VendorFeature::Bookings))->toBeFalse()
        ->and($settings->allows('pro', VendorFeature::Bookings))->toBeTrue()
        ->and($settings->allows('basic', VendorFeature::Points))->toBeFalse();
});

it('stores only the features an admin set apart from the plan for one vendor', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.vendors.features', $this->vendor), [
            'features' => collect(VendorFeature::cases())->mapWithKeys(fn (VendorFeature $feature): array => [$feature->value => 'plan'])
                ->merge(['bookings' => 'closed', 'points' => 'open'])
                ->all(),
        ])
        ->assertSessionHasNoErrors();

    expect($this->vendor->fresh()->feature_overrides)->toBe(['bookings' => false, 'points' => true]);
});

it('shows the admin the feature pages and keeps them from everyone else', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.vendor-features.index'))
        ->assertOk()
        ->assertSee(VendorFeature::Bookings->label());

    $this->actingAs($this->vendor->user)->get(route('admin.vendor-features.index'))->assertForbidden();
    $this->actingAs($this->vendor->user)->put(route('admin.vendors.features', $this->vendor), ['features' => ['bookings' => 'open']])->assertForbidden();
});
