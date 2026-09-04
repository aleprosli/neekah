<?php

namespace App\Enums;

enum PaymentType: string
{
    case Deposit = 'deposit';
    case Balance = 'balance';

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Deposit',
            self::Balance => 'Baki',
        };
    }
}
