<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\CancelBooking;
use App\Enums\CancellationReason;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The vendor calling off a booking, and recording that a deposit they took was
 * given back. Neekah moves no money, so a refund is only ever a record of
 * what the vendor did.
 */
class BookingCancellationController extends Controller
{
    public function store(Request $request, Booking $booking, CancelBooking $cancelBooking): RedirectResponse
    {
        Gate::authorize('vendorCancel', $booking);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:200']], attributes: ['reason' => __('fields.sebab')]);

        $cancelBooking->handle($booking, $request->user(), $validated['reason'], CancellationReason::Vendor);

        return redirect()->route('vendor.bookings.show', $booking)
            ->with('status', __('flash.vendor.booking_cancelled', ['reference' => $booking->reference]));
    }

    public function refunded(Request $request, Booking $booking, Payment $payment): RedirectResponse
    {
        Gate::authorize('verifyPayment', $booking);
        abort_unless($payment->isPaid(), 404);

        $payment->update(['status' => PaymentStatus::Refunded]);

        return redirect()->route('vendor.bookings.show', $booking)
            ->with('status', __('flash.vendor.payment_refunded', ['amount' => 'RM'.number_format((float) $payment->amount, 2)]));
    }
}
