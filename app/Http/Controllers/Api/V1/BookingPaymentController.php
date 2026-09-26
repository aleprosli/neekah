<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\VerifyManualPayment;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The vendor checking a transfer the couple recorded, or noting that a paid
 * deposit went back. Only the vendor can see their own account, so nothing
 * the couple enters moves the booking until it passes through here.
 */
class BookingPaymentController extends Controller
{
    public function verify(Request $request, Booking $booking, Payment $payment, VerifyManualPayment $verify): JsonResponse
    {
        $this->authorizePending($booking, $payment);

        $verify->handle($payment, $request->user());

        return BookingController::answer($booking, __('flash.vendor.payment_verified', ['amount' => number_format((float) $payment->amount, 2)]));
    }

    public function reject(Request $request, Booking $booking, Payment $payment, VerifyManualPayment $verify): JsonResponse
    {
        $this->authorizePending($booking, $payment);

        $verify->reject($payment, $request->user());

        return BookingController::answer($booking, __('flash.vendor.payment_rejected'));
    }

    public function refunded(Booking $booking, Payment $payment): JsonResponse
    {
        Gate::authorize('verifyPayment', $booking);
        abort_unless($payment->isPaid(), 404);

        $payment->update(['status' => PaymentStatus::Refunded]);

        return BookingController::answer($booking, __('flash.vendor.payment_refunded', ['amount' => 'RM'.number_format((float) $payment->amount, 2)]));
    }

    private function authorizePending(Booking $booking, Payment $payment): void
    {
        Gate::authorize('verifyPayment', $booking);

        abort_unless($payment->isAwaitingVerification(), 403);
    }
}
