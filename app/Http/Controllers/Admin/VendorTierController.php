<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RecalculateVendorStats;
use App\Enums\VendorTier;
use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VendorTierController extends Controller
{
    /**
     * Set the ranking tier by hand. The Vendor Score is recalculated from it.
     */
    public function update(Request $request, Vendor $vendor, RecalculateVendorStats $recalculateStats): RedirectResponse
    {
        $validated = $request->validate([
            'tier' => ['required', Rule::enum(VendorTier::class)],
            'response_rate' => ['nullable', 'integer', 'between:0,100'],
            'tier_locked' => ['nullable', 'boolean'],
        ]);

        $vendor->tier = VendorTier::from($validated['tier']);
        $vendor->tier_locked = $request->boolean('tier_locked');

        if (isset($validated['response_rate'])) {
            $vendor->response_rate = $validated['response_rate'];
        }

        $vendor->save();
        $recalculateStats->handle($vendor);

        $note = $vendor->tier_locked ? ' Tahap dikunci, pengiraan automatik tidak akan mengubahnya.' : '';

        return back()->with('status', $vendor->name.' kini '.$vendor->fresh()->tier->label().' Vendor (score '.number_format((float) $vendor->fresh()->score, 2).').'.$note);
    }
}
