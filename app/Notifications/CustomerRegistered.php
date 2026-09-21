<?php

namespace App\Notifications;

use App\Support\ContactSettings;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The welcome a couple gets when they sign up, pointing them at the first
 * thing to do rather than leaving them on an empty dashboard.
 */
class CustomerRegistered extends Notification implements ShouldQueue
{
    use Queueable;

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
            'icon' => '💍',
            'title_key' => 'notifications.customer_registered.title',
            'body_key' => 'notifications.customer_registered.body',
            'url' => route('dashboard'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return NeekahMail::to($notifiable)
            ->subject(__('notifications.customer_registered.subject'))
            ->greeting(__('notifications.welcome', ['name' => $notifiable->name]))
            ->line(__('notifications.customer_registered.intro'))
            ->line(__('notifications.customer_registered.next_step'))
            ->action(__('notifications.actions.open_dashboard'), route('dashboard'))
            ->line(app(ContactSettings::class)->supportSentence());
    }
}
