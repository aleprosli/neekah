<?php

namespace App\Notifications;

use App\Models\CameraAlbum;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A Kamera Majlis album is only kept for a while after the event, so the
 * couple is told when to download it. `moment` is "after_event" (the day
 * after), "expiring_7" and "expiring_1" (days before it is deleted), or
 * "purged".
 */
class CameraRetentionNotice extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CameraAlbum $album, public string $moment) {}

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
            'icon' => $this->moment === 'purged' ? '🗑️' : '📸',
            'title_key' => "notifications.camera_retention.{$this->moment}_title",
            'body_key' => "notifications.camera_retention.{$this->moment}_body",
            'body_params' => $this->params(),
            'url' => route('camera.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return NeekahMail::to($notifiable)
            ->subject(__("notifications.camera_retention.{$this->moment}_title"))
            ->line(__("notifications.camera_retention.{$this->moment}_body", $this->params()))
            ->action(__('notifications.camera_retention.action'), route('camera.index'));
    }

    /**
     * @return array{date: string, count: int}
     */
    private function params(): array
    {
        return [
            'date' => $this->album->expires_at?->translatedFormat('j F Y') ?? '',
            'count' => $this->album->photos_count + $this->album->videos_count,
        ];
    }
}
