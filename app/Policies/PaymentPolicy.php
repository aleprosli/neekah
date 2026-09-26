<?php

namespace App\Policies;

use App\Enums\PaymentPurpose;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Its receipt is for whoever paid and whoever it concerns: the vendor who
     * bought Pro or boost, the couple behind a Kenangan album, both sides of a
     * booking, and the admin.
     */
    public function view(User $user, Payment $payment): bool
    {
        if ($user->isAdmin() || $payment->recorded_by === $user->id) {
            return true;
        }

        return match ($payment->purpose) {
            PaymentPurpose::VendorPro, PaymentPurpose::BoostTokens => $user->isVendor() && $user->vendor?->id === $payment->vendor_id,
            PaymentPurpose::Kenangan => $payment->wedding?->hasMember($user) ?? false,
            PaymentPurpose::Booking => $payment->booking !== null && $user->can('view', $payment->booking),
        };
    }
}
