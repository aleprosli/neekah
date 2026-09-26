<?php

namespace App\Support\Herepay;

use App\Models\User;
use App\Models\VendorSubscription;
use Illuminate\Http\Request;

/**
 * What the Pro checkout needs from a payment provider: a link to send the
 * vendor to, and a way to trust what comes back.
 */
interface PaymentLinkGateway
{
    /**
     * Whether checkout can be offered: switched on by an admin and every key
     * in place.
     */
    public function isConfigured(): bool;

    /**
     * Create a one-off payment link for this purchase and return its URL.
     */
    public function createPaymentLink(VendorSubscription $subscription, User $payer): string;

    /**
     * Read a callback. Null when it cannot be verified as coming from the
     * provider, which the webhook answers with a refusal.
     *
     * @return array{reference: string, gateway_reference: string|null, status: 'paid'|'failed'|'pending', amount: float}|null
     */
    public function parseCallback(Request $request): ?array;
}
