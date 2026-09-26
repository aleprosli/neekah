<?php

namespace App\Support\Herepay;

use App\Models\VendorBookingSetting;

/**
 * Whose Herepay account a call is made on. Neekah's own takes Pro
 * subscriptions; a vendor's own takes their booking deposits, so that money
 * never passes through Neekah. The base URL (UAT or production) is always
 * Neekah's: a vendor's keys must belong to the same environment.
 */
final readonly class HerepayCredentials
{
    public function __construct(
        public string $secretKey,
        public string $privateKey,
    ) {}

    public static function neekah(): self
    {
        return new self((string) config('services.herepay.secret_key'), (string) config('services.herepay.private_key'));
    }

    public static function forVendor(VendorBookingSetting $settings): ?self
    {
        return $settings->hasHerepay()
            ? new self((string) $settings->herepay_secret_key, (string) $settings->herepay_private_key)
            : null;
    }
}
