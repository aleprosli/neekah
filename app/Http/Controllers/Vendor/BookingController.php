<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\CreateBooking;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVendorBookingRequest;
use App\Models\Booking;
use App\Models\Package;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $vendor = $request->user()->vendor;
        $status = BookingStatus::tryFrom($request->string('status')->toString());

        $bookings = $vendor->bookings()
            ->with(['user', 'payments'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('vendor.bookings.index', [
            'bookings' => $bookings,
            'status' => $status,
            'counts' => $vendor->bookings()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('vendor.bookings.create', [
            'packages' => $request->user()->vendor->packages()->active()->get(),
        ]);
    }

    public function store(StoreVendorBookingRequest $request, CreateBooking $createBooking): RedirectResponse
    {
        $vendor = $request->user()->vendor;
        $package = Package::findOrFail($request->integer('package_id'));

        $customer = $request->customer();

        $booking = $createBooking->handle($customer, $vendor, $package, [
            'event_date' => $request->date('event_date'),
            'wedding_id' => $customer->weddings()->latest('event_date')->value('weddings.id'),
            'notes' => $request->string('notes')->toString() ?: null,
        ]);

        return redirect()
            ->route('vendor.bookings.show', $booking)
            ->with('status', 'Booking '.$booking->reference.' direkod. Pelanggan boleh bayar deposit dari akaun mereka.');
    }

    public function show(Booking $booking): View
    {
        Gate::authorize('view', $booking);

        $booking->load(['user', 'package', 'payments', 'review']);

        return view('vendor.bookings.show', [
            'booking' => $booking,
            // Vendors see only the timeline slots assigned to them, as the kertas kerja specifies.
            'timelineItems' => $booking->wedding_id
                ? $booking->wedding->timelineItems()->where('vendor_id', $booking->vendor_id)->get()
                : collect(),
        ]);
    }
}
