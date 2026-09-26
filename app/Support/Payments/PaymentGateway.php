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

    /** Whether a link can be made for this payment: switched on, and the account's keys in place. */
    public function isConfiguredFor(Payment $payment): bool;

    /**
     * Create a one-off payment link and return where to send the payer.
     *
     * @return array{url: string, response: array<string, mixed>}
     *
     * @throws \Throwable when the gateway refuses
     */
    public function createLink(Payment $payment, PaymentLink $link): array;

    /** Read and verify the gateway's server-to-server callback. */
    public function readCallback(Request $request, Payment $payment): GatewayResult;

    /** Read and verify what the gateway put on the payer's way back. */
    public function readReturn(Request $request, Payment $payment): GatewayResult;

    /** Whether the gateway can be asked about this payment again. */
    public function canRequery(Payment $payment): bool;

    /** Ask the gateway where this payment stands. */
    public function requery(Payment $payment): GatewayResult;
}
