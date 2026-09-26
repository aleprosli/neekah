<?php

namespace App\Http\Controllers;

use App\Enums\VendorTier;
use App\Models\Category;
use App\Models\Package;
use App\Models\PortfolioItem;
use App\Models\Review;
use App\Models\ReviewPhoto;
use App\Models\User;
use App\Models\Vendor;
use App\Support\ContactSettings;
use App\Support\ContentVersion;
use App\Support\Seo;
use App\Support\SeoSettings;
use App\Support\States;
use App\Support\VendorAvailability;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class VendorController extends Controller
{
    /** Vendors per page of the listing. */
    private const PER_PAGE = 12;

    /** How many reviews a visitor reads on a profile before paging. */
    private const REVIEWS_ON_PAGE = 12;

    /** Other vendors suggested at the foot of a profile. */
    private const RELATED_VENDORS = 3;

    /** User agents that fetch a page without a person reading it. */
    private const NOT_A_VISITOR = ['bot', 'crawler', 'spider', 'facebookexternalhit', 'whatsapp', 'preview', 'lighthouse', 'headless'];

    /**
     * The orders the list can be put in. A method rather than a constant: the
     * labels are translated, and a constant cannot hold a function call.
     *
     * @return array<string, string>
     */
    public static function sorts(): array
    {
        return [
            'recommended' => __('marketplace.sorts.recommended'),
            'rating' => __('marketplace.sorts.rating'),
            'price_asc' => __('marketplace.sorts.price_asc'),
            'price_desc' => __('marketplace.sorts.price_desc'),
            'reviews' => __('marketplace.sorts.reviews'),
            'popular' => __('marketplace.sorts.popular'),
        ];
    }

    /**
     * List approved vendors with search, filters, sorting and pagination.
     */
    public function index(Request $request, Seo $seo): View
    {
        $categories = $this->categories();
        $sort = $request->string('sort')->toString();

        $filters = [
            'q' => $request->string('q')->trim()->toString() ?: null,
            'category' => $request->string('category')->toString() ?: null,
            'state' => $request->string('state')->toString() ?: null,
            'min_price' => $request->filled('min_price') ? $request->integer('min_price') : null,
            'max_price' => $request->filled('max_price') ? $request->integer('max_price') : null,
            'min_rating' => $request->filled('min_rating') ? (float) $request->input('min_rating') : null,
            'tier' => VendorTier::tryFrom($request->string('tier')->toString())?->value,
            'sort' => array_key_exists($sort, self::sorts()) ? $sort : 'recommended',
        ];

        $activeCategory = $filters['category'] ? $categories->firstWhere('slug', $filters['category']) : null;

        $vendors = $this->listing($filters, $activeCategory, $request);

        $this->describeListing($seo, $request, $activeCategory, $filters);

        return view('vendors.index', [
            'vendors' => $vendors,
            'filters' => $filters,
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'states' => States::names(),
            'stateOptions' => States::options(),
            'tiers' => VendorTier::cases(),
            'sorts' => self::sorts(),
            'activeFilterCount' => count(array_filter([$filters['state'], $filters['min_price'], $filters['max_price'], $filters['min_rating'], $filters['tier']], fn ($value) => $value !== null)),
            'helpUrl' => $vendors->isEmpty() ? $this->helpUrl($activeCategory, $filters) : null,
        ]);
    }

    /**
     * The category list every marketplace render draws, cached against the
     * global version so a category added or hidden is picked up with nothing
     * to clear.
     *
     * Raw attribute rows rather than the models themselves. config/cache.php
     * sets serializable_classes to false on purpose - no PHP object comes back
     * out of the cache, so a leaked APP_KEY cannot be turned into a gadget
     * chain - and hydrate() rebuilds the models from plain arrays without
     * going back to the database.
     *
     * No language in the key either: name and examples are Translatable casts,
     * which resolve from the raw JSON when they are read, so one cached row
     * serves both languages.
     *
     * @return Collection<int, Category>
     */
    private function categories(): Collection
    {
        $rows = Cache::remember(
            'marketplace:categories:'.ContentVersion::global(),
            ContentVersion::TTL,
            fn (): array => self::rows(Category::active()->ordered()->get()),
        );

        return Category::hydrate($rows);
    }

    /**
     * A page of the listing, cached against the global version and the filters
     * that produced it.
     *
     * The filters are hashed rather than spelled out, because a keyword search
     * is free text and would otherwise put anything a visitor types into a
     * cache key. The page number and the request URL go into the hash too: the
     * paginator builds its page links from the path it was given, and that path
     * is /en on the English side.
     *
     * What is cached is the page's rows and its three counters, not the
     * paginator - see categories() for why nothing here may be an object.
     *
     * The hour is in the key too: boosts start and end on the hour, so one
     * that runs out leaves the top without anything having to save.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Vendor>
     */
    private function listing(array $filters, ?Category $activeCategory, Request $request): LengthAwarePaginator
    {
        $page = $request->integer('page', 1) ?: 1;
        $key = md5(serialize([$filters, $page, $request->url()]));

        $cached = Cache::remember(
            'marketplace:list:'.$key.':'.now()->format('YmdH').':'.ContentVersion::global(),
            ContentVersion::TTL,
            function () use ($filters, $activeCategory): array {
                $paginator = self::filtered($filters, $activeCategory)
                    ->tap(fn (Builder $query) => match ($filters['sort']) {
                        'rating' => $query->orderByDesc('rating_avg')->orderByDesc('reviews_count'),
                        'price_asc' => $query->orderBy('price_from'),
                        'price_desc' => $query->orderByDesc('price_from'),
                        'reviews' => $query->orderByDesc('reviews_count'),
                        'popular' => $query->orderByDesc('views_30d'),
                        default => $query->boostedFirst($activeCategory)->orderByDesc('score'),
                    })
                    ->orderBy('id')
                    ->paginate(self::PER_PAGE);

                return [
                    'total' => $paginator->total(),
                    'rows' => self::vendorRows($paginator->getCollection()),
                ];
            },
        );

        return (new LengthAwarePaginator(
            self::hydrateVendors($cached['rows']),
            $cached['total'],
            self::PER_PAGE,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page'],
        ))->withQueryString();
    }

    /**
     * Approved vendors matching the visitor's filters, before any ordering.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Vendor>
     */
    private static function filtered(array $filters, ?Category $activeCategory): Builder
    {
        return Vendor::query()
            ->approved()
            ->with('category')
            ->when($filters['q'], fn (Builder $query, string $keyword) => $query->matching($keyword))
            ->when($activeCategory, fn (Builder $query, Category $category) => $query->inCategory($category))
            ->when($filters['state'], fn (Builder $query, string $state) => $query->servingState($state))
            ->when($filters['min_price'] !== null, fn (Builder $query) => $query->where('price_from', '>=', $filters['min_price']))
            ->when($filters['max_price'] !== null, fn (Builder $query) => $query->where('price_from', '<=', $filters['max_price']))
            ->when($filters['min_rating'] !== null, fn (Builder $query) => $query->where('rating_avg', '>=', $filters['min_rating']))
            ->when($filters['tier'], fn (Builder $query, string $tier) => $query->where('tier', $tier));
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
            __('props.vendor_onboarding.whatsapp_looking', ['looking' => $looking])
        );
    }

    /**
     * Show an approved vendor's public profile with packages, reviews and booking form.
     */
    public function show(Request $request, Vendor $vendor, Seo $seo): View
    {
        abort_unless($vendor->isApproved(), 404);

        // The two category relations stay live. They are a vendor's own rows but
        // a category's own text, which the vendor's version cannot see moving.
        $vendor->load(['category', 'categories']);

        // Read once and passed down. It used to be memoised on the controller,
        // which is wrong: Route::getController() keeps the controller on the
        // route, so the property outlived the request and the next visitor was
        // served the last one's catalogue.
        $payload = $this->cachedPayload($vendor);

        $this->attachCatalogue($vendor, $payload);

        // Each review belongs to the vendor already in hand. Setting the
        // inverse keeps sourceLabel() from fetching it back one row at a time.
        // After the cache read, never before it: the vendor carries its own
        // reviews, so caching the pair would serialise a loop.
        $vendor->reviews->each->setRelation('vendor', $vendor);

        $related = $this->related($vendor);

        $this->countView($request, $vendor);

        $description = $vendor->tagline ?: Str::of((string) $vendor->description)->squish()->value();

        $seo->title(__('seo.marketplace.vendor_title', ['name' => $vendor->name, 'category' => $vendor->category->name, 'city' => $vendor->city]))
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
                'areaServed' => array_map(fn (string $state): array => ['@type' => 'AdministrativeArea', 'name' => $state], $vendor->serviceStates()),
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

        $openReviews = $payload['openReviews'];

        return view('vendors.show', [
            'vendor' => $vendor,
            'openReviews' => $openReviews,
            // How many reviews a visitor can read on the page. reviews_count
            // stays booking-backed only, because the ranking reads it.
            'publishedReviewsCount' => $vendor->reviews_count + $openReviews['total'],
            // Back into a collection: the view takes the first five off it.
            'gallery' => collect($payload['gallery']),
            'category' => $vendor->category,
            'extraCategories' => $vendor->extraCategories(),
            'related' => $related,
            'defaultEventDate' => $request->user()?->weddings()->latest('event_date')->first()?->event_date->toDateString(),
            'onlineBooking' => $this->onlineBooking($vendor),
        ]);
    }

    /**
     * What the booking form needs when this vendor takes online bookings, or
     * null to leave the contact card. Worked out on every request and never
     * cached with the page: it moves with today's date and every booking.
     *
     * @return array<string, mixed>|null
     */
    private function onlineBooking(Vendor $vendor): ?array
    {
        $availability = VendorAvailability::for($vendor);

        if (! $availability->acceptsOnlineBookings()) {
            return null;
        }

        $settings = $availability->settings();

        return [
            'channel' => $availability->paymentChannel(),
            'terms' => $settings->deposit_terms,
            'packages' => $vendor->packages->map(fn (Package $package): array => [
                'id' => $package->id,
                'name' => $package->name,
                'price' => (float) $package->price,
                'deposit' => $settings->depositFor((float) $package->price),
            ])->values(),
            'nextOpen' => $availability->nextOpenDays(6),
            'availabilityUrl' => route('vendors.availability', $vendor),
        ];
    }

    /**
     * One view for the vendor's analytics. Once per visitor per session, so a
     * couple flicking between photos is one look, not ten; and never the vendor
     * themselves, an admin, or a crawler.
     *
     * Views also order "Paling ramai dilihat", so one address counts once a
     * day per vendor as well: clearing cookies and reloading does not climb.
     */
    private function countView(Request $request, Vendor $vendor): void
    {
        $user = $request->user();

        if ($user?->isAdmin() || ($user && $user->getKey() === $vendor->user_id)
            || Str::contains(Str::lower((string) $request->userAgent()), self::NOT_A_VISITOR)) {
            return;
        }

        $key = 'vendor-viewed.'.$vendor->getKey();

        if (! $request->hasSession() || $request->session()->has($key)) {
            return;
        }

        $request->session()->put($key, true);

        if (Cache::add('vendor-view:'.$vendor->getKey().':'.sha1((string) $request->ip()).':'.today()->toDateString(), true, now()->endOfDay())) {
            $vendor->recordStat('profile_views');
        }
    }

    /**
     * Everything on a vendor's page that is the same for every visitor, read
     * once and held against that vendor's own version.
     *
     * Scoped to the one vendor on purpose: a global version would mean any of
     * the other three hundred editing a price threw this away too. What a
     * vendor's version cannot see - a category renamed, a review photo removed
     * without its review being saved - is what ContentVersion::TTL is for.
     *
     * The author is stored as id and name alone. The rest of a user row has no
     * business on this page, and CACHE_STORE is database in production, so
     * everything cached here is a row in MySQL holding a copy of it.
     *
     * @return array{packages: array<int, array<string, mixed>>, portfolioItems: array<int, array<string, mixed>>, reviews: array<int, array{review: array<string, mixed>, user: array<string, mixed>|null, photos: array<int, array<string, mixed>>}>, gallery: array<int, array{id: int, url: string, thumbnail: string|null, caption: string|null}>, openReviews: array{total: int, average: float}}
     */
    private function cachedPayload(Vendor $vendor): array
    {
        return Cache::remember(
            'vendor-page:'.$vendor->getKey().':'.ContentVersion::forVendor($vendor->getKey()),
            ContentVersion::TTL,
            function () use ($vendor): array {
                $portfolio = $vendor->portfolioItems()->visible()->get();

                $reviews = $vendor->reviews()
                    ->published()
                    ->with(['user:id,name', 'photos'])
                    ->latest()
                    ->limit(self::REVIEWS_ON_PAGE)
                    ->get();

                return [
                    'packages' => self::rows($vendor->packages()->active()->get()),
                    'portfolioItems' => self::rows($portfolio),
                    'reviews' => $reviews->map(fn (Review $review): array => [
                        'review' => $review->getAttributes(),
                        'user' => $review->user?->getAttributes(),
                        'photos' => self::rows($review->photos),
                    ])->all(),
                    'gallery' => $this->gallery($portfolio)->all(),
                    'openReviews' => $this->openReviewSummary($vendor),
                ];
            },
        );
    }

    /**
     * Hand the cached rows back to the model as ordinary loaded relations, so
     * every view and every method downstream reads them the way it always did.
     *
     * @param  array<string, mixed>  $payload
     */
    private function attachCatalogue(Vendor $vendor, array $payload): void
    {
        $vendor->setRelation('packages', Package::hydrate($payload['packages']));
        $vendor->setRelation('portfolioItems', PortfolioItem::hydrate($payload['portfolioItems']));

        $vendor->setRelation('reviews', Review::hydrate(array_column($payload['reviews'], 'review'))
            ->each(function (Review $review, int $index) use ($payload): void {
                $row = $payload['reviews'][$index];

                $review->setRelation('user', $row['user'] ? User::hydrate([$row['user']])->first() : null);
                $review->setRelation('photos', ReviewPhoto::hydrate($row['photos']));
            }));
    }

    /**
     * Three more vendors in the same category. Held against the global version
     * rather than this vendor's, because it is the others that decide it.
     *
     * @return Collection<int, Vendor>
     */
    private function related(Vendor $vendor): Collection
    {
        $rows = Cache::remember(
            'vendor-related:'.$vendor->getKey().':'.ContentVersion::global(),
            ContentVersion::TTL,
            fn (): array => self::vendorRows(
                Vendor::query()
                    ->approved()
                    ->with('category')
                    ->inCategory($vendor->category)
                    ->whereKeyNot($vendor->getKey())
                    ->orderByDesc('score')
                    ->limit(self::RELATED_VENDORS)
                    ->get()
            ),
        );

        return self::hydrateVendors($rows);
    }

    /**
     * A vendor card needs its category, so each row carries both.
     *
     * @param  Collection<int, Vendor>  $vendors
     * @return array<int, array{vendor: array<string, mixed>, category: array<string, mixed>|null}>
     */
    private static function vendorRows(Collection $vendors): array
    {
        return $vendors->map(fn (Vendor $vendor): array => [
            'vendor' => $vendor->getAttributes(),
            'category' => $vendor->category?->getAttributes(),
        ])->values()->all();
    }

    /**
     * @param  array<int, array{vendor: array<string, mixed>, category: array<string, mixed>|null}>  $rows
     * @return Collection<int, Vendor>
     */
    private static function hydrateVendors(array $rows): Collection
    {
        return Vendor::hydrate(array_column($rows, 'vendor'))
            ->each(function (Vendor $vendor, int $index) use ($rows): void {
                $category = $rows[$index]['category'];

                $vendor->setRelation('category', $category ? Category::hydrate([$category])->first() : null);
            });
    }

    /**
     * The raw column values behind a set of models, which is what hydrate()
     * takes and what a cache holding no objects can store.
     *
     * @param  Collection<int, Model>  $models
     * @return array<int, array<string, mixed>>
     */
    private static function rows(Collection $models): array
    {
        return $models->map(fn (Model $model): array => $model->getAttributes())->values()->all();
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
     * @param  Collection<int, PortfolioItem>  $portfolioItems
     * @return Collection<int, array{id: int, url: string, thumbnail: string|null, caption: string|null}>
     */
    private function gallery(Collection $portfolioItems): Collection
    {
        return $portfolioItems->map(fn (PortfolioItem $item): array => [
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

        $where = __('seo.marketplace.in', ['place' => $filters['state'] ?: __('seo.marketplace.malaysia')]);

        $seo->title($category
                ? __('seo.marketplace.title_category', ['category' => $category->name, 'where' => $where])
                : __('seo.marketplace.title', ['where' => $where]))
            ->description($category
                ? __('seo.marketplace.description_category', ['category' => Str::lower($category->name), 'where' => $where])
                : __('seo.marketplace.description', ['where' => $where]))
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
