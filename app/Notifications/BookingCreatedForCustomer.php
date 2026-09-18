<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCreatedForCustomer extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking) {}

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
            'icon' => '🧾',
            'title' => "Booking {$this->booking->reference} dibuat",
            'body' => "Hubungi {$this->booking->vendor->name} untuk berbincang, kemudian rekodkan bayaran anda di sini.",
            'url' => route('bookings.show', $this->booking),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Booking '.$this->booking->reference.' dengan '.$this->booking->vendor->name)
            ->greeting('Hai '.$notifiable->name.',')
            ->line('Booking anda dengan '.$this->booking->vendor->name.' telah direkod.')
            ->line($this->booking->package_name.' · '.$this->booking->event_date->translatedFormat('l, j F Y'))
            ->line('Jumlah pakej RM'.number_format((float) $this->booking->total_amount, 2).'.')
            ->line('Berbincang terus dengan vendor tentang bayaran. Setelah anda membayar, rekodkan bayaran itu di halaman booking dan vendor akan mengesahkannya.')
            ->action('Lihat booking', route('bookings.show', $this->booking))
            ->salutation('Terima kasih, Neekah');
    }
}
