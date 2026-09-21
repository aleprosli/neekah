<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCreatedForVendor extends Notification implements ShouldQueue
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
            'title_key' => 'notifications.booking_created_vendor.title',
            'title_params' => ['reference' => $this->booking->reference],
            'body_key' => 'notifications.booking_created_vendor.body',
            'body_params' => ['name' => $this->booking->user->name, 'package' => $this->booking->package_name, 'date' => $this->booking->event_date->translatedFormat('j M Y')],
            'url' => route('vendor.bookings.show', $this->booking),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return NeekahMail::to($notifiable)
            ->subject(__('notifications.booking_created_vendor.subject', ['reference' => $this->booking->reference, 'name' => $this->booking->user->name]))
            ->greeting(__('notifications.congratulations'))
            ->line(__('notifications.booking_created_vendor.intro', ['name' => $this->booking->user->name, 'package' => $this->booking->package_name, 'date' => $this->booking->event_date->translatedFormat('l, j F Y')]))
            ->line(__('notifications.total', ['amount' => 'RM'.number_format((float) $this->booking->total_amount, 2)]))
            ->line(__('notifications.booking_created_vendor.awaiting_payment'))
            ->action(__('notifications.actions.view_booking'), route('vendor.bookings.show', $this->booking));
    }
}
