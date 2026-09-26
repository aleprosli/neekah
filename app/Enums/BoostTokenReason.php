<?php

namespace App\Enums;

/** Why a vendor's boost tokens went up or down. */
enum BoostTokenReason: string
{
    case Welcome = 'welcome';
    case ProMonthly = 'pro_monthly';

    /** Pro Elite's bonus, on top of Pro's, every 30 days while the vendor is Elite. */
    case EliteMonthly = 'elite_monthly';
    case Purchase = 'purchase';
    case Admin = 'admin';
    case Spend = 'spend';
    case Refund = 'refund';

    public function label(): string
    {
        return __('enums.boost_token_reason.'.$this->value);
    }
}
