<?php

namespace App\Notifications;

use App\Models\VendorBoost;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** A vendor's boost runs out tomorrow, while they can still extend it. */
class BoostEnding extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public VendorBoost $boost) {}

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
            'title_key' => 'notifications.boost_ending.title',
            'title_params' => ['category' => $this->boost->category->name],
            'body_key' => 'notifications.boost_ending.body',
            'body_params' => ['date' => $this->boost->ends_at->translatedFormat('j F Y, g:i A')],
            'url' => route('vendor.boost.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return NeekahMail::to($notifiable)
            ->subject(__('notifications.boost_ending.title', ['category' => $this->boost->category->name]))
            ->line(__('notifications.boost_ending.body', ['date' => $this->boost->ends_at->translatedFormat('j F Y, g:i A')]))
            ->action(__('notifications.boost_ending.action'), route('vendor.boost.index'));
    }
}
