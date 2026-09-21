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
            self::Pending => __('enums.payment_status.pending'),
            self::AwaitingVerification => __('enums.payment_status.awaiting_verification'),
            self::Paid => __('enums.payment_status.paid'),
            self::Failed => __('enums.payment_status.failed'),
            self::Refunded => __('enums.payment_status.refunded'),
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
