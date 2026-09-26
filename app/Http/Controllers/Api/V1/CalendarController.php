<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\SwitchOnlineBooking;
use App\Actions\UpdateVendorCalendar;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUnavailableDateRequest;
use App\Http\Resources\Api\V1\OnlineBooking;
use App\Http\Resources\Api\V1\Status;
use App\Models\Booking;
use App\Models\VendorUnavailableDate;
use App\Support\VendorAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * One month of the vendor's calendar — what is booked, what is closed —
 * closing and reopening days, and switching online booking on or off.
 */
class CalendarController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        $month = self::month($request->string('month')->toString());
        $range = [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()];

        return response()->json([
            'month' => $month->format('Y-m'),
            'today' => today()->toDateString(),
            'capacity' => max(1, (int) VendorAvailability::for($vendor)->settings()->max_per_day),
            'booked' => $vendor->bookings()
                ->with('user')
                ->whereIn('status', [BookingStatus::PendingPayment, BookingStatus::Confirmed, BookingStatus::Completed])
                ->whereBetween('event_date', $range)
                ->orderBy('event_date')
                ->get()
                ->map(fn (Booking $booking): array => [
                    'date' => $booking->event_date->toDateString(),
                    'reference' => $booking->reference,
                    'customer_name' => $booking->user?->name,
                    'package_name' => $booking->package_name,
                    'status' => Status::of($booking->status),
                ])->values(),
            'closed' => $vendor->unavailableDates()
                ->whereBetween('date', $range)
                ->orderBy('date')
                ->get()
                ->map(fn (VendorUnavailableDate $date): array => [
                    'id' => $date->id,
                    'date' => $date->date->toDateString(),
                    'reason' => $date->reason,
                    'slots' => $date->slots,
                    'imported' => $date->source === VendorUnavailableDate::SOURCE_ICAL,
                    'can_reopen' => $date->source === VendorUnavailableDate::SOURCE_MANUAL,
                ])->values(),
            'online' => OnlineBooking::of($vendor),
        ]);
    }

    public function close(StoreUnavailableDateRequest $request, UpdateVendorCalendar $calendar): JsonResponse
    {
        $added = $calendar->close(
            $request->user()->vendor,
            $request->date('from'),
            $request->filled('to') ? $request->date('to') : null,
            $request->string('reason')->toString() ?: null,
            $request->filled('slots') ? $request->integer('slots') : null,
        );

        return response()->json(['message' => __('flash.vendor.dates_closed', ['count' => $added]), 'added' => $added]);
    }

    public function reopen(Request $request, VendorUnavailableDate $date, UpdateVendorCalendar $calendar): JsonResponse
    {
        abort_unless($date->vendor_id === $request->user()->vendor->id, 404);
        // An imported day comes back on the next import; it is reopened in Google Calendar.
        abort_unless($date->source === VendorUnavailableDate::SOURCE_MANUAL, 403);

        $calendar->reopen($request->user()->vendor, $date);

        return response()->json(['message' => __('flash.vendor.date_reopened')]);
    }

    public function online(Request $request, SwitchOnlineBooking $switch): JsonResponse
    {
        $enabled = (bool) $request->validate(['enabled' => ['required', 'boolean']])['enabled'];
        $vendor = $request->user()->vendor;

        $switch->handle($vendor, $enabled);

        return response()->json([
            'message' => $enabled ? __('flash.vendor.online_on') : __('flash.vendor.online_off'),
            'online' => OnlineBooking::of($vendor->fresh()),
        ]);
    }

    /** The first day of the month asked for, or of this month. */
    private static function month(string $asked): Carbon
    {
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $asked) === 1) {
            return Carbon::createFromFormat('Y-m-d', $asked.'-01')->startOfDay();
        }

        return today()->startOfMonth();
    }
}
