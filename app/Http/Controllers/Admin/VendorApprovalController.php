<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ChangeVendorStatus;
use App\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VendorApprovalController extends Controller
{
    /**
     * Approve, reject or suspend one vendor.
     */
    public function store(Request $request, Vendor $vendor, ChangeVendorStatus $changeStatus): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(VendorStatus::class)],
        ]);

        $status = VendorStatus::from($validated['status']);

        $changeStatus->handle($vendor, $status);

        return back()->with('status', $vendor->name.' kini '.$status->label().'.');
    }

    /**
     * The same change over a batch the admin ticked. A fresh marketplace can
     * arrive with dozens of registrations at once, and approving them one at a
     * time is a dialog and a page load each.
     */
    public function bulk(Request $request, ChangeVendorStatus $changeStatus): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(VendorStatus::class)],
            'ids' => ['required', 'array', 'max:100'],
            'ids.*' => ['integer', 'exists:vendors,id'],
        ], [
            'ids.required' => 'Pilih sekurang-kurangnya satu vendor.',
            'ids.max' => 'Maksimum 100 vendor dalam satu masa.',
        ]);

        $status = VendorStatus::from($validated['status']);

        // Only the ones that are not already in that state, so nobody is
        // emailed twice for a status they already hold.
        $vendors = Vendor::with('user')->whereIn('id', $validated['ids'])->whereNot('status', $status)->get();

        $vendors->each(fn (Vendor $vendor) => $changeStatus->handle($vendor, $status));

        return back()->with('status', $vendors->isEmpty()
            ? 'Tiada perubahan: vendor yang dipilih sudah '.$status->label().'.'
            : $vendors->count().' vendor kini '.$status->label().'.');
    }
}
