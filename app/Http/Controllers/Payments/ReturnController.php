<?php

namespace App\Http\Controllers\Payments;

use App\Actions\SettlePayment;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Support\Payments\PaymentGateways;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Where a gateway sends the payer back, for every kind of payment. Herepay
 * signs what it sends on this address, so a verified return settles the
 * payment even when its callback is late or lost; an unverified one changes
 * nothing. Either way it is kept, and the payer goes on to the page that
 * tells them where their purchase stands.
 *
 * Not a signed route: the gateway adds its own fields, which would break any
 * signature of ours. The checksum is the proof. It may arrive as a GET query
 * or a posted form (outside CSRF); a posted one is answered with a page that
 * moves on by itself rather than a redirect, because the browser keeps the
 * Lax session cookie back from a cross-site POST and everything it leads to,
 * and the payer would land on the sign-in page.
 */
class ReturnController extends Controller
{
    public function __invoke(Request $request, Payment $payment, PaymentGateways $gateways, SettlePayment $settle): RedirectResponse|View
    {
        $fields = $request->isMethod('post') ? $request->request->all() : $request->query();

        if ($payment->isOnline() && $gateways->has($payment->gateway) && $fields !== []) {
            $result = $gateways->for($payment->gateway)->readReturn($request, $payment);
            $outcome = $settle->apply($payment, $result);

            PaymentEvent::record($payment, $payment->gateway, PaymentEvent::RETURN, $fields, [
                'ip' => $request->ip(),
                'method' => $request->method(),
                'error' => $result->error,
            ], $result->verified, $outcome);
        }

        if ($request->isMethod('post')) {
            return view('payments.returning', ['url' => route('payments.show', $payment)]);
        }

        return redirect()->route('payments.show', $payment);
    }
}
