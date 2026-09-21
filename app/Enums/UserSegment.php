<?php

namespace App\Enums;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * The groups the team works through when they pick up the phone: accounts that
 * signed up and then stopped somewhere short of being useful to themselves.
 *
 * Each case owns its own definition, so the count on the chip and the rows in
 * the table can never disagree about who belongs to it.
 */
enum UserSegment: string
{
    case VendorSetupComplete = 'vendor-setup-complete';
    case VendorSetupPending = 'vendor-setup-pending';
    case CoupleNoWedding = 'couple-no-wedding';
    case CoupleNoCard = 'couple-no-card';
    case CoupleNoPartner = 'couple-no-partner';

    public function label(): string
    {
        return match ($this) {
            self::VendorSetupComplete => __('enums.user_segment.vendor_setup_complete'),
            self::VendorSetupPending => __('enums.user_segment.vendor_setup_pending'),
            self::CoupleNoWedding => __('enums.user_segment.couple_no_wedding'),
            self::CoupleNoCard => __('enums.user_segment.couple_no_card'),
            self::CoupleNoPartner => __('enums.user_segment.couple_no_partner'),
        };
    }

    /**
     * What the chip actually counts, in the admin's own words. Every segment
     * says this out loud, because "belum cipta kad digital" could just as
     * easily mean everyone without a majlis, and it does not.
     */
    public function description(): string
    {
        return match ($this) {
            self::VendorSetupComplete => __('enums.user_segment.vendor_setup_complete_desc'),
            self::VendorSetupPending => __('enums.user_segment.vendor_setup_pending_desc'),
            self::CoupleNoWedding => __('enums.user_segment.couple_no_wedding_desc'),
            self::CoupleNoCard => __('enums.user_segment.couple_no_card_desc'),
            self::CoupleNoPartner => __('enums.user_segment.couple_no_partner_desc'),
        };
    }

    public function role(): UserRole
    {
        return match ($this) {
            self::VendorSetupComplete, self::VendorSetupPending => UserRole::Vendor,
            default => UserRole::Customer,
        };
    }

    /**
     * Narrow a user query to this segment.
     *
     * @param  Builder<User>  $users
     * @return Builder<User>
     */
    public function apply(Builder $users): Builder
    {
        $users->where('role', $this->role());

        return match ($this) {
            self::VendorSetupComplete => $users
                ->whereHas('vendor', fn (Builder $vendor) => $vendor->setupComplete()),

            /** A vendor account with no profile row at all has certainly not finished. */
            self::VendorSetupPending => $users
                ->whereNot(fn (Builder $user) => $user->whereHas('vendor', fn (Builder $vendor) => $vendor->setupComplete())),

            self::CoupleNoWedding => $users->whereDoesntHave('weddings'),

            /** Asked of the majlis they created, because that is who the team calls. */
            self::CoupleNoCard => $users
                ->whereHas('createdWeddings', fn (Builder $weddings) => $weddings->whereDoesntHave('site')),

            self::CoupleNoPartner => $users
                ->whereHas('createdWeddings', fn (Builder $weddings) => $weddings
                    ->has('members', '<', 2)
                    ->whereDoesntHave('invitations', fn (Builder $invitations) => $invitations->pending())),
        };
    }
}
