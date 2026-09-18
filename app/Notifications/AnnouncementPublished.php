<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class AnnouncementPublished extends Notification
{
    use Queueable;

    /**
     * @param  bool  $mailOnly  A test send an admin reads in their own inbox;
     *                          it has no business in anybody's notification bell.
     */
    public function __construct(public Announcement $announcement, public bool $mailOnly = false) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->mailOnly ? ['mail'] : ['mail', 'database'];
    }

    /**
     * @return array<string, string>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => '📣',
            'title' => $this->announcement->subject,
            'body' => Str::limit(Str::squish($this->announcement->body), 140),
            'url' => $this->announcement->hasAction()
                ? $this->announcement->action_url
                : route('notifications.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->announcement->subject)
            ->greeting('Hai '.$notifiable->name.',');

        foreach ($this->announcement->paragraphs() as $paragraph) {
            $message->line($paragraph);
        }

        if ($this->announcement->hasAction()) {
            $message->action($this->announcement->action_label, $this->announcement->action_url);
        }

        return $message;
    }
}
