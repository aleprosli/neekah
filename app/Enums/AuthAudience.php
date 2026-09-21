<?php

namespace App\Enums;

/**
 * Who is at the door of the sign-in and sign-up pages. Login itself does not
 * care — the account decides which dashboard it lands on — but the two sides
 * sign up through different forms, and a vendor looking at "Daftar sebagai
 * pengantin" is the reason this choice is asked first.
 */
enum AuthAudience: string
{
    case Couple = 'pengantin';
    case Vendor = 'vendor';

    public function label(): string
    {
        return match ($this) {
            self::Couple => __('enums.auth_audience.couple'),
            self::Vendor => __('enums.auth_audience.vendor'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Couple => __('auth_pages.audience.couple_description'),
            self::Vendor => __('auth_pages.audience.vendor_description'),
        };
    }

    /** A name from components/nav-icon.blade.php, drawn by the chooser. */
    public function icon(): string
    {
        return match ($this) {
            self::Couple => 'rings',
            self::Vendor => 'store',
        };
    }

    public function loginUrl(): string
    {
        return route('login', ['as' => $this->value]);
    }

    public function registerUrl(): string
    {
        return match ($this) {
            self::Couple => route('register', ['as' => $this->value]),
            self::Vendor => route('vendor.register'),
        };
    }
}
