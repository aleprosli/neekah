<?php

namespace App\Notifications;

use App\Enums\BookingStatus;
use App\Enums\PaymentPurpose;
use App\Models\Payment;
use App\Support\NeekahMail;
use App\Support\Payments\PaymentDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * The receipt for a paid payment, in full in the email itself, with a link
 * to the page that prints it or saves it as a PDF.
 */
class PaymentReceipt extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $document = PaymentDocument::for($this->payment);

        return NeekahMail::to($notifiable)
            ->subject(__('notifications.payment_receipt.subject', ['number' => $document->number(), 'app' => config('app.name')]))
            ->markdown('mail.payments.receipt', [
                'document' => $document,
                'next' => $this->nextLine(),
                'url' => route('payments.document', $this->payment),
            ]);
    }

    /** What the payment has now done for them, in one sentence. */
    private function nextLine(): ?string
    {
        $payment = $this->payment;

        return match ($payment->purpose) {
            PaymentPurpose::VendorPro => $payment->detail('ends_at')
                ? __('notifications.payment_receipt.next_pro', ['date' => Carbon::parse($payment->detail('ends_at'))->translatedFormat('j F Y')])
                : null,
            PaymentPurpose::BoostTokens => __('notifications.payment_receipt.next_boost', ['count' => (int) $payment->detail('tokens', 0)]),
            PaymentPurpose::Kenangan => __('notifications.payment_receipt.next_kenangan'),
            PaymentPurpose::Booking => $payment->booking?->status === BookingStatus::Confirmed
                ? __('notifications.payment_receipt.next_booking', ['vendor' => $payment->booking->vendor->name])
                : null,
        };
    }
}
