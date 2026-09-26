<?php

namespace App\Support;

use App\Enums\VendorPlan;

/**
 * Neekah Pro, the paid vendor plan: what it costs. Configured under Admin →
 * Tetapan. Its monthly boost tokens are set with the rest of boosting, in
 * BoostSettings.
 *
 * Switching it off stops new checkouts only. A vendor who already paid keeps
 * what they paid for until pro_until.
 */
class ProSettings extends SettingGroup
{
    public function isEnabled(): bool
    {
        return (bool) $this->value('enabled');
    }

    public function price(VendorPlan $plan): float
    {
        return (float) $this->value($plan->value.'_price');
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
        ];
    }

    protected static function prefix(): string
    {
        return 'pro';
    }
}
