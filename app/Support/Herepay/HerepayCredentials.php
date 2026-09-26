<?php

namespace App\Support\Herepay;

use App\Models\VendorBookingSetting;

/**
 * Whose Herepay account a call is made on. Neekah's own takes Pro, boost
 * packs and Neekah Kenangan; a vendor's own takes their booking deposits, so
 * that money never passes through Neekah. The base URL (UAT or production)
 * is always Neekah's: a vendor's keys must belong to the same environment.
 *
 * The API key (XApiKey) is only needed to ask Herepay about a payment again;
 * without it everything else still works.
 */
final readonly class HerepayCredentials
{
    public function __construct(
        public string $secretKey,
        public string $privateKey,
        public string $apiKey = '',
    ) {}

    public static function neekah(): self
    {
        return new self(
            (string) config('services.herepay.secret_key'),
            (string) config('services.herepay.private_key'),
            (string) config('services.herepay.api_key'),
        );
    }

    public static function forVendor(VendorBookingSetting $settings): ?self
    {
        return $settings->hasHerepay()
            ? new self((string) $settings->herepay_secret_key, (string) $settings->herepay_private_key, (string) $settings->herepay_api_key)
            : null;
    }

    public function canRequery(): bool
    {
        return $this->secretKey !== '' && $this->apiKey !== '';
    }
}
