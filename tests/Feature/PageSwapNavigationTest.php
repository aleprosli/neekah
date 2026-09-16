<?php

use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
});

it('gives every dashboard page the regions navigation swaps', function () {
    $vendor = Vendor::factory()->for(Category::first())->create();

    // resources/js/navigation.js replaces <main> and [data-nav-region]. A page
    // missing either falls back to a full load, which is the thing this avoids.
    $this->actingAs($vendor->user)
        ->get(route('vendor.dashboard'))
        ->assertOk()
        ->assertSee('<main', false)
        ->assertSee('data-nav-region', false);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('<main', false)
        ->assertSee('data-nav-region', false);
});

it('serves a swap request the same page a browser would get', function () {
    $vendor = Vendor::factory()->for(Category::first())->create();

    // The server does not treat a swap specially: it renders the whole page and
    // the browser keeps the parts that did not change.
    $swapped = $this->actingAs($vendor->user)
        ->withHeaders(['X-Page-Swap' => '1'])
        ->get(route('vendor.packages.index'))
        ->assertOk();

    expect($swapped->headers->get('content-type'))->toContain('text/html')
        ->and($swapped->getContent())->toContain('data-vue="vendor-packages-page"');
});
