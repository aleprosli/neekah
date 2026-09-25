<?php

namespace App\Support;

/**
 * Boost tokens, set under Admin → Tetapan → Wang → Boost: how many a vendor
 * is given on approval and every month on Pro, the longest boost, and the
 * packs sold. One token lifts a vendor to the top of one category for a day.
 *
 * Switching it off stops new purchases only. Tokens already held can still
 * be spent.
 */
class BoostSettings extends SettingGroup
{
    /** The packs a vendor can buy, each its own pair of settings. */
    public const PACKS = ['small', 'large'];

    public function isEnabled(): bool
    {
        return (bool) $this->value('enabled');
    }

    public function welcomeTokens(): int
    {
        return max(0, (int) $this->value('welcome_tokens'));
    }

    public function proMonthlyTokens(): int
    {
        return max(0, (int) $this->value('pro_monthly_tokens'));
    }

    public function maxDays(): int
    {
        return max(1, (int) $this->value('max_days'));
    }

    /**
     * @return array{tokens: int, price: float}|null
     */
    public function pack(string $pack): ?array
    {
        if (! in_array($pack, self::PACKS, true)) {
            return null;
        }

        return ['tokens' => max(1, (int) $this->value($pack.'_tokens')), 'price' => (float) $this->value($pack.'_price')];
    }

    /**
     * @return array<string, int|bool>
     */
    public static function defaults(): array
    {
        return [
            'enabled' => false,
            'welcome_tokens' => 7,
            'pro_monthly_tokens' => 10,
            'max_days' => 30,
            'small_tokens' => 10,
            'small_price' => 20,
            'large_tokens' => 30,
            'large_price' => 50,
        ];
    }

    protected static function prefix(): string
    {
        return 'boost';
    }
}
