<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking, public User $canceller, public ?string $reason = null) {}

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
            'icon' => '🚫',
            'title' => "Booking {$this->booking->reference} dibatalkan",
            'body' => $this->canceller->name.' membatalkan tempahan '.$this->booking->package_name.'.',
            'url' => $this->url($notifiable),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Booking '.$this->booking->reference.' dibatalkan')
            ->greeting('Hai '.$notifiable->name.',')
            ->line($this->canceller->name.' telah membatalkan booking '.$this->booking->reference.'.')
            ->line($this->booking->package_name.' · '.$this->booking->event_date->translatedFormat('l, j F Y'));

        if ($this->reason) {
            $message->line('Sebab: '.$this->reason);
        }

        return $message
            ->line('Tiada bayaran yang disahkan pada booking ini, jadi tiada apa yang perlu dipulangkan melalui Neekah.')
            ->action('Lihat booking', $this->url($notifiable))
            ->salutation('Terima kasih, Neekah');
    }

    private function url(object $notifiable): string
    {
        return $notifiable->isVendor()
            ? route('vendor.bookings.show', $this->booking)
            : route('bookings.show', $this->booking);
    }
}
