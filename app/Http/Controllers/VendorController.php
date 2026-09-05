<?php

namespace App\Http\Controllers;

use App\Enums\VendorTier;
use App\Models\Category;
use App\Models\Vendor;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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
        ]);
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
            'portfolioItems',
            'reviews' => fn ($query) => $query->with('user')->latest()->limit(6),
        ]);

        $related = Vendor::query()
            ->approved()
            ->with('category')
            ->whereBelongsTo($vendor->category)
            ->whereKeyNot($vendor->getKey())
            ->orderByDesc('score')
            ->limit(3)
            ->get();

        $seo->title($vendor->name.' — '.$vendor->category->name.' di '.$vendor->city)
            ->description($vendor->tagline ?: Str::of((string) $vendor->description)->squish()->value())
            ->image($vendor->portfolioItems->first()?->url())
            ->type('profile');

        return view('vendors.show', [
            'vendor' => $vendor,
            'category' => $vendor->category,
            'related' => $related,
            'defaultEventDate' => $request->user()?->weddings()->latest('event_date')->first()?->event_date->toDateString(),
        ]);
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
                : null)
            ->canonical(url()->current().($keep ? '?'.http_build_query($keep) : ''));

        if ($filters['q']) {
            $seo->noindex();
        }
    }
}
