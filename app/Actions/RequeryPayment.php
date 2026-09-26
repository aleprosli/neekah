<?php

namespace App\Actions;

use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Support\Herepay\HerepayGateway;
use App\Support\Payments\GatewayResult;
use App\Support\Payments\PaymentGateways;
use Throwable;

/**
 * Ask the gateway where a payment stands, for when its callback never came
 * (or came garbled). What the gateway answers is kept, and a verified answer
 * settles the payment exactly as a callback would.
 */
class RequeryPayment
{
    public function __construct(private PaymentGateways $gateways, private SettlePayment $settle) {}

    /**
     * @return string one of SettlePayment's outcomes, or 'unavailable'
     */
    public function handle(Payment $payment): string
    {
        if (! $this->gateways->has($payment->gateway)) {
            return 'unavailable';
        }

        $gateway = $this->gateways->for($payment->gateway);

        if (! $gateway->canRequery($payment)) {
            return 'unavailable';
        }

        try {
            $result = $gateway->requery($payment);
        } catch (Throwable $exception) {
            $result = GatewayResult::unverified($exception->getMessage());
        }

        $outcome = $this->settle->apply($payment, $result);
        $payment->update(['last_checked_at' => now()]);

        PaymentEvent::record($payment, $gateway->name(), PaymentEvent::REQUERY, $result->raw ?: null, [
            'code' => HerepayGateway::lookupCode($payment) ?? $payment->gateway_invoice,
            'error' => $result->error,
        ], $result->verified, $outcome, $result->httpStatus);

        return $outcome;
    }
}
