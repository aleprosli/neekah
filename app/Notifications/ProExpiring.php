<?php

namespace App\Notifications;

use App\Models\Vendor;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * FPX has no auto-debit, so a vendor has to be told before Pro runs out or the
 * sponsored slot simply disappears one morning.
 */
class ProExpiring extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Vendor $vendor, public int $days) {}

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
            'icon' => '⏳',
            'title_key' => 'notifications.pro_expiring.title',
            'title_params' => ['days' => $this->days],
            'body_key' => 'notifications.pro_expiring.body',
            'body_params' => ['date' => $this->vendor->pro_until->translatedFormat('j F Y')],
            'url' => route('vendor.pro.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return NeekahMail::to($notifiable)
            ->subject(trans_choice('notifications.pro_expiring.subject', $this->days, ['days' => $this->days]))
            ->line(__('notifications.pro_expiring.body', ['date' => $this->vendor->pro_until->translatedFormat('j F Y')]))
            ->line(__('notifications.pro_expiring.renew'))
            ->action(__('notifications.pro_expiring.action'), route('vendor.pro.index'));
    }
}
