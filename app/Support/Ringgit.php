<?php

namespace App\Support;

/**
 * A price as couples read it: "RM1,500" for whole ringgit, "RM2.50" when there are sen.
 *
 * number_format() with no decimals rounds, so a RM2.50/pax doorgift used to read RM3
 * on the profile while the vendor's form said 2.50.
 */
class Ringgit
{
    public static function format(float|int|string|null $amount): string
    {
        $amount = (float) $amount;

        return 'RM'.number_format($amount, fmod($amount, 1.0) == 0.0 ? 0 : 2);
    }
}
