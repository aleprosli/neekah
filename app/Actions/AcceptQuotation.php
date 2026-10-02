<?php

namespace App\Actions;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Notifications\QuotationAccepted;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The client agreeing to a quotation from its public page. Who typed their
 * name, when and from where is kept with it; nothing is paid here, the client
 * pays the vendor directly.
 */
class AcceptQuotation
{
    public function handle(Quotation $quotation, string $name, ?string $ip): Quotation
    {
        return DB::transaction(function () use ($quotation, $name, $ip): Quotation {
            $locked = Quotation::query()->lockForUpdate()->findOrFail($quotation->id);

            if (! $locked->awaitsClient()) {
                throw ValidationException::withMessages(['name' => __('validation.custom.quotation_closed')]);
            }

            $locked->update([
                'status' => QuotationStatus::Accepted,
                'accepted_at' => now(),
                'accepted_name' => $name,
                'accepted_ip' => $ip,
            ]);

            $locked->vendor->user->notify(new QuotationAccepted($locked));

            return $locked;
        });
    }
}
