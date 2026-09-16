<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUnavailableDateRequest;
use App\Models\Booking;
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

        return view('vendor.availability.index', [
            'props' => VueProps::for([
                'storeUrl' => route('vendor.availability.store'),
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
                        'destroy_url' => route('vendor.availability.destroy', $date),
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

    public function store(StoreUnavailableDateRequest $request): RedirectResponse
    {
        $vendor = $request->user()->vendor;
        $from = $request->date('from');
        $to = $request->filled('to') ? $request->date('to') : $from;
        $reason = $request->string('reason')->toString() ?: null;
        $added = 0;

        foreach (Carbon::parse($from)->toPeriod($to) as $day) {
            $created = $vendor->unavailableDates()->firstOrCreate(
                ['date' => $day->toDateString()],
                ['reason' => $reason],
            );

            $added += $created->wasRecentlyCreated ? 1 : 0;
        }

        return redirect()->route('vendor.availability.index')->with('status', $added.' hari ditutup.');
    }

    public function destroy(Request $request, VendorUnavailableDate $date): RedirectResponse
    {
        abort_unless($date->vendor_id === $request->user()->vendor?->id, 403);

        $date->delete();

        return redirect()->route('vendor.availability.index')->with('status', 'Tarikh dibuka semula.');
    }
}
