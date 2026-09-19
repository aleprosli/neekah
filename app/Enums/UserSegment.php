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
            self::VendorSetupComplete => 'Vendor setup lengkap',
            self::VendorSetupPending => 'Vendor setup belum lengkap',
            self::CoupleNoWedding => 'Belum cipta majlis',
            self::CoupleNoCard => 'Belum cipta kad digital',
            self::CoupleNoPartner => 'Belum jemput pasangan',
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
            self::VendorSetupComplete => 'Profil penuh (tagline, penerangan, telefon, harga) dan katalog penuh (sekurang-kurangnya satu pakej aktif dan tiga gambar portfolio).',
            self::VendorSetupPending => 'Masih kurang sekurang-kurangnya satu daripada perkara di atas, jadi profil mereka belum layak dinilai pengantin.',
            self::CoupleNoWedding => 'Pengantin yang mendaftar tetapi tiada majlis langsung — bukan pemilik, bukan pasangan.',
            self::CoupleNoCard => 'Pengantin yang sudah cipta majlis tetapi majlis itu belum ada kad digital. Mereka yang belum cipta majlis tidak dikira di sini.',
            self::CoupleNoPartner => 'Pengantin yang cipta majlis seorang diri: pasangan belum menyertai, dan tiada jemputan yang masih sah.',
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
