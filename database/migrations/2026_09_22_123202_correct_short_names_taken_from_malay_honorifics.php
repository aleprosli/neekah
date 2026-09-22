<?php

use App\Support\CallingName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The short names the card switch derived were the first word of the full name,
 * which for most Malaysian names is an honorific: a cover printed "Muhammad & Nur"
 * for a couple everyone calls Faez and Aina.
 *
 * Only the names that still look derived are corrected — a couple who has since
 * typed their own keeps it.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sites = DB::table('wedding_sites')
            ->select('id', 'bride_name', 'groom_name', 'bride_short', 'groom_short')
            ->get();

        foreach ($sites as $site) {
            $update = [];

            foreach (['bride', 'groom'] as $side) {
                $full = $site->{$side.'_name'};
                $short = $site->{$side.'_short'};
                $calling = CallingName::from($full);

                if ($calling !== '' && $calling !== $short && CallingName::looksDerived($short, $full)) {
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
        // The old value was the first word of the full name, which is what this
        // corrected; putting it back would only restore the wrong name.
    }
};
