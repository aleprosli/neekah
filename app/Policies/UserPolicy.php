<?php

namespace App\Policies;

use App\Models\User;

/**
 * What an admin may do to someone else's account. Anything that would lose a
 * booking or payment record, or another person's wedding, is refused: those
 * accounts are deactivated instead.
 */
class UserPolicy
{
    public function switchToVendor(User $admin, User $user): bool
    {
        return $this->managesOther($admin, $user)
            && $user->isCustomer()
            && ! $user->bookings()->exists();
    }

    public function switchToCouple(User $admin, User $user): bool
    {
        return $this->managesOther($admin, $user)
            && $user->isVendor()
            && ! $user->vendor?->bookings()->exists()
            && ! $user->vendor?->enquiries()->exists()
            && ! $user->vendor?->reviews()->exists();
    }

    public function deactivate(User $admin, User $user): bool
    {
        return $this->managesOther($admin, $user) && ! $user->isDeactivated();
    }

    public function reactivate(User $admin, User $user): bool
    {
        return $this->managesOther($admin, $user) && $user->isDeactivated();
    }

    public function delete(User $admin, User $user): bool
    {
        return $this->managesOther($admin, $user)
            && ! $user->bookings()->exists()
            && ! $user->vendor?->bookings()->exists()
            && ! $user->createdWeddings()->whereHas('members', fn ($query) => $query->whereKeyNot($user->id))->exists();
    }

    /**
     * Admin accounts are never managed from here, so one admin cannot lock out another.
     */
    private function managesOther(User $admin, User $user): bool
    {
        return $admin->isAdmin() && ! $user->isAdmin();
    }
}
