<?php

namespace App\Support;

use App\Enums\VendorPlan;

/**
 * Neekah Pro, the paid vendor plan: what it costs and how many sponsored slots
 * sit above the listing. Configured under Admin → Tetapan.
 *
 * Switching it off stops new checkouts only. A vendor who already paid keeps
 * what they paid for until pro_until.
 */
class ProSettings extends SettingGroup
{
    /** The most sponsored slots the listing will ever show, whatever is saved. */
    public const MAX_SPONSORED_SLOTS = 6;

    public function isEnabled(): bool
    {
        return (bool) $this->value('enabled');
    }

    public function price(VendorPlan $plan): float
    {
        return (float) $this->value($plan->value.'_price');
    }

    public function sponsoredSlots(): int
    {
        return max(0, min(self::MAX_SPONSORED_SLOTS, (int) $this->value('sponsored_slots')));
    }

    /**
     * @return array<string, int|bool>
     */
    public static function defaults(): array
    {
        return [
            'enabled' => false,
            'monthly_price' => 49,
            'yearly_price' => 490,
            'sponsored_slots' => 3,
        ];
    }

    protected static function prefix(): string
    {
        return 'pro';
    }
}
