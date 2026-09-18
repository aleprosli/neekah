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

it('gives every dashboard page the same navigation region to swap', function () {
    $vendor = Vendor::factory()->for(Category::first())->create();

    // The dashboard shell has one navigation that shows an active item: the
    // sidebar, which on a phone is the drawer. navigation.js pairs regions by
    // position, so the count has to match between any two dashboard pages —
    // and the drawer's checkbox lives inside it, so every swap closes it.
    $dashboard = $this->actingAs($vendor->user)->get(route('vendor.dashboard'))->assertOk()->getContent();
    $packages = $this->actingAs($vendor->user)->get(route('vendor.packages.index'))->assertOk()->getContent();

    expect(substr_count($dashboard, 'data-nav-region'))->toBe(1)
        ->and(substr_count($packages, 'data-nav-region'))->toBe(1)
        ->and($dashboard)->toContain('id="dashboard-drawer"');
});

it('keeps the public site furniture out of the dashboard shell', function () {
    $vendor = Vendor::factory()->for(Category::first())->create();

    // A web app, not a page of the marketplace: no floating site header, no
    // footer, no phone bottom bar.
    $dashboard = $this->actingAs($vendor->user)->get(route('vendor.dashboard'))->assertOk()->getContent();

    expect($dashboard)->not->toContain('Cara Ia Berfungsi')
        ->not->toContain('aria-label="Navigasi mudah alih"')
        ->not->toContain('aria-label="Footer"');
});

it('gives the vendor list furniture outside main that a blog post does not have', function () {
    // Only <main> and the navigations are swapped, so navigation.js refuses to
    // swap between pages whose top-level furniture differs. Without that the
    // vendor search bar, compare tray and filter dialog stayed on a blog post.
    $vendorList = $this->get(route('vendors.index'))->assertOk()->getContent();
    $blog = $this->get(route('blog.index'))->assertOk()->getContent();
    $outsideMain = fn (string $html): string => preg_replace('/<main\b.*<\/main>/s', '', $html);

    expect($outsideMain($vendorList))->toContain('data-compare-tray')->toContain('id="filters"')
        ->and($outsideMain($blog))->not->toContain('data-compare-tray')->not->toContain('id="filters"');
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
