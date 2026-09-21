<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Vendor = 'vendor';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Customer => __('enums.user_role.customer'),
            self::Vendor => __('enums.user_role.vendor'),
            self::Admin => __('enums.user_role.admin'),
        };
    }
}
