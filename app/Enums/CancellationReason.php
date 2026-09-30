<?php

namespace App\Enums;

/** Who or what ended a booking. */
enum CancellationReason: string
{
    case Couple = 'couple';
    case Vendor = 'vendor';

    /** The deposit was not paid before the hold ran out. */
    case Expired = 'expired';

    public function label(): string
    {
        return __('enums.cancellation_reason.'.$this->value);
    }
}
