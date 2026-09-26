<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\EnquiryStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use App\Support\AnalyticsPeriod;
use App\Support\MonthlyTotals;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $period = AnalyticsPeriod::fromRequest($request);

        $paid = Payment::query()->where('status', PaymentStatus::Paid)->whereBetween('paid_at', [$period->start, $period->end]);
        $earned = Booking::query()->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])->whereBetween('confirmed_at', [$period->start, $period->end]);
        $enquiries = Enquiry::query()->whereBetween('created_at', [$period->start, $period->end]);

        $gross = MonthlyTotals::of($paid->clone(), 'paid_at', 'sum', 'amount');
        $commission = MonthlyTotals::of($earned->clone(), 'confirmed_at', 'sum', 'commission_amount');

        $enquiryCount = $enquiries->clone()->count();
        $money = fn (float $value): string => 'RM'.number_format($value);
        $whole = fn (float $value): string => number_format($value);

        return view('admin.analytics', [
            'period' => $period,
            'props' => VueProps::for([
                'stats' => [
                    ['label' => __('props.admin.nilai_transaksi'), 'value' => $money((float) $gross->sum()), 'hint' => __('props.admin.bayaran_diterima_dalam_tempoh')],
                    ['label' => __('props.admin.komisen'), 'value' => $money((float) $commission->sum()), 'hint' => __('props.admin.dari_tempahan_yang_disahkan')],
                    ['label' => __('props.admin.tempahan_baharu'), 'value' => number_format(Booking::query()->whereBetween('created_at', [$period->start, $period->end])->count()), 'hint' => __('props.admin.dicipta_dalam_tempoh')],
                    ['label' => __('props.admin.enquiry_dijawab'), 'value' => $enquiryCount > 0
                        ? round($enquiries->clone()->whereIn('status', [EnquiryStatus::Replied, EnquiryStatus::Closed])->count() / $enquiryCount * 100).'%'
                        : __('props.copy.no_data'), 'hint' => __('props.units.enquiries_received', ['count' => $enquiryCount])],
                ],
                'charts' => [
                    ['title' => __('props.admin.nilai_transaksi_mengikut_bulan'), 'type' => 'bars', 'series' => $this->formatted($period->series($gross), $money)],
                    ['title' => __('props.admin.komisen_mengikut_bulan'), 'type' => 'bars', 'series' => $this->formatted($period->series($commission), $money)],
                    ['title' => __('props.admin.tempahan_mengikut_status'), 'type' => 'donut', 'series' => $this->formatted($this->bookingsByStatus($period), $whole)],
                    ['title' => __('props.admin.kategori_mengikut_nilai_tempahan'), 'type' => 'donut', 'series' => $this->formatted($this->categoryMix($period), $money)],
                    ['title' => __('props.admin.pendaftaran_pengantin'), 'type' => 'line', 'series' => $this->formatted($period->series(MonthlyTotals::of(User::query()->where('role', UserRole::Customer)->whereBetween('created_at', [$period->start, $period->end]), 'created_at')), $whole)],
                    ['title' => __('props.admin.pendaftaran_vendor'), 'type' => 'line', 'series' => $this->formatted($period->series(MonthlyTotals::of(Vendor::query()->whereBetween('created_at', [$period->start, $period->end]), 'created_at')), $whole)],
                ],
                'topVendors' => Vendor::with('category')->approved()->orderByDesc('score')->limit(8)->get()
                    ->map(fn (Vendor $vendor): array => [
                        'name' => $vendor->name,
                        'score' => number_format((float) $vendor->score, 1),
                        'url' => route('admin.vendors.show', $vendor),
                        'icon' => $vendor->category->icon,
                        'illustration' => $vendor->category->illustrationUrl(),
                    ])->values(),
            ]),
        ]);
    }

    /**
     * A series with each value formatted the way the page shows it, so a chart
     * component never has to know what a number means.
     *
     * @param  array<int, array{label: string, value: float}>  $series
     * @return array<int, array{label: string, value: float, display: string}>
     */
    private function formatted(array $series, callable $format): array
    {
        return collect($series)
            ->map(fn (array $row): array => [...$row, 'display' => $format((float) $row['value'])])
            ->all();
    }

    /**
     * @return array<int, array{label: string, value: float}>
     */
    private function bookingsByStatus(AnalyticsPeriod $period): array
    {
        $counts = Booking::query()
            ->whereBetween('created_at', [$period->start, $period->end])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(BookingStatus::cases())
            ->map(fn (BookingStatus $status): array => [
                'label' => $status->label(),
                'value' => (float) ($counts[$status->value] ?? 0),
            ])
            ->all();
    }

    /**
     * What couples actually spend on, by vendor category.
     *
     * @return array<int, array{label: string, value: float}>
     */
    private function categoryMix(AnalyticsPeriod $period): array
    {
        return Booking::query()
            ->join('vendors', 'vendors.id', '=', 'bookings.vendor_id')
            ->join('categories', 'categories.id', '=', 'vendors.category_id')
            ->whereIn('bookings.status', [BookingStatus::Confirmed, BookingStatus::Completed])
            ->whereBetween('bookings.created_at', [$period->start, $period->end])
            ->selectRaw('categories.name as label, sum(bookings.total_amount) as value')
            ->groupBy('categories.name')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($row): array => ['label' => $row->label, 'value' => (float) $row->value])
            ->all();
    }
}
