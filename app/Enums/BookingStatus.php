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
            self::PendingPayment => __('enums.booking_status.pending_payment'),
            self::Confirmed => __('enums.booking_status.confirmed'),
            self::Completed => __('enums.booking_status.completed'),
            self::Cancelled => __('enums.booking_status.cancelled'),
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
