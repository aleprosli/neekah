<?php

use App\Enums\VendorTier;
use App\Models\Category;
use App\Models\Package;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->photography = Category::where('slug', 'photography')->first();
});

it('invites the visitor to pick vendors when none are selected', function () {
    $this->get(route('vendors.compare'))
        ->assertOk()
        ->assertSee('Belum ada vendor dipilih');
});

it('compares vendors side by side and marks the best value in each row', function () {
    $cheap = Vendor::factory()->for($this->photography)->create(['name' => 'Murah Studio', 'slug' => 'murah', 'price_from' => 1200, 'rating_avg' => 4.2, 'reviews_count' => 10]);
    $premium = Vendor::factory()->for($this->photography)->tier(VendorTier::Recommended)->create(['name' => 'Premium Studio', 'slug' => 'premium', 'price_from' => 3500, 'rating_avg' => 4.9, 'reviews_count' => 40, 'completed_bookings_count' => 60]);
    Package::factory()->for($premium)->create(['name' => 'Full Day', 'price' => 3500]);

    $response = $this->get(route('vendors.compare', ['vendors' => ['murah', 'premium']]));

    $response->assertOk()
        ->assertSee('Murah Studio')
        ->assertSee('Premium Studio')
        ->assertSee('Harga bermula')
        ->assertSee('Vendor Score')
        ->assertSee('Full Day')
        ->assertSee('terbaik');

    // The order in the query string is the order on screen.
    $response->assertSeeInOrder(['Murah Studio', 'Premium Studio']);
    $this->get(route('vendors.compare', ['vendors' => ['premium', 'murah']]))->assertSeeInOrder(['Premium Studio', 'Murah Studio']);
});

it('never compares more than four vendors', function () {
    $slugs = collect(range(1, 6))->map(function (int $index) {
        return Vendor::factory()->for($this->photography)->create(['name' => 'Studio '.$index, 'slug' => 'studio-'.$index])->slug;
    });

    $response = $this->get(route('vendors.compare', ['vendors' => $slugs->all()]));

    $response->assertOk()->assertSee('Studio 4')->assertDontSee('Studio 5');
});

it('leaves out unapproved and unknown vendors', function () {
    Vendor::factory()->for($this->photography)->create(['name' => 'Approved Studio', 'slug' => 'approved']);
    Vendor::factory()->pending()->for($this->photography)->create(['name' => 'Pending Studio', 'slug' => 'pending']);

    $this->get(route('vendors.compare', ['vendors' => ['approved', 'pending', 'tak-wujud']]))
        ->assertOk()
        ->assertSee('Approved Studio')
        ->assertDontSee('Pending Studio');
});

it('offers a compare checkbox and tray on the marketplace', function () {
    Vendor::factory()->for($this->photography)->create(['name' => 'ABC Studio', 'slug' => 'abc']);

    $this->get(route('vendors.index'))
        ->assertOk()
        ->assertSee('data-compare="abc"', false)
        ->assertSee('data-compare-tray', false)
        ->assertSee('Banding');
});
