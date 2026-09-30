<?php

namespace App\Notifications;

use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The vendor's Google Calendar has not imported for a few hours in a row.
 * Dates already imported stay closed, but new ones are not coming in.
 */
class IcalSyncFailed extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  string  $reason  one of the ical_* error keys from ImportVendorIcal
     */
    public function __construct(public string $reason) {}

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
            'icon' => '⚠️',
            'title_key' => 'notifications.ical_failed.title',
            'body_key' => 'notifications.ical_failed.body',
            'body_params' => ['reason' => __('pages.booking_settings.ical_errors.'.$this->reason)],
            'url' => route('vendor.booking-settings.edit'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return NeekahMail::to($notifiable)
            ->subject(__('notifications.ical_failed.title'))
            ->line(__('notifications.ical_failed.body', ['reason' => __('pages.booking_settings.ical_errors.'.$this->reason)]))
            ->action(__('notifications.ical_failed.action'), route('vendor.booking-settings.edit'));
    }
}
