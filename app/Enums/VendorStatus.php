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
            self::Pending => __('enums.vendor_status.pending'),
            self::Approved => __('enums.vendor_status.approved'),
            self::Rejected => __('enums.vendor_status.rejected'),
            self::Suspended => __('enums.vendor_status.suspended'),
        };
    }

    /** The palette key the badge components use, in Blade and in Vue. */
    public function tone(): string
    {
        return match ($this) {
            self::Approved => 'emerald',
            self::Pending => 'amber',
            self::Rejected, self::Suspended => 'muted',
        };
    }
}
