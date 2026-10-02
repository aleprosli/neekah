<?php

namespace App\Console\Commands;

use App\Actions\RecalculateVendorStats;
use App\Enums\VendorStatus;
use App\Models\Vendor;
use Illuminate\Console\Command;

/**
 * Recalculate every approved vendor's counters, score and tier. The tier
 * climbs on a complete profile and reviews (VendorTier::requirements), and a
 * profile edit does not recalculate by itself, so this runs nightly; run it
 * once after changing the ladder too.
 */
class RecalculateVendorRanks extends Command
{
    protected $signature = 'neekah:vendor-ranks';

    protected $description = 'Recalculate every approved vendor\'s stats, score and ranking tier';

    public function handle(RecalculateVendorStats $stats): int
    {
        $changed = 0;

        Vendor::query()
            ->where('status', VendorStatus::Approved)
            ->lazyById(100)
            ->each(function (Vendor $vendor) use ($stats, &$changed): void {
                $before = $vendor->tier;

                if ($stats->handle($vendor)->tier !== $before) {
                    $changed++;
                }
            });

        $this->info("Vendor ranks recalculated. {$changed} changed tier.");

        return self::SUCCESS;
    }
}
