<?php

namespace App\Actions;

use App\Models\Vendor;
use App\Support\VendorAvailability;
use Illuminate\Validation\ValidationException;

/**
 * Turning online booking on or off. It cannot go on before there is somewhere
 * for the deposit to go: the vendor's own Herepay or their bank details.
 */
class SwitchOnlineBooking
{
    /**
     * @throws ValidationException
     */
    public function handle(Vendor $vendor, bool $enabled): void
    {
        if ($enabled && VendorAvailability::for($vendor)->paymentChannel() === null) {
            throw ValidationException::withMessages(['enabled' => __('flash.vendor.online_needs_payment')]);
        }

        $vendor->bookingSettings()->updateOrCreate([], ['enabled' => $enabled]);
    }
}
