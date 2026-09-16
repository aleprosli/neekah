<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable([
    'reference', 'user_id', 'vendor_id', 'wedding_id', 'package_id', 'package_name', 'event_date',
    'total_amount', 'commission_rate', 'commission_amount', 'status', 'notes',
    'confirmed_at', 'completed_at', 'cancelled_at',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    public const COMMISSION_RATE = 8.0;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'total_amount' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'status' => BookingStatus::class,
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'NK-'.Str::upper(Str::random(6));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Bookings the customer made themselves, plus every booking on a wedding they share.
     */
    #[Scope]
    protected function forCustomer(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user): void {
            $query->where('user_id', $user->id)
                ->orWhereIn('wedding_id', $user->weddings()->select('weddings.id'));
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /** What the vendor has confirmed receiving, not what was merely claimed. */
    public function paidAmount(): float
    {
        return (float) $this->payments->where('status', PaymentStatus::Paid)->sum('amount');
    }

    public function outstandingAmount(): float
    {
        return max(round((float) $this->total_amount - $this->paidAmount(), 2), 0);
    }

    public function isFullyPaid(): bool
    {
        return $this->paidAmount() >= (float) $this->total_amount;
    }

    /**
     * A booking the couple may still call off themselves.
     *
     * Once the vendor has confirmed money arrived, cancelling is no longer a
     * correction of a mistake — it is a refund, which the two sides settle
     * between themselves.
     */
    public function canBeCancelled(): bool
    {
        return $this->status->isActive() && ! $this->payments->contains(fn (Payment $payment): bool => $payment->isPaid());
    }

    public function canBeReviewed(): bool
    {
        return $this->status === BookingStatus::Completed && $this->review === null;
    }
}
