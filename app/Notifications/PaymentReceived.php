<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment) {}

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
            'icon' => '💰',
            'title' => 'Bayaran RM'.number_format((float) $this->payment->amount, 2).' disahkan',
            'body' => "Vendor mengesahkan bayaran anda untuk booking {$this->payment->booking->reference}.",
            'url' => route('bookings.show', $this->payment->booking),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->payment->booking;

        return (new MailMessage)
            ->subject('Bayaran '.$this->payment->reference.' disahkan')
            ->greeting('Hai '.$notifiable->name.',')
            ->line($booking->vendor->name.' mengesahkan bayaran RM'.number_format((float) $this->payment->amount, 2).' untuk booking '.$booking->reference.'.')
            ->line('Majlis: '.$booking->event_date->translatedFormat('j F Y').' · Rujukan bayaran: '.$this->payment->reference)
            ->action('Lihat booking', route('bookings.show', $booking))
            ->salutation('Terima kasih, Neekah');
    }
}
