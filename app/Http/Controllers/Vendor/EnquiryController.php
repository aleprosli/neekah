<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\EnquiryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReplyEnquiryRequest;
use App\Models\Enquiry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EnquiryController extends Controller
{
    public function index(Request $request): View
    {
        $enquiries = $request->user()->vendor->enquiries()
            ->with(['user', 'package'])
            ->orderByRaw("case when status = 'open' then 0 else 1 end")
            ->latest()
            ->orderByDesc('id')
            ->paginate(15);

        return view('vendor.enquiries.index', ['enquiries' => $enquiries]);
    }

    public function show(Enquiry $enquiry): View
    {
        Gate::authorize('view', $enquiry);

        $enquiry->load(['user', 'package', 'wedding']);

        return view('vendor.enquiries.show', ['enquiry' => $enquiry]);
    }

    public function update(ReplyEnquiryRequest $request, Enquiry $enquiry): RedirectResponse
    {
        $enquiry->update([
            'reply' => $request->string('reply')->toString(),
            'replied_at' => now(),
            'status' => EnquiryStatus::Replied,
        ]);

        return redirect()->route('vendor.enquiries.show', $enquiry)->with('status', 'Balasan dihantar kepada pelanggan.');
    }
}
