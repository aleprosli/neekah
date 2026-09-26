<?php

namespace App\Support\Herepay;

use App\Models\BoostPurchase;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * What buying boost tokens needs from a payment provider, on Neekah's own
 * account.
 */
interface BoostPaymentGateway
{
    /** Switched on by an admin with every key in place. */
    public function isConfigured(): bool;

    public function createPaymentLink(BoostPurchase $purchase, User $payer): string;

    /**
     * @return array{reference: string, gateway_reference: string|null, status: 'paid'|'failed'|'pending', amount: float}|null
     */
    public function parseCallback(Request $request): ?array;
}
