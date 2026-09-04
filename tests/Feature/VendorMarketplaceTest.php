<?php

use App\Enums\VendorTier;
use App\Models\Category;
use App\Models\Package;
use App\Models\Review;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->photography = Category::where('slug', 'photography')->first();
    $this->catering = Category::where('slug', 'catering')->first();
});

it('serves the approved vendor listing at the root url with pagination', function () {
    Vendor::factory()->count(13)->for($this->photography)->create();
    Vendor::factory()->pending()->for($this->photography)->create(['name' => 'Vendor Belum Lulus']);

    $this->get('/')
        ->assertOk()
        ->assertSee('13 vendor')
        ->assertDontSee('Vendor Belum Lulus')
        ->assertSee('page=2');
});

it('redirects legacy listing urls to the root', function () {
    $this->get('/marketplace')->assertRedirect('/');
    $this->get('/vendors')->assertRedirect('/');
});

it('filters vendors by category', function () {
    Vendor::factory()->for($this->catering)->create(['name' => 'Dapur Warisan Catering']);
    Vendor::factory()->for($this->photography)->create(['name' => 'ABC Wedding Photography']);

    $this->get(route('vendors.index', ['category' => 'catering']))
        ->assertOk()
        ->assertSee('Dapur Warisan Catering')
        ->assertDontSee('ABC Wedding Photography');
});

it('searches vendors by keyword and state', function () {
    Vendor::factory()->for($this->photography)->create(['name' => 'Pelamin Warisan Kuching', 'state' => 'Sarawak']);
    Vendor::factory()->for($this->photography)->create(['name' => 'Pelamin Seri Selangor', 'state' => 'Selangor']);

    $this->get(route('vendors.index', ['q' => 'pelamin', 'state' => 'Sarawak']))
        ->assertOk()
        ->assertSee('1 vendor')
        ->assertSee('Pelamin Warisan Kuching')
        ->assertDontSee('Pelamin Seri Selangor');
});

it('filters vendors by price, rating and tier', function () {
    Vendor::factory()->for($this->photography)->tier(VendorTier::Recommended)->create(['name' => 'Match Vendor', 'price_from' => 1500, 'rating_avg' => 4.9, 'reviews_count' => 10]);
    Vendor::factory()->for($this->photography)->tier(VendorTier::Recommended)->create(['name' => 'Too Expensive', 'price_from' => 2800, 'rating_avg' => 4.9, 'reviews_count' => 10]);
    Vendor::factory()->for($this->photography)->tier(VendorTier::Verified)->create(['name' => 'Wrong Tier', 'price_from' => 1500, 'rating_avg' => 4.9, 'reviews_count' => 10]);

    $this->get(route('vendors.index', ['min_price' => 1000, 'max_price' => 2000, 'min_rating' => 4.8, 'tier' => 'recommended']))
        ->assertOk()
        ->assertSee('Match Vendor')
        ->assertDontSee('Too Expensive')
        ->assertDontSee('Wrong Tier');
});

it('shows an empty state when nothing matches', function () {
    $this->get(route('vendors.index', ['q' => 'tiada-vendor-begini']))
        ->assertOk()
        ->assertSee('Tiada vendor sepadan');
});

it('sorts vendors by price ascending', function () {
    Vendor::factory()->for($this->photography)->create(['name' => 'Mahal Studio', 'price_from' => 3000]);
    Vendor::factory()->for($this->photography)->create(['name' => 'Murah Studio', 'price_from' => 500]);
    Vendor::factory()->for($this->photography)->create(['name' => 'Sederhana Studio', 'price_from' => 1500]);

    $this->get(route('vendors.index', ['sort' => 'price_asc']))
        ->assertOk()
        ->assertSeeInOrder(['Murah Studio', 'Sederhana Studio', 'Mahal Studio']);
});

it('shows a vendor profile with packages, reviews and related vendors', function () {
    $vendor = Vendor::factory()->for($this->photography)->create(['name' => 'ABC Wedding Photography']);
    Package::factory()->for($vendor)->create(['name' => 'Premium Package', 'price' => 2500]);
    Package::factory()->for($vendor)->create(['name' => 'Pakej Tidak Aktif', 'is_active' => false]);
    $review = Review::factory()->create(['vendor_id' => $vendor->id, 'comment' => 'Hasil kerja sangat memuaskan.']);
    Vendor::factory()->for($this->photography)->create(['name' => 'Lensa Cahaya Studio']);

    $this->get(route('vendors.show', $vendor))
        ->assertOk()
        ->assertSee('ABC Wedding Photography')
        ->assertSee('Premium Package')
        ->assertDontSee('Pakej Tidak Aktif')
        ->assertSee('Hasil kerja sangat memuaskan.')
        ->assertSee($review->user->name)
        ->assertSee('Lensa Cahaya Studio')
        ->assertSee('Log masuk untuk tempah');
});

it('returns 404 for unknown or unapproved vendors', function () {
    $pending = Vendor::factory()->pending()->for($this->photography)->create();

    $this->get(route('vendors.show', 'vendor-tak-wujud'))->assertNotFound();
    $this->get(route('vendors.show', $pending))->assertNotFound();
});
