<?php

namespace App\Http\Controllers\Customer;

use App\Actions\CancelBooking;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Calling off a booking the couple made by mistake. The policy allows it only
 * while no payment has been verified, so this is a correction, never a refund.
 */
class BookingCancellationController extends Controller
{
    public function store(Request $request, Booking $booking, CancelBooking $cancelBooking): RedirectResponse
    {
        $booking->load('payments');

        Gate::authorize('cancel', $booking);

        if ($request->user()->isImpersonated()) {
            return back()->withErrors(['booking' => 'Pembatalan dimatikan semasa mod impersonate.']);
        }

        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:200']]);

        $cancelBooking->handle($booking, $request->user(), $validated['reason'] ?? null);

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', 'Booking '.$booking->reference.' dibatalkan.');
    }
}
