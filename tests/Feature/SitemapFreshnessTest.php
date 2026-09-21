<?php

use App\Actions\RecalculateVendorStats;
use App\Enums\VendorStatus;
use App\Models\Category;
use App\Models\Package;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
});

it('lists a vendor the moment an admin approves them', function () {
    $vendor = Vendor::factory()->pending()->for(Category::first())->create();

    // A vendor awaiting review has no public page — their address answers 404,
    // and a sitemap full of 404s is one a crawler learns to distrust.
    $this->get(route('vendors.show', $vendor))->assertNotFound();
    $this->get(route('sitemap.vendors'))->assertOk()->assertDontSee($vendor->slug);

    $vendor->update(['status' => VendorStatus::Approved]);

    // Nothing to regenerate: the sitemap is built from the database per request.
    $this->get(route('sitemap.vendors'))->assertOk()->assertSee(route('vendors.show', $vendor), false);
});

it('moves lastmod when the vendor changes their page, and only then', function () {
    $vendor = Vendor::factory()->for(Category::first())->create(['updated_at' => now()->subMonth()]);

    $before = lastmodFor($this, $vendor);

    // Recalculating the score writes counters, a tier and a score. None of that
    // is a change to the page, so it must not move lastmod — a lastmod that
    // churns on its own is one Google stops believing.
    app(RecalculateVendorStats::class)->handle($vendor);

    expect(lastmodFor($this, $vendor))->toBe($before);

    // A new package is a real change to what the page shows.
    Package::factory()->for($vendor)->create();

    expect(lastmodFor($this, $vendor))->not->toBe($before);
});

it('tells a crawler which list changed, so it need not read all four', function () {
    Vendor::factory()->for(Category::first())->create();

    $index = $this->get(route('sitemap.index'))->assertOk()->getContent();

    expect(substr_count($index, '<sitemap>'))->toBe(4)
        ->and($index)->toContain('<lastmod>');
});

/**
 * The lastmod this vendor's entry carries in the sitemap.
 */
function lastmodFor($test, Vendor $vendor): ?string
{
    $xml = simplexml_load_string($test->get(route('sitemap.vendors'))->getContent());

    foreach ($xml->url as $url) {
        if ((string) $url->loc === route('vendors.show', $vendor)) {
            return (string) $url->lastmod;
        }
    }

    return null;
}

it('offers every page in both languages, each naming the other', function () {
    $this->seed(CategorySeeder::class);
    $vendor = Vendor::factory()->create();

    $xml = $this->get(route('sitemap.pages'))->assertOk()->getContent();

    // The English pages are linked from nowhere outside the site, so if the
    // sitemap leaves them out they are never found at all.
    expect($xml)
        ->toContain('<loc>'.route('landing').'</loc>')
        ->toContain('<loc>'.url()->routeIn('en', 'landing').'</loc>')
        ->toContain('hreflang="en-MY"')
        ->toContain('hreflang="x-default"');

    expect(simplexml_load_string($xml))->not->toBeFalse();
});

it('lists the Malay addresses even when an English page was served first', function () {
    $this->seed(CategorySeeder::class);

    // The sitemap routes carry no locale middleware, so they used to inherit
    // whatever language the last request left behind — and a worker that had
    // just served /en would publish a sitemap of nothing but /en addresses.
    $this->get('/en')->assertOk();

    // Written out rather than built with route(), which would itself come back
    // in whatever language the request left behind and assert nothing.
    expect($this->get('/sitemap-pages.xml')->assertOk()->getContent())
        ->toContain('<loc>'.config('app.url').'</loc>')
        ->toContain('<loc>'.config('app.url').'/blog</loc>');
});
