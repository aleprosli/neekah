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

it('marks a dashboard and a public page as different shells', function () {
    $vendor = Vendor::factory()->for(Category::first())->create();

    // Swapping <main> between these two would leave the dashboard sidebar
    // standing around a public page, so navigation must see they differ and
    // fall back to an ordinary page load.
    $dashboard = $this->actingAs($vendor->user)->get(route('vendor.dashboard'))->assertOk();
    $public = $this->actingAs($vendor->user)->get(route('vendors.index'))->assertOk();

    expect($dashboard->getContent())->toContain('<meta name="page-shell" content="dashboard">')
        ->and($public->getContent())->toContain('<meta name="page-shell" content="site">');
});

it('gives a dashboard page both navigations a swap has to update', function () {
    $vendor = Vendor::factory()->for(Category::first())->create();

    // The site header and the dashboard sidebar both show an active item.
    // navigation.js pairs them by position, so the count has to match between
    // any two pages of the same shell.
    $dashboard = $this->actingAs($vendor->user)->get(route('vendor.dashboard'))->assertOk()->getContent();
    $packages = $this->actingAs($vendor->user)->get(route('vendor.packages.index'))->assertOk()->getContent();

    expect(substr_count($dashboard, 'data-nav-region'))->toBe(2)
        ->and(substr_count($packages, 'data-nav-region'))->toBe(2);
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
