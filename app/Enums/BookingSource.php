<?php

namespace App\Enums;

/** How a booking came in. */
enum BookingSource: string
{
    /** Recorded by the vendor for a customer they dealt with. */
    case Vendor = 'vendor';

    /** Made by the couple on the vendor's page, with a deposit. */
    case Online = 'online';

    public function label(): string
    {
        return __('enums.booking_source.'.$this->value);
    }
}
