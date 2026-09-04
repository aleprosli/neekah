<?php

namespace App\Http\Controllers\Customer;

use App\Actions\CreateBooking;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Vendor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $bookings = $request->user()->bookings()
            ->with(['vendor.category', 'payments'])
            ->latest()
            ->orderByDesc('id')
            ->paginate(10);

        return view('customer.bookings.index', ['bookings' => $bookings]);
    }

    public function store(StoreBookingRequest $request, Vendor $vendor, CreateBooking $createBooking): RedirectResponse
    {
        abort_unless($vendor->isApproved(), 404);

        $package = Package::findOrFail($request->integer('package_id'));

        $booking = $createBooking->handle($request->user(), $vendor, $package, [
            'event_date' => $request->date('event_date'),
            'wedding_id' => $request->filled('wedding_id') ? $request->integer('wedding_id') : null,
            'notes' => $request->string('notes')->toString() ?: null,
        ]);

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', 'Booking '.$booking->reference.' dibuat. Bayar deposit untuk sahkan tempahan anda.');
    }

    public function show(Request $request, Booking $booking): View|RedirectResponse
    {
        Gate::authorize('view', $booking);

        if ($request->user()->isVendor()) {
            return redirect()->route('vendor.bookings.show', $booking);
        }

        $booking->load(['vendor.category', 'package', 'payments', 'review']);

        return view('customer.bookings.show', ['booking' => $booking]);
    }
}
