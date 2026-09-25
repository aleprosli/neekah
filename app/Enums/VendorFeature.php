<?php

namespace App\Enums;

/**
 * The parts of the vendor area an admin can open or close, per plan (Admin →
 * Ciri vendor) and per vendor (the vendor's admin page).
 *
 * The overview, the business profile, the Pro page and the account are not
 * here: every vendor always has them, and the Pro page is where a closed
 * feature is bought.
 */
enum VendorFeature: string
{
    case Packages = 'packages';
    case Portfolio = 'portfolio';
    case Calendar = 'calendar';
    case Bookings = 'bookings';
    case Enquiries = 'enquiries';
    case Reviews = 'reviews';
    case Points = 'points';
    case OnlineBooking = 'online_booking';

    /**
     * What a plan opens until an admin says otherwise. Everything is open to
     * both, except online booking, which is what Neekah Pro is sold on.
     */
    public function openByDefault(string $plan): bool
    {
        return $this !== self::OnlineBooking || $plan === 'pro';
    }

    public function label(): string
    {
        return __('enums.vendor_feature.'.$this->value);
    }

    public function description(): string
    {
        return __('enums.vendor_feature_desc.'.$this->value);
    }

    /** The sidebar icon, one of x-nav-icon's names. */
    public function icon(): string
    {
        return match ($this) {
            self::Packages => 'box',
            self::Portfolio => 'image',
            self::Calendar => 'calendar',
            self::Bookings => 'receipt',
            self::Enquiries => 'chat',
            self::Reviews => 'star',
            self::Points => 'trophy',
            self::OnlineBooking => 'calendar',
        };
    }

    /** The page the sidebar links to. */
    public function route(): string
    {
        return match ($this) {
            self::Packages => 'vendor.packages.index',
            self::Portfolio => 'vendor.portfolio.index',
            self::Calendar => 'vendor.availability.index',
            self::Bookings => 'vendor.bookings.index',
            self::Enquiries => 'vendor.enquiries.index',
            self::Reviews => 'vendor.reviews.index',
            self::Points => 'vendor.points.index',
            self::OnlineBooking => 'vendor.booking-settings.edit',
        };
    }

    /** Every route of the feature, for the sidebar's active state. */
    public function routePattern(): string
    {
        return match ($this) {
            self::Packages => 'vendor.packages.*',
            self::Portfolio => 'vendor.portfolio.*',
            self::Calendar => 'vendor.availability.*',
            self::Bookings => 'vendor.bookings.*',
            self::Enquiries => 'vendor.enquiries.*',
            self::Reviews => 'vendor.reviews.*',
            self::Points => 'vendor.points.*',
            self::OnlineBooking => 'vendor.booking-settings.*',
        };
    }
}
