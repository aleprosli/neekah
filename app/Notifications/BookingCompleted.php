<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCompleted extends Notification implements ShouldQueue
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
            'icon' => '⭐',
            'title_key' => 'notifications.booking_completed.title',
            'title_params' => ['vendor' => $this->booking->vendor->name],
            'body_key' => 'notifications.booking_completed.body',
            'url' => route('bookings.show', $this->booking),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return NeekahMail::to($notifiable)
            ->subject(__('notifications.booking_completed.subject', ['vendor' => $this->booking->vendor->name]))
            ->line(__('notifications.booking_completed.intro', ['vendor' => $this->booking->vendor->name, 'reference' => $this->booking->reference]))
            ->line(__('notifications.booking_completed.ask_review'))
            ->action(__('notifications.actions.write_review'), route('bookings.show', $this->booking));
    }
}
