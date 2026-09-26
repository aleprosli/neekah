<?php

namespace App\Enums;

/**
 * How the couple pays the deposit on an online booking. Either way the money
 * goes to the vendor, never to Neekah.
 */
enum DepositChannel: string
{
    /** A payment link on the vendor's own Herepay account; confirmed by its callback. */
    case Herepay = 'herepay';

    /** A transfer to the vendor's bank account; confirmed when the vendor verifies the receipt. */
    case Manual = 'manual';

    public function label(): string
    {
        return __('enums.deposit_channel.'.$this->value);
    }
}
