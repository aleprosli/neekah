<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wedding;

class WeddingPolicy
{
    public function view(User $user, Wedding $wedding): bool
    {
        return $user->isAdmin() || $wedding->user_id === $user->id;
    }

    public function update(User $user, Wedding $wedding): bool
    {
        return $wedding->user_id === $user->id;
    }
}
