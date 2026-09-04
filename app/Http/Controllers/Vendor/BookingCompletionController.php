<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\CompleteBooking;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class BookingCompletionController extends Controller
{
    /**
     * Vendor marks a confirmed booking as completed once the event has taken place.
     */
    public function store(Request $request, Booking $booking, CompleteBooking $completeBooking): RedirectResponse
    {
        abort_unless($booking->vendor_id === $request->user()->vendor?->id, 403);

        if ($booking->event_date->isFuture()) {
            return back()->withErrors(['booking' => 'Booking hanya boleh ditandakan selesai selepas tarikh majlis.']);
        }

        try {
            $completeBooking->handle($booking);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['booking' => $exception->getMessage()]);
        }

        return back()->with('status', 'Booking '.$booking->reference.' ditandakan selesai. Pelanggan kini boleh memberi review.');
    }
}
