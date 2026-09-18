<?php

namespace App\Enums;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

enum AnnouncementAudience: string
{
    case Everyone = 'everyone';
    case Customers = 'customers';
    case Vendors = 'vendors';

    public function label(): string
    {
        return match ($this) {
            self::Everyone => 'Semua pengguna',
            self::Customers => 'Pengantin sahaja',
            self::Vendors => 'Vendor sahaja',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Everyone => 'Setiap pengantin dan vendor yang aktif.',
            self::Customers => 'Akaun pengantin sahaja.',
            self::Vendors => 'Akaun vendor sahaja.',
        };
    }

    /**
     * Who an announcement of this audience reaches. Admins are never included
     * — an announcement is something the platform says to its users — and
     * neither is a deactivated account, which EnsureAccountIsActive would sign
     * straight back out anyway.
     *
     * @return Builder<User>
     */
    public function recipients(): Builder
    {
        return User::query()
            ->whereNull('deactivated_at')
            ->whereIn('role', match ($this) {
                self::Everyone => [UserRole::Customer, UserRole::Vendor],
                self::Customers => [UserRole::Customer],
                self::Vendors => [UserRole::Vendor],
            });
    }
}
