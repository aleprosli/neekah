<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCreatedForVendor extends Notification
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
            ->subject('Booking baharu '.$this->booking->reference.' daripada '.$this->booking->user->name)
            ->greeting('Tahniah!')
            ->line($this->booking->user->name.' telah menempah '.$this->booking->package_name.' untuk majlis pada '.$this->booking->event_date->translatedFormat('l, j F Y').'.')
            ->line('Jumlah: RM'.number_format((float) $this->booking->total_amount, 2).' · Deposit: RM'.number_format((float) $this->booking->deposit_amount, 2))
            ->line('Booking akan disahkan sebaik sahaja pelanggan membayar deposit.')
            ->action('Lihat booking', route('vendor.bookings.show', $this->booking))
            ->salutation('Terima kasih, Neekah');
    }
}
