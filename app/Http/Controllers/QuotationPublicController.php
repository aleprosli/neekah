<?php

namespace App\Http\Controllers;

use App\Actions\AcceptQuotation;
use App\Actions\DeclineQuotation;
use App\Enums\QuotationStatus;
use App\Http\Requests\AcceptQuotationRequest;
use App\Http\Requests\DeclineQuotationRequest;
use App\Models\Quotation;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * A quotation (or the invoice issued from it) as the client sees it, with no
 * account: the random token in the address is the key. A draft is shown
 * only to its vendor, and the link keeps working after the vendor's Pro ends, because
 * the client already holds it.
 */
class QuotationPublicController extends Controller
{
    public function show(Request $request, Quotation $quotation, Seo $seo): View
    {
        $isOwner = $request->user()?->vendor?->id === $quotation->vendor_id;

        // A draft is the vendor's alone to preview until they send it.
        abort_if($quotation->status === QuotationStatus::Draft && ! $isOwner, 404);

        $quotation->load(['vendor.user', 'items', 'booking.payments']);

        if (! $isOwner && $quotation->viewed_at === null) {
            $quotation->forceFill(['viewed_at' => now()])->saveQuietly();
        }

        $seo->noindex();

        return view('quotations.show', [
            'quotation' => $quotation,
            'vendor' => $quotation->vendor,
            'isOwner' => $isOwner,
            'asInvoice' => $quotation->isInvoiced(),
        ]);
    }

    public function accept(AcceptQuotationRequest $request, Quotation $quotation, AcceptQuotation $accept): RedirectResponse
    {
        $accept->handle($quotation, $request->string('name')->trim()->toString(), $request->ip());

        return redirect()->to($quotation->publicUrl())->with('status', __('flash.quotation.accepted'));
    }

    public function decline(DeclineQuotationRequest $request, Quotation $quotation, DeclineQuotation $decline): RedirectResponse
    {
        $decline->handle($quotation, $request->string('reason')->trim()->toString() ?: null);

        return redirect()->to($quotation->publicUrl())->with('status', __('flash.quotation.declined'));
    }
}
