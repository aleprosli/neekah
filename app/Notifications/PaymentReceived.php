<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    /**
     * The email is the receipt (PaymentReceipt, sent by IssueReceipt); this
     * only rings the bell.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, string>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => '💰',
            'title_key' => 'notifications.payment_received.title',
            'title_params' => ['amount' => 'RM'.number_format((float) $this->payment->amount, 2)],
            'body_key' => 'notifications.payment_received.body',
            'body_params' => ['reference' => $this->payment->booking->reference],
            'url' => route('bookings.show', $this->payment->booking),
        ];
    }
}
