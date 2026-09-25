<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A deposit arrived after its hold ran out and the date went to someone else.
 * The money is in the vendor's account, so the vendor refunds it; Neekah only
 * says so, to both sides.
 */
class DepositNeedsRefund extends Notification implements ShouldQueue
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
            'icon' => '↩️',
            'title_key' => 'notifications.deposit_refund.title',
            'title_params' => ['reference' => $this->payment->booking->reference],
            'body_key' => 'notifications.deposit_refund.body',
            'body_params' => $this->params(),
            'url' => $this->url($notifiable),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return NeekahMail::to($notifiable)
            ->subject(__('notifications.deposit_refund.subject', ['reference' => $this->payment->booking->reference]))
            ->line(__('notifications.deposit_refund.body', $this->params()))
            ->action(__('notifications.actions.view_booking'), $this->url($notifiable));
    }

    /**
     * @return array<string, string>
     */
    private function params(): array
    {
        return [
            'amount' => 'RM'.number_format((float) $this->payment->amount, 2),
            'vendor' => $this->payment->booking->vendor->name,
            'date' => $this->payment->booking->event_date->translatedFormat('j M Y'),
        ];
    }

    private function url(object $notifiable): string
    {
        return $notifiable->isVendor()
            ? route('vendor.bookings.show', $this->payment->booking)
            : route('bookings.show', $this->payment->booking);
    }
}
