<?php

namespace App\Http\Controllers\Customer;

use App\Actions\RecordSuccessfulPayment;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Sandbox payment gateway: settles a pending payment immediately.
 * Replace with a real gateway redirect + callback when one is chosen.
 */
class PaymentController extends Controller
{
    public function store(Request $request, Booking $booking, Payment $payment, RecordSuccessfulPayment $recordPayment): RedirectResponse
    {
        Gate::authorize('pay', $booking);

        if ($request->user()->isImpersonated()) {
            return back()->withErrors(['payment' => 'Pembayaran dimatikan semasa mod impersonate.']);
        }

        if ($payment->status !== PaymentStatus::Pending) {
            return back()->with('status', 'Bayaran ini telah diselesaikan.');
        }

        if ($payment->type === PaymentType::Balance && ! $booking->depositPayment?->isPaid()) {
            return back()->withErrors(['payment' => 'Sila bayar deposit terlebih dahulu.']);
        }

        $recordPayment->handle($payment, 'SBX-'.Str::upper(Str::random(10)));

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', $payment->type->label().' RM'.number_format((float) $payment->amount, 2).' diterima (sandbox).');
    }
}
