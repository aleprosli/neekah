<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmed extends Notification
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
        $isVendor = $notifiable->isVendor();

        return (new MailMessage)
            ->subject('Booking '.$this->booking->reference.' disahkan')
            ->greeting('Hai '.$notifiable->name.',')
            ->line('Deposit telah dibayar dan booking '.$this->booking->reference.' kini disahkan.')
            ->line(($isVendor ? 'Pelanggan: '.$this->booking->user->name : 'Vendor: '.$this->booking->vendor->name).' · '.$this->booking->package_name)
            ->line('Tarikh majlis: '.$this->booking->event_date->translatedFormat('l, j F Y'))
            ->line('Baki RM'.number_format($this->booking->balanceAmount(), 2).' perlu dijelaskan sebelum majlis.')
            ->action('Lihat booking', $isVendor ? route('vendor.bookings.show', $this->booking) : route('bookings.show', $this->booking))
            ->salutation('Terima kasih, Neekah');
    }
}
