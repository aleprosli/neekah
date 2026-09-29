<?php

namespace App\Enums;

/**
 * Whether a vendor takes online bookings right now, and if not, the first
 * reason why, in the order a vendor would have to fix them.
 */
enum OnlineBookingState: string
{
    case Open = 'open';
    case GloballyOff = 'globally_off';
    case NotApproved = 'not_approved';
    case FeatureOff = 'feature_off';
    case SwitchedOff = 'switched_off';
    case NoPackages = 'no_packages';
    case NoPaymentPath = 'no_payment_path';
    case CalendarStale = 'calendar_stale';

    public function label(): string
    {
        return __('enums.online_booking_state.'.$this->value);
    }

    public function isOpen(): bool
    {
        return $this === self::Open;
    }
}
