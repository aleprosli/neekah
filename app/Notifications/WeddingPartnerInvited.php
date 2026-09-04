<?php

namespace App\Notifications;

use App\Models\WeddingInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeddingPartnerInvited extends Notification
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

        return (new MailMessage)
            ->subject($this->invitation->inviter->name.' menjemput anda menguruskan majlis "'.$wedding->title.'"')
            ->greeting('Hai!')
            ->line($this->invitation->inviter->name.' menjemput anda menjadi pasangan dalam wedding project di Neekah.')
            ->line($wedding->title.' · '.$wedding->event_date->translatedFormat('l, j F Y').' · '.$wedding->city.', '.$wedding->state)
            ->line('Setelah menerima jemputan, anda berdua akan berkongsi checklist, bajet, tempahan dan pembayaran yang sama.')
            ->action('Terima jemputan', route('invitations.show', $this->invitation))
            ->line('Pautan ini sah selama '.WeddingInvitation::EXPIRES_AFTER_DAYS.' hari. Jika anda tidak mengenali jemputan ini, abaikan emel ini.')
            ->salutation('Terima kasih, Neekah');
    }
}
