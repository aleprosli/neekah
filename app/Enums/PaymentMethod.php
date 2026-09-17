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
            self::ManualTransfer => 'Rekod bayaran manual',
            self::Billplz => 'Billplz',
            self::Bayarcash => 'Bayarcash',
            self::Stripe => 'Stripe',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ManualTransfer => 'Pengantin membayar terus kepada vendor, merekodkan bayaran berserta resit, dan vendor mengesahkannya.',
            self::Billplz => 'FPX dan kad melalui Billplz.',
            self::Bayarcash => 'FPX, DuitNow dan kad melalui Bayarcash.',
            self::Stripe => 'Kad kredit dan debit melalui Stripe.',
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
