<?php

namespace App\Support\Payments;

/**
 * Where the payer's browser goes to pay: a form posted to the bank (FPX),
 * exactly as the gateway handed it to us, which our own page auto-submits.
 */
final readonly class PaymentRedirect
{
    /**
     * @param  array<string, string>  $fields
     */
    public function __construct(
        public string $action,
        public array $fields,
        public string $method = 'post',
    ) {}

    public function host(): string
    {
        return (string) parse_url($this->action, PHP_URL_HOST);
    }
}
