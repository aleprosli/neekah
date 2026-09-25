<?php

namespace App\Notifications;

use App\Models\CameraAlbum;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** The album's ZIP is ready to download. */
class CameraExportReady extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CameraAlbum $album) {}

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
            'icon' => '🗜️',
            'title_key' => 'notifications.camera_export.title',
            'body_key' => 'notifications.camera_export.body',
            'body_params' => ['date' => $this->album->expires_at?->translatedFormat('j F Y') ?? ''],
            'url' => route('camera.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return NeekahMail::to($notifiable)
            ->subject(__('notifications.camera_export.title'))
            ->line(__('notifications.camera_export.body', ['date' => $this->album->expires_at?->translatedFormat('j F Y') ?? '']))
            ->action(__('notifications.camera_activated.action'), route('camera.index'));
    }
}
