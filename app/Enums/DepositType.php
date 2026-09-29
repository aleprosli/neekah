<?php

namespace App\Enums;

/**
 * The vendor's one deposit rule for every package: a share of the price or a
 * fixed sum. Whatever it works out to, it is at least RM1 (Herepay's minimum)
 * and never more than the package itself.
 */
enum DepositType: string
{
    case Percent = 'percent';
    case Fixed = 'fixed';

    public function label(): string
    {
        return __('enums.deposit_type.'.$this->value);
    }

    public function amountFor(float $price, float $value): float
    {
        $amount = match ($this) {
            self::Percent => $price * min(100, max(0, $value)) / 100,
            self::Fixed => $value,
        };

        return round(min(max($amount, 1.0), max($price, 1.0)), 2);
    }
}
