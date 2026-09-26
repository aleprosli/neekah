<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Enums\VendorPlan;
use Database\Factories\VendorSubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One Pro purchase: a checkout started, or a payment an admin recorded by hand.
 * Only a paid one moves vendors.pro_until, and ActivateVendorPro is the only
 * thing that marks one paid.
 */
#[Fillable([
    'vendor_id', 'reference', 'plan', 'amount', 'status', 'gateway', 'gateway_reference', 'payment_url',
    'added_by', 'note', 'paid_at', 'starts_at', 'ends_at',
])]
class VendorSubscription extends Model
{
    /** @use HasFactory<VendorSubscriptionFactory> */
    use HasFactory;

    public const GATEWAY_HEREPAY = 'herepay';

    public const GATEWAY_MANUAL = 'manual';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'plan' => VendorPlan::class,
            'status' => SubscriptionStatus::class,
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function isPaid(): bool
    {
        return $this->status === SubscriptionStatus::Paid;
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'PRO-'.Str::upper(Str::random(8));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }
}
