<?php

namespace App\Support\Herepay;

use App\Models\User;
use App\Models\VendorSubscription;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Herepay payment links for Neekah Pro.
 *
 * The request and callback formats are filled in from Herepay's payment link
 * documentation. Until then isConfigured() stays false, so the Pro page offers
 * no checkout and an admin records payments by hand instead.
 */
class HerepayClient implements PaymentLinkGateway
{
    public function isConfigured(): bool
    {
        return filled(config('services.herepay.api_key'))
            && filled(config('services.herepay.secret'))
            && filled(config('services.herepay.base_url'));
    }

    public function createPaymentLink(VendorSubscription $subscription, User $payer): string
    {
        throw new RuntimeException('Herepay payment links are not wired up yet.');
    }

    public function parseCallback(Request $request): ?array
    {
        return null;
    }
}
