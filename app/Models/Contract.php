<?php

namespace App\Models;

use App\Enums\ContractStatus;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A Pro vendor's contract with a client: scope of work, cancellation and
 * payment, overtime and delivery, copyright and terms, signed online.
 *
 * The client needs no account; the random token is the key. Once sent the
 * text never changes, and signing keeps a sha256 of exactly what was shown
 * (contentHash), so either side can show later what was agreed.
 */
#[Fillable([
    'vendor_id', 'quotation_id', 'booking_id', 'number', 'token', 'status',
    'client_name', 'client_phone', 'client_email', 'event_date', 'sections', 'vendor_signatory',
    'sent_at', 'viewed_at', 'signed_at', 'signer_name', 'signer_ip', 'signer_user_agent',
    'signature_path', 'content_hash', 'voided_at', 'void_reason',
])]
class Contract extends Model
{
    /** @use HasFactory<ContractFactory> */
    use HasFactory;

    /** The sections every new contract starts with, in order. */
    public const SECTION_KEYS = ['scope', 'cancellation_payment', 'overtime_delivery', 'copyright', 'terms'];

    public const MAX_SECTIONS = 15;

    /** Signatures live here, never on the public media disk. */
    public const SIGNATURE_DISK = 'local';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContractStatus::class,
            'sections' => 'array',
            'event_date' => 'date',
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'signed_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    /** Twelve lowercase letters and digits, without the ones that read alike. */
    public static function freshToken(): string
    {
        do {
            $token = Str::lower(Str::password(12, letters: true, numbers: true, symbols: false, spaces: false));
            $token = str_replace(['0', 'o', '1', 'l', 'i'], ['2', '3', '4', '5', '6'], $token);
        } while (static::query()->where('token', $token)->exists());

        return $token;
    }

    /**
     * The five standard sections, titled in the current language and holding
     * the text the vendor saved as their default, if any.
     *
     * @return list<array{key: string, title: string, body: string}>
     */
    public static function defaultSections(VendorBookingSetting $settings): array
    {
        $saved = $settings->contract_defaults ?? [];

        return array_map(fn (string $key): array => [
            'key' => $key,
            'title' => __('pages.contracts.sections.'.$key),
            'body' => (string) ($saved[$key] ?? ''),
        ], self::SECTION_KEYS);
    }

    /**
     * The token, in the vendor's own pages as on the public one: a running id
     * in the address would tell anyone how many there are and invite guessing.
     */
    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** The page the client reads and signs, with no account. */
    public function publicUrl(): string
    {
        return route('contracts.public.show', $this->token);
    }

    public function signatureUrl(): ?string
    {
        return $this->signature_path ? route('contracts.public.signature', $this->token) : null;
    }

    public function awaitsSignature(): bool
    {
        return $this->status === ContractStatus::Sent;
    }

    /**
     * Exactly what the client is agreeing to, in a fixed order: who, the
     * text, and the quotation's lines and sums when one is attached.
     *
     * @return array<string, mixed>
     */
    public function canonicalContent(): array
    {
        $quotation = $this->quotation;

        return [
            'number' => $this->number,
            'vendor' => ['id' => $this->vendor_id, 'name' => $this->vendor->name, 'signatory' => $this->vendor_signatory],
            'client' => ['name' => $this->client_name, 'phone' => $this->client_phone, 'email' => $this->client_email],
            'event_date' => $this->event_date?->toDateString(),
            'sections' => array_map(fn (array $section): array => ['title' => $section['title'], 'body' => $section['body']], $this->sections ?? []),
            'quotation' => $quotation ? [
                'number' => $quotation->number,
                'items' => $quotation->items->map(fn (QuotationItem $item): array => [
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'unit_price' => (string) $item->unit_price,
                    'line_total' => (string) $item->line_total,
                ])->all(),
                'total' => (string) $quotation->total,
                'deposit' => (string) $quotation->deposit_amount,
            ] : null,
        ];
    }

    public function computeHash(): string
    {
        return hash('sha256', (string) json_encode($this->canonicalContent(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
