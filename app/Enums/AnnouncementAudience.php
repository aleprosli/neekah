<?php

namespace App\Enums;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

enum AnnouncementAudience: string
{
    case Everyone = 'everyone';
    case Customers = 'customers';
    case Vendors = 'vendors';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Everyone => __('enums.announcement_audience.everyone'),
            self::Customers => __('enums.announcement_audience.customers'),
            self::Vendors => __('enums.announcement_audience.vendors'),
            self::Custom => __('enums.announcement_audience.custom'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Everyone => __('enums.announcement_audience_desc.everyone'),
            self::Customers => __('enums.announcement_audience_desc.customers'),
            self::Vendors => __('enums.announcement_audience_desc.vendors'),
            self::Custom => __('enums.announcement_audience_desc.custom'),
        };
    }

    /** A hand-picked list, so there is no audience-wide count to show. */
    public function isCustom(): bool
    {
        return $this === self::Custom;
    }

    /**
     * Who an announcement of this audience reaches. Admins are never included
     * — an announcement is something the platform says to its users — and
     * neither is a deactivated account, which EnsureAccountIsActive would sign
     * straight back out anyway.
     *
     * Custom matches nobody here on purpose: its recipients are the accounts
     * picked on the announcement itself, so ask Announcement::recipientQuery().
     *
     * @return Builder<User>
     */
    public function recipients(): Builder
    {
        $users = User::query()->whereNull('deactivated_at');

        return $this === self::Custom
            ? $users->whereRaw('1 = 0')
            : $users->whereIn('role', match ($this) {
                self::Everyone => [UserRole::Customer, UserRole::Vendor],
                self::Customers => [UserRole::Customer],
                self::Vendors => [UserRole::Vendor],
                self::Custom => [],
            });
    }
}
