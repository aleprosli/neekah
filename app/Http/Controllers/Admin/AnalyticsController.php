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

        return view('admin.analytics', [
            'period' => $period,
            'grossSeries' => $period->series($gross),
            'commissionSeries' => $period->series($commission),
            'grossTotal' => $gross->sum(),
            'commissionTotal' => $commission->sum(),
            'bookingsByStatus' => $this->bookingsByStatus($period),
            'categoryMix' => $this->categoryMix($period),
            'signupSeries' => $period->series(MonthlyTotals::of(User::query()->where('role', UserRole::Customer)->whereBetween('created_at', [$period->start, $period->end]), 'created_at')),
            'vendorSignupSeries' => $period->series(MonthlyTotals::of(Vendor::query()->whereBetween('created_at', [$period->start, $period->end]), 'created_at')),
            'enquiryCount' => $enquiries->clone()->count(),
            'enquiryReplied' => $enquiries->clone()->whereIn('status', [EnquiryStatus::Replied, EnquiryStatus::Closed])->count(),
            'bookingCount' => Booking::query()->whereBetween('created_at', [$period->start, $period->end])->count(),
            'topVendors' => Vendor::with('category')->approved()->orderByDesc('score')->limit(8)->get(),
        ]);
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
