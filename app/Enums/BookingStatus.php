<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PendingPayment = 'pending_payment';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Pending Payment',
            self::Confirmed => 'Confirmed',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /** The palette key the badge components use, in Blade and in Vue. */
    public function tone(): string
    {
        return match ($this) {
            self::PendingPayment => 'amber',
            self::Confirmed => 'emerald',
            self::Completed => 'sky',
            self::Cancelled => 'muted',
        };
    }

    public function isActive(): bool
    {
        return $this === self::PendingPayment || $this === self::Confirmed;
    }
}
