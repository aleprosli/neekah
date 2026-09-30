<?php

namespace App\Enums;

/**
 * What a payment is for. Each purpose knows whose account the money goes to
 * and the prefix of its reference; what happens once it is paid is
 * App\Actions\SettlePayment's to decide.
 */
enum PaymentPurpose: string
{
    /** A couple paying a vendor for a booking (deposit or later), on the vendor's own account. */
    case Booking = 'booking';

    /** A vendor paying Neekah for Neekah Pro. */
    case VendorPro = 'vendor_pro';

    /** A vendor paying Neekah for a pack of boost tokens. */
    case BoostTokens = 'boost_tokens';

    /** A couple paying Neekah for a Neekah Kenangan album or its upgrade. */
    case Kenangan = 'kenangan';

    public function label(): string
    {
        return __('enums.payment_purpose.'.$this->value);
    }

    /** The account the money lands in: Neekah's own, or the vendor's. */
    public function merchant(): string
    {
        return $this === self::Booking ? PaymentMerchant::VENDOR : PaymentMerchant::NEEKAH;
    }

    public function referencePrefix(): string
    {
        return match ($this) {
            self::Booking => 'PAY',
            self::VendorPro => 'PRO',
            self::BoostTokens => 'BST',
            self::Kenangan => 'CAM',
        };
    }

    /** The palette key the badge components use, in Blade and in Vue. */
    public function tone(): string
    {
        return match ($this) {
            self::Booking => 'sky',
            self::VendorPro => 'brand',
            self::BoostTokens => 'amber',
            self::Kenangan => 'emerald',
        };
    }
}
