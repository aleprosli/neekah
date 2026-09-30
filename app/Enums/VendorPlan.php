<?php

namespace App\Enums;

use App\Support\ProSettings;

/**
 * How long one Pro payment buys. FPX has no auto-debit, so each is a single
 * payment and the vendor pays again when it runs out.
 */
enum VendorPlan: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => __('enums.vendor_plan.monthly'),
            self::Yearly => __('enums.vendor_plan.yearly'),
        };
    }

    public function months(): int
    {
        return match ($this) {
            self::Monthly => 1,
            self::Yearly => 12,
        };
    }

    /** The price today, from Admin → Tetapan → Pro. */
    public function price(): float
    {
        return app(ProSettings::class)->price($this);
    }
}
