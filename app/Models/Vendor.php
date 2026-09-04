<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PriceUnit;
use App\Enums\VendorStatus;
use App\Enums\VendorTier;
use Database\Factories\VendorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'user_id', 'category_id', 'name', 'slug', 'tagline', 'description', 'city', 'state',
    'phone', 'whatsapp', 'price_from', 'price_unit', 'cover_image', 'cover_tone',
    'status', 'tier', 'rating_avg', 'reviews_count', 'completed_bookings_count',
    'response_rate', 'completion_rate', 'score', 'points_total', 'tier_locked', 'penalty_points', 'violations_count', 'approved_at',
])]
class Vendor extends Model
{
    /** @use HasFactory<VendorFactory> */
    use HasFactory;

    public const STATES = [
        'Kedah', 'Pulau Pinang', 'Perak', 'Selangor', 'Kuala Lumpur', 'Negeri Sembilan',
        'Melaka', 'Johor', 'Pahang', 'Terengganu', 'Kelantan', 'Sabah', 'Sarawak', 'Perlis', 'Putrajaya', 'Labuan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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

    public function violations(): HasMany
    {
        return $this->hasMany(VendorViolation::class);
    }

    public function points(): HasMany
    {
        return $this->hasMany(VendorPoint::class);
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
        return $this->packages()->where('is_active', true)->exists()
            && $this->portfolioItems()->count() >= 3;
    }

    #[Scope]
    protected function approved(Builder $query): Builder
    {
        return $query->where('status', VendorStatus::Approved);
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
    public function calculateScore(): float
    {
        $quality = ($this->hasCompleteProfile() ? 5 : 0) + ($this->hasCompleteCatalogue() ? 5 : 0);

        return max(0, round(
            ((float) $this->rating_avg / 5 * 30)
            + (min($this->completed_bookings_count, 50) / 50 * 20)
            + ($this->completion_rate / 100 * 15)
            + ($this->response_rate / 100 * 15)
            + (min($this->points_total, 2000) / 2000 * 10)
            + $quality
            - ($this->penalty_points / 10),
            2
        ));
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
