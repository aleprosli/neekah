<?php

use App\Models\Category;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
});

it('renders the promotional landing page at /about', function () {
    $this->get('/about')
        ->assertOk()
        ->assertSee('Semua Urusan Majlis')
        ->assertSee('Satu Platform');
});

it('shows every active vendor category in the marketplace preview', function () {
    $response = $this->get(route('landing'));

    foreach (Category::active()->get() as $category) {
        $response->assertSee($category->name);
    }
});

it('shows approved featured vendors and the vendor point system', function () {
    $approved = Vendor::factory()->for(Category::first())->create(['name' => 'ABC Wedding Photography']);
    Vendor::factory()->pending()->for(Category::first())->create(['name' => 'Vendor Belum Lulus']);

    $this->get(route('landing'))
        ->assertSee($approved->name)
        ->assertDontSee('Vendor Belum Lulus')
        ->assertSee('Booking melalui platform')
        ->assertSee('+100');
});
