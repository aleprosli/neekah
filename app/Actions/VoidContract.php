<?php

namespace App\Actions;

use App\Enums\ContractStatus;
use App\Models\Contract;
use Illuminate\Validation\ValidationException;

/**
 * Withdraw a sent contract the client has not signed, so the link can no
 * longer be signed. A signed contract is never voided here: it is a record
 * of what both sides agreed.
 */
class VoidContract
{
    public function handle(Contract $contract, ?string $reason): Contract
    {
        if ($contract->status !== ContractStatus::Sent) {
            throw ValidationException::withMessages(['reason' => __('validation.custom.contract_not_voidable')]);
        }

        $contract->update([
            'status' => ContractStatus::Void,
            'voided_at' => now(),
            'void_reason' => $reason,
        ]);

        return $contract;
    }
}
