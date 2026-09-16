<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';

    /** Recorded by the couple, waiting for the vendor to check their account. */
    case AwaitingVerification = 'awaiting_verification';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Belum dibayar',
            self::AwaitingVerification => 'Menunggu pengesahan',
            self::Paid => 'Dibayar',
            self::Failed => 'Gagal',
            self::Refunded => 'Dipulangkan',
        };
    }

    /** The palette key the badge components use, in Blade and in Vue. */
    public function tone(): string
    {
        return match ($this) {
            self::Paid => 'emerald',
            self::AwaitingVerification => 'amber',
            self::Pending => 'amber',
            self::Failed, self::Refunded => 'muted',
        };
    }
}
