<?php

namespace App\Http\Controllers;

use App\Enums\VendorTier;
use App\Models\Category;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use App\Support\ContactSettings;
use App\Support\Seo;
use App\Support\SeoSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VendorController extends Controller
{
    public const SORTS = [
        'recommended' => 'Disyorkan',
        'rating' => 'Rating tertinggi',
        'price_asc' => 'Harga: rendah ke tinggi',
        'price_desc' => 'Harga: tinggi ke rendah',
        'reviews' => 'Paling banyak review',
    ];

    /**
     * List approved vendors with search, filters, sorting and pagination.
     */
    public function index(Request $request, Seo $seo): View
    {
        $categories = Category::active()->ordered()->get();
        $sort = $request->string('sort')->toString();

        $filters = [
            'q' => $request->string('q')->trim()->toString() ?: null,
            'category' => $request->string('category')->toString() ?: null,
            'state' => $request->string('state')->toString() ?: null,
            'min_price' => $request->filled('min_price') ? $request->integer('min_price') : null,
            'max_price' => $request->filled('max_price') ? $request->integer('max_price') : null,
            'min_rating' => $request->filled('min_rating') ? (float) $request->input('min_rating') : null,
            'tier' => VendorTier::tryFrom($request->string('tier')->toString())?->value,
            'sort' => array_key_exists($sort, self::SORTS) ? $sort : 'recommended',
        ];

        $activeCategory = $filters['category'] ? $categories->firstWhere('slug', $filters['category']) : null;

        $vendors = Vendor::query()
            ->approved()
            ->with('category')
            ->when($filters['q'], fn (Builder $query, string $keyword) => $query->where(function (Builder $query) use ($keyword): void {
                $like = '%'.$keyword.'%';
                $query->where('name', 'like', $like)
                    ->orWhere('tagline', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('state', 'like', $like)
                    ->orWhereHas('category', fn (Builder $query) => $query->where('name', 'like', $like));
            }))
            ->when($activeCategory, fn (Builder $query, Category $category) => $query->whereBelongsTo($category))
            ->when($filters['state'], fn (Builder $query, string $state) => $query->where('state', $state))
            ->when($filters['min_price'] !== null, fn (Builder $query) => $query->where('price_from', '>=', $filters['min_price']))
            ->when($filters['max_price'] !== null, fn (Builder $query) => $query->where('price_from', '<=', $filters['max_price']))
            ->when($filters['min_rating'] !== null, fn (Builder $query) => $query->where('rating_avg', '>=', $filters['min_rating']))
            ->when($filters['tier'], fn (Builder $query, string $tier) => $query->where('tier', $tier))
            ->tap(fn (Builder $query) => match ($filters['sort']) {
                'rating' => $query->orderByDesc('rating_avg')->orderByDesc('reviews_count'),
                'price_asc' => $query->orderBy('price_from'),
                'price_desc' => $query->orderByDesc('price_from'),
                'reviews' => $query->orderByDesc('reviews_count'),
                default => $query->orderByDesc('score'),
            })
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        $this->describeListing($seo, $request, $activeCategory, $filters);

        return view('vendors.index', [
            'vendors' => $vendors,
            'filters' => $filters,
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'states' => Vendor::STATES,
            'tiers' => VendorTier::cases(),
            'sorts' => self::SORTS,
            'activeFilterCount' => count(array_filter([$filters['state'], $filters['min_price'], $filters['max_price'], $filters['min_rating'], $filters['tier']], fn ($value) => $value !== null)),
            'helpUrl' => $vendors->isEmpty() ? $this->helpUrl($activeCategory, $filters) : null,
        ]);
    }

    /**
     * When the list comes up empty, a WhatsApp to Neekah already saying what
     * the couple was looking for, so the team can pass it on to vendors in the
     * network. Null when no number is set under Admin → Tetapan.
     *
     * @param  array<string, mixed>  $filters
     */
    private function helpUrl(?Category $category, array $filters): ?string
    {
        $looking = trim(implode(' ', array_filter([
            $category ? 'vendor '.$category->name : 'vendor',
            $filters['q'] ? '"'.$filters['q'].'"' : null,
            $filters['state'] ? 'di '.$filters['state'] : null,
        ])));

        return app(ContactSettings::class)->whatsappUrl(
            'Salam Neekah, saya sedang mencari '.$looking.' untuk majlis saya tetapi belum jumpa di laman web. Boleh bantu kongsikan kepada vendor lain?'
        );
    }

    /**
     * Show an approved vendor's public profile with packages, reviews and booking form.
     */
    public function show(Request $request, Vendor $vendor, Seo $seo): View
    {
        abort_unless($vendor->isApproved(), 404);

        $vendor->load([
            'category',
            'packages' => fn ($query) => $query->active(),
            'portfolioItems' => fn ($query) => $query->visible(),
            'reviews' => fn ($query) => $query->published()->with(['user', 'photos'])->latest()->limit(12),
        ]);

        // Each review belongs to the vendor already in hand. Setting the
        // inverse keeps sourceLabel() from fetching it back one row at a time.
        $vendor->reviews->each->setRelation('vendor', $vendor);

        $related = Vendor::query()
            ->approved()
            ->with('category')
            ->whereBelongsTo($vendor->category)
            ->whereKeyNot($vendor->getKey())
            ->orderByDesc('score')
            ->limit(3)
            ->get();

        $description = $vendor->tagline ?: Str::of((string) $vendor->description)->squish()->value();

        $seo->title($vendor->name.' — '.$vendor->category->name.' di '.$vendor->city)
            ->description($description)
            ->image($vendor->portfolioItems->first()?->url())
            ->type('profile')
            ->breadcrumbs([
                'Neekah' => route('vendors.index'),
                $vendor->category->name => route('vendors.index', ['category' => $vendor->category->slug]),
                $vendor->name => route('vendors.show', $vendor),
            ])
            ->schema([
                '@type' => 'LocalBusiness',
                '@id' => route('vendors.show', $vendor).'#vendor',
                'name' => $vendor->name,
                'description' => $description ?: null,
                'url' => route('vendors.show', $vendor),
                'image' => $vendor->portfolioItems->take(3)->map(fn ($item): string => $item->url())->values()->all(),
                'address' => ['@type' => 'PostalAddress', 'addressLocality' => $vendor->city, 'addressRegion' => $vendor->state, 'addressCountry' => 'MY'],
                'priceRange' => 'Dari RM'.number_format((float) $vendor->price_from),
                // Stars in results are only claimed once real reviews exist.
                'aggregateRating' => $vendor->reviews_count > 0 ? [
                    '@type' => 'AggregateRating',
                    'ratingValue' => round((float) $vendor->rating_avg, 1),
                    'reviewCount' => $vendor->reviews_count,
                    'bestRating' => 5,
                    'worstRating' => 1,
                ] : null,
            ]);

        $openReviews = $this->openReviewSummary($vendor);

        return view('vendors.show', [
            'vendor' => $vendor,
            'openReviews' => $openReviews,
            // How many reviews a visitor can read on the page. reviews_count
            // stays booking-backed only, because the ranking reads it.
            'publishedReviewsCount' => $vendor->reviews_count + $openReviews['total'],
            'gallery' => $this->gallery($vendor),
            'category' => $vendor->category,
            'related' => $related,
            'defaultEventDate' => $request->user()?->weddings()->latest('event_date')->first()?->event_date->toDateString(),
        ]);
    }

    /**
     * Reviews written straight on the profile, counted on their own.
     *
     * They are kept out of rating_avg and reviews_count on purpose, so the
     * page has to say what they add up to separately rather than folding them
     * into a number that decides the vendor's ranking.
     *
     * @return array{total: int, average: float}
     */
    private function openReviewSummary(Vendor $vendor): array
    {
        $summary = $vendor->reviews()->open()->published()
            ->selectRaw('count(*) as total, avg(rating) as average')
            ->first();

        return [
            'total' => (int) $summary->total,
            'average' => round((float) $summary->average, 1),
        ];
    }

    /**
     * The photos the gallery component renders, in the order the vendor
     * arranged them. Thumbnails ride along so the grid and the filmstrip never
     * download a full-size photo they only show at 56 pixels.
     *
     * @return Collection<int, array{id: int, url: string, thumbnail: string|null, caption: string|null}>
     */
    private function gallery(Vendor $vendor): Collection
    {
        return $vendor->portfolioItems->map(fn (PortfolioItem $item): array => [
            'id' => $item->id,
            'url' => $item->url(),
            'thumbnail' => $item->thumbnailUrl(),
            'caption' => $item->caption,
        ])->values();
    }

    /**
     * Describe the listing to search engines.
     *
     * Only the filters that make a genuinely different page reach the canonical
     * URL. Price, rating, tier and sort produce the same vendors in a different
     * order, so folding them onto one address stops a dozen near-identical
     * pages competing with each other. A keyword search is not our content at
     * all, so it stays out of the index entirely.
     *
     * @param  array<string, mixed>  $filters
     */
    private function describeListing(Seo $seo, Request $request, ?Category $category, array $filters): void
    {
        $page = max(1, $request->integer('page', 1));

        $keep = array_filter([
            'category' => $filters['category'],
            'state' => $filters['state'],
            'page' => $page > 1 ? $page : null,
        ]);

        $where = $filters['state'] ? ' di '.$filters['state'] : ' di Malaysia';

        $seo->title($category ? 'Vendor '.$category->name.$where : 'Cari vendor perkahwinan'.$where)
            ->description($category
                ? 'Bandingkan dan tempah '.Str::lower($category->name).$where.'. Harga, pakej, rating dan review daripada pasangan yang benar-benar menempah.'
                : 'Cari dan tempah vendor perkahwinan'.$where.': jurugambar, katering, pelamin, mak andam dan banyak lagi. Harga, pakej dan review sebenar.')
            ->canonical(url()->current().($keep ? '?'.http_build_query($keep) : ''));

        if ($category) {
            $seo->breadcrumbs(['Neekah' => route('vendors.index'), 'Vendor '.$category->name => route('vendors.index', ['category' => $category->slug])]);
        }

        // The home page is where Google learns the site's own name and logo.
        if ($keep === []) {
            $seo->schema([
                '@type' => 'WebSite',
                '@id' => route('vendors.index').'#website',
                'name' => config('app.name'),
                'url' => route('vendors.index'),
                'inLanguage' => 'ms-MY',
            ])->schema([
                '@type' => 'Organization',
                '@id' => route('vendors.index').'#organization',
                'name' => config('app.name'),
                'url' => route('vendors.index'),
                'logo' => asset(config('neekah.brand.mark')),
                'description' => app(SeoSettings::class)->description(),
            ]);
        }

        if ($filters['q']) {
            $seo->noindex();
        }
    }
}
