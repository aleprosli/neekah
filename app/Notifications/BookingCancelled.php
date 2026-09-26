<?php

namespace App\Notifications;

use App\Enums\CancellationReason;
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

    public function __construct(
        public Booking $booking,
        public ?User $canceller,
        public ?string $reason = null,
        public CancellationReason $why = CancellationReason::Couple,
    ) {}

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
            'body_key' => $this->why === CancellationReason::Expired ? 'notifications.booking_cancelled.expired_body' : 'notifications.booking_cancelled.body',
            'body_params' => ['name' => $this->cancellerName(), 'package' => $this->booking->package_name],
            'url' => $this->url($notifiable),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = NeekahMail::to($notifiable)
            ->subject(__('notifications.booking_cancelled.subject', ['reference' => $this->booking->reference]))
            ->line($this->why === CancellationReason::Expired
                ? __('notifications.booking_cancelled.expired_intro', ['reference' => $this->booking->reference])
                : __('notifications.booking_cancelled.intro', ['name' => $this->cancellerName(), 'reference' => $this->booking->reference]))
            ->line($this->booking->package_name.' · '.$this->booking->event_date->translatedFormat('l, j F Y'));

        if ($this->reason) {
            $message->line(__('notifications.booking_cancelled.reason', ['reason' => $this->reason]));
        }

        // A vendor calling off a booking with a paid deposit owes it back; in
        // every other case nothing has been paid.
        $refund = $this->why === CancellationReason::Vendor && $this->booking->paidAmount() > 0
            ? 'notifications.booking_cancelled.vendor_refunds'
            : 'notifications.booking_cancelled.no_refund';

        return $message
            ->line(__($refund))
            ->action(__('notifications.actions.view_booking'), $this->url($notifiable));
    }

    private function cancellerName(): string
    {
        return $this->canceller?->name ?? __('notifications.booking_cancelled.system');
    }

    private function url(object $notifiable): string
    {
        return $notifiable->isVendor()
            ? route('vendor.bookings.show', $this->booking)
            : route('bookings.show', $this->booking);
    }
}
