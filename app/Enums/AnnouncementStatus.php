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
            self::Draft => 'Draf',
            self::Sending => 'Sedang dihantar',
            self::Sent => 'Dihantar',
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
