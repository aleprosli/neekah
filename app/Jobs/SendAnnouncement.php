<?php

namespace App\Jobs;

use App\Enums\AnnouncementStatus;
use App\Models\Announcement;
use App\Notifications\AnnouncementPublished;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

/**
 * Deliver one announcement to its whole audience. Queued and chunked, because
 * "everyone" is a mailing list, not a request: the admin gets their page back
 * the moment they press send.
 */
class SendAnnouncement implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(private readonly Announcement $announcement) {}

    public function handle(): void
    {
        if ($this->announcement->status === AnnouncementStatus::Sent) {
            return;
        }

        $this->announcement->update(['status' => AnnouncementStatus::Sending]);

        $sent = 0;

        $this->announcement->recipientQuery()->chunkById(200, function (Collection $recipients) use (&$sent): void {
            Notification::send($recipients, new AnnouncementPublished($this->announcement));
            $sent += $recipients->count();
        });

        // Addresses typed in by hand that belong to no account: email only,
        // because there is no bell to put anything in.
        foreach ($this->announcement->addressesWithoutAccounts() as $address) {
            Notification::route('mail', $address)->notify(new AnnouncementPublished($this->announcement));
            $sent++;
        }

        $this->announcement->update([
            'status' => AnnouncementStatus::Sent,
            'recipients_count' => $sent,
            'sent_at' => now(),
        ]);
    }

    /**
     * A failed run leaves the record honest rather than stuck on "sending",
     * so an admin can see it never went out and write it again.
     */
    public function failed(?\Throwable $exception): void
    {
        $this->announcement->update(['status' => AnnouncementStatus::Draft]);
    }
}
