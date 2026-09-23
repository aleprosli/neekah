<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ActivateVendorPro;
use App\Enums\VendorPlan;
use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VendorProController extends Controller
{
    /**
     * Record a Pro payment made outside the checkout (a bank transfer, a
     * promotion). It extends whatever the vendor has left, like a checkout.
     */
    public function store(Request $request, Vendor $vendor, ActivateVendorPro $activate): RedirectResponse
    {
        $validated = $request->validate([
            'plan' => ['required', Rule::enum(VendorPlan::class)],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $subscription = $activate->recordManually(
            $vendor,
            VendorPlan::from($validated['plan']),
            $request->user(),
            $validated['note'] ?? null,
            isset($validated['amount']) ? (float) $validated['amount'] : null,
        );

        return back()->with('status', __('flash.admin.pro_activated', [
            'vendor' => $vendor->name,
            'date' => $subscription->ends_at->translatedFormat('j M Y'),
        ]));
    }
}
