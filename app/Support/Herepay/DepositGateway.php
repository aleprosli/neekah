<?php

namespace App\Support\Herepay;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * What an online booking needs from a vendor's payment account: a link for
 * the deposit, a way to trust what comes back, and a check that the keys a
 * vendor pasted work at all.
 */
interface DepositGateway
{
    public function createDepositLink(Payment $payment, User $payer, HerepayCredentials $credentials): string;

    /**
     * @return array{reference: string, gateway_reference: string|null, status: 'paid'|'failed'|'pending', amount: float}|null
     */
    public function parseCallback(Request $request, HerepayCredentials $credentials): ?array;

    public function testConnection(HerepayCredentials $credentials): bool;
}
