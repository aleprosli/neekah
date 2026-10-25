<?php

namespace App\Enums;

enum ThreadsPostStatus: string
{
    /** Waiting for its time. */
    case Scheduled = 'scheduled';

    /** Threads has the container and is still fetching its image. */
    case Processing = 'processing';

    case Published = 'published';

    /** Threads refused it, or three tries in a row did not get through. */
    case Failed = 'failed';

    /** Its time passed while nothing was publishing, so it was not sent late. */
    case Missed = 'missed';

    /**
     * Taken off the schedule by an admin. The row stays, so the planner sees
     * the slot as decided and does not fill it again.
     */
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => __('enums.threads_post_status.scheduled'),
            self::Processing => __('enums.threads_post_status.processing'),
            self::Published => __('enums.threads_post_status.published'),
            self::Failed => __('enums.threads_post_status.failed'),
            self::Missed => __('enums.threads_post_status.missed'),
            self::Cancelled => __('enums.threads_post_status.cancelled'),
        };
    }

    /** The palette key the badge components use, in Blade and in Vue. */
    public function tone(): string
    {
        return match ($this) {
            self::Scheduled => 'sky',
            self::Processing => 'amber',
            self::Published => 'emerald',
            self::Failed => 'red',
            self::Missed, self::Cancelled => 'muted',
        };
    }

    /** Whether an admin may still change the post before it goes out. */
    public function isEditable(): bool
    {
        return in_array($this, [self::Scheduled, self::Failed, self::Missed], true);
    }
}
