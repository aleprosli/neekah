<?php

namespace App\Enums;

enum VendorTier: string
{
    case New = 'new';
    case Verified = 'verified';
    case Trusted = 'trusted';
    case Top = 'top';
    case Recommended = 'recommended';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Verified => 'Verified',
            self::Trusted => 'Trusted',
            self::Top => 'Top',
            self::Recommended => 'Recommended',
        };
    }

    /**
     * Position in the ranking ladder, 0 for New up to 4 for Recommended.
     */
    public function rank(): int
    {
        return match ($this) {
            self::New => 0,
            self::Verified => 1,
            self::Trusted => 2,
            self::Top => 3,
            self::Recommended => 4,
        };
    }
}
