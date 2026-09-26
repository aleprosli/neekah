<?php

namespace App\Jobs;

use App\Actions\ImportVendorIcal;
use App\Models\VendorBookingSetting;
use App\Notifications\IcalSyncFailed;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;

/**
 * One vendor's Google Calendar import, queued so a slow calendar server holds
 * up nobody. One at a time per vendor. A failure keeps the dates already
 * imported (a calendar that is briefly down must not reopen booked days),
 * records why, and tells the vendor once after a few failures in a row.
 */
class SyncVendorIcal implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $uniqueFor = 900;

    public function __construct(public VendorBookingSetting $settings) {}

    public function uniqueId(): string
    {
        return (string) $this->settings->vendor_id;
    }

    public function handle(ImportVendorIcal $import): void
    {
        $settings = $this->settings->fresh();

        if (! $settings || blank($settings->ical_url)) {
            return;
        }

        try {
            $import->handle($settings);
        } catch (RuntimeException $exception) {
            $failures = $settings->ical_failures + 1;
            $settings->update(['ical_error' => $exception->getMessage(), 'ical_failures' => $failures]);

            if ($failures === ImportVendorIcal::FAILURES_BEFORE_NOTICE) {
                $settings->vendor->user->notify(new IcalSyncFailed($exception->getMessage()));
            }
        }
    }
}
