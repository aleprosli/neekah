<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable([
    'reference', 'user_id', 'vendor_id', 'wedding_id', 'package_id', 'package_name', 'event_date',
    'total_amount', 'deposit_amount', 'commission_rate', 'commission_amount', 'status', 'notes',
    'confirmed_at', 'completed_at', 'cancelled_at',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    public const DEPOSIT_RATE = 0.4;

    public const COMMISSION_RATE = 8.0;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'total_amount' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
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

    public function depositPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->where('type', PaymentType::Deposit);
    }

    public function balancePayment(): HasOne
    {
        return $this->hasOne(Payment::class)->where('type', PaymentType::Balance);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function balanceAmount(): float
    {
        return round((float) $this->total_amount - (float) $this->deposit_amount, 2);
    }

    public function paidAmount(): float
    {
        return (float) $this->payments->where('status', PaymentStatus::Paid)->sum('amount');
    }

    public function isFullyPaid(): bool
    {
        return $this->paidAmount() >= (float) $this->total_amount;
    }

    public function canBeReviewed(): bool
    {
        return $this->status === BookingStatus::Completed && $this->review === null;
    }
}
