<?php

namespace App\Notifications;

use App\Enums\DepositChannel;
use App\Models\Booking;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * An online booking is waiting for its deposit: how much, by when, and how,
 * because the date is released if it is not paid in time.
 */
class BookingHeldForCustomer extends Notification implements ShouldQueue
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
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => '⏳',
            'title_key' => 'notifications.booking_held.title',
            'title_params' => ['reference' => $this->booking->reference],
            'body_key' => 'notifications.booking_held.body',
            'body_params' => $this->params(),
            'url' => route('bookings.show', $this->booking),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return NeekahMail::to($notifiable)
            ->subject(__('notifications.booking_held.subject', ['vendor' => $this->booking->vendor->name]))
            ->line(__('notifications.booking_held.intro', ['vendor' => $this->booking->vendor->name]))
            ->line($this->booking->package_name.' · '.$this->booking->event_date->translatedFormat('l, j F Y'))
            ->line(__('notifications.booking_held.body', $this->params()))
            ->line(__($this->booking->payment_mode === DepositChannel::Herepay ? 'notifications.booking_held.pay_online' : 'notifications.booking_held.pay_transfer'))
            ->action(__('notifications.actions.view_booking'), route('bookings.show', $this->booking));
    }

    /**
     * @return array<string, string>
     */
    private function params(): array
    {
        return [
            'deposit' => 'RM'.number_format((float) $this->booking->deposit_amount, 2),
            'deadline' => $this->booking->hold_expires_at?->translatedFormat('j M Y, g:i A') ?? '',
        ];
    }
}
