<?php

namespace App\Enums;

/** What a couple sees for one day on a vendor's booking calendar. */
enum DayStatus: string
{
    case Open = 'open';
    case Full = 'full';
    case Closed = 'closed';
    case WeekdayOff = 'weekday_off';
    case TooSoon = 'too_soon';
    case TooFar = 'too_far';
    case Past = 'past';

    public function label(): string
    {
        return __('enums.day_status.'.$this->value);
    }

    public function isOpen(): bool
    {
        return $this === self::Open;
    }

    /** Why the day cannot be booked, as a validation message. */
    public function refusal(): string
    {
        return match ($this) {
            self::Full, self::Closed, self::Open => __('validation.custom.vendor_unavailable'),
            self::WeekdayOff => __('validation.custom.date_weekday_off'),
            self::TooSoon => __('validation.custom.date_too_soon'),
            self::TooFar => __('validation.custom.date_too_far'),
            self::Past => __('validation.custom.date_past'),
        };
    }
}
