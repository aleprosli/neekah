<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCreatedForCustomer extends Notification
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
            ->subject('Booking '.$this->booking->reference.' dengan '.$this->booking->vendor->name)
            ->greeting('Hai '.$notifiable->name.',')
            ->line('Booking anda dengan '.$this->booking->vendor->name.' telah direkod.')
            ->line($this->booking->package_name.' · '.$this->booking->event_date->translatedFormat('l, j F Y'))
            ->line('Jumlah RM'.number_format((float) $this->booking->total_amount, 2).'. Bayar deposit RM'.number_format((float) $this->booking->deposit_amount, 2).' untuk mengesahkan tempahan.')
            ->action('Bayar deposit', route('bookings.show', $this->booking))
            ->salutation('Terima kasih, Neekah');
    }
}
