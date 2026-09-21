<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmed extends Notification implements ShouldQueue
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
            'icon' => '✅',
            'title_key' => 'notifications.booking_confirmed.title',
            'title_params' => ['reference' => $this->booking->reference],
            'body_key' => 'notifications.booking_confirmed.body',
            'body_params' => ['date' => $this->booking->event_date->translatedFormat('j M Y')],
            'url' => $notifiable->isVendor() ? route('vendor.bookings.show', $this->booking) : route('bookings.show', $this->booking),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isVendor = $notifiable->isVendor();

        return NeekahMail::to($notifiable)
            ->subject(__('notifications.booking_confirmed.subject', ['reference' => $this->booking->reference]))
            ->line(__('notifications.booking_confirmed.intro', ['reference' => $this->booking->reference]))
            ->line(($isVendor ? __('notifications.customer_is', ['name' => $this->booking->user->name]) : __('notifications.vendor_is', ['name' => $this->booking->vendor->name])).' · '.$this->booking->package_name)
            ->line(__('notifications.event_date', ['date' => $this->booking->event_date->translatedFormat('l, j F Y')]))
            ->line(__('notifications.booking_confirmed.outstanding', ['amount' => 'RM'.number_format($this->booking->outstandingAmount(), 2)]))
            ->action(__('notifications.actions.view_booking'), $isVendor ? route('vendor.bookings.show', $this->booking) : route('bookings.show', $this->booking));
    }
}
