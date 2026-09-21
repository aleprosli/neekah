<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Support\NeekahMail;
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
            'title_key' => 'notifications.booking_created_customer.title',
            'title_params' => ['reference' => $this->booking->reference],
            'body_key' => 'notifications.booking_created_customer.body',
            'body_params' => ['vendor' => $this->booking->vendor->name],
            'url' => route('bookings.show', $this->booking),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return NeekahMail::to($notifiable)
            ->subject(__('notifications.booking_created_customer.subject', ['reference' => $this->booking->reference, 'vendor' => $this->booking->vendor->name]))
            ->line(__('notifications.booking_created_customer.intro', ['vendor' => $this->booking->vendor->name]))
            ->line($this->booking->package_name.' · '.$this->booking->event_date->translatedFormat('l, j F Y'))
            ->line(__('notifications.package_total', ['amount' => 'RM'.number_format((float) $this->booking->total_amount, 2)]))
            ->line(__('notifications.booking_created_customer.how_to_pay'))
            ->action(__('notifications.actions.view_booking'), route('bookings.show', $this->booking));
    }
}
