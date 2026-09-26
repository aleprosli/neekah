<?php

use App\Enums\UserRole;
use App\Enums\VendorStatus;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Enquiry;
use App\Models\PortfolioItem;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wedding;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->admin = User::factory()->admin()->create();
});

it('links each account in the user list to a page an admin alone can open', function () {
    $customer = User::factory()->create();

    $rows = $this->actingAs($this->admin)->getJson(route('admin.users.data'))->json('data');
    expect(collect($rows)->firstWhere('email', $customer->email)['url'])->toBe(route('admin.users.show', $customer));

    $this->actingAs($this->admin)->get(route('admin.users.show', $customer))->assertOk()->assertSee($customer->email);
    $this->actingAs($customer)->get(route('admin.users.show', $customer))->assertForbidden();
});

it('lets an admin switch a couple with activity but no booking to a pending vendor', function () {
    $customer = User::factory()->create();
    Wedding::factory()->for($customer)->create();

    $this->actingAs($this->admin)
        ->post(route('admin.users.vendor.store', $customer), [
            'business_name' => 'Studio Aina',
            'category_id' => Category::first()->id,
            'city' => 'Ipoh',
            'district' => 'Kinta',
            'state' => 'Perak',
            'phone' => '012-345 6789',
        ])
        ->assertRedirect(route('admin.vendors.show', Vendor::sole()));

    expect($customer->fresh()->role)->toBe(UserRole::Vendor)
        ->and(Vendor::sole()->status)->toBe(VendorStatus::Pending);
});

it('refuses to switch a couple who has booked a vendor', function () {
    $booking = Booking::factory()->for(Vendor::factory()->for(Category::first()))->create();

    $this->actingAs($this->admin)
        ->post(route('admin.users.vendor.store', $booking->user), ['business_name' => 'Studio', 'category_id' => Category::first()->id, 'city' => 'Ipoh', 'district' => 'Kinta', 'state' => 'Perak', 'phone' => '0123'])
        ->assertForbidden();

    expect($booking->user->fresh()->role)->toBe(UserRole::Customer);
});

it('switches a vendor with no activity back to a couple and removes their photos', function () {
    Storage::fake('public');
    $vendor = Vendor::factory()->for(Category::first())->create(['cover_image' => 'vendors/1/cover.webp']);
    PortfolioItem::factory()->for($vendor)->create(['path' => 'portfolio/1/photo.webp']);
    Storage::disk('public')->put('vendors/1/cover.webp', 'x');
    Storage::disk('public')->put('portfolio/1/photo.webp', 'x');

    $this->actingAs($this->admin)
        ->delete(route('admin.users.vendor.destroy', $vendor->user))
        ->assertRedirect(route('admin.users.show', $vendor->user));

    expect($vendor->user->fresh()->role)->toBe(UserRole::Customer)
        ->and(Vendor::count())->toBe(0);
    Storage::disk('public')->assertMissing(['vendors/1/cover.webp', 'portfolio/1/photo.webp']);
});

it('refuses to switch a vendor who already has enquiries or bookings', function () {
    $vendor = Vendor::factory()->for(Category::first())->create();
    Enquiry::factory()->for(User::factory())->for($vendor)->create();

    $this->actingAs($this->admin)->delete(route('admin.users.vendor.destroy', $vendor->user))->assertForbidden();

    expect($vendor->fresh())->not->toBeNull();
});

it('signs a deactivated account out, suspends its vendor profile, and lets it back in on reactivation', function () {
    $vendor = Vendor::factory()->for(Category::first())->create(['status' => VendorStatus::Approved]);
    $owner = $vendor->user;

    $this->actingAs($this->admin)->post(route('admin.users.deactivate', $owner))->assertRedirect();

    expect($vendor->fresh()->status)->toBe(VendorStatus::Suspended);

    $this->actingAs($owner->fresh())
        ->get(route('vendor.dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', fn (string $status): bool => str_contains($status, 'dinyahaktifkan'));
    $this->assertGuest();

    $this->actingAs($this->admin)->delete(route('admin.users.reactivate', $owner))->assertRedirect();

    $this->actingAs($owner->fresh())->get(route('vendor.dashboard'))->assertOk();
    // Coming back is not the same as being approved again.
    expect($vendor->fresh()->status)->toBe(VendorStatus::Suspended);
});

it('deletes an account without bookings', function () {
    $customer = User::factory()->create();
    Wedding::factory()->for($customer)->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.users.destroy', $customer))
        ->assertRedirect(route('admin.users.index'));

    expect($customer->fresh())->toBeNull()
        ->and(Wedding::count())->toBe(0);
});

it('refuses to delete an account whose records others depend on', function (Closure $makeAccount) {
    $user = $makeAccount();

    $this->actingAs($this->admin)->delete(route('admin.users.destroy', $user))->assertForbidden();

    expect($user->fresh())->not->toBeNull();
})->with([
    'a couple with a booking' => fn () => Booking::factory()->for(Vendor::factory()->for(Category::first()))->create()->user,
    'a vendor with a booking' => fn () => Booking::factory()->for(Vendor::factory()->for(Category::first()))->create()->vendor->user,
    'a wedding shared with a partner' => function () {
        $wedding = Wedding::factory()->create();
        $wedding->members()->attach(User::factory()->create());

        return $wedding->user;
    },
]);

it('never lets an admin manage another admin account', function () {
    $otherAdmin = User::factory()->admin()->create();

    $this->actingAs($this->admin)->post(route('admin.users.deactivate', $otherAdmin))->assertForbidden();
    $this->actingAs($this->admin)->delete(route('admin.users.destroy', $otherAdmin))->assertForbidden();
});

it('hands the page every account tool, each with its own question or its reason', function () {
    $admin = User::factory()->admin()->create();
    $couple = User::factory()->create(['name' => 'Aina']);

    $this->actingAs($admin)
        ->get(route('admin.users.show', $couple))
        ->assertOk()
        ->assertViewHas('props', function (array $props): bool {
            $actions = collect($props['actions']);
            $delete = $actions->firstWhere('key', 'delete');

            return $actions->pluck('key')->contains('switchToVendor')
                && $actions->every(fn (array $action): bool => filled($action['heading']) && filled($action['body']))
                && $delete['confirm_title'] === 'Padam akaun Aina?'
                && $delete['tone'] === 'danger'
                && $props['user']['is_admin'] === false;
        });
});

it('tells the page why an account cannot be deleted instead of hiding the card', function () {
    $admin = User::factory()->admin()->create();
    $couple = User::factory()->create();
    Booking::factory()->for($couple)->create();

    $this->actingAs($admin)
        ->get(route('admin.users.show', $couple))
        ->assertOk()
        ->assertViewHas('props', function (array $props): bool {
            $delete = collect($props['actions'])->firstWhere('key', 'delete');

            return $delete['allowed'] === false && str_contains($delete['body'], 'Tidak boleh dipadam');
        });
});
