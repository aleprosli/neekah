<?php

namespace App\Enums;

/**
 * What the vendor says has been paid on an invoice. A label they set by hand,
 * not a ledger: the money moves between the client and the vendor, and a
 * booking's payments stay the only record Neekah verifies.
 */
enum InvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case DepositPaid = 'deposit_paid';
    case Paid = 'paid';

    public function label(): string
    {
        return __('enums.invoice_status.'.$this->value);
    }

    /** The palette key the badge components use, in Blade and in Vue. */
    public function tone(): string
    {
        return match ($this) {
            self::Unpaid => 'amber',
            self::DepositPaid => 'sky',
            self::Paid => 'emerald',
        };
    }
}
