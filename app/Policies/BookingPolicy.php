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
     * Either half of the couple may record a payment on their shared wedding.
     */
    public function recordPayment(User $user, Booking $booking): bool
    {
        return $this->belongsToCustomer($user, $booking) && $booking->status->isActive();
    }

    /**
     * Calling off a booking is the couple's to do, and only while it is still a
     * correction rather than a refund.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        return $this->belongsToCustomer($user, $booking) && $booking->canBeCancelled();
    }

    /** Only the vendor can see their own account, so only they may verify. */
    public function verifyPayment(User $user, Booking $booking): bool
    {
        return $user->isVendor() && $user->vendor?->id === $booking->vendor_id;
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
