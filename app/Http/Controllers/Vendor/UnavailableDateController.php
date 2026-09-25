<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\VendorFeature;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUnavailableDateRequest;
use App\Models\Vendor;
use App\Models\VendorUnavailableDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class UnavailableDateController extends Controller
{
    /**
     * Close a day or a range. With several places a day, an outside booking can
     * take just `slots` of them instead of the whole day. Closing is also a look
     * at the calendar, so it counts as confirming it.
     */
    public function store(StoreUnavailableDateRequest $request): RedirectResponse
    {
        $vendor = $request->user()->vendor;
        $from = $request->date('from');
        $to = $request->filled('to') ? $request->date('to') : $from;
        $reason = $request->string('reason')->toString() ?: null;
        $slots = $request->filled('slots') ? $request->integer('slots') : null;
        $added = 0;

        foreach (Carbon::parse($from)->toPeriod($to) as $day) {
            $row = $vendor->unavailableDates()->firstOrNew(
                ['date' => $day->toDateString(), 'source' => VendorUnavailableDate::SOURCE_MANUAL],
            );

            $added += $row->exists ? 0 : 1;
            $row->fill(['reason' => $reason, 'slots' => $slots])->save();
        }

        $this->touchCalendar($vendor);

        return redirect()->route('vendor.availability.index')->with('status', __('flash.vendor.dates_closed', ['count' => $added]));
    }

    public function destroy(Request $request, VendorUnavailableDate $date): RedirectResponse
    {
        abort_unless($date->vendor_id === $request->user()->vendor?->id, 403);
        // An imported day comes back on the next import; it is reopened in Google Calendar.
        abort_unless($date->source === VendorUnavailableDate::SOURCE_MANUAL, 403);

        $date->delete();
        $this->touchCalendar($request->user()->vendor);

        return redirect()->route('vendor.availability.index')->with('status', __('flash.vendor.date_reopened'));
    }

    private function touchCalendar(Vendor $vendor): void
    {
        if ($vendor->hasFeature(VendorFeature::OnlineBooking)) {
            $vendor->bookingSettings()->updateOrCreate([], ['calendar_confirmed_at' => now()]);
        }
    }
}
