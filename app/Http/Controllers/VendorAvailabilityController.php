<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Support\VendorAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * One month of a vendor's booking calendar for the date picker on their page.
 * Statuses and places left only: never who booked or why a day is closed.
 */
class VendorAvailabilityController extends Controller
{
    public function __invoke(Request $request, Vendor $vendor): JsonResponse
    {
        $availability = VendorAvailability::for($vendor);

        abort_unless($vendor->isApproved() && $availability->acceptsOnlineBookings(), 404);

        try {
            $month = Carbon::createFromFormat('Y-m', $request->string('bulan')->toString() ?: now()->format('Y-m'))->startOfMonth();
        } catch (Throwable) {
            $month = today()->startOfMonth();
        }

        return response()
            ->json([
                'month' => $month->format('Y-m'),
                'days' => $availability->calendar($month, $month->copy()->endOfMonth()),
            ])
            ->header('Cache-Control', 'private, max-age=60');
    }
}
