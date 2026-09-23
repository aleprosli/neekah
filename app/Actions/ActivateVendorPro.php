<?php

namespace App\Actions;

use App\Enums\SubscriptionStatus;
use App\Enums\VendorPlan;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorSubscription;
use App\Notifications\ProActivated;
use Illuminate\Support\Facades\DB;

/**
 * Mark a Pro purchase paid and move the vendor's pro_until.
 *
 * The one place that does it, whether the gateway called back or an admin
 * recorded a transfer by hand. Safe to call twice: a gateway retries its
 * callback, and the second call must not add another month.
 *
 * Time is added onto what is left, so a vendor who renews a week early loses
 * nothing.
 */
class ActivateVendorPro
{
    public function handle(VendorSubscription $subscription, ?string $gatewayReference = null): VendorSubscription
    {
        $activated = DB::transaction(function () use ($subscription, $gatewayReference): bool {
            $subscription = VendorSubscription::query()->lockForUpdate()->findOrFail($subscription->getKey());

            if ($subscription->isPaid()) {
                return false;
            }

            $vendor = Vendor::query()->lockForUpdate()->findOrFail($subscription->vendor_id);

            $startsAt = $vendor->isPro() ? $vendor->pro_until : now();
            $endsAt = $startsAt->copy()->addMonthsNoOverflow($subscription->plan->months());

            $subscription->update([
                'status' => SubscriptionStatus::Paid,
                'gateway_reference' => $gatewayReference ?? $subscription->gateway_reference,
                'paid_at' => now(),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            $vendor->update(['pro_until' => $endsAt]);

            return true;
        });

        $subscription->refresh();

        if ($activated) {
            $subscription->vendor->user->notify(new ProActivated($subscription));
        }

        return $subscription;
    }

    /**
     * A payment made outside the gateway (a bank transfer, a promotion), which
     * an admin records. It goes through handle() like any other, so the
     * history, the dates and the email are the same.
     */
    public function recordManually(Vendor $vendor, VendorPlan $plan, User $admin, ?string $note = null, ?float $amount = null): VendorSubscription
    {
        $subscription = $vendor->subscriptions()->create([
            'reference' => VendorSubscription::generateReference(),
            'plan' => $plan,
            'amount' => $amount ?? $plan->price(),
            'status' => SubscriptionStatus::Pending,
            'gateway' => VendorSubscription::GATEWAY_MANUAL,
            'added_by' => $admin->getKey(),
            'note' => $note,
        ]);

        return $this->handle($subscription);
    }
}
