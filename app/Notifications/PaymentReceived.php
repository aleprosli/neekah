<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->payment->booking;

        return (new MailMessage)
            ->subject('Resit '.$this->payment->reference.' · '.$this->payment->type->label().' diterima')
            ->greeting('Hai '.$notifiable->name.',')
            ->line($this->payment->type->label().' sebanyak RM'.number_format((float) $this->payment->amount, 2).' untuk booking '.$booking->reference.' telah diterima.')
            ->line('Vendor: '.$booking->vendor->name.' · Majlis: '.$booking->event_date->translatedFormat('j F Y'))
            ->line('Rujukan gateway: '.$this->payment->gateway_reference)
            ->action('Lihat booking', route('bookings.show', $booking))
            ->salutation('Terima kasih, Neekah');
    }
}
