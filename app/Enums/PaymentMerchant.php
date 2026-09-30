<?php

namespace App\Enums;

/**
 * Whose gateway account a payment is taken on. Pro, boost packs and Neekah
 * Kenangan are Neekah's revenue; a booking deposit goes straight to the
 * vendor, and Neekah never holds it.
 */
final class PaymentMerchant
{
    public const NEEKAH = 'neekah';

    public const VENDOR = 'vendor';
}
