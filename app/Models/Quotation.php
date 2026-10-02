<?php

namespace App\Models;

use App\Enums\DepositType;
use App\Enums\InvoiceStatus;
use App\Enums\QuotationStatus;
use Database\Factories\QuotationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A Pro vendor's quotation, and once accepted, the invoice issued from it.
 *
 * The client needs no account: the unguessable token is the key to the
 * public page, never the running number, which anyone could count through.
 * Prices are a snapshot taken on save (quotation_items), so a later package
 * edit never rewrites what the client was quoted.
 */
#[Fillable([
    'vendor_id', 'enquiry_id', 'booking_id', 'number', 'token', 'status',
    'client_name', 'client_phone', 'client_email', 'event_date', 'event_location', 'valid_until',
    'discount_type', 'discount_value', 'deposit_type', 'deposit_value',
    'subtotal', 'discount_amount', 'total', 'deposit_amount', 'terms', 'notes',
    'sent_at', 'viewed_at', 'accepted_at', 'accepted_name', 'accepted_ip', 'declined_at', 'decline_reason',
    'invoice_number', 'invoiced_at', 'invoice_status',
])]
class Quotation extends Model
{
    /** @use HasFactory<QuotationFactory> */
    use HasFactory;

    /** How long a new quotation stays open unless the vendor says otherwise. */
    public const DEFAULT_VALID_DAYS = 14;

    public const MAX_ITEMS = 30;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => QuotationStatus::class,
            'invoice_status' => InvoiceStatus::class,
            'discount_type' => DepositType::class,
            'deposit_type' => DepositType::class,
            'event_date' => 'date',
            'valid_until' => 'date',
            'discount_value' => 'decimal:2',
            'deposit_value' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
            'invoiced_at' => 'datetime',
        ];
    }

    /**
     * Twelve lowercase letters and digits, without the ones that read alike
     * (0/o, 1/l/i), because a client may type it from a printed page.
     */
    public static function freshToken(): string
    {
        do {
            $token = Str::lower(Str::password(12, letters: true, numbers: true, symbols: false, spaces: false));
            $token = str_replace(['0', 'o', '1', 'l', 'i'], ['2', '3', '4', '5', '6'], $token);
        } while (static::query()->where('token', $token)->exists());

        return $token;
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /** The page the client opens, with no account. */
    public function publicUrl(): string
    {
        return route('quotations.public.show', $this->token);
    }

    /** Sent, and its last valid day is behind us. */
    public function isExpired(): bool
    {
        return $this->status === QuotationStatus::Sent && $this->valid_until->lt(today());
    }

    /** Whether the client can still accept or decline it. */
    public function awaitsClient(): bool
    {
        return $this->status === QuotationStatus::Sent && ! $this->isExpired();
    }

    public function isInvoiced(): bool
    {
        return $this->invoice_number !== null;
    }

    public function hasDeposit(): bool
    {
        return (float) $this->deposit_amount > 0;
    }

    /** What remains after the deposit. */
    public function balanceAmount(): float
    {
        return round((float) $this->total - (float) $this->deposit_amount, 2);
    }

    /**
     * The only place a quotation's sums are worked out: subtotal from the
     * lines, then the discount (never more than the subtotal), then the
     * deposit (never more than the total). Call after the items are saved.
     */
    public function recalculate(): void
    {
        $subtotal = round((float) $this->items()->sum('line_total'), 2);
        $discountValue = max(0, (float) $this->discount_value);

        $discount = match ($this->discount_type ?? DepositType::Fixed) {
            DepositType::Percent => $subtotal * min(100, $discountValue) / 100,
            DepositType::Fixed => min($discountValue, $subtotal),
        };
        $total = round($subtotal - round($discount, 2), 2);

        $depositValue = max(0, (float) $this->deposit_value);
        $deposit = match ($this->deposit_type ?? DepositType::Percent) {
            DepositType::Percent => $total * min(100, $depositValue) / 100,
            DepositType::Fixed => min($depositValue, $total),
        };

        $this->forceFill([
            'subtotal' => $subtotal,
            'discount_amount' => round($discount, 2),
            'total' => $total,
            'deposit_amount' => round($deposit, 2),
        ])->save();
    }

    /** Ringgit, the way every document prints it. */
    public static function money(float|string|null $amount): string
    {
        return 'RM'.number_format((float) $amount, 2);
    }
}
