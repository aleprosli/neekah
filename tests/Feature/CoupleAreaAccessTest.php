<?php

use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
});

it('sends a vendor who opens a couple page to the vendor dashboard', function (string $route) {
    $vendor = Vendor::factory()->for(Category::first())->create();

    $this->actingAs($vendor->user)
        ->get(route($route))
        ->assertRedirect(route('vendor.dashboard'));
})->with(['dashboard', 'bookings.index', 'enquiries.index', 'weddings.create', 'checklist.index', 'guests.index', 'budget.index', 'site.edit', 'camera.index']);

it('sends an admin who opens a couple page to the admin panel', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard'))
        ->assertRedirect(route('admin.dashboard'));
});

it('refuses a vendor who tries to change couple data', function () {
    $vendor = Vendor::factory()->for(Category::first())->create();

    $this->actingAs($vendor->user)
        ->post(route('weddings.store'), ['title' => 'Majlis'])
        ->assertForbidden();

    expect($vendor->user->weddings()->count())->toBe(0);
});

it('still lets a couple into their own pages', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk();
});
