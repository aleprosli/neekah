<?php

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Support\Quotations\DocumentNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turn an accepted quotation into an invoice: the same lines and sums under
 * the vendor's next INV number, at the same link. Issuing twice keeps the
 * first number.
 */
class IssueQuotationInvoice
{
    public function handle(Quotation $quotation): Quotation
    {
        return DB::transaction(function () use ($quotation): Quotation {
            $locked = Quotation::query()->lockForUpdate()->findOrFail($quotation->id);

            if ($locked->status !== QuotationStatus::Accepted) {
                throw ValidationException::withMessages(['invoice' => __('validation.custom.quotation_not_accepted')]);
            }

            if ($locked->isInvoiced()) {
                return $locked;
            }

            $locked->update([
                'invoice_number' => DocumentNumber::next($locked->vendor, 'INV'),
                'invoiced_at' => now(),
                'invoice_status' => InvoiceStatus::Unpaid,
            ]);

            return $locked;
        });
    }
}
