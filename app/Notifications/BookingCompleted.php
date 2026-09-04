<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCompleted extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bagaimana majlis anda dengan '.$this->booking->vendor->name.'?')
            ->greeting('Hai '.$notifiable->name.',')
            ->line($this->booking->vendor->name.' telah menandakan booking '.$this->booking->reference.' sebagai selesai.')
            ->line('Kongsi pengalaman anda. Review hanya boleh diberi oleh pengantin yang benar-benar menempah, jadi ulasan anda sangat bermakna kepada pengantin lain.')
            ->action('Beri review', route('bookings.show', $this->booking))
            ->salutation('Terima kasih, Neekah');
    }
}
