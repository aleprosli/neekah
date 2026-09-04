<?php

use App\Models\Booking;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->admin = User::factory()->admin()->create();
});

it('lists and filters users by role', function () {
    $customer = User::factory()->create(['name' => 'Aina Zulkifli']);
    $vendor = Vendor::factory()->for(Category::first())->create();

    $this->actingAs($this->admin)->get(route('admin.users.index'))->assertOk()->assertSee('Aina Zulkifli')->assertSee($vendor->user->email);

    $this->actingAs($this->admin)
        ->get(route('admin.users.index', ['role' => 'customer']))
        ->assertOk()
        ->assertSee($customer->email)
        ->assertDontSee($vendor->user->email);
});

it('searches bookings by reference and filters by status', function () {
    $vendor = Vendor::factory()->for(Category::first())->create();
    $confirmed = Booking::factory()->confirmed()->for($vendor)->create();
    $cancelled = Booking::factory()->cancelled()->for($vendor)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.bookings.index', ['status' => 'confirmed']))
        ->assertOk()
        ->assertSee($confirmed->reference)
        ->assertDontSee($cancelled->reference);

    $this->actingAs($this->admin)
        ->get(route('admin.bookings.index', ['q' => $cancelled->reference]))
        ->assertOk()
        ->assertSee($cancelled->reference)
        ->assertDontSee($confirmed->reference);

    $this->actingAs($this->admin)->get(route('admin.bookings.show', $confirmed))->assertOk()->assertSee('Payout vendor');
});

it('creates, updates and deletes categories', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.categories.store'), ['name' => 'Kereta Pengantin', 'icon' => '🚗', 'examples' => 'Sewa kereta', 'sort_order' => 20, 'is_active' => 1])
        ->assertRedirect();

    $category = Category::where('slug', 'kereta-pengantin')->sole();
    expect($category->icon)->toBe('🚗');

    $this->actingAs($this->admin)
        ->put(route('admin.categories.update', $category), ['name' => 'Kereta', 'icon' => '🚙', 'is_active' => 0])
        ->assertRedirect();

    expect($category->fresh()->is_active)->toBeFalse();

    $this->actingAs($this->admin)->delete(route('admin.categories.destroy', $category))->assertRedirect();
    expect(Category::where('slug', 'kereta-pengantin')->exists())->toBeFalse();
});

it('refuses to delete a category still used by a vendor', function () {
    $category = Category::first();
    Vendor::factory()->for($category)->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.categories.destroy', $category))
        ->assertSessionHasErrors('category');

    expect(Category::whereKey($category->id)->exists())->toBeTrue();
});

it('hides inactive categories from the marketplace filters', function () {
    $category = Category::where('slug', 'catering')->first();

    $this->actingAs($this->admin)
        ->put(route('admin.categories.update', $category), ['name' => $category->name, 'icon' => $category->icon, 'is_active' => 0])
        ->assertRedirect();

    $this->get('/')->assertOk()->assertDontSee('Catering');
});
