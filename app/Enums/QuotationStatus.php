<?php

namespace App\Enums;

/**
 * Where a quotation stands with the client. "Expired" is not stored: a sent
 * quotation past its valid_until reads as expired (Quotation::isExpired),
 * so nothing has to run at midnight to say so.
 */
enum QuotationStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Declined = 'declined';

    public function label(): string
    {
        return __('enums.quotation_status.'.$this->value);
    }

    /** The palette key the badge components use, in Blade and in Vue. */
    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'muted',
            self::Sent => 'sky',
            self::Accepted => 'emerald',
            self::Declined => 'amber',
        };
    }

    /** Whether the vendor may still change what it says. */
    public function isEditable(): bool
    {
        return $this === self::Draft || $this === self::Sent;
    }
}
