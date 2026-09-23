<?php

namespace App\Notifications;

use App\Models\VendorSubscription;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The receipt for a Pro payment, and the date it now runs to.
 */
class ProActivated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public VendorSubscription $subscription) {}

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
            'icon' => '⭐',
            'title_key' => 'notifications.pro_activated.title',
            'title_params' => [],
            'body_key' => 'notifications.pro_activated.body',
            'body_params' => ['date' => $this->subscription->ends_at->translatedFormat('j F Y')],
            'url' => route('vendor.pro.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return NeekahMail::to($notifiable)
            ->subject(__('notifications.pro_activated.subject'))
            ->line(__('notifications.pro_activated.thanks', ['vendor' => $this->subscription->vendor->name]))
            ->line(__('notifications.pro_activated.receipt', [
                'reference' => $this->subscription->reference,
                'plan' => $this->subscription->plan->label(),
                'amount' => number_format((float) $this->subscription->amount, 2),
            ]))
            ->line(__('notifications.pro_activated.until', ['date' => $this->subscription->ends_at->translatedFormat('j F Y')]))
            ->action(__('notifications.actions.open_dashboard'), route('vendor.pro.index'));
    }
}
