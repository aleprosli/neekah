<?php

namespace App\Http\Controllers\Customer;

use App\Enums\EnquiryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnquiryRequest;
use App\Models\Enquiry;
use App\Models\Vendor;
use App\Notifications\EnquiryReceived;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EnquiryController extends Controller
{
    public function index(Request $request): View
    {
        $enquiries = $request->user()->enquiries()
            ->with(['vendor.category'])
            ->latest()
            ->orderByDesc('id')
            ->paginate(15);

        $enquiries->setCollection($enquiries->getCollection()->map(fn (Enquiry $enquiry): array => [
            'id' => $enquiry->id,
            'url' => route('enquiries.show', $enquiry),
            'vendor' => $enquiry->vendor->name,
            'message' => $enquiry->message,
            'replied' => $enquiry->status === EnquiryStatus::Replied,
            'sent' => $enquiry->created_at->diffForHumans(),
            'category' => [
                'tone' => $enquiry->vendor->cover_tone,
                'icon' => $enquiry->vendor->category->icon,
                'illustration' => $enquiry->vendor->category->illustrationUrl(),
            ],
        ]));

        return view('customer.enquiries.index', [
            'props' => VueProps::for([
                'enquiries' => $enquiries->items(),
                'findVendorsUrl' => route('vendors.index'),
                'pagination' => $enquiries->hasPages() ? (string) $enquiries->links() : '',
            ]),
        ]);
    }

    public function store(StoreEnquiryRequest $request, Vendor $vendor): RedirectResponse
    {
        abort_unless($vendor->isApproved(), 404);

        $enquiry = $request->user()->enquiries()->create([
            ...$request->safe()->only(['message', 'event_date', 'package_id']),
            'vendor_id' => $vendor->id,
            'wedding_id' => $request->filled('wedding_id')
                ? $request->integer('wedding_id')
                : $request->user()->weddings()->latest('event_date')->value('weddings.id'),
            'status' => EnquiryStatus::Open,
        ]);

        $enquiry->setRelation('user', $request->user());
        $vendor->user->notify(new EnquiryReceived($enquiry));

        return redirect()
            ->route('enquiries.show', $enquiry)
            ->with('status', 'Enquiry dihantar kepada '.$vendor->name.'. Anda akan lihat balasan di sini.');
    }

    public function show(Enquiry $enquiry): View
    {
        Gate::authorize('view', $enquiry);

        $enquiry->load(['vendor.category', 'package']);

        return view('customer.enquiries.show', ['enquiry' => $enquiry]);
    }
}
