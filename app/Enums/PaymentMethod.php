<?php

namespace App\Enums;

/**
 * Every way a couple could pay, each switched on or off by an admin under
 * Admin → Tetapan → Bayaran.
 *
 * Only manual transfer is built. A gateway can be switched on ahead of its
 * integration, but it is not offered to anyone until isIntegrated() says so.
 */
enum PaymentMethod: string
{
    case ManualTransfer = 'manual_transfer';
    case Billplz = 'billplz';
    case Bayarcash = 'bayarcash';
    case Stripe = 'stripe';

    public function label(): string
    {
        return match ($this) {
            self::ManualTransfer => __('enums.payment_method.manual_transfer'),
            self::Billplz => __('enums.payment_method.billplz'),
            self::Bayarcash => __('enums.payment_method.bayarcash'),
            self::Stripe => __('enums.payment_method.stripe'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ManualTransfer => __('enums.payment_method_desc.manual_transfer'),
            self::Billplz => __('enums.payment_method_desc.billplz'),
            self::Bayarcash => __('enums.payment_method_desc.bayarcash'),
            self::Stripe => __('enums.payment_method_desc.stripe'),
        };
    }

    /** Whether the checkout for this method exists yet. */
    public function isIntegrated(): bool
    {
        return $this === self::ManualTransfer;
    }

    /** The settings key, without the payments prefix, that switches it on. */
    public function settingKey(): string
    {
        return $this->value.'_enabled';
    }
}
