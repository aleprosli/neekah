<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VendorStatus;
use App\Enums\VendorTier;
use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Notifications\VendorStatusChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VendorApprovalController extends Controller
{
    /**
     * Approve, reject or suspend a vendor. Approving promotes a new vendor to Verified.
     */
    public function store(Request $request, Vendor $vendor): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(VendorStatus::class)],
        ]);

        $status = VendorStatus::from($validated['status']);

        $attributes = ['status' => $status];

        if ($status === VendorStatus::Approved) {
            $attributes['approved_at'] = $vendor->approved_at ?? now();

            if ($vendor->tier === VendorTier::New) {
                $attributes['tier'] = VendorTier::Verified;
            }
        }

        $vendor->update($attributes);
        $vendor->update(['score' => $vendor->calculateScore()]);

        $vendor->user->notify(new VendorStatusChanged($vendor->fresh()));

        return back()->with('status', $vendor->name.' kini '.$status->label().'.');
    }
}
