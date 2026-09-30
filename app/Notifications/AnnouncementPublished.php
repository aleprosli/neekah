<?php

namespace App\Notifications;

use App\Models\Announcement;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class AnnouncementPublished extends Notification implements ShouldQueue
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
        // A typed-in address has no account, so there is no name to greet.
        $name = $notifiable->name ?? null;

        $message = NeekahMail::to($notifiable)
            ->subject($this->announcement->subject)
            ->greeting($name ? 'Hai '.$name.',' : 'Hai,');

        foreach ($this->announcement->paragraphs() as $paragraph) {
            $message->line($this->mailParagraph($paragraph));
        }

        if ($this->announcement->hasAction()) {
            $message->action($this->announcement->action_label, $this->announcement->action_url);
        }

        return $message;
    }

    /**
     * A paragraph with "- " lines in it is a list. MailMessage::line() folds
     * every line of a string into one, which ran the preset checklists together
     * into a single sentence full of dashes. Those paragraphs are handed over as
     * Markdown instead — escaped line by line, with a blank line wherever text
     * turns into list or back — so the layout's Markdown pass draws a real list.
     */
    private function mailParagraph(string $paragraph): string|HtmlString
    {
        $lines = collect(preg_split('/\R/', $paragraph))->map(fn (string $line): string => trim($line));
        $isItem = fn (string $line): bool => (bool) preg_match('/^[-•*]\s+/u', $line);

        if (! $lines->contains($isItem)) {
            return $paragraph;
        }

        $markdown = [];
        $previousWasItem = null;

        foreach ($lines as $line) {
            $item = $isItem($line);

            if ($previousWasItem !== null && $previousWasItem !== $item) {
                $markdown[] = '';
            }

            $markdown[] = $item ? '- '.e(preg_replace('/^[-•*]\s+/u', '', $line)) : e($line);
            $previousWasItem = $item;
        }

        return new HtmlString(implode("\n", $markdown));
    }
}
