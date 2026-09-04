<?php

use App\Enums\VendorStatus;
use App\Enums\VendorTier;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->admin = User::factory()->admin()->create();
});

it('approves a pending vendor, promotes it to Verified and lists it publicly', function () {
    $vendor = Vendor::factory()->pending()->for(Category::first())->create(['name' => 'Studio Baharu']);

    $this->get('/')->assertDontSee('Studio Baharu');

    $this->actingAs($this->admin)
        ->post(route('admin.vendors.status', $vendor), ['status' => 'approved'])
        ->assertRedirect();

    $vendor->refresh();

    expect($vendor->status)->toBe(VendorStatus::Approved)
        ->and($vendor->tier)->toBe(VendorTier::Verified)
        ->and($vendor->approved_at)->not->toBeNull()
        ->and((float) $vendor->score)->toBeGreaterThan(0);

    $this->get('/')->assertSee('Studio Baharu');
    $this->get(route('vendors.show', $vendor))->assertOk();
});

it('suspends an approved vendor and hides it from the marketplace', function () {
    $vendor = Vendor::factory()->for(Category::first())->create(['name' => 'Suspended Studio']);

    $this->actingAs($this->admin)
        ->post(route('admin.vendors.status', $vendor), ['status' => 'suspended'])
        ->assertRedirect();

    expect($vendor->fresh()->status)->toBe(VendorStatus::Suspended);

    $this->get('/')->assertDontSee('Suspended Studio');
    $this->get(route('vendors.show', $vendor))->assertNotFound();
});

it('rejects an unknown status value', function () {
    $vendor = Vendor::factory()->pending()->for(Category::first())->create();

    $this->actingAs($this->admin)
        ->post(route('admin.vendors.status', $vendor), ['status' => 'banana'])
        ->assertSessionHasErrors('status');

    expect($vendor->fresh()->status)->toBe(VendorStatus::Pending);
});

it('pins a tier the admin locks and recalculates the score', function () {
    $vendor = Vendor::factory()->for(Category::first())->create(['response_rate' => 80]);

    $this->actingAs($this->admin)
        ->put(route('admin.vendors.tier', $vendor), ['tier' => 'recommended', 'response_rate' => 100, 'tier_locked' => 1])
        ->assertRedirect();

    $vendor->refresh();

    expect($vendor->tier)->toBe(VendorTier::Recommended)
        ->and($vendor->tier_locked)->toBeTrue()
        ->and($vendor->response_rate)->toBe(100)
        ->and((float) $vendor->score)->toBe($vendor->calculateScore());
});

it('recomputes an unlocked tier from the vendor metrics instead of trusting the form', function () {
    $vendor = Vendor::factory()->for(Category::first())->create();

    $this->actingAs($this->admin)
        ->put(route('admin.vendors.tier', $vendor), ['tier' => 'recommended'])
        ->assertRedirect();

    // No completed bookings or reviews yet, so the engine puts them back at Verified.
    expect($vendor->fresh()->tier)->toBe(VendorTier::Verified)
        ->and($vendor->fresh()->tier_locked)->toBeFalse();
});

it('shows a vendor detail page with owner, packages and controls', function () {
    $vendor = Vendor::factory()->pending()->for(Category::first())->create();

    $this->actingAs($this->admin)
        ->get(route('admin.vendors.show', $vendor))
        ->assertOk()
        ->assertSee($vendor->name)
        ->assertSee($vendor->user->email)
        ->assertSee('Vendor Score')
        ->assertSee('Simpan ranking');
});
