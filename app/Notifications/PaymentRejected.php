<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The vendor looked and could not find the money. Said plainly, because the
 * usual reason is a typo in the amount or a transfer that never went through.
 */
class PaymentRejected extends Notification implements ShouldQueue
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
            'icon' => '⚠️',
            'title' => 'Bayaran RM'.number_format((float) $this->payment->amount, 2).' tidak ditemui',
            'body' => 'Vendor tidak menemui bayaran ini dalam akaun mereka. Sila semak dan rekod semula.',
            'url' => route('bookings.show', $this->payment->booking),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->payment->booking;

        return (new MailMessage)
            ->subject('Bayaran '.$this->payment->reference.' tidak dapat disahkan')
            ->greeting('Hai '.$notifiable->name.',')
            ->line($booking->vendor->name.' tidak menemui bayaran RM'.number_format((float) $this->payment->amount, 2).' yang anda rekodkan untuk booking '.$booking->reference.'.')
            ->line('Semak resit dan tarikh bayaran anda, hubungi vendor jika perlu, kemudian rekodkan semula.')
            ->action('Lihat booking', route('bookings.show', $booking))
            ->salutation('Terima kasih, Neekah');
    }
}
