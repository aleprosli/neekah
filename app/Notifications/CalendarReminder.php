<?php

namespace App\Notifications;

use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Couples book straight off a vendor's calendar, so a date taken on WhatsApp
 * must be closed here. `moment` is "weekly", "pausing" (two days before
 * online booking pauses) or "paused".
 */
class CalendarReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $moment) {}

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
            'icon' => $this->moment === 'paused' ? '⏸️' : '📅',
            'title_key' => "notifications.calendar_reminder.{$this->moment}_title",
            'body_key' => "notifications.calendar_reminder.{$this->moment}_body",
            'url' => route('vendor.availability.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return NeekahMail::to($notifiable)
            ->subject(__("notifications.calendar_reminder.{$this->moment}_title"))
            ->line(__("notifications.calendar_reminder.{$this->moment}_body"))
            ->action(__('notifications.calendar_reminder.action'), route('vendor.availability.index'));
    }
}
