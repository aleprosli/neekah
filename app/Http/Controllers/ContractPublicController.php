<?php

namespace App\Http\Controllers;

use App\Actions\SignContract;
use App\Enums\ContractStatus;
use App\Http\Requests\SignContractRequest;
use App\Models\Contract;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A vendor's contract as the client reads and signs it, with no account: the
 * random token in the address is the key. A draft is shown only to its
 * vendor, and the link keeps working after the vendor's Pro ends.
 */
class ContractPublicController extends Controller
{
    public function show(Request $request, Contract $contract, Seo $seo): View
    {
        $isOwner = $request->user()?->vendor?->id === $contract->vendor_id;

        abort_if($contract->status === ContractStatus::Draft && ! $isOwner, 404);

        $contract->load(['vendor.user', 'quotation.items']);

        if (! $isOwner && $contract->viewed_at === null) {
            $contract->forceFill(['viewed_at' => now()])->saveQuietly();
        }

        $seo->noindex();

        return view('contracts.show', [
            'contract' => $contract,
            'vendor' => $contract->vendor,
            'isOwner' => $isOwner,
        ]);
    }

    public function sign(SignContractRequest $request, Contract $contract, SignContract $sign): RedirectResponse
    {
        $sign->handle(
            $contract,
            $request->string('name')->trim()->toString(),
            (string) $request->signaturePng(),
            $request->ip(),
            $request->userAgent(),
        );

        return redirect()->to($contract->publicUrl())->with('status', __('flash.contract.signed'));
    }

    /**
     * The drawn signature, from the private disk, only to someone holding
     * the contract's link.
     */
    public function signature(Contract $contract): StreamedResponse
    {
        abort_unless($contract->signature_path, 404);

        return Storage::disk(Contract::SIGNATURE_DISK)->response($contract->signature_path, 'signature.png', [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, max-age=3600',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
