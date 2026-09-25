<?php

namespace App\Models;

use App\Enums\CameraTier;
use App\Enums\SubscriptionStatus;
use Database\Factories\CameraPurchaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One Kamera Majlis purchase: a checkout started, or a payment an admin
 * recorded by hand. Only ActivateCameraAlbum marks one paid.
 */
#[Fillable([
    'wedding_id', 'user_id', 'reference', 'tier', 'kind', 'amount', 'status', 'gateway',
    'gateway_reference', 'payment_url', 'added_by', 'note', 'paid_at',
])]
class CameraPurchase extends Model
{
    /** @use HasFactory<CameraPurchaseFactory> */
    use HasFactory;

    public const GATEWAY_HEREPAY = 'herepay';

    public const GATEWAY_MANUAL = 'manual';

    public const KIND_NEW = 'new';

    public const KIND_UPGRADE = 'upgrade';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tier' => CameraTier::class,
            'status' => SubscriptionStatus::class,
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
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
            $reference = 'CAM-'.Str::upper(Str::random(8));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }
}
