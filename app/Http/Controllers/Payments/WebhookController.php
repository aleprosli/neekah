<?php

namespace App\Http\Controllers\Payments;

use App\Actions\SettlePayment;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Support\Payments\PaymentGateways;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Every gateway's server-to-server callback, for every kind of payment.
 *
 * The callback address is a signed route carrying our reference, since a
 * gateway's body says nothing of ours. The signature is checked first: only
 * then can the reference be trusted to pick the payment, and with it the
 * account whose key the body is checked against (a vendor's for a deposit,
 * Neekah's for everything else). Whatever arrives is kept, verified or not,
 * with the answer we gave.
 */
class WebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGateways $gateways, SettlePayment $settle, string $gateway = 'herepay'): Response
    {
        abort_unless($gateways->has($gateway), 404);

        $body = $request->isJson() ? $request->json()->all() : $request->request->all();
        $meta = ['ip' => $request->ip(), 'ref' => $request->query('ref'), 'user_agent' => mb_substr((string) $request->userAgent(), 0, 200)];

        if (! $request->hasValidRelativeSignature()) {
            return $this->answer(null, $gateway, $body, $meta + ['error' => 'Invalid signature'], false, null, 403);
        }

        $payment = Payment::query()->where('reference', (string) $request->query('ref'))->where('gateway', $gateway)->first();

        if (! $payment) {
            return $this->answer(null, $gateway, $body, $meta + ['error' => 'Unknown reference'], null, null, 404);
        }

        $result = $gateways->for($gateway)->readCallback($request, $payment);

        if (! $result->verified) {
            return $this->answer($payment, $gateway, $body, $meta + ['error' => $result->error], false, SettlePayment::UNVERIFIED, 403);
        }

        $outcome = $settle->apply($payment, $result);

        return $this->answer($payment, $gateway, $body, $meta, true, $outcome, $outcome === SettlePayment::AMOUNT_MISMATCH ? 422 : 200);
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>  $meta
     */
    private function answer(?Payment $payment, string $gateway, array $body, array $meta, ?bool $verified, ?string $outcome, int $status): Response
    {
        PaymentEvent::record($payment, $gateway, PaymentEvent::CALLBACK, $body, $meta, $verified, $outcome, $status);

        return response($status === 200 ? 'OK' : ($meta['error'] ?? $outcome ?? 'Refused'), $status);
    }
}
