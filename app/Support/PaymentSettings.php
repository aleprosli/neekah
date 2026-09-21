<?php

namespace App\Support;

use App\Enums\PaymentMethod;

/**
 * Which payment methods couples may use, each switched on or off by an admin.
 *
 * Neekah holds no bank details of its own: with manual transfer the couple pays
 * the vendor directly, so the only thing to configure is what they are told.
 */
class PaymentSettings extends SettingGroup
{
    public function isEnabled(PaymentMethod $method): bool
    {
        return (bool) $this->value($method->settingKey());
    }

    /** Switched on by an admin and actually built, so a couple can use it. */
    public function isOffered(PaymentMethod $method): bool
    {
        return $method->isIntegrated() && $this->isEnabled($method);
    }

    /**
     * @return list<PaymentMethod>
     */
    public function offeredMethods(): array
    {
        return array_values(array_filter(PaymentMethod::cases(), $this->isOffered(...)));
    }

    public function manualTransferEnabled(): bool
    {
        return $this->isOffered(PaymentMethod::ManualTransfer);
    }

    public function instructions(): string
    {
        return $this->string('instructions');
    }

    /**
     * @return array<string, string|bool>
     */
    public static function defaults(): array
    {
        return [
            ...collect(PaymentMethod::cases())
                ->mapWithKeys(fn (PaymentMethod $method): array => [$method->settingKey() => $method === PaymentMethod::ManualTransfer])
                ->all(),
            'instructions' => __('props.vendor_onboarding.manual_instructions'),
        ];
    }

    protected static function prefix(): string
    {
        return 'payments';
    }
}
