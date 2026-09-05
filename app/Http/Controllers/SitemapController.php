<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SiteTemplate;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

/**
 * The sitemap is built from the database rather than by crawling, so it lists
 * exactly what we mean to have indexed and nothing else. Anything the app
 * marks noindex is absent here too: search pages, comparisons, the signed-in
 * areas, and every couple's invitation card.
 */
class SitemapController extends Controller
{
    /** Points Googlebot at the three lists rather than one long file. */
    public function index(Request $request): Response
    {
        $this->refuseOnCardHost($request);

        return $this->xml('sitemaps.index', [
            'sitemaps' => [
                route('sitemap.pages'),
                route('sitemap.vendors'),
                route('sitemap.templates'),
            ],
        ]);
    }

    public function pages(Request $request): Response
    {
        $this->refuseOnCardHost($request);

        $urls = collect([
            ['loc' => route('vendors.index'), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => route('landing'), 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => route('sites.templates'), 'priority' => '0.8', 'changefreq' => 'weekly'],
        ]);

        // Category listings are real pages: each one is the canonical address
        // for its slice of the marketplace.
        $categories = Category::active()->ordered()->get()
            ->map(fn (Category $category): array => [
                'loc' => route('vendors.index', ['category' => $category->slug]),
                'priority' => '0.7',
                'changefreq' => 'daily',
            ]);

        return $this->xml('sitemaps.urls', ['urls' => $urls->concat($categories)]);
    }

    public function vendors(Request $request): Response
    {
        $this->refuseOnCardHost($request);

        return $this->xml('sitemaps.urls', [
            'urls' => Vendor::query()->approved()->orderBy('id')->get()
                ->map(fn (Vendor $vendor): array => [
                    'loc' => route('vendors.show', $vendor),
                    'lastmod' => $vendor->updated_at?->toAtomString(),
                    'priority' => '0.9',
                    'changefreq' => 'weekly',
                ]),
        ]);
    }

    public function templates(Request $request): Response
    {
        $this->refuseOnCardHost($request);

        return $this->xml('sitemaps.urls', [
            'urls' => SiteTemplate::active()->ordered()->get()
                ->map(fn (SiteTemplate $template): array => [
                    'loc' => route('sites.templates.show', $template),
                    'lastmod' => $template->updated_at?->toAtomString(),
                    'priority' => '0.6',
                    'changefreq' => 'monthly',
                ]),
        ]);
    }

    /**
     * A card lives on a subdomain of the invitation host, which is exactly how
     * the route group matches it. Comparing against the app's own host instead
     * would break the moment the two are configured apart.
     */
    private function onCardHost(Request $request): bool
    {
        return str_ends_with($request->getHost(), '.'.config('neekah.site_domain'));
    }

    private function refuseOnCardHost(Request $request): void
    {
        abort_if($this->onCardHost($request), 404);
    }

    /**
     * @param  array<string, Collection<int, array<string, string|null>>|array<int, string>>  $data
     */
    private function xml(string $view, array $data): Response
    {
        return response()
            ->view($view, $data)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
