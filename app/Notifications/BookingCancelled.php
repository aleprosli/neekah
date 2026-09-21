<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\User;
use App\Support\NeekahMail;
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
            'title_key' => 'notifications.booking_cancelled.title',
            'title_params' => ['reference' => $this->booking->reference],
            'body_key' => 'notifications.booking_cancelled.body',
            'body_params' => ['name' => $this->canceller->name, 'package' => $this->booking->package_name],
            'url' => $this->url($notifiable),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = NeekahMail::to($notifiable)
            ->subject(__('notifications.booking_cancelled.subject', ['reference' => $this->booking->reference]))
            ->line(__('notifications.booking_cancelled.intro', ['name' => $this->canceller->name, 'reference' => $this->booking->reference]))
            ->line($this->booking->package_name.' · '.$this->booking->event_date->translatedFormat('l, j F Y'));

        if ($this->reason) {
            $message->line(__('notifications.booking_cancelled.reason', ['reason' => $this->reason]));
        }

        return $message
            ->line(__('notifications.booking_cancelled.no_refund'))
            ->action(__('notifications.actions.view_booking'), $this->url($notifiable));
    }

    private function url(object $notifiable): string
    {
        return $notifiable->isVendor()
            ? route('vendor.bookings.show', $this->booking)
            : route('bookings.show', $this->booking);
    }
}
