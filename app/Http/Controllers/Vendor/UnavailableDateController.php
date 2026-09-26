<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\UpdateVendorCalendar;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUnavailableDateRequest;
use App\Models\VendorUnavailableDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UnavailableDateController extends Controller
{
    /** Close a day or a range (UpdateVendorCalendar). */
    public function store(StoreUnavailableDateRequest $request, UpdateVendorCalendar $calendar): RedirectResponse
    {
        $added = $calendar->close(
            $request->user()->vendor,
            $request->date('from'),
            $request->filled('to') ? $request->date('to') : null,
            $request->string('reason')->toString() ?: null,
            $request->filled('slots') ? $request->integer('slots') : null,
        );

        return redirect()->route('vendor.availability.index')->with('status', __('flash.vendor.dates_closed', ['count' => $added]));
    }

    public function destroy(Request $request, VendorUnavailableDate $date, UpdateVendorCalendar $calendar): RedirectResponse
    {
        abort_unless($date->vendor_id === $request->user()->vendor?->id, 403);
        // An imported day comes back on the next import; it is reopened in Google Calendar.
        abort_unless($date->source === VendorUnavailableDate::SOURCE_MANUAL, 403);

        $calendar->reopen($request->user()->vendor, $date);

        return redirect()->route('vendor.availability.index')->with('status', __('flash.vendor.date_reopened'));
    }
}
