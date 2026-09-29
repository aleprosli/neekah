<?php

namespace App\Support\Payments;

use Carbon\CarbonInterface;

/**
 * What a checkout asks a gateway for: a one-off link for this amount, with
 * the addresses the gateway calls back on and sends the payer back to.
 */
final readonly class PaymentLink
{
    public function __construct(
        public string $title,
        public string $description,
        public float $amount,
        public CarbonInterface $expiresAt,
        public string $callbackUrl,
        public string $returnUrl,
        public ?string $payerName = null,
        public ?string $payerEmail = null,
        public ?string $payerPhone = null,
    ) {}
}
