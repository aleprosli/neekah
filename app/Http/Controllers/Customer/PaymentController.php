<?php

namespace App\Http\Controllers\Customer;

use App\Actions\RecordManualPayment;
use App\Actions\StoreOptimizedImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecordManualPaymentRequest;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The couple's own record of what they paid the vendor.
 *
 * Money never passes through Neekah: the two sides deal directly, and this is
 * where the couple writes down what changed hands so both of them — and later,
 * an admin — are looking at the same numbers.
 */
class PaymentController extends Controller
{
    public function store(RecordManualPaymentRequest $request, Booking $booking, RecordManualPayment $recordPayment, StoreOptimizedImage $storeImage): RedirectResponse|JsonResponse
    {
        // An admin looking through a couple's eyes must not leave a payment
        // record behind in their name.
        if ($request->user()->isImpersonated()) {
            return back()->withErrors(['payment' => __('flash.impersonation.payments_off')]);
        }

        $payment = $recordPayment->handle($booking, $request->user(), [
            'amount' => $request->float('amount'),
            'paid_on' => $request->date('paid_on')->toDateString(),
            'note' => $request->string('note')->toString() ?: null,
            'receipt_image' => $request->hasFile('receipt')
                ? $storeImage->handle($request->file('receipt'), 'receipts/'.$booking->id)
                : null,
        ]);

        return $this->redirectOrJson(
            $request,
            route('bookings.show', $booking),
            'Bayaran RM'.number_format((float) $payment->amount, 2).' direkod. Vendor akan mengesahkannya setelah menyemak akaun mereka.',
        );
    }

    /**
     * Take back a record the vendor has not confirmed yet. Once it is verified
     * it is the vendor's word too, and no longer the couple's to erase.
     */
    public function destroy(Request $request, Booking $booking, Payment $payment, StoreOptimizedImage $storeImage): RedirectResponse
    {
        Gate::authorize('recordPayment', $booking);

        abort_unless($payment->isAwaitingVerification(), 403);

        if ($payment->receipt_image) {
            $storeImage->delete($payment->receipt_image);
        }

        $payment->delete();

        return redirect()->route('bookings.show', $booking)->with('status', __('flash.couple.payment_removed'));
    }
}
