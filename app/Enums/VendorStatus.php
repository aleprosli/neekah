<?php

namespace App\Enums;

enum VendorStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu kelulusan',
            self::Approved => 'Diluluskan',
            self::Rejected => 'Ditolak',
            self::Suspended => 'Digantung',
        };
    }
}
