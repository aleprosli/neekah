<?php

use App\Support\CallingName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Some names open with two honorifics — "Muhammad Nur Haikal Firdaus" — and the
 * first correction skipped only one, leaving "Nur" printed on a groom's cover.
 *
 * Only a short name that is exactly what one of the earlier rules produced is
 * corrected, so anything a couple has typed themselves is left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        /** What the earlier rules would have derived from this full name. */
        $derived = function (?string $full): array {
            $words = preg_split('/\s+/u', trim((string) $full), -1, PREG_SPLIT_NO_EMPTY) ?: [];

            if ($words === []) {
                return [];
            }

            $skippedOnce = in_array(Str::lower(rtrim($words[0], '.')), CallingName::PREFIXES, true) && isset($words[1])
                ? $words[1]
                : $words[0];

            return [$words[0], $skippedOnce];
        };

        $sites = DB::table('wedding_sites')
            ->select('id', 'bride_name', 'groom_name', 'bride_short', 'groom_short')
            ->get();

        foreach ($sites as $site) {
            $update = [];

            foreach (['bride', 'groom'] as $side) {
                $full = $site->{$side.'_name'};
                $short = (string) $site->{$side.'_short'};
                $calling = CallingName::from($full);

                if ($calling !== '' && $calling !== $short && in_array($short, $derived($full), true)) {
                    $update[$side.'_short'] = mb_substr($calling, 0, 40);
                }
            }

            if ($update !== []) {
                DB::table('wedding_sites')->where('id', $site->id)->update($update);
            }
        }
    }

    public function down(): void
    {
        // Putting back a name nobody is called by is not a restoration.
    }
};
