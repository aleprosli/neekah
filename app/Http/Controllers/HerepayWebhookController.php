<?php

namespace App\Http\Controllers;

use App\Actions\ActivateVendorPro;
use App\Enums\SubscriptionStatus;
use App\Models\VendorSubscription;
use App\Support\Herepay\PaymentLinkGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

/**
 * Herepay's server-to-server callback for a Pro payment. It is the only thing
 * that can activate a checkout: the vendor's return to the site proves nothing.
 */
class HerepayWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentLinkGateway $gateway, ActivateVendorPro $activate): Response
    {
        $result = $gateway->parseCallback($request);

        if ($result === null) {
            return response('Invalid signature', 403);
        }

        $subscription = VendorSubscription::query()
            ->where('reference', $result['reference'])
            ->where('gateway', VendorSubscription::GATEWAY_HEREPAY)
            ->first();

        if (! $subscription) {
            return response('Unknown reference', 404);
        }

        // A payment for less than the price is not this purchase, whatever it says.
        if ($result['status'] === 'paid' && round($result['amount'], 2) < round((float) $subscription->amount, 2)) {
            report(new RuntimeException("Herepay paid {$result['amount']} for {$subscription->reference}, which costs {$subscription->amount}."));

            return response('Amount mismatch', 422);
        }

        match ($result['status']) {
            'paid' => $activate->handle($subscription, $result['gateway_reference']),
            'failed' => $subscription->status === SubscriptionStatus::Pending
                ? $subscription->update(['status' => SubscriptionStatus::Failed, 'gateway_reference' => $result['gateway_reference']])
                : null,
            // Still settling: Herepay calls again once it knows.
            'pending' => null,
        };

        return response('OK');
    }
}
