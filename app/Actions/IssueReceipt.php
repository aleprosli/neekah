<?php

namespace App\Actions;

use App\Models\Payment;
use App\Notifications\PaymentReceipt;
use App\Support\Payments\PaymentDocument;
use App\Support\Payments\ReceiptNumber;
use Illuminate\Support\Facades\DB;

/**
 * Every paid payment gets a receipt: a number of its own, in sequence for
 * the year, and an email to whoever paid. Numbering runs inside the
 * transaction that marks it paid, so a number is never spent on a payment
 * that rolled back; the email goes once, however often the payment is
 * settled again.
 */
class IssueReceipt
{
    /** Give a paid payment its receipt number, if it has none yet. */
    public function number(Payment $payment): void
    {
        if ($payment->receipt_number === null) {
            DB::transaction(fn () => $payment->update(['receipt_number' => ReceiptNumber::next()]));
        }
    }

    /**
     * Email the receipt to the payer. The first send is claimed atomically,
     * so two settlements racing each other send one email; an admin may send
     * it again on purpose.
     */
    public function send(Payment $payment, bool $again = false): bool
    {
        $payer = PaymentDocument::payerOf($payment);

        if (! $payment->isPaid() || $payer === null) {
            return false;
        }

        $claimed = Payment::query()
            ->whereKey($payment->getKey())
            ->when(! $again, fn ($query) => $query->whereNull('receipt_sent_at'))
            ->update(['receipt_sent_at' => now()]);

        if ($claimed === 0) {
            return false;
        }

        $this->number($payment);
        $payer->notify(new PaymentReceipt($payment->fresh()));

        return true;
    }
}
