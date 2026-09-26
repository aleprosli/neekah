<?php

namespace App\Http\Controllers\Payments;

use App\Actions\SettlePayment;
use App\Enums\PaymentPurpose;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Support\Payments\PaymentGateways;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Where a gateway sends the payer back, for every kind of payment. Herepay
 * signs what it puts on this address, so a verified return settles the
 * payment even when its callback is late or lost; an unverified one changes
 * nothing. Either way it is kept, and the payer goes on to the page that
 * tells them where their purchase stands.
 *
 * Not a signed route: the gateway adds its own query, which would break any
 * signature of ours. The checksum is the proof.
 */
class ReturnController extends Controller
{
    public function __invoke(Request $request, Payment $payment, PaymentGateways $gateways, SettlePayment $settle): RedirectResponse
    {
        if ($payment->isOnline() && $gateways->has($payment->gateway) && $request->query() !== []) {
            $result = $gateways->for($payment->gateway)->readReturn($request, $payment);
            $outcome = $settle->apply($payment, $result);

            PaymentEvent::record($payment, $payment->gateway, PaymentEvent::RETURN, $request->query(), [
                'ip' => $request->ip(),
                'error' => $result->error,
            ], $result->verified, $outcome);
        }

        return redirect()->to(match ($payment->purpose) {
            PaymentPurpose::Booking => route('bookings.payment.done', $payment->booking),
            PaymentPurpose::VendorPro => route('vendor.pro.done', ['ref' => $payment->reference]),
            PaymentPurpose::BoostTokens => route('vendor.boost.done', ['ref' => $payment->reference]),
            PaymentPurpose::Kenangan => route('camera.done', ['ref' => $payment->reference]),
        });
    }
}
