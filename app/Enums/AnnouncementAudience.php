<?php

namespace App\Enums;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

enum AnnouncementAudience: string
{
    case Everyone = 'everyone';
    case Customers = 'customers';
    case Vendors = 'vendors';
    case PendingVendors = 'pending_vendors';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Everyone => __('enums.announcement_audience.everyone'),
            self::Customers => __('enums.announcement_audience.customers'),
            self::Vendors => __('enums.announcement_audience.vendors'),
            self::PendingVendors => __('enums.announcement_audience.pending_vendors'),
            self::Custom => __('enums.announcement_audience.custom'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Everyone => __('enums.announcement_audience_desc.everyone'),
            self::Customers => __('enums.announcement_audience_desc.customers'),
            self::Vendors => __('enums.announcement_audience_desc.vendors'),
            self::PendingVendors => __('enums.announcement_audience_desc.pending_vendors'),
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
     * PendingVendors is every vendor whose profile an admin has not approved
     * yet — the ones still waiting to be verified.
     *
     * @return Builder<User>
     */
    public function recipients(): Builder
    {
        $users = User::query()->whereNull('deactivated_at');

        return match ($this) {
            self::Everyone => $users->whereIn('role', [UserRole::Customer, UserRole::Vendor]),
            self::Customers => $users->where('role', UserRole::Customer),
            self::Vendors => $users->where('role', UserRole::Vendor),
            self::PendingVendors => $users->where('role', UserRole::Vendor)
                ->whereHas('vendor', fn (Builder $vendor) => $vendor->where('status', VendorStatus::Pending)),
            self::Custom => $users->whereRaw('1 = 0'),
        };
    }
}
