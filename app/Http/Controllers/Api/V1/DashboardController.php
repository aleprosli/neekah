<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\EnquiryStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BookingResource;
use App\Http\Resources\Api\V1\OnlineBooking;
use App\Http\Resources\Api\V1\VendorResource;
use App\Models\Payment;
use App\Support\ProSettings;
use App\Support\VendorAnalytics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The app's home: the numbers the web dashboard shows a Pro vendor, what
 * their page did for them this month, and the next few events.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, ProSettings $pro): JsonResponse
    {
        $vendor = $request->user()->vendor->loadMissing('category');
        $analytics = new VendorAnalytics($vendor);
        $paidToVendor = Payment::query()->whereHas('booking', fn ($query) => $query->whereBelongsTo($vendor));

        return response()->json([
            'vendor' => new VendorResource($vendor),
            'stats' => [
                'upcoming_events' => $vendor->bookings()->where('status', BookingStatus::Confirmed)->whereDate('event_date', '>=', today())->count(),
                'awaiting_deposit' => $vendor->bookings()->where('status', BookingStatus::PendingPayment)->count(),
                'open_enquiries' => $vendor->enquiries()->where('status', EnquiryStatus::Open)->count(),
                'payments_to_verify' => $paidToVendor->clone()->where('status', PaymentStatus::AwaitingVerification)->count(),
                'paid_total' => (float) $paidToVendor->clone()->where('status', PaymentStatus::Paid)->sum('amount'),
            ],
            'reach' => ['days' => VendorAnalytics::DAYS, ...$analytics->totals(VendorAnalytics::DAYS)],
            'daily_views' => array_map(
                fn (array $day): array => ['date' => $day['date'], 'label' => $day['label'], 'views' => $day['value']],
                $analytics->dailyViews(),
            ),
            'upcoming' => BookingResource::collection($vendor->bookings()
                ->with(['user', 'payments'])
                ->whereIn('status', [BookingStatus::PendingPayment, BookingStatus::Confirmed])
                ->whereDate('event_date', '>=', today())
                ->orderBy('event_date')
                ->limit(5)
                ->get()),
            'online' => OnlineBooking::of($vendor),
            'elite' => $pro->eliteEnabled() ? ($vendor->isElite() ? 'elite' : 'need_tier') : null,
        ]);
    }
}
