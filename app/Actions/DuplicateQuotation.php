<?php

namespace App\Actions;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Support\Quotations\DocumentNumber;
use Illuminate\Support\Facades\DB;

/**
 * A fresh draft from an existing quotation, under a new number and link. It
 * is how a quotation the client already answered gets revised: what they
 * agreed to is never edited under them.
 */
class DuplicateQuotation
{
    public function handle(Quotation $quotation): Quotation
    {
        return DB::transaction(function () use ($quotation): Quotation {
            $copy = $quotation->replicate([
                'number', 'token', 'status', 'booking_id', 'sent_at', 'viewed_at', 'accepted_at', 'accepted_name', 'accepted_ip',
                'declined_at', 'decline_reason', 'invoice_number', 'invoiced_at', 'invoice_status',
            ]);

            $copy->fill([
                'number' => DocumentNumber::next($quotation->vendor, 'QT'),
                'token' => Quotation::freshToken(),
                'status' => QuotationStatus::Draft,
                'valid_until' => today()->addDays(Quotation::DEFAULT_VALID_DAYS),
            ])->save();

            $quotation->items->each(fn (QuotationItem $item) => $copy->items()->create(
                $item->only(['package_id', 'kind', 'name', 'description', 'features', 'quantity', 'unit_price', 'line_total', 'sort_order'])
            ));

            $copy->recalculate();

            return $copy;
        });
    }
}
