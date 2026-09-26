<?php

namespace App\Actions;

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\VendorPlan;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\ProActivated;
use Illuminate\Support\Facades\DB;

/**
 * What a paid Neekah Pro payment buys: the plan's months added onto the
 * vendor's pro_until. SettlePayment calls it once, inside its lock, whether
 * the gateway said so or an admin recorded a transfer by hand.
 *
 * Time is added onto what is left, so a vendor who renews a week early loses
 * nothing. The period is written back onto the payment for the history.
 */
class ActivateVendorPro
{
    public function fulfil(Payment $payment): void
    {
        $vendor = Vendor::query()->lockForUpdate()->findOrFail($payment->vendor_id);
        $plan = VendorPlan::from((string) $payment->detail('plan', VendorPlan::Monthly->value));
        $startsAt = $vendor->isPro() ? $vendor->pro_until : now();
        $endsAt = $startsAt->copy()->addMonthsNoOverflow($plan->months());

        $payment->update(['details' => [...($payment->details ?? []), 'starts_at' => $startsAt->toIso8601String(), 'ends_at' => $endsAt->toIso8601String()]]);
        $vendor->update(['pro_until' => $endsAt]);

        DB::afterCommit(fn () => $vendor->user->notify(new ProActivated($payment->fresh())));
    }

    /**
     * A payment made outside the gateway (a bank transfer, a promotion), which
     * an admin records. It settles like any other, so the history, the dates
     * and the email are the same.
     */
    public function recordManually(Vendor $vendor, VendorPlan $plan, User $admin, ?string $note = null, ?float $amount = null): Payment
    {
        $payment = Payment::query()->create([
            'purpose' => PaymentPurpose::VendorPro,
            'vendor_id' => $vendor->id,
            'recorded_by' => $admin->id,
            'amount' => $amount ?? $plan->price(),
            'status' => PaymentStatus::Pending,
            'gateway' => Payment::GATEWAY_MANUAL,
            'method' => 'manual',
            'note' => $note,
            'details' => ['plan' => $plan->value],
        ]);

        PaymentEvent::record($payment, Payment::GATEWAY_MANUAL, PaymentEvent::MANUAL_RECORDED, meta: ['note' => $note]);
        app(SettlePayment::class)->markPaid($payment);

        return $payment->fresh();
    }
}
