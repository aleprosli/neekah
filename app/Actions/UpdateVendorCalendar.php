<?php

namespace App\Actions;

use App\Enums\VendorFeature;
use App\Models\Vendor;
use App\Models\VendorUnavailableDate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Closing and reopening days on a vendor's calendar, from the website or the
 * Pro app. With several places a day, an outside booking can take just
 * `slots` of them instead of the whole day. Either change is also a look at
 * the calendar, so it counts as confirming it.
 */
class UpdateVendorCalendar
{
    /** Close every day from $from to $to; returns how many were newly closed. */
    public function close(Vendor $vendor, Carbon $from, ?Carbon $to = null, ?string $reason = null, ?int $slots = null): int
    {
        $added = DB::transaction(function () use ($vendor, $from, $to, $reason, $slots): int {
            $added = 0;

            foreach ($from->copy()->toPeriod($to ?? $from) as $day) {
                $row = $vendor->unavailableDates()->firstOrNew(
                    ['date' => $day->toDateString(), 'source' => VendorUnavailableDate::SOURCE_MANUAL],
                );

                $added += $row->exists ? 0 : 1;
                $row->fill(['reason' => $reason, 'slots' => $slots])->save();
            }

            return $added;
        });

        $this->touch($vendor);

        return $added;
    }

    /** Reopen a day the vendor closed by hand; an imported one comes back on the next import. */
    public function reopen(Vendor $vendor, VendorUnavailableDate $date): void
    {
        $date->delete();
        $this->touch($vendor);
    }

    private function touch(Vendor $vendor): void
    {
        if ($vendor->hasFeature(VendorFeature::OnlineBooking)) {
            $vendor->bookingSettings()->updateOrCreate([], ['calendar_confirmed_at' => now()]);
        }
    }
}
