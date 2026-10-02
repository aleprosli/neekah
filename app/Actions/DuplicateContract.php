<?php

namespace App\Actions;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Support\Quotations\DocumentNumber;
use Illuminate\Support\Facades\DB;

/**
 * A fresh draft from an existing contract, under a new number and link: how
 * a sent contract gets changed (void the old one, send the copy).
 */
class DuplicateContract
{
    public function handle(Contract $contract): Contract
    {
        return DB::transaction(function () use ($contract): Contract {
            $copy = $contract->replicate([
                'number', 'token', 'status', 'vendor_signatory', 'sent_at', 'viewed_at', 'signed_at', 'signer_name',
                'signer_ip', 'signer_user_agent', 'signature_path', 'content_hash', 'voided_at', 'void_reason',
            ]);

            $copy->fill([
                'number' => DocumentNumber::next($contract->vendor, 'KT'),
                'token' => Contract::freshToken(),
                'status' => ContractStatus::Draft,
            ])->save();

            return $copy;
        });
    }
}
