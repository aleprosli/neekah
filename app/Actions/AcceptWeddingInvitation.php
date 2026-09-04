<?php

namespace App\Actions;

use App\Enums\WeddingRole;
use App\Models\User;
use App\Models\WeddingInvitation;

class AcceptWeddingInvitation
{
    public const SESSION_KEY = 'wedding_invitation';

    /**
     * Join a user to a wedding. Returns null when the invitation can no longer be used.
     */
    public function handle(WeddingInvitation $invitation, User $user): ?WeddingInvitation
    {
        $wedding = $invitation->wedding;

        if (! $invitation->isPending() || $wedding->isFull() || ! $user->isCustomer()) {
            return null;
        }

        $wedding->addMember($user, WeddingRole::Partner);

        $invitation->update([
            'accepted_at' => now(),
            'accepted_by' => $user->id,
        ]);

        return $invitation;
    }

    /**
     * Consume a token stashed in the session when the invitee signed up or logged in.
     */
    public function fromSession(User $user): ?WeddingInvitation
    {
        $token = session()->pull(self::SESSION_KEY);

        if (! $token) {
            return null;
        }

        $invitation = WeddingInvitation::with('wedding')->where('token', $token)->first();

        if (! $invitation || $invitation->wedding->hasMember($user)) {
            return null;
        }

        return $this->handle($invitation, $user);
    }
}
