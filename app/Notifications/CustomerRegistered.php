<?php

namespace App\Notifications;

use App\Support\ContactSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The welcome a couple gets when they sign up, pointing them at the first
 * thing to do rather than leaving them on an empty dashboard.
 */
class CustomerRegistered extends Notification implements ShouldQueue
{
    use Queueable;

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
            'icon' => '💍',
            'title' => 'Selamat datang ke Neekah',
            'body' => 'Mulakan dengan menetapkan tarikh majlis anda, kemudian cari vendor.',
            'url' => route('dashboard'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Selamat datang ke Neekah')
            ->greeting('Selamat datang, '.$notifiable->name.'!')
            ->line('Akaun anda sudah sedia. Neekah mengumpulkan semua urusan majlis anda di satu tempat: cari dan tempah vendor yang disahkan, jejak bajet, checklist dan timeline, dan hantar kad jemputan digital.')
            ->line('Mulakan dengan menetapkan tarikh majlis anda. Selepas itu kami boleh cadangkan vendor yang masih kosong pada tarikh tersebut.')
            ->action('Buka dashboard', route('dashboard'))
            ->line(app(ContactSettings::class)->supportSentence())
            ->salutation('Terima kasih, Neekah');
    }
}
