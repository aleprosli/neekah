<?php

namespace App\Enums;

enum GuestStatus: string
{
    case Attending = 'attending';
    case Declined = 'declined';
    case Opened = 'opened';
    case Shared = 'shared';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Attending => 'Hadir',
            self::Declined => 'Tidak hadir',
            self::Opened => 'Pautan peribadi dibuka',
            self::Shared => 'Anda tanda hantar',
            self::Pending => 'Belum ditanda hantar',
        };
    }
}
