<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\BookingStatus;
use App\Enums\VendorFeature;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUnavailableDateRequest;
use App\Models\Booking;
use App\Models\Vendor;
use App\Models\VendorUnavailableDate;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class UnavailableDateController extends Controller
{
    public function index(Request $request): View
    {
        $vendor = $request->user()->vendor;

        $settings = $vendor->bookingSettingsOrDefault();

        return view('vendor.availability.index', [
            'props' => VueProps::for([
                'storeUrl' => route('vendor.availability.store'),
                'capacity' => max(1, (int) $settings->max_per_day),
                'confirm' => $vendor->hasFeature(VendorFeature::OnlineBooking) ? [
                    'url' => route('vendor.booking-settings.calendar'),
                    'ago' => $settings->calendar_confirmed_at?->diffForHumans(),
                ] : null,
                'today' => today()->toDateString(),
                'closed' => $vendor->unavailableDates()
                    ->whereDate('date', '>=', today())
                    ->orderBy('date')
                    ->get()
                    ->map(fn (VendorUnavailableDate $date): array => [
                        'id' => $date->id,
                        'date' => $date->date->toDateString(),
                        'label' => $date->date->translatedFormat('D, j M Y'),
                        'reason' => $date->reason,
                        'slots' => $date->slots,
                        'imported' => $date->source === VendorUnavailableDate::SOURCE_ICAL,
                        'destroy_url' => $date->source === VendorUnavailableDate::SOURCE_MANUAL ? route('vendor.availability.destroy', $date) : null,
                    ])->values(),
                'booked' => $vendor->bookings()
                    ->whereIn('status', [BookingStatus::PendingPayment, BookingStatus::Confirmed])
                    ->whereDate('event_date', '>=', today())
                    ->orderBy('event_date')
                    ->get()
                    ->map(fn (Booking $booking): array => [
                        'date' => $booking->event_date->toDateString(),
                        'label' => $booking->event_date->translatedFormat('D, j M Y'),
                        'reference' => $booking->reference,
                        'url' => route('vendor.bookings.show', $booking),
                        'status_label' => $booking->status->label(),
                        'status_tone' => $booking->status->tone(),
                    ])->values(),
            ]),
        ]);
    }

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
