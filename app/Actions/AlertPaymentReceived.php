<?php

namespace App\Actions;

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Jobs\SendTelegramAlert;
use App\Models\Payment;
use App\Support\Payments\PaymentDocument;
use Illuminate\Support\Traits\Localizable;

/**
 * Tell the admin Telegram chat that money came in: a Kenangan album, Neekah
 * Pro, a boost pack or a couple's booking payment. Called once per payment,
 * by whoever just marked it paid, so a retried callback never alerts twice.
 * Always in Malay, whatever language the payer was browsing in.
 */
class AlertPaymentReceived
{
    use Localizable;

    public function handle(Payment $payment): void
    {
        $this->withLocale('ms', function () use ($payment): void {
            $document = PaymentDocument::for($payment);
            $item = $document->items()[0];
            $payer = $document->billTo();
            $vendor = $payment->purpose === PaymentPurpose::Booking ? $payment->booking?->vendor : null;

            SendTelegramAlert::about('💰 <b>Bayaran diterima: '.e($payment->purpose->label()).'</b>', [
                'Jumlah' => $item['amount'],
                'Item' => $item['description'],
                'Butiran' => $item['detail'],
                'Pembayar' => implode(' · ', array_filter([$payer['name'], ...$payer['lines']])),
                'Vendor' => $vendor?->name,
                'Kaedah' => $this->method($payment),
                'Resit' => $payment->receipt_number ?? $payment->reference,
                'Pautan' => route('admin.payments.show', $payment),
            ]);
        });
    }

    /** How it was paid: FPX and the like for Herepay, else the gateway's name. */
    private function method(Payment $payment): string
    {
        $method = PaymentMethod::tryFrom((string) $payment->method)?->label() ?? $payment->method;

        return implode(' · ', array_filter([ucfirst($payment->gateway), $method]));
    }
}
