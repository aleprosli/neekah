<?php

namespace App\Http\Controllers;

use App\Enums\ViolationStatus;
use App\Enums\ViolationType;
use App\Http\Requests\ReportVendorRequest;
use App\Models\Vendor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReportVendorController extends Controller
{
    public function create(Request $request, Vendor $vendor): View
    {
        abort_unless($vendor->isApproved(), 404);

        return view('report-vendor', [
            'vendor' => $vendor,
            'types' => ViolationType::cases(),
            'bookings' => $request->user()->bookings()->whereBelongsTo($vendor)->latest()->get(),
        ]);
    }

    public function store(ReportVendorRequest $request, Vendor $vendor): RedirectResponse
    {
        abort_unless($vendor->isApproved(), 404);

        $vendor->violations()->create([
            ...$request->safe()->only(['type', 'description', 'booking_id']),
            'reported_by' => $request->user()->id,
            'status' => ViolationStatus::Open,
        ]);

        return redirect()
            ->route('vendors.show', $vendor)
            ->with('status', 'Laporan anda telah dihantar. Admin akan menyiasat sebelum sebarang tindakan diambil.');
    }
}
