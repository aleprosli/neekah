<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * The name someone is actually called by, taken from their full name.
 *
 * Taking the first word is wrong for most Malaysian names: "Muhammad Faez Hakimi"
 * is Faez and "Nur Aina Adriana" is Aina, so a card cover reading "Muhammad & Nur"
 * names two people nobody knows. The leading honorific is skipped instead.
 */
class CallingName
{
    /**
     * Words that open a name far more often than they are used to address someone.
     *
     * Deliberately short. "Ahmad", "Wan", "Nik" and "Tengku" are left out because
     * they are frequently the calling name themselves, and getting those wrong is
     * worse than leaving a long first word alone.
     *
     * @var array<int, string>
     */
    public const PREFIXES = [
        'muhammad', 'muhamad', 'mohammad', 'mohamad', 'mohamed', 'mohd', 'muhd', 'md',
        'nur', 'nurul', 'noor', 'nor', 'siti',
    ];

    public static function from(?string $full): string
    {
        $words = preg_split('/\s+/u', trim((string) $full), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($words === []) {
            return '';
        }

        $first = Str::lower(rtrim($words[0], '.'));

        // Only when there is something better to use: "Nur" on its own stays "Nur".
        if (in_array($first, self::PREFIXES, true) && isset($words[1])) {
            return $words[1];
        }

        return $words[0];
    }

    /**
     * Whether this short name looks like one we derived rather than one a couple
     * typed. Used when correcting names that were derived by an earlier rule.
     */
    public static function looksDerived(?string $short, ?string $full): bool
    {
        $words = preg_split('/\s+/u', trim((string) $full), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return $words !== [] && Str::lower(trim((string) $short)) === Str::lower($words[0]);
    }
}
