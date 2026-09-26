<?php

namespace App\Actions;

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Support\Payments\GatewayResult;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The one way a payment becomes paid (or failed), whether the gateway called
 * back, the payer came back with a signed answer, a requery found it, or an
 * admin recorded it by hand. Paid runs once, under a lock, and hands the
 * payment to what its purpose buys: Pro time, boost tokens, a Kenangan album,
 * a confirmed booking. Herepay retries its callback and the payer may come
 * back too, so every path may arrive more than once.
 */
class SettlePayment
{
    /** The payment was paid now. */
    public const PAID = 'paid';

    /** It was already paid; nothing changed. */
    public const ALREADY_PAID = 'already_paid';

    public const FAILED = 'failed';

    public const PENDING = 'pending';

    /** The gateway says paid, but for less than this payment is. */
    public const AMOUNT_MISMATCH = 'amount_mismatch';

    /** What came could not be proved to be the gateway's. */
    public const UNVERIFIED = 'unverified';

    public function __construct(
        private ActivateVendorPro $pro,
        private ActivateBoostPurchase $boost,
        private ActivateCameraAlbum $kenangan,
        private ConfirmOnlineDeposit $deposit,
    ) {}

    /**
     * Apply what a gateway said. The gateway's references are kept even while
     * it is still settling, so a requery later has something to ask by.
     */
    public function apply(Payment $payment, GatewayResult $result): string
    {
        if (! $result->verified) {
            return self::UNVERIFIED;
        }

        $known = array_filter([
            'gateway_reference' => $result->reference,
            'gateway_invoice' => $result->invoice,
            'gateway_transaction_id' => $result->transactionId,
            'gateway_status' => $result->gatewayStatus ? mb_substr($result->gatewayStatus, 0, 60) : null,
            'method' => $result->method,
        ], fn (?string $value): bool => filled($value));

        if ($result->status === GatewayResult::PAID) {
            // A payment for less than the price is not this purchase, whatever it says.
            if ($result->amount !== null && round($result->amount, 2) < round((float) $payment->amount, 2)) {
                $payment->update($known);
                report(new RuntimeException("{$payment->gateway} paid {$result->amount} for {$payment->reference}, which is {$payment->amount}."));

                return self::AMOUNT_MISMATCH;
            }

            return $this->markPaid($payment, $known) ? self::PAID : self::ALREADY_PAID;
        }

        if ($result->status === GatewayResult::FAILED) {
            $payment->update($known + ($payment->isPending() ? ['status' => PaymentStatus::Failed] : []));

            return $payment->isPaid() ? self::ALREADY_PAID : self::FAILED;
        }

        $payment->update($known);

        return self::PENDING;
    }

    /**
     * Mark it paid and give the payer what it bought. False when it already
     * was: the second caller changes nothing and sends nothing.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function markPaid(Payment $payment, array $attributes = []): bool
    {
        $settled = DB::transaction(function () use ($payment, $attributes): bool {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());

            if ($locked->isPaid()) {
                return false;
            }

            $locked->update([...$attributes, 'status' => PaymentStatus::Paid, 'paid_at' => now()]);

            match ($locked->purpose) {
                PaymentPurpose::VendorPro => $this->pro->fulfil($locked),
                PaymentPurpose::BoostTokens => $this->boost->fulfil($locked),
                PaymentPurpose::Kenangan => $this->kenangan->fulfil($locked),
                PaymentPurpose::Booking => $this->deposit->fulfil($locked),
            };

            return true;
        });

        $payment->refresh();

        return $settled;
    }
}
