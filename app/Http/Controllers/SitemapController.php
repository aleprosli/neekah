<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\SiteTemplate;
use App\Models\Vendor;
use App\Support\Locales;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The sitemap is built from the database rather than by crawling, so it lists
 * exactly what we mean to have indexed and nothing else. Anything the app
 * marks noindex is absent here too: search pages, comparisons, the signed-in
 * areas, and every couple's invitation card.
 */
class SitemapController extends Controller
{
    /**
     * Points Googlebot at the separate lists rather than one long file.
     *
     * Each one carries the date its newest entry changed, so a crawler can see
     * which list is worth fetching again instead of re-reading all four.
     */
    public function index(Request $request): Response
    {
        $this->refuseOnCardHost($request);

        return $this->xml('sitemaps.index', [
            'sitemaps' => [
                ['loc' => route('sitemap.pages'), 'lastmod' => null],
                ['loc' => route('sitemap.vendors'), 'lastmod' => $this->vendorsChangedAt()],
                ['loc' => route('sitemap.templates'), 'lastmod' => SiteTemplate::active()->max('updated_at')],
                ['loc' => route('sitemap.blog'), 'lastmod' => Post::query()->published()->max('updated_at')],
            ],
        ]);
    }

    public function pages(Request $request): Response
    {
        $this->refuseOnCardHost($request);

        $urls = collect([
            ...$this->inEveryLanguage('vendors.index', [], ['priority' => '1.0', 'changefreq' => 'daily']),
            ...$this->inEveryLanguage('landing', [], ['priority' => '0.8', 'changefreq' => 'monthly']),
            ...$this->inEveryLanguage('sites.templates', [], ['priority' => '0.8', 'changefreq' => 'weekly']),
            ...$this->inEveryLanguage('blog.index', [], ['priority' => '0.8', 'changefreq' => 'weekly']),
            ...$this->inEveryLanguage('vendor.register', [], ['priority' => '0.6', 'changefreq' => 'monthly']),
        ]);

        // Category listings are real pages: each one is the canonical address
        // for its slice of the marketplace.
        $categories = Category::active()->ordered()->get()
            ->flatMap(fn (Category $category): array => $this->inEveryLanguage(
                'vendors.index',
                ['category' => $category->slug],
                ['priority' => '0.7', 'changefreq' => 'daily'],
            ));

        return $this->xml('sitemaps.urls', ['urls' => $urls->concat($categories)]);
    }

    /**
     * One entry per language for the same page, each naming the others through
     * xhtml:link. Without this the sitemap would list whichever language the
     * app happened to be in when it was built — and the English pages, which
     * nothing else links to from outside, would never be found at all.
     *
     * @param  array<string, mixed>  $parameters
     * @param  array<string, string|null>  $extra
     * @return array<int, array<string, mixed>>
     */
    private function inEveryLanguage(string $name, array $parameters = [], array $extra = []): array
    {
        $alternates = collect(Locales::codes())
            ->mapWithKeys(fn (string $code): array => [$code => url()->routeIn($code, $name, $parameters)])
            ->filter()
            ->all();

        return collect($alternates)
            ->map(fn (string $loc): array => ['loc' => $loc, 'alternates' => $alternates] + $extra)
            ->values()
            ->all();
    }

    /**
     * Only published articles; a draft or a scheduled post appears here the
     * moment its date passes, with no one having to regenerate anything.
     */
    public function blog(Request $request): Response
    {
        $this->refuseOnCardHost($request);

        return $this->xml('sitemaps.urls', [
            'urls' => Post::query()->published()->latest('published_at')->orderByDesc('id')->get(['id', 'slug', 'published_at', 'updated_at'])
                ->flatMap(fn (Post $post): array => $this->inEveryLanguage('blog.show', ['post' => $post], [
                    'lastmod' => $post->updated_at?->toAtomString(),
                    'priority' => '0.7',
                    'changefreq' => 'monthly',
                ])),
        ]);
    }

    /**
     * Approved vendors only: a vendor awaiting review has no public page yet,
     * and listing an address that answers 404 is how a sitemap loses a
     * crawler's trust.
     */
    public function vendors(Request $request): Response
    {
        $this->refuseOnCardHost($request);

        return $this->xml('sitemaps.urls', [
            'urls' => Vendor::query()
                ->approved()
                ->withMax('packages', 'updated_at')
                ->withMax('portfolioItems', 'updated_at')
                ->orderBy('id')
                ->get()
                ->flatMap(fn (Vendor $vendor): array => $this->inEveryLanguage('vendors.show', ['vendor' => $vendor], [
                    'lastmod' => $this->contentChangedAt($vendor)?->toAtomString(),
                    'priority' => '0.9',
                    'changefreq' => 'weekly',
                ])),
        ]);
    }

    /**
     * When this vendor's page last actually changed: their own profile, their
     * packages or their portfolio. The score and the counters are deliberately
     * not part of it — they move on their own and say nothing about the page.
     */
    private function contentChangedAt(Vendor $vendor): ?Carbon
    {
        return collect([
            $vendor->updated_at,
            $vendor->packages_max_updated_at ? Carbon::parse($vendor->packages_max_updated_at) : null,
            $vendor->portfolio_items_max_updated_at ? Carbon::parse($vendor->portfolio_items_max_updated_at) : null,
        ])->filter()->max();
    }

    /** The newest change across every approved vendor's page. */
    private function vendorsChangedAt(): ?Carbon
    {
        return Vendor::query()
            ->approved()
            ->withMax('packages', 'updated_at')
            ->withMax('portfolioItems', 'updated_at')
            ->get()
            ->map(fn (Vendor $vendor): ?Carbon => $this->contentChangedAt($vendor))
            ->filter()
            ->max();
    }

    public function templates(Request $request): Response
    {
        $this->refuseOnCardHost($request);

        return $this->xml('sitemaps.urls', [
            'urls' => SiteTemplate::active()->ordered()->get()
                ->flatMap(fn (SiteTemplate $template): array => $this->inEveryLanguage('sites.templates.show', ['template' => $template], [
                    'lastmod' => $template->updated_at?->toAtomString(),
                    'priority' => '0.6',
                    'changefreq' => 'monthly',
                ])),
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
