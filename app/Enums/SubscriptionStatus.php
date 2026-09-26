<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    /** Checkout started; the vendor has not paid, or the gateway has not told us. */
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('enums.subscription_status.pending'),
            self::Paid => __('enums.subscription_status.paid'),
            self::Failed => __('enums.subscription_status.failed'),
        };
    }

    /** The palette key the badge components use, in Blade and in Vue. */
    public function tone(): string
    {
        return match ($this) {
            self::Paid => 'emerald',
            self::Pending => 'amber',
            self::Failed => 'muted',
        };
    }
}
