<?php

namespace App\Notifications;

use App\Models\WeddingInvitation;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeddingPartnerInvited extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public WeddingInvitation $invitation) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $wedding = $this->invitation->wedding;

        return NeekahMail::to($notifiable)
            ->subject(__('notifications.partner_invited.subject', ['name' => $this->invitation->inviter->name, 'wedding' => $wedding->title]))
            ->greeting(__('notifications.greeting_plain'))
            ->line(__('notifications.partner_invited.intro', ['name' => $this->invitation->inviter->name]))
            ->line($wedding->title.' · '.$wedding->event_date->translatedFormat('l, j F Y').' · '.$wedding->city.', '.$wedding->state)
            ->line(__('notifications.partner_invited.shared'))
            ->action(__('notifications.actions.accept_invitation'), route('invitations.show', $this->invitation))
            ->line(__('notifications.partner_invited.expires', ['days' => WeddingInvitation::EXPIRES_AFTER_DAYS]));
    }
}
