<?php

namespace App\Policies;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\User;

/**
 * A contract is its vendor's alone. The client never signs in: they reach it
 * by its token on the public page, which no policy guards.
 */
class ContractPolicy
{
    public function view(User $user, Contract $contract): bool
    {
        return $this->owns($user, $contract);
    }

    /** Only a draft: a sent contract is what the client is reading. */
    public function update(User $user, Contract $contract): bool
    {
        return $this->owns($user, $contract) && $contract->status === ContractStatus::Draft;
    }

    /** A draft to open, or a sent one to email again. */
    public function send(User $user, Contract $contract): bool
    {
        return $this->owns($user, $contract) && in_array($contract->status, [ContractStatus::Draft, ContractStatus::Sent], true);
    }

    public function void(User $user, Contract $contract): bool
    {
        return $this->owns($user, $contract) && $contract->status === ContractStatus::Sent;
    }

    public function delete(User $user, Contract $contract): bool
    {
        return $this->owns($user, $contract) && $contract->status === ContractStatus::Draft;
    }

    private function owns(User $user, Contract $contract): bool
    {
        return $user->isVendor() && $user->vendor?->id === $contract->vendor_id;
    }
}
