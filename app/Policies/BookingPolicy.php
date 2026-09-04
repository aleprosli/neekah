<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $user->isAdmin()
            || $booking->user_id === $user->id
            || ($user->isVendor() && $user->vendor?->id === $booking->vendor_id);
    }

    /**
     * Only the customer who owns the booking pays for it.
     */
    public function pay(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id;
    }
}
