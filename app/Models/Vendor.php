<?php

namespace App\Models;

use App\Actions\StoreOptimizedImage;
use App\Enums\BookingStatus;
use App\Enums\PriceUnit;
use App\Enums\VendorStatus;
use App\Enums\VendorTier;
use App\Support\PhoneNumber;
use App\Support\SocialLinks;
use App\Support\States;
use Database\Factories\VendorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

#[Fillable([
    'user_id', 'category_id', 'name', 'slug', 'tagline', 'description', 'city', 'state', 'service_states',
    'phone', 'whatsapp', 'social_links', 'price_from', 'price_unit', 'cover_image', 'logo', 'cover_tone',
    'status', 'tier', 'rating_avg', 'reviews_count', 'completed_bookings_count',
    'response_rate', 'completion_rate', 'score', 'points_total', 'tier_locked', 'penalty_points', 'violations_count', 'approved_at',
])]
class Vendor extends Model
{
    /** @use HasFactory<VendorFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'service_states' => 'array',
            'price_from' => 'decimal:2',
            'price_unit' => PriceUnit::class,
            'status' => VendorStatus::class,
            'tier' => VendorTier::class,
            'rating_avg' => 'decimal:2',
            'score' => 'decimal:2',
            'tier_locked' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Every category the vendor works in, primary one included. A hantaran
     * maker who also does makeup is one business, not two profiles, so the
     * pivot is what the marketplace filters on; category_id stays the one
     * shown on the card, the profile heading and the breadcrumb.
     *
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->orderBy('sort_order')->orderBy('name');
    }

    /**
     * The categories beyond the primary one, for the places that already print
     * the primary and want the rest beside it.
     *
     * @return Collection<int, Category>
     */
    public function extraCategories(): Collection
    {
        return $this->categories->reject(fn (Category $category): bool => $category->is($this->category))->values();
    }

    /**
     * Every negeri the vendor travels to, home state first. Never empty: a
     * vendor covers at least where they are.
     *
     * @return array<int, string>
     */
    public function serviceStates(): array
    {
        return $this->service_states ?: array_filter([$this->state]);
    }

    /**
     * The two lists are kept whole here rather than at each call site, so a
     * vendor saved by the profile form, the admin, a seeder or a factory all
     * end up with the primary category in the pivot and the home state in the
     * coverage. Search may then read one place and trust it.
     */
    protected static function booted(): void
    {
        static::saving(function (self $vendor): void {
            // No negeri at all means config/states.php did not load — a deploy
            // that pulled the new config but skipped config:cache. Filtering
            // against an empty list would quietly wipe every vendor's coverage
            // on their next save, so leave it alone and let the empty dropdown
            // be the thing that is noticed.
            if (States::names() === []) {
                return;
            }

            $covered = collect($vendor->service_states ?? [])
                ->filter(fn (mixed $state): bool => States::has(is_string($state) ? $state : null));

            $vendor->service_states = $covered->prepend($vendor->state)->filter()->unique()->values()->all();
        });

        static::saved(function (self $vendor): void {
            if ($vendor->category_id && ($vendor->wasRecentlyCreated || $vendor->wasChanged('category_id'))) {
                $vendor->categories()->syncWithoutDetaching([$vendor->category_id]);
            }
        });
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class)->orderBy('sort_order')->orderBy('price');
    }

    public function portfolioItems(): HasMany
    {
        return $this->hasMany(PortfolioItem::class)->orderBy('sort_order');
    }

    public function unavailableDates(): HasMany
    {
        return $this->hasMany(VendorUnavailableDate::class);
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * The reviews rating_avg, the points and the tier are computed from: ones
     * backed by a booking this platform can prove, and still visible. Anyone
     * may now leave a review, so anything wider than this would let a stranger
     * with an email address move a vendor's ranking.
     */
    public function rankingReviews(): HasMany
    {
        return $this->reviews()->verified()->published();
    }

    public function violations(): HasMany
    {
        return $this->hasMany(VendorViolation::class);
    }

    public function points(): HasMany
    {
        return $this->hasMany(VendorPoint::class);
    }

    /**
     * The business logo, if the vendor uploaded one. Everywhere it is shown
     * falls back to the initial in a tinted circle, which is what a vendor
     * without a logo has always had.
     */
    public function logoUrl(): ?string
    {
        return StoreOptimizedImage::thumbnailUrl($this->logo);
    }

    /**
     * The vendor's own WhatsApp link, for a couple who would rather talk before
     * they book. Falls back to the phone number, which is what registration
     * seeds whatsapp with anyway.
     */
    public function whatsappUrl(?string $message = null): ?string
    {
        $number = PhoneNumber::normalise($this->whatsapp ?: $this->phone);

        if (! $number) {
            return null;
        }

        return 'https://wa.me/'.$number.($message ? '?text='.rawurlencode($message) : '');
    }

    /**
     * The vendor's social media and website, in the order the platforms are
     * listed. Each link is checked again on the way out, so a row written
     * before a rule tightened never reaches the public page.
     *
     * @return list<array{platform: string, label: string, url: string}>
     */
    public function socialLinks(): array
    {
        $links = [];

        foreach (SocialLinks::PLATFORMS as $platform => $details) {
            $url = $this->social_links[$platform] ?? null;

            if (is_string($url) && SocialLinks::isAllowed($platform, $url)) {
                $links[] = ['platform' => $platform, 'label' => $details['label'], 'url' => $url];
            }
        }

        return $links;
    }

    /**
     * A profile counts as complete once a couple has everything they need to judge it.
     */
    public function hasCompleteProfile(): bool
    {
        return filled($this->tagline)
            && filled($this->description)
            && filled($this->phone)
            && (float) $this->price_from > 0;
    }

    /**
     * A catalogue counts as complete with at least one active package and three portfolio images.
     */
    public function hasCompleteCatalogue(): bool
    {
        $packages = $this->active_packages_count ?? $this->packages()->where('is_active', true)->count();
        $portfolio = $this->portfolio_items_count ?? $this->portfolioItems()->count();

        return $packages > 0 && $portfolio >= 3;
    }

    /**
     * The same two tests as hasCompleteProfile() and hasCompleteCatalogue(),
     * in SQL, so a list of vendors can be counted and paged without loading
     * every one of them. VendorSetupScopeTest fails if the two drift apart.
     */
    #[Scope]
    protected function setupComplete(Builder $query): Builder
    {
        return $query
            ->whereNotNull('tagline')->where('tagline', '!=', '')
            ->whereNotNull('description')->where('description', '!=', '')
            ->whereNotNull('phone')->where('phone', '!=', '')
            ->where('price_from', '>', 0)
            ->whereHas('packages', fn (Builder $packages) => $packages->where('is_active', true))
            ->has('portfolioItems', '>=', 3);
    }

    /**
     * Free-text search over everything a couple would type: the business name,
     * its tagline and description, where it is, its category, and the names of
     * the packages it sells. Each word has to match somewhere, so the more a
     * couple types the narrower the list gets rather than the wider.
     */
    #[Scope]
    protected function matching(Builder $query, string $keyword): Builder
    {
        $words = collect(preg_split('/\s+/', trim($keyword)) ?: [])
            ->filter(fn (string $word): bool => mb_strlen($word) > 1)
            ->take(6);

        if ($words->isEmpty()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($words): void {
            foreach ($words as $word) {
                $like = '%'.$word.'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query->where('name', 'like', $like)
                        ->orWhere('tagline', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('city', 'like', $like)
                        ->orWhere('state', 'like', $like)
                        // The coverage list is a JSON array of negeri names,
                        // and LIKE over it is enough to find one by name.
                        ->orWhere('service_states', 'like', $like)
                        ->orWhereHas('categories', fn (Builder $categories) => $categories->where('name', 'like', $like))
                        ->orWhereHas('packages', fn (Builder $packages) => $packages
                            ->where('is_active', true)
                            ->where(fn (Builder $package) => $package->where('name', 'like', $like)->orWhere('description', 'like', $like)));
                });
            }
        });
    }

    #[Scope]
    protected function approved(Builder $query): Builder
    {
        return $query->where('status', VendorStatus::Approved);
    }

    /**
     * Vendors who work in this category, whether it is their primary one or
     * one they added.
     */
    #[Scope]
    protected function inCategory(Builder $query, Category $category): Builder
    {
        return $query->whereHas('categories', fn (Builder $categories) => $categories->whereKey($category->getKey()));
    }

    /**
     * Vendors who cover this negeri. A KL studio that travels to Johor belongs
     * in a Johor search, so this reads the coverage, not the home address.
     */
    #[Scope]
    protected function servingState(Builder $query, string $state): Builder
    {
        return $query->where(fn (Builder $query) => $query
            ->whereJsonContains('service_states', $state)
            ->orWhere(fn (Builder $query) => $query->whereNull('service_states')->where('state', $state)));
    }

    public function isApproved(): bool
    {
        return $this->status === VendorStatus::Approved;
    }

    public function isRecommended(): bool
    {
        return $this->tier === VendorTier::Recommended;
    }

    /**
     * Vendor Score using the kertas kerja weights: customer rating 30%, completed
     * bookings 20%, completion rate 15%, response rate 15%, platform transactions
     * 10%, profile and catalogue quality 10%. Penalty points subtract from the total.
     */
    /**
     * Below this many answerable enquiries, a response rate is noise.
     */
    public const MIN_ENQUIRIES_FOR_RESPONSE_RATE = 5;

    public function calculateScore(): float
    {
        $quality = ($this->hasCompleteProfile() ? 5 : 0) + ($this->hasCompleteCatalogue() ? 5 : 0);

        return max(0, round(
            ((float) $this->rating_avg / 5 * 30)
            + (min($this->completed_bookings_count, 50) / 50 * 20)
            + ($this->completion_rate / 100 * 15)
            + (($this->response_rate ?? 0) / 100 * 15)
            + (min($this->points_total, 2000) / 2000 * 10)
            + $quality
            - ($this->penalty_points / 10),
            2
        ));
    }

    /**
     * How the response rate reads to a human, including when we have not
     * measured one yet.
     */
    public function responseRateLabel(): string
    {
        return $this->response_rate === null ? 'Belum diukur' : $this->response_rate.'%';
    }

    /**
     * How many enquiries the vendor answered, over the enquiries old enough to
     * have been answered. Returns null below the threshold: with two or three
     * enquiries the figure swings between 0 and 100 and means nothing, and a
     * number shown to couples as a performance fact has to be measured.
     */
    public function calculateResponseRate(): ?int
    {
        $answerable = $this->enquiries()->where('created_at', '<=', now()->subDay());
        $total = $answerable->clone()->count();

        if ($total < self::MIN_ENQUIRIES_FOR_RESPONSE_RATE) {
            return null;
        }

        return (int) round($answerable->clone()->whereNotNull('replied_at')->count() / $total * 100);
    }

    /**
     * Share of non-cancelled bookings that reached completion, as a percentage.
     */
    public function calculateCompletionRate(): int
    {
        $settled = $this->bookings()
            ->whereIn('status', [BookingStatus::Completed, BookingStatus::Cancelled])
            ->count();

        if ($settled === 0) {
            return 100;
        }

        return (int) round($this->bookings()->where('status', BookingStatus::Completed)->count() / $settled * 100);
    }

    /**
     * Whether the vendor can take a booking on the given date.
     */
    public function isAvailableOn(\DateTimeInterface|string $date): bool
    {
        $date = Carbon::parse($date)->toDateString();

        if ($this->unavailableDates()->whereDate('date', $date)->exists()) {
            return false;
        }

        return ! $this->bookings()
            ->whereDate('event_date', $date)
            ->whereIn('status', [BookingStatus::PendingPayment, BookingStatus::Confirmed])
            ->exists();
    }
}
