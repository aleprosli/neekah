<?php

namespace App\Http\Controllers;

use App\Actions\ActivateBoostPurchase;
use App\Enums\SubscriptionStatus;
use App\Models\BoostPurchase;
use App\Support\Herepay\BoostPaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

/**
 * Herepay's server-to-server callback for a boost token pack, on
 * Neekah's own account. The vendor's return proves nothing; this does.
 */
class HerepayBoostWebhookController extends Controller
{
    public function __invoke(Request $request, BoostPaymentGateway $gateway, ActivateBoostPurchase $activate): Response
    {
        $result = $gateway->parseCallback($request);

        if ($result === null) {
            return response('Invalid signature', 403);
        }

        $purchase = BoostPurchase::query()
            ->where('reference', $result['reference'])
            ->where('gateway', BoostPurchase::GATEWAY_HEREPAY)
            ->first();

        if (! $purchase) {
            return response('Unknown reference', 404);
        }

        if ($result['status'] === 'paid' && round($result['amount'], 2) < round((float) $purchase->amount, 2)) {
            report(new RuntimeException("Herepay paid {$result['amount']} for {$purchase->reference}, which costs {$purchase->amount}."));

            return response('Amount mismatch', 422);
        }

        match ($result['status']) {
            'paid' => $activate->handle($purchase, $result['gateway_reference']),
            'failed' => $purchase->status === SubscriptionStatus::Pending
                ? $purchase->update(['status' => SubscriptionStatus::Failed, 'gateway_reference' => $result['gateway_reference']])
                : null,
            'pending' => null,
        };

        return response('OK');
    }
}
