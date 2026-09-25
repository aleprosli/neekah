<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Database\Factories\BoostPurchaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A pack of boost tokens a vendor started paying for. Only
 * ActivateBoostPurchase marks one paid.
 */
#[Fillable([
    'vendor_id', 'user_id', 'reference', 'pack', 'tokens', 'amount', 'status', 'gateway',
    'gateway_reference', 'payment_url', 'paid_at',
])]
class BoostPurchase extends Model
{
    /** @use HasFactory<BoostPurchaseFactory> */
    use HasFactory;

    public const GATEWAY_HEREPAY = 'herepay';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tokens' => 'integer',
            'status' => SubscriptionStatus::class,
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPaid(): bool
    {
        return $this->status === SubscriptionStatus::Paid;
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'BST-'.Str::upper(Str::random(8));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }
}
