<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Reduce a Malaysian number to digits only in international form, so the
     * same person typed as "012-345 6789", "+60123456789" or "0123456789"
     * matches. Returns null when there is nothing usable to match on.
     */
    public static function normalise(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = '60'.ltrim($digits, '0');
        }

        return strlen($digits) >= 9 ? substr($digits, 0, 20) : null;
    }
}
