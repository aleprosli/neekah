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
     * Pro Elite: Pro vendors whose tier is Top or Recommended. It is earned by
     * performance, never bought — see Vendor::isElite().
     */
    public function eliteEnabled(): bool
    {
        return (bool) $this->value('elite_enabled');
    }

    /** Boost tokens an Elite vendor gets every 30 days, on top of Pro's. */
    public function eliteBonusTokens(): int
    {
        return max(0, (int) $this->value('elite_bonus_tokens'));
    }

    /** How many vendors the "Pilihan Elite" row on the marketplace shows. */
    public function eliteRowSize(): int
    {
        return max(0, min(12, (int) $this->value('elite_row_size')));
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
            'elite_enabled' => true,
            'elite_bonus_tokens' => 10,
            'elite_row_size' => 6,
        ];
    }

    protected static function prefix(): string
    {
        return 'pro';
    }
}
