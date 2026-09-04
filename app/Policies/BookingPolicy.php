<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $user->isAdmin()
            || $this->belongsToCustomer($user, $booking)
            || ($user->isVendor() && $user->vendor?->id === $booking->vendor_id);
    }

    /**
     * Either half of the couple may settle a payment on their shared wedding.
     */
    public function pay(User $user, Booking $booking): bool
    {
        return $this->belongsToCustomer($user, $booking);
    }

    /**
     * Only the couple reviews the vendor, and only on a booking that is theirs.
     */
    public function review(User $user, Booking $booking): bool
    {
        return $this->belongsToCustomer($user, $booking);
    }

    private function belongsToCustomer(User $user, Booking $booking): bool
    {
        if ($booking->user_id === $user->id) {
            return true;
        }

        return $booking->wedding_id !== null
            && $user->weddings()->whereKey($booking->wedding_id)->exists();
    }
}
