<?php

namespace App\Enums;

/**
 * The parts of the vendor area, each open to every vendor or only on Neekah
 * Pro. Fixed by the owner (26 Sep 2026): Basic gets what builds the public
 * profile (packages, portfolio, reviews) and boosting; Pro adds the calendar
 * with online booking, bookings, enquiries, quotations & invoices,
 * digital contracts and points & ranking.
 *
 * The overview, the business profile, the Pro page and the account are not
 * here: every vendor always has them, and the Pro page is where a locked
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
    case Boost = 'boost';
    case Quotations = 'quotations';
    case Contracts = 'contracts';

    /** Whether only a Neekah Pro vendor can use it. */
    public function requiresPro(): bool
    {
        return in_array($this, [self::Calendar, self::Bookings, self::Enquiries, self::Quotations, self::Contracts, self::Points, self::OnlineBooking], true);
    }

    /**
     * The features in the order the sidebar lists them, Basic first. Online
     * booking has no page of its own: it lives on the calendar page.
     *
     * @return array<int, self>
     */
    public static function menu(): array
    {
        return [self::Packages, self::Portfolio, self::Reviews, self::Boost, self::Calendar, self::Bookings, self::Enquiries, self::Quotations, self::Contracts, self::Points];
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
            self::Boost => 'rocket',
            self::Quotations => 'document',
            self::Contracts => 'pencil',
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
            self::OnlineBooking => 'vendor.availability.index',
            self::Boost => 'vendor.boost.index',
            self::Quotations => 'vendor.quotations.index',
            self::Contracts => 'vendor.contracts.index',
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
            self::OnlineBooking => 'vendor.availability.*',
            self::Boost => 'vendor.boost.*',
            self::Quotations => 'vendor.quotations.*',
            self::Contracts => 'vendor.contracts.*',
        };
    }
}
