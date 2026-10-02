<?php

namespace App\Actions;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Notifications\QuotationDeclined;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** The client saying no, with a reason if they want to give one. */
class DeclineQuotation
{
    public function handle(Quotation $quotation, ?string $reason): Quotation
    {
        return DB::transaction(function () use ($quotation, $reason): Quotation {
            $locked = Quotation::query()->lockForUpdate()->findOrFail($quotation->id);

            if (! $locked->awaitsClient()) {
                throw ValidationException::withMessages(['reason' => __('validation.custom.quotation_closed')]);
            }

            $locked->update([
                'status' => QuotationStatus::Declined,
                'declined_at' => now(),
                'decline_reason' => $reason,
            ]);

            $locked->vendor->user->notify(new QuotationDeclined($locked));

            return $locked;
        });
    }
}
