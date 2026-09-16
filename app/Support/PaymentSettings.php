<?php

namespace App\Support;

/**
 * How couples may pay. Only manual bank transfer exists today: the couple pays
 * the vendor directly and records what they paid, with a receipt.
 *
 * The bank details here are the platform's own, shown to a couple when they
 * record a payment, so an admin can change them without a deploy.
 */
class PaymentSettings extends SettingGroup
{
    public function manualTransferEnabled(): bool
    {
        return (bool) $this->value('manual_transfer_enabled');
    }

    public function instructions(): string
    {
        return $this->string('instructions');
    }

    /** The account a couple transfers to, when one has been filled in. */
    public function bankAccount(): ?array
    {
        $account = [
            'bank' => $this->string('bank_name'),
            'holder' => $this->string('account_holder'),
            'number' => $this->string('account_number'),
        ];

        return array_filter($account) === [] ? null : $account;
    }

    /**
     * @return array<string, string|bool>
     */
    public static function defaults(): array
    {
        return [
            'manual_transfer_enabled' => true,
            'bank_name' => '',
            'account_holder' => '',
            'account_number' => '',
            'instructions' => 'Bayar terus kepada vendor mengikut persetujuan anda, kemudian rekodkan bayaran itu di sini supaya kedua-dua pihak ada rekod yang sama.',
        ];
    }

    protected static function prefix(): string
    {
        return 'payments';
    }
}
