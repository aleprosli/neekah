<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\RecalculateVendorStats;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\PointReason;
use App\Enums\VendorTier;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Vendor;
use App\Support\AnalyticsPeriod;
use App\Support\MonthlyTotals;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PointController extends Controller
{
    /**
     * Show the vendor how their points, score and ranking are earned.
     */
    public function index(Request $request, RecalculateVendorStats $recalculateStats): View
    {
        $vendor = $request->user()->vendor;
        $recalculateStats->handle($vendor);
        $vendor->refresh();

        $period = AnalyticsPeriod::fromRequest($request);

        $completed = $vendor->bookings()->where('status', BookingStatus::Completed)->whereBetween('completed_at', [$period->start, $period->end]);
        $revenue = Payment::query()
            ->whereIn('booking_id', $vendor->bookings()->select('id'))
            ->where('status', PaymentStatus::Paid)
            ->whereBetween('paid_at', [$period->start, $period->end]);
        $enquiries = $vendor->enquiries()->whereBetween('created_at', [$period->start, $period->end]);

        return view('vendor.points', [
            'vendor' => $vendor,
            'period' => $period,
            'revenueSeries' => $period->series(MonthlyTotals::of($revenue->clone(), 'paid_at', 'sum', 'amount')),
            'completedSeries' => $period->series(MonthlyTotals::of($completed->clone(), 'completed_at')),
            'ratingSeries' => $period->series(MonthlyTotals::of($vendor->reviews()->whereBetween('created_at', [$period->start, $period->end]), 'created_at', 'avg', 'rating')),
            'revenueTotal' => (float) $revenue->clone()->sum('amount'),
            'enquiryCount' => $enquiries->clone()->count(),
            'enquiryReplied' => $enquiries->clone()->whereNotNull('replied_at')->count(),
            'enquiryToBookings' => $vendor->bookings()->whereBetween('created_at', [$period->start, $period->end])->count(),
            'history' => $vendor->points()->with('pointable')->latest()->limit(25)->get(),
            'breakdown' => $vendor->points()
                ->selectRaw('reason, sum(points) as total, count(*) as awards')
                ->groupBy('reason')
                ->get()
                ->keyBy(fn ($row) => $row->reason->value),
            'earnable' => collect(PointReason::cases())->reject(fn (PointReason $reason) => $reason === PointReason::ViolationPenalty),
            'nextTier' => $this->nextTier($vendor->tier),
            'requirements' => $this->requirements($vendor),
        ]);
    }

    private function nextTier(VendorTier $tier): ?VendorTier
    {
        return match ($tier) {
            VendorTier::New, VendorTier::Verified => VendorTier::Trusted,
            VendorTier::Trusted => VendorTier::Top,
            VendorTier::Top => VendorTier::Recommended,
            VendorTier::Recommended => null,
        };
    }

    /**
     * What the vendor still needs for the tier above them.
     *
     * @return array<int, array{label: string, current: string, target: string, met: bool}>
     */
    private function requirements(Vendor $vendor): array
    {
        $targets = match ($this->nextTier($vendor->tier)) {
            VendorTier::Trusted => ['completed' => 5, 'rating' => 4.0, 'reviews' => 3, 'response' => 0, 'completion' => 0],
            VendorTier::Top => ['completed' => 15, 'rating' => 4.5, 'reviews' => 8, 'response' => 90, 'completion' => 0],
            VendorTier::Recommended => ['completed' => 30, 'rating' => 4.7, 'reviews' => 15, 'response' => 95, 'completion' => 90],
            default => null,
        };

        if (! $targets) {
            return [];
        }

        $rows = [
            ['label' => 'Booking selesai', 'value' => $vendor->completed_bookings_count, 'target' => $targets['completed'], 'suffix' => ''],
            ['label' => 'Rating purata', 'value' => (float) $vendor->rating_avg, 'target' => $targets['rating'], 'suffix' => ''],
            ['label' => 'Jumlah review', 'value' => $vendor->reviews_count, 'target' => $targets['reviews'], 'suffix' => ''],
            ['label' => 'Response rate', 'value' => $vendor->response_rate ?? 0, 'target' => $targets['response'], 'suffix' => '%'],
            ['label' => 'Completion rate', 'value' => $vendor->completion_rate, 'target' => $targets['completion'], 'suffix' => '%'],
        ];

        return collect($rows)
            ->reject(fn (array $row): bool => $row['target'] <= 0)
            ->map(fn (array $row): array => [
                'label' => $row['label'],
                'current' => $row['value'].$row['suffix'],
                'target' => $row['target'].$row['suffix'],
                'met' => $row['value'] >= $row['target'],
            ])
            ->values()
            ->all();
    }
}
