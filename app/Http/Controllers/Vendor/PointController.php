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
use App\Support\TierProgress;
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
        $nextTier = TierProgress::next($vendor->tier);

        return view('vendor.points', [
            'props' => VueProps::for([
                'stats' => [
                    ['label' => __('props.vendor.performance_point'), 'value' => number_format($vendor->points_total), 'hint' => $vendor->penalty_points ? __('props.vendor.penalties', ['count' => $vendor->penalty_points]) : __('props.vendor.no_penalties')],
                    ['label' => __('props.vendor.vendor_score'), 'value' => number_format((float) $vendor->score, 2), 'hint' => __('props.vendor.maksimum_100')],
                    ['label' => __('props.vendor.completion_rate'), 'value' => $vendor->completion_rate.'%', 'hint' => __('ui.points.majlis_selesai_count', ['count' => $vendor->completed_bookings_count])],
                    ['label' => __('props.vendor.response_rate'), 'value' => $vendor->responseRateLabel(), 'hint' => $vendor->response_rate === null
                        ? __('ui.points.perlu_enquiry_untuk_diukur', ['count' => Vendor::MIN_ENQUIRIES_FOR_RESPONSE_RATE])
                        : __('ui.points.enquiry_yang_anda_balas')],
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
                    ['label' => __('props.vendor.pendapatan'), 'value' => 'RM'.number_format((float) $revenue->clone()->sum('amount')), 'hint' => __('props.vendor.bayaran_diterima_dalam_tempoh')],
                    ['label' => __('props.vendor.enquiry_dibalas'), 'value' => $enquiryCount > 0 ? $enquiries->clone()->whereNotNull('replied_at')->count().' / '.$enquiryCount : __('props.vendor.tiada_enquiry'), 'hint' => __('props.vendor.enquiry_yang_anda_terima')],
                    ['label' => __('props.vendor.enquiry_jadi_tempahan'), 'value' => $enquiryCount > 0 ? round($bookingsInPeriod / $enquiryCount * 100).'%' : __('props.copy.no_data'), 'hint' => __('props.copy.bookings_in_period', ['count' => $bookingsInPeriod])],
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
                    'requirements' => TierProgress::requirements($vendor),
                    'clean_record_note' => $nextTier === VendorTier::Recommended
                        ? __('ui.points.recommended_needs_clean')
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
}
