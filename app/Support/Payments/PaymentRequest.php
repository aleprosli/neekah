<?php

namespace App\Support\Payments;

/**
 * What a checkout asks a gateway to start: this amount, paid through the bank
 * the payer picked on the confirmation page, with the addresses the gateway
 * calls back on and sends the payer back to.
 */
final readonly class PaymentRequest
{
    public function __construct(
        public string $description,
        public float $amount,
        public string $callbackUrl,
        public string $returnUrl,
        /** The channel group the bank sits in (Herepay: "Online Banking" or "Corporate Banking"). */
        public string $paymentMethod,
        /** The bank's code from the gateway's channel list (Herepay: prefix). */
        public string $bankCode,
        public string $payerName,
        public string $payerEmail,
        public string $payerPhone,
    ) {}
}
