<?php

namespace App\Support\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * A payment gateway, as Neekah uses one. Herepay is the first; another is
 * one more class registered in PaymentGateways, never another table or
 * webhook. Whose account a call is made on comes from the payment itself
 * (its merchant: Neekah, or the vendor of a booking).
 */
interface PaymentGateway
{
    /** The name stored on payments.gateway and used in the webhook address. */
    public function name(): string;

    /** Whether a payment can be started: switched on, and the account's keys in place. */
    public function isConfiguredFor(Payment $payment): bool;

    /**
     * The banks the payer may pick from right now, grouped by kind of
     * account. Banks that are offline are left out.
     *
     * @return list<array{method: string, channels: list<array{code: string, name: string, logo: ?string}>}>
     *
     * @throws \Throwable when the gateway cannot be reached or refuses
     */
    public function channels(Payment $payment): array;

    /**
     * Start the payment with the bank the payer picked, and return the form
     * that takes them to it.
     *
     * @throws \Throwable when the gateway refuses
     */
    public function initiate(Payment $payment, PaymentRequest $request): PaymentRedirect;

    /** Read and verify the gateway's server-to-server callback. */
    public function readCallback(Request $request, Payment $payment): GatewayResult;

    /** Read and verify what the gateway put on the payer's way back. */
    public function readReturn(Request $request, Payment $payment): GatewayResult;

    /** Whether the gateway can be asked about this payment again. */
    public function canRequery(Payment $payment): bool;

    /** Ask the gateway where this payment stands. */
    public function requery(Payment $payment): GatewayResult;
}
