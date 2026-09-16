<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'reference', 'booking_id', 'recorded_by', 'amount', 'paid_on', 'method',
    'receipt_image', 'note', 'status', 'gateway', 'gateway_reference', 'paid_at',
    'verified_at', 'verified_by',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'paid_on' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'PAY-'.Str::upper(Str::random(8));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }

    public function isAwaitingVerification(): bool
    {
        return $this->status === PaymentStatus::AwaitingVerification;
    }

    /** The receipt the couple uploaded, if they attached one. */
    public function receiptUrl(): ?string
    {
        return $this->receipt_image ? Storage::disk('public')->url($this->receipt_image) : null;
    }
}
