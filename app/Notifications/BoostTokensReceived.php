<?php

namespace App\Notifications;

use App\Enums\BoostTokenReason;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Boost tokens landed in a vendor's balance: the welcome gift, the month's
 * Pro tokens, a pack bought, or an admin's grant.
 */
class BoostTokensReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $tokens, public BoostTokenReason $reason, public int $balance) {}

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
            'icon' => '🚀',
            'title_key' => 'notifications.boost_tokens.title',
            'title_params' => ['count' => $this->tokens],
            'body_key' => "notifications.boost_tokens.{$this->reason->value}",
            'body_params' => ['balance' => $this->balance],
            'url' => route('vendor.boost.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return NeekahMail::to($notifiable)
            ->subject(__('notifications.boost_tokens.title', ['count' => $this->tokens]))
            ->line(__("notifications.boost_tokens.{$this->reason->value}", ['balance' => $this->balance]))
            ->line(__('notifications.boost_tokens.how'))
            ->action(__('notifications.boost_tokens.action'), route('vendor.boost.index'));
    }
}
