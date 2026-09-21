<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A couple says they have paid. Only the vendor can check their own account, so
 * this asks them to look and confirm.
 */
class PaymentRecorded extends Notification implements ShouldQueue
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
            'icon' => '💸',
            'title_key' => 'notifications.payment_recorded.title',
            'title_params' => ['amount' => 'RM'.number_format((float) $this->payment->amount, 2)],
            'body_key' => 'notifications.payment_recorded.body',
            'body_params' => ['name' => $this->payment->booking->user->name, 'reference' => $this->payment->booking->reference],
            'url' => route('vendor.bookings.show', $this->payment->booking),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->payment->booking;

        return NeekahMail::to($notifiable)
            ->subject(__('notifications.payment_recorded.subject', ['reference' => $booking->reference]))
            ->line(__('notifications.payment_recorded.intro', ['name' => $booking->user->name, 'amount' => 'RM'.number_format((float) $this->payment->amount, 2), 'reference' => $booking->reference]))
            ->line(__('notifications.payment_recorded.paid_on', ['date' => $this->payment->paid_on->translatedFormat('j F Y')]))
            ->line(__('notifications.payment_recorded.verify'))
            ->action(__('notifications.actions.check_payment'), route('vendor.bookings.show', $booking));
    }
}
