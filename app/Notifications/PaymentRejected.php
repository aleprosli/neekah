<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The vendor looked and could not find the money. Said plainly, because the
 * usual reason is a typo in the amount or a transfer that never went through.
 */
class PaymentRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * @return array<string, string>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => '⚠️',
            'title_key' => 'notifications.payment_rejected.title',
            'title_params' => ['amount' => 'RM'.number_format((float) $this->payment->amount, 2)],
            'body_key' => 'notifications.payment_rejected.body',
            'url' => route('bookings.show', $this->payment->booking),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->payment->booking;

        return NeekahMail::to($notifiable)
            ->subject(__('notifications.payment_rejected.subject', ['reference' => $this->payment->reference]))
            ->line(__('notifications.payment_rejected.intro', ['vendor' => $booking->vendor->name, 'amount' => 'RM'.number_format((float) $this->payment->amount, 2), 'reference' => $booking->reference]))
            ->line(__('notifications.payment_rejected.what_to_do'))
            ->action(__('notifications.actions.view_booking'), route('bookings.show', $booking));
    }
}
