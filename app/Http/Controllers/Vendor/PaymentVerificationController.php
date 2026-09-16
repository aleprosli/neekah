<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\VerifyManualPayment;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The vendor checking a payment the couple recorded.
 *
 * Only the vendor can see their own bank account, so nothing the couple enters
 * moves the booking until it passes through here.
 */
class PaymentVerificationController extends Controller
{
    public function store(Request $request, Booking $booking, Payment $payment, VerifyManualPayment $verify): RedirectResponse
    {
        $this->authorizePending($request, $booking, $payment);

        $verify->handle($payment, $request->user());

        return redirect()
            ->route('vendor.bookings.show', $booking)
            ->with('status', 'Bayaran RM'.number_format((float) $payment->amount, 2).' disahkan diterima.');
    }

    public function destroy(Request $request, Booking $booking, Payment $payment, VerifyManualPayment $verify): RedirectResponse
    {
        $this->authorizePending($request, $booking, $payment);

        $verify->reject($payment, $request->user());

        return redirect()
            ->route('vendor.bookings.show', $booking)
            ->with('status', 'Bayaran ditanda sebagai tidak diterima. Pelanggan dimaklumkan untuk menyemak semula.');
    }

    private function authorizePending(Request $request, Booking $booking, Payment $payment): void
    {
        Gate::authorize('verifyPayment', $booking);

        abort_unless($payment->isAwaitingVerification(), 403);
    }
}
