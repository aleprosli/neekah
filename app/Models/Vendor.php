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
    'response_rate', 'score', 'penalty_points', 'violations_count', 'approved_at',
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
     * Recommended Vendor score using the kertas kerja weights.
     * Platform-transaction and profile-quality components are approximated by tier until Phase 2.
     */
    public function calculateScore(): float
    {
        return max(0, round(
            ((float) $this->rating_avg / 5 * 30)
            + (min($this->completed_bookings_count, 300) / 300 * 20)
            + ($this->response_rate / 100 * 15)
            + ($this->tier->rank() / 4 * 35)
            - ($this->penalty_points / 10),
            2
        ));
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
