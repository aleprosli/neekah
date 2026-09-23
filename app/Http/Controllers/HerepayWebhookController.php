<?php

namespace App\Http\Controllers;

use App\Actions\ActivateVendorPro;
use App\Enums\SubscriptionStatus;
use App\Models\VendorSubscription;
use App\Support\Herepay\PaymentLinkGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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

        if ($result['paid']) {
            $activate->handle($subscription, $result['gateway_reference']);
        } elseif ($subscription->status === SubscriptionStatus::Pending) {
            $subscription->update(['status' => SubscriptionStatus::Failed, 'gateway_reference' => $result['gateway_reference']]);
        }

        return response('OK');
    }
}
