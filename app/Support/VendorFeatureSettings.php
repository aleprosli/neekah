<?php

namespace App\Support;

use App\Enums\VendorFeature;

/**
 * Which vendor features each plan opens, set under Admin → Ciri vendor. One
 * row per "vendor_features.<plan>_<feature>". Everything is open by default
 * (VendorFeature::openByDefault), except online booking, which only Pro opens.
 *
 * A vendor's own overrides (vendors.feature_overrides) win over these; see
 * Vendor::hasFeature().
 */
class VendorFeatureSettings extends SettingGroup
{
    public const BASIC = 'basic';

    public const PRO = 'pro';

    public const PLANS = [self::BASIC, self::PRO];

    public function allows(string $plan, VendorFeature $feature): bool
    {
        return (bool) $this->value(self::key($plan, $feature));
    }

    public static function key(string $plan, VendorFeature $feature): string
    {
        return $plan.'_'.$feature->value;
    }

    /**
     * @return array<string, bool>
     */
    public static function defaults(): array
    {
        return collect(self::PLANS)
            ->crossJoin(VendorFeature::cases())
            ->mapWithKeys(fn (array $pair): array => [self::key($pair[0], $pair[1]) => $pair[1]->openByDefault($pair[0])])
            ->all();
    }

    protected static function prefix(): string
    {
        return 'vendor_features';
    }
}
