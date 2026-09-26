<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Vendor;
use App\Support\VendorAvailability;

/**
 * Whether couples can book this vendor online right now, and why not.
 */
class OnlineBooking
{
    /**
     * @return array{enabled: bool, open: bool, label: string, can_enable: bool}
     */
    public static function of(Vendor $vendor): array
    {
        $availability = VendorAvailability::for($vendor);
        $state = $availability->onlineState();

        return [
            'enabled' => (bool) $availability->settings()->enabled,
            'open' => $state->isOpen(),
            'label' => $state->label(),
            'can_enable' => $availability->paymentChannel() !== null,
        ];
    }
}
