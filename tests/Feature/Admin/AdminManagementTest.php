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

    $this->actingAs($this->admin)->get(route('admin.users.index'))->assertOk()->assertSee('data-vue="data-table"', false);

    $all = $this->actingAs($this->admin)->getJson(route('admin.users.data'))->assertOk()->json('data');

    expect(collect($all)->pluck('name'))->toContain('Aina Zulkifli')
        ->and(collect($all)->pluck('email'))->toContain($vendor->user->email)
        // Impersonation is offered on the accounts that allow it, and only those.
        ->and(collect($all)->firstWhere('email', $customer->email)['impersonate_url'])->toBe(route('admin.users.impersonate', $customer))
        ->and(collect($all)->firstWhere('email', $this->admin->email)['impersonate_url'])->toBeNull();

    $customers = $this->actingAs($this->admin)->getJson(route('admin.users.data', ['role' => 'customer']))->assertOk()->json('data');

    expect(collect($customers)->pluck('email'))->toContain($customer->email)
        ->not->toContain($vendor->user->email);
});

it('searches bookings by reference and filters by status', function () {
    $vendor = Vendor::factory()->for(Category::first())->create();
    $confirmed = Booking::factory()->confirmed()->for($vendor)->create();
    $cancelled = Booking::factory()->cancelled()->for($vendor)->create();

    // The page is the shell; the rows come from the table's own endpoint.
    $this->actingAs($this->admin)->get(route('admin.bookings.index'))->assertOk()->assertSee('data-vue="data-table"', false);

    $filtered = $this->actingAs($this->admin)
        ->getJson(route('admin.bookings.data', ['status' => 'confirmed']))
        ->assertOk()
        ->json('data');

    expect(collect($filtered)->pluck('reference'))->toContain($confirmed->reference)
        ->not->toContain($cancelled->reference);

    $searched = $this->actingAs($this->admin)
        ->getJson(route('admin.bookings.data', ['search' => $cancelled->reference]))
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->json('data');

    expect($searched[0]['reference'])->toBe($cancelled->reference)
        ->and($searched[0]['url'])->toBe(route('admin.bookings.show', $cancelled));

    $detail = $this->actingAs($this->admin)->get(route('admin.bookings.show', $confirmed))->assertOk()->viewData('props');

    // The payout is what the admin is here to check: total less commission.
    expect($detail['booking']['payout'])
        ->toBe('RM'.number_format((float) $confirmed->total_amount - (float) $confirmed->commission_amount, 2));
});

it('keeps the booking table endpoint to admins', function () {
    $this->actingAs(User::factory()->create())->getJson(route('admin.bookings.data'))->assertForbidden();
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
