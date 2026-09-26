<?php

namespace App\Console\Commands;

use App\Enums\VendorFeature;
use App\Jobs\SyncVendorIcal;
use App\Models\VendorBookingSetting;
use Illuminate\Console\Command;

class SyncVendorIcals extends Command
{
    protected $signature = 'neekah:sync-ical';

    protected $description = "Import busy days from every online-booking vendor's Google Calendar";

    /**
     * Hourly. Only vendors who can take online bookings: a calendar feeds
     * nothing else. Each import is spread over a few minutes so a full hour of
     * vendors does not hit Google at the same second.
     */
    public function handle(): int
    {
        $queued = 0;

        VendorBookingSetting::query()
            ->whereNotNull('ical_url')
            ->with('vendor')
            ->each(function (VendorBookingSetting $settings) use (&$queued): void {
                if ($settings->vendor->isApproved() && $settings->vendor->hasFeature(VendorFeature::OnlineBooking)) {
                    SyncVendorIcal::dispatch($settings)->delay(now()->addSeconds(random_int(0, 600)));
                    $queued++;
                }
            });

        $this->components->info("{$queued} calendar import(s) queued.");

        return self::SUCCESS;
    }
}
