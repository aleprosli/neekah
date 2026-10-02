<?php

namespace App\Enums;

/**
 * Where a contract stands. Only a draft can be changed: once sent, the
 * client must sign exactly what they read, so a change means voiding it and
 * sending a copy.
 */
enum ContractStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Signed = 'signed';
    case Void = 'void';

    public function label(): string
    {
        return __('enums.contract_status.'.$this->value);
    }

    /** The palette key the badge components use, in Blade and in Vue. */
    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'muted',
            self::Sent => 'sky',
            self::Signed => 'emerald',
            self::Void => 'amber',
        };
    }
}
