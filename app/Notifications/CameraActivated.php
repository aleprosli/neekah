<?php

namespace App\Notifications;

use App\Models\CameraAlbum;
use App\Models\CameraPurchase;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The receipt for Kamera Majlis, with the guest link and until when the
 * album is kept.
 */
class CameraActivated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CameraPurchase $purchase) {}

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
            'icon' => '📸',
            'title_key' => 'notifications.camera_activated.title',
            'title_params' => ['tier' => $this->purchase->tier->label()],
            'body_key' => 'notifications.camera_activated.body',
            'body_params' => ['date' => $this->album()?->expires_at?->translatedFormat('j F Y') ?? ''],
            'url' => $this->albumUrl(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return NeekahMail::to($notifiable)
            ->subject(__('notifications.camera_activated.title', ['tier' => $this->purchase->tier->label()]))
            ->line(__('notifications.camera_activated.receipt', [
                'reference' => $this->purchase->reference,
                'amount' => number_format((float) $this->purchase->amount, 2),
            ]))
            ->line(__('notifications.camera_activated.body', ['date' => $this->album()?->expires_at?->translatedFormat('j F Y') ?? '']))
            ->action(__('notifications.camera_activated.action'), $this->albumUrl());
    }

    private function albumUrl(): string
    {
        return $this->album() ? route('camera.album', $this->album()) : route('camera.index');
    }

    private function album(): ?CameraAlbum
    {
        return $this->purchase->album;
    }
}
