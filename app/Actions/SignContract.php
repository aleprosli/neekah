<?php

namespace App\Actions;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Notifications\ContractSigned;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The client signing a contract from its public page: their drawn signature,
 * their typed name, when, from where, and a hash of exactly what they were
 * shown. The signature goes to the private disk, never the public one.
 *
 * When the contract carries a quotation still waiting for an answer, signing
 * accepts it too, so the client has one thing to do.
 */
class SignContract
{
    public function __construct(private AcceptQuotation $acceptQuotation) {}

    public function handle(Contract $contract, string $name, string $signaturePng, ?string $ip, ?string $userAgent): Contract
    {
        $disk = Storage::disk(Contract::SIGNATURE_DISK);
        $path = 'contracts/'.$contract->vendor_id.'/signatures/'.Str::random(40).'.png';

        if (! $disk->put($path, $signaturePng)) {
            throw new \RuntimeException('The signature could not be stored.');
        }

        try {
            return DB::transaction(function () use ($contract, $name, $path, $ip, $userAgent): Contract {
                $locked = Contract::query()->lockForUpdate()->findOrFail($contract->id);
                $locked->load(['vendor', 'quotation.items']);

                if (! $locked->awaitsSignature()) {
                    throw ValidationException::withMessages(['name' => __('validation.custom.contract_closed')]);
                }

                $locked->update([
                    'status' => ContractStatus::Signed,
                    'signed_at' => now(),
                    'signer_name' => $name,
                    'signer_ip' => $ip,
                    'signer_user_agent' => Str::limit((string) $userAgent, 490, ''),
                    'signature_path' => $path,
                    'content_hash' => $locked->computeHash(),
                ]);

                if ($locked->quotation?->awaitsClient()) {
                    $this->acceptQuotation->handle($locked->quotation, $name, $ip);
                }

                $locked->vendor->user->notify(new ContractSigned($locked, forClient: false));

                if (filled($locked->client_email)) {
                    Notification::route('mail', $locked->client_email)->notify(new ContractSigned($locked, forClient: true));
                }

                return $locked;
            });
        } catch (\Throwable $exception) {
            $disk->delete($path);

            throw $exception;
        }
    }
}
