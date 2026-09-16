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
use App\Models\VendorPoint;
use App\Support\AnalyticsPeriod;
use App\Support\MonthlyTotals;
use App\Support\VueProps;
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

        $breakdown = $vendor->points()
            ->selectRaw('reason, sum(points) as total, count(*) as awards')
            ->groupBy('reason')
            ->get()
            ->keyBy(fn ($row) => $row->reason->value);

        $enquiryCount = $enquiries->clone()->count();
        $bookingsInPeriod = $vendor->bookings()->whereBetween('created_at', [$period->start, $period->end])->count();
        $nextTier = $this->nextTier($vendor->tier);

        return view('vendor.points', [
            'props' => VueProps::for([
                'stats' => [
                    ['label' => 'Performance point', 'value' => number_format($vendor->points_total), 'hint' => $vendor->penalty_points ? '− '.$vendor->penalty_points.' penalti pelanggaran' : 'Tiada penalti'],
                    ['label' => 'Vendor Score', 'value' => number_format((float) $vendor->score, 2), 'hint' => 'Maksimum 100'],
                    ['label' => 'Completion rate', 'value' => $vendor->completion_rate.'%', 'hint' => $vendor->completed_bookings_count.' majlis selesai'],
                    ['label' => 'Response rate', 'value' => $vendor->responseRateLabel(), 'hint' => $vendor->response_rate === null
                        ? 'Perlu sekurang-kurangnya '.Vendor::MIN_ENQUIRIES_FOR_RESPONSE_RATE.' enquiry untuk diukur'
                        : 'Enquiry yang anda balas'],
                ],
                'period' => [
                    'months' => $period->months,
                    'label' => $period->label(),
                    'choices' => collect(AnalyticsPeriod::CHOICES)
                        ->map(fn (string $label, int $months): array => [
                            'months' => $months,
                            'label' => $label,
                            'url' => route('vendor.points.index', ['months' => $months]),
                        ])->values(),
                ],
                'periodStats' => [
                    ['label' => 'Pendapatan', 'value' => 'RM'.number_format((float) $revenue->clone()->sum('amount')), 'hint' => 'Bayaran diterima dalam tempoh'],
                    ['label' => 'Enquiry dibalas', 'value' => $enquiryCount > 0 ? $enquiries->clone()->whereNotNull('replied_at')->count().' / '.$enquiryCount : 'Tiada enquiry', 'hint' => 'Enquiry yang anda terima'],
                    ['label' => 'Enquiry jadi tempahan', 'value' => $enquiryCount > 0 ? round($bookingsInPeriod / $enquiryCount * 100).'%' : 'Tiada data', 'hint' => $bookingsInPeriod.' tempahan dalam tempoh'],
                ],
                'charts' => [
                    'revenue' => $this->chart($period->series(MonthlyTotals::of($revenue->clone(), 'paid_at', 'sum', 'amount')), fn (float $value): string => 'RM'.number_format($value)),
                    'completed' => $this->chart($period->series(MonthlyTotals::of($completed->clone(), 'completed_at')), fn (float $value): string => number_format($value)),
                    'rating' => $this->chart($period->series(MonthlyTotals::of($vendor->reviews()->whereBetween('created_at', [$period->start, $period->end]), 'created_at', 'avg', 'rating')), fn (float $value): string => number_format($value, 1)),
                ],
                'tiers' => collect(VendorTier::cases())
                    ->map(fn (VendorTier $case): array => [
                        'label' => ($case === VendorTier::Recommended ? '🏆 ' : '').$case->label(),
                        'step' => $case->rank() + 1,
                        'reached' => $case->rank() <= $vendor->tier->rank(),
                        'is_current' => $case === $vendor->tier,
                    ])->values(),
                'progress' => [
                    'locked' => (bool) $vendor->tier_locked,
                    'next' => $nextTier?->label(),
                    'requirements' => $this->requirements($vendor),
                    'clean_record_note' => $nextTier === VendorTier::Recommended
                        ? 'Recommended Vendor juga memerlukan rekod bersih tanpa pelanggaran disahkan dalam tempoh 6 bulan terakhir.'
                        : null,
                ],
                'earnable' => collect(PointReason::cases())
                    ->reject(fn (PointReason $reason): bool => $reason === PointReason::ViolationPenalty)
                    ->map(function (PointReason $reason) use ($breakdown): array {
                        $row = $breakdown[$reason->value] ?? null;

                        return [
                            'label' => $reason->label(),
                            'points' => $reason->points(),
                            'earned' => $row ? number_format($row->total) : null,
                            'awards' => $row && ! $reason->isMilestone() ? $row->awards : null,
                        ];
                    })->values(),
                'history' => $vendor->points()->with('pointable')->latest()->limit(25)->get()
                    ->map(fn (VendorPoint $point): array => [
                        'id' => $point->id,
                        'reason' => $point->reason->label(),
                        'points' => $point->points,
                        'awarded_at' => $point->created_at->translatedFormat('j M Y, g:i A'),
                    ])->values(),
            ]),
        ]);
    }

    /**
     * A monthly series with each value formatted the way the page shows it, so
     * the chart component never has to know what a number means.
     *
     * @param  array<int, array{label: string, value: float}>  $series
     * @return array<int, array{label: string, value: float, display: string}>
     */
    private function chart(array $series, callable $format): array
    {
        return collect($series)
            ->map(fn (array $row): array => [...$row, 'display' => $format((float) $row['value'])])
            ->all();
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
