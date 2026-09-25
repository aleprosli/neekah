<?php

use App\Models\Category;
use App\Models\Package;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(CategorySeeder::class);

    $this->vendor = Vendor::factory()->pending()->for(Category::first())->create([
        'tagline' => null,
        'description' => null,
        'cover_image' => null,
        'price_from' => 0,
    ]);
});

it('shows a vendor awaiting approval the setup guide instead of the sidebar and bookings', function () {
    $this->actingAs($this->vendor->user)
        ->get(route('vendor.dashboard'))
        ->assertOk()
        ->assertViewIs('vendor.setup')
        ->assertSee(__('pages.vendor_setup.guide_title'))
        ->assertSee(route('vendor.setup.profile'), false)
        ->assertDontSee('id="dashboard-drawer"', false)
        ->assertDontSee(route('vendor.bookings.create'), false);
});

it('sends a vendor awaiting approval back to the dashboard from every other vendor page', function (string $route) {
    $this->actingAs($this->vendor->user)
        ->get(route($route))
        ->assertRedirect(route('vendor.dashboard'));
})->with(['vendor.bookings.index', 'vendor.bookings.create', 'vendor.profile.edit', 'vendor.packages.index', 'vendor.availability.index', 'vendor.enquiries.index', 'vendor.pro.index']);

it('does not let a vendor awaiting approval record a booking', function () {
    $this->actingAs($this->vendor->user)
        ->post(route('vendor.bookings.store'), [])
        ->assertRedirect(route('vendor.dashboard'));

    expect($this->vendor->bookings()->count())->toBe(0);
});

it('keeps the full vendor area for an approved vendor', function () {
    $approved = Vendor::factory()->for(Category::first())->create();

    $this->actingAs($approved->user)
        ->get(route('vendor.dashboard'))
        ->assertOk()
        ->assertViewIs('vendor.dashboard')
        ->assertSee('id="dashboard-drawer"', false);

    $this->actingAs($approved->user)->get(route('vendor.bookings.index'))->assertOk();
});

it('saves the tagline and description from the setup card', function () {
    $this->actingAs($this->vendor->user)
        ->put(route('vendor.setup.profile'), ['tagline' => 'Candid di utara', 'description' => 'Fotografi majlis.'])
        ->assertRedirect(route('vendor.dashboard').'#profil')
        ->assertSessionHas('status', __('flash.vendor.setup_profile_saved'));

    expect($this->vendor->refresh())
        ->tagline->toBe('Candid di utara')
        ->description->toBe('Fotografi majlis.');
});

it('keeps setup card errors in the card own bag', function () {
    $this->actingAs($this->vendor->user)
        ->put(route('vendor.setup.profile'), [])
        ->assertSessionHasErrors(['tagline', 'description'], errorBag: 'setupProfile');

    $this->actingAs($this->vendor->user)
        ->put(route('vendor.setup.price'), ['price_from' => 0, 'price_unit' => 'package'])
        ->assertSessionHasErrors(['price_from'], errorBag: 'setupPrice');
});

it('uploads the cover photo and sets the starting price from the setup cards', function () {
    Storage::fake('public');

    $this->actingAs($this->vendor->user)
        ->post(route('vendor.setup.cover'), ['cover_image' => UploadedFile::fake()->image('cover.jpg', 800, 600)])
        ->assertRedirect(route('vendor.dashboard').'#cover');

    $this->actingAs($this->vendor->user)
        ->put(route('vendor.setup.price'), ['price_from' => 1500, 'price_unit' => 'pax'])
        ->assertRedirect(route('vendor.dashboard').'#harga');

    $this->vendor->refresh();

    Storage::disk('public')->assertExists($this->vendor->cover_image);
    expect((float) $this->vendor->price_from)->toBe(1500.0)
        ->and($this->vendor->price_unit->value)->toBe('pax');
});

it('returns a vendor awaiting approval to the dashboard after changing the catalogue', function () {
    Storage::fake('public');
    $user = $this->vendor->user;

    $this->actingAs($user)
        ->post(route('vendor.portfolio.store'), ['images' => [UploadedFile::fake()->image('a.jpg', 800, 600)]])
        ->assertRedirect(route('vendor.dashboard').'#portfolio');

    $this->actingAs($user)
        ->delete(route('vendor.portfolio.destroy', PortfolioItem::sole()))
        ->assertRedirect(route('vendor.dashboard').'#portfolio');

    $this->actingAs($user)
        ->post(route('vendor.packages.store'), ['name' => 'Pakej Asas', 'price' => 2000, 'features' => "Album\nVideo"])
        ->assertRedirect(route('vendor.dashboard').'#pakej');

    $this->actingAs($user)
        ->delete(route('vendor.packages.destroy', Package::sole()))
        ->assertRedirect(route('vendor.dashboard').'#pakej');

});
