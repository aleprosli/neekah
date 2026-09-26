<?php

namespace App\Support\Herepay;

use App\Models\CameraPurchase;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * What the Kamera Majlis checkout needs from a payment provider, on Neekah's
 * own account.
 */
interface CameraPaymentGateway
{
    /** Switched on by an admin with every key in place. */
    public function isConfigured(): bool;

    public function createPaymentLink(CameraPurchase $purchase, User $payer): string;

    /**
     * @return array{reference: string, gateway_reference: string|null, status: 'paid'|'failed'|'pending', amount: float}|null
     */
    public function parseCallback(Request $request): ?array;
}
