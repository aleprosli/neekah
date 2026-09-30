<?php

namespace App\Support\Payments;

/**
 * What a gateway said about a payment, in Neekah's words, whichever way it
 * said it: a callback, the payer's return, or an answer to a requery.
 */
final readonly class GatewayResult
{
    public const PAID = 'paid';

    public const FAILED = 'failed';

    public const PENDING = 'pending';

    public function __construct(
        /** Whether it could be proved to come from the gateway (checksum, or our own authenticated call). */
        public bool $verified,
        /** paid, failed or pending. */
        public string $status = self::PENDING,
        public ?float $amount = null,
        /** The gateway's id for the payment (Herepay: payment_code). */
        public ?string $reference = null,
        /** The gateway's invoice, the key a requery asks by (Herepay: reference_code). */
        public ?string $invoice = null,
        public ?string $transactionId = null,
        /** How the payer paid, as the gateway names it: FPX, card. */
        public ?string $method = null,
        /** The gateway's own words for the status, kept for the admin. */
        public ?string $gatewayStatus = null,
        /** Why it could not be read, when it could not. */
        public ?string $error = null,
        /**
         * What the gateway answered, as it answered, for the event log. Empty
         * for a callback or a return, whose request is logged instead.
         *
         * @var array<string, mixed>
         */
        public array $raw = [],
        /** The HTTP status of our own call to the gateway, for a requery. */
        public ?int $httpStatus = null,
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function unverified(string $error, array $raw = [], ?int $httpStatus = null): self
    {
        return new self(verified: false, error: $error, raw: $raw, httpStatus: $httpStatus);
    }

    public function isPaid(): bool
    {
        return $this->verified && $this->status === self::PAID;
    }

    public function isFailed(): bool
    {
        return $this->verified && $this->status === self::FAILED;
    }
}
