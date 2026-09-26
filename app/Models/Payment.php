<?php

namespace App\Models;

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * One payment in Neekah's single ledger, whatever it is for (purpose), whose
 * account took it (merchant) and through which gateway. A booking payment,
 * Neekah Pro, a boost pack and Neekah Kenangan are all rows here; what each
 * paid for is linked by its own foreign key, and anything particular to the
 * purpose sits in `details`. Every exchange with the gateway is kept in
 * payment_events.
 */
#[Fillable([
    'reference', 'purpose', 'merchant', 'booking_id', 'vendor_id', 'wedding_id', 'camera_album_id', 'recorded_by',
    'amount', 'currency', 'paid_on', 'method', 'receipt_image', 'note', 'details', 'status',
    'gateway', 'gateway_reference', 'gateway_invoice', 'gateway_transaction_id', 'gateway_status',
    'paid_at', 'verified_at', 'verified_by', 'payment_url', 'expires_at', 'last_checked_at',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    public const GATEWAY_HEREPAY = 'herepay';

    public const GATEWAY_MANUAL = 'manual';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purpose' => PaymentPurpose::class,
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'details' => 'array',
            'paid_at' => 'datetime',
            'paid_on' => 'date',
            'verified_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_checked_at' => 'datetime',
        ];
    }

    /**
     * A booking payment belongs to the booking's vendor, and the money goes to
     * their account; filling that in here keeps every caller from forgetting.
     */
    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            $payment->purpose ??= PaymentPurpose::Booking;
            $payment->merchant ??= $payment->purpose->merchant();
            $payment->reference ??= self::generateReference($payment->purpose);

            if ($payment->booking_id && ! $payment->vendor_id) {
                $payment->vendor_id = Booking::query()->whereKey($payment->booking_id)->value('vendor_id');
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public static function generateReference(PaymentPurpose $purpose = PaymentPurpose::Booking): string
    {
        do {
            $reference = $purpose->referencePrefix().'-'.Str::upper(Str::random(8));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(CameraAlbum::class, 'camera_album_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** What passed between Neekah and the gateway for this payment, oldest first. */
    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class)->oldest('id');
    }

    /**
     * @param  Builder<Payment>  $query
     */
    public function scopeFor(Builder $query, PaymentPurpose $purpose): void
    {
        $query->where('purpose', $purpose);
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    public function isAwaitingVerification(): bool
    {
        return $this->status === PaymentStatus::AwaitingVerification;
    }

    /** Taken through a gateway, so there is something to ask it about. */
    public function isOnline(): bool
    {
        return $this->gateway !== self::GATEWAY_MANUAL;
    }

    /** One fact from `details`. */
    public function detail(string $key, mixed $default = null): mixed
    {
        return data_get($this->details, $key, $default);
    }

    /** The receipt the couple uploaded, if they attached one. */
    public function receiptUrl(): ?string
    {
        return $this->receipt_image ? Storage::disk('public')->url($this->receipt_image) : null;
    }
}
