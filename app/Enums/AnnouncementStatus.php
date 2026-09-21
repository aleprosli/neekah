<?php

namespace App\Enums;

enum AnnouncementStatus: string
{
    case Draft = 'draft';
    case Sending = 'sending';
    case Sent = 'sent';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('enums.announcement_status.draft'),
            self::Sending => __('enums.announcement_status.sending'),
            self::Sent => __('enums.announcement_status.sent'),
        };
    }

    /** The palette key the badge components use, in Blade and in Vue. */
    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'muted',
            self::Sending => 'amber',
            self::Sent => 'emerald',
        };
    }
}
