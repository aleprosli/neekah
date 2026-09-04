<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wedding;

class WeddingPolicy
{
    public function view(User $user, Wedding $wedding): bool
    {
        return $user->isAdmin() || $wedding->hasMember($user);
    }

    /**
     * Both halves of the couple may edit the shared project.
     */
    public function update(User $user, Wedding $wedding): bool
    {
        return $wedding->hasMember($user);
    }

    /**
     * Only the creator invites or removes a partner.
     */
    public function manageMembers(User $user, Wedding $wedding): bool
    {
        return $wedding->isOwnedBy($user);
    }
}
