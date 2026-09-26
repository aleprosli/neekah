<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One exchange with a payment gateway, or one thing an admin did by hand,
 * kept exactly as it happened. Only ever added, never changed.
 */
#[Fillable(['payment_id', 'gateway', 'type', 'verified', 'outcome', 'http_status', 'payload', 'meta', 'user_id'])]
class PaymentEvent extends Model
{
    public const LINK_CREATED = 'link_created';

    public const LINK_FAILED = 'link_failed';

    public const CALLBACK = 'callback';

    public const RETURN = 'return';

    public const REQUERY = 'requery';

    public const MANUAL_RECORDED = 'manual_recorded';

    public const MANUAL_VERIFIED = 'manual_verified';

    public const MANUAL_REJECTED = 'manual_rejected';

    public const INVOICE_ATTACHED = 'invoice_attached';

    public const UPDATED_AT = null;

    /** The most of a gateway's payload kept in one event. */
    public const MAX_PAYLOAD_BYTES = 20_000;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verified' => 'boolean',
            'payload' => 'array',
            'meta' => 'array',
            'http_status' => 'integer',
        ];
    }

    /**
     * Keep one exchange. `payload` is what the gateway sent or answered,
     * exactly; `meta` is ours (who asked, from where, what went wrong).
     *
     * @param  array<string, mixed>|null  $payload
     * @param  array<string, mixed>  $meta
     */
    public static function record(?Payment $payment, string $gateway, string $type, ?array $payload = null, array $meta = [], ?bool $verified = null, ?string $outcome = null, ?int $httpStatus = null): self
    {
        // Anyone can post to a webhook; keep what they sent, but not without limit.
        if ($payload !== null && strlen((string) json_encode($payload)) > self::MAX_PAYLOAD_BYTES) {
            $payload = ['truncated' => true, 'keys' => array_slice(array_keys($payload), 0, 50)];
        }

        return self::query()->create([
            'payment_id' => $payment?->id,
            'gateway' => $gateway,
            'type' => $type,
            'verified' => $verified,
            'outcome' => $outcome,
            'http_status' => $httpStatus,
            'payload' => $payload,
            'meta' => array_filter($meta, fn (mixed $value): bool => $value !== null && $value !== '') ?: null,
            'user_id' => auth()->id(),
        ]);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
