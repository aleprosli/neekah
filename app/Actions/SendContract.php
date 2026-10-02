<?php

namespace App\Actions;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Notifications\ContractSent;
use Illuminate\Support\Facades\Notification;

/**
 * Open a contract to the client for signing. The vendor's name and the time
 * it was sent stand as the vendor's side of the agreement. Sending again
 * only re-sends the email.
 */
class SendContract
{
    public function handle(Contract $contract, string $signatory): Contract
    {
        if ($contract->status === ContractStatus::Draft) {
            $contract->update([
                'status' => ContractStatus::Sent,
                'sent_at' => now(),
                'vendor_signatory' => $signatory,
            ]);
        }

        if (filled($contract->client_email)) {
            Notification::route('mail', $contract->client_email)->notify(new ContractSent($contract));
        }

        return $contract;
    }
}
