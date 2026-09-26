<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A couple sent a deposit receipt two days ago and the vendor has not checked
 * it. The booking holds its date meanwhile, so it should not wait for ever.
 */
class ManualDepositAwaitingVerification extends Notification implements ShouldQueue
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
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => '🧾',
            'title_key' => 'notifications.deposit_waiting.title',
            'title_params' => ['reference' => $this->payment->booking->reference],
            'body_key' => 'notifications.deposit_waiting.body',
            'body_params' => ['amount' => 'RM'.number_format((float) $this->payment->amount, 2)],
            'url' => route('vendor.bookings.show', $this->payment->booking),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return NeekahMail::to($notifiable)
            ->subject(__('notifications.deposit_waiting.title', ['reference' => $this->payment->booking->reference]))
            ->line(__('notifications.deposit_waiting.body', ['amount' => 'RM'.number_format((float) $this->payment->amount, 2)]))
            ->action(__('notifications.actions.view_booking'), route('vendor.bookings.show', $this->payment->booking));
    }
}
