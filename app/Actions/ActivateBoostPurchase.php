<?php

namespace App\Actions;

use App\Enums\BoostTokenReason;
use App\Enums\SubscriptionStatus;
use App\Models\BoostPurchase;
use App\Notifications\BoostTokensReceived;
use Illuminate\Support\Facades\DB;

/**
 * Mark a boost pack paid and credit its tokens. Idempotent: Herepay may call
 * back more than once, and only the first call credits anything.
 */
class ActivateBoostPurchase
{
    public function __construct(private GrantBoostTokens $tokens) {}

    public function handle(BoostPurchase $purchase, ?string $gatewayReference = null): BoostPurchase
    {
        $credited = DB::transaction(function () use ($purchase, $gatewayReference): bool {
            $purchase = BoostPurchase::query()->lockForUpdate()->findOrFail($purchase->getKey());

            if ($purchase->isPaid()) {
                return false;
            }

            $purchase->update([
                'status' => SubscriptionStatus::Paid,
                'gateway_reference' => $gatewayReference ?? $purchase->gateway_reference,
                'paid_at' => now(),
            ]);

            $this->tokens->handle($purchase->vendor, $purchase->tokens, BoostTokenReason::Purchase, $purchase);

            return true;
        });

        $purchase->refresh();

        if ($credited) {
            $purchase->vendor->user->notify(new BoostTokensReceived($purchase->tokens, BoostTokenReason::Purchase, (int) $purchase->vendor->fresh()->boost_tokens));
        }

        return $purchase;
    }
}
