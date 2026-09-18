<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A couple says they have paid. Only the vendor can check their own account, so
 * this asks them to look and confirm.
 */
class PaymentRecorded extends Notification implements ShouldQueue
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
            'icon' => '💸',
            'title' => 'Bayaran direkod: RM'.number_format((float) $this->payment->amount, 2),
            'body' => $this->payment->booking->user->name.' merekodkan bayaran untuk '.$this->payment->booking->reference.'. Sahkan setelah anda semak akaun anda.',
            'url' => route('vendor.bookings.show', $this->payment->booking),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->payment->booking;

        return (new MailMessage)
            ->subject('Bayaran direkod untuk '.$booking->reference)
            ->greeting('Hai '.$notifiable->name.',')
            ->line($booking->user->name.' merekodkan bayaran sebanyak RM'.number_format((float) $this->payment->amount, 2).' untuk '.$booking->reference.'.')
            ->line('Tarikh bayaran yang direkod: '.$this->payment->paid_on->translatedFormat('j F Y').'.')
            ->line('Semak akaun anda, kemudian sahkan bayaran ini. Booking hanya menjadi Confirmed selepas anda mengesahkannya.')
            ->action('Semak bayaran', route('vendor.bookings.show', $booking))
            ->salutation('Terima kasih, Neekah');
    }
}
