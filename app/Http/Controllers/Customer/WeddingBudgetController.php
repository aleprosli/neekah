<?php

namespace App\Http\Controllers\Customer;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Wedding;
use App\Support\AnalyticsPeriod;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class WeddingBudgetController extends Controller
{
    /**
     * Budget versus actual per category, exactly as laid out in the kertas kerja.
     * Planned amounts are what the couple set; actuals come from real bookings.
     */
    public function index(Request $request): View
    {
        $wedding = $request->user()->weddings()->latest('event_date')->firstOrFail();
        Gate::authorize('view', $wedding);

        $period = AnalyticsPeriod::fromRequest($request);
        $categories = Category::active()->ordered()->get();
        $planned = $wedding->budgetItems()->pluck('planned_amount', 'category_id');

        $bookings = Booking::query()
            ->where('wedding_id', $wedding->id)
            ->whereNot('status', BookingStatus::Cancelled)
            ->with(['vendor.category', 'payments'])
            ->get();

        $rows = $categories->map(function (Category $category) use ($planned, $bookings): array {
            $categoryBookings = $bookings->filter(fn (Booking $booking): bool => $booking->vendor->category_id === $category->id);
            $actual = (float) $categoryBookings->sum('total_amount');
            $plannedAmount = (float) ($planned[$category->id] ?? 0);

            return [
                'category' => $category,
                'planned' => $plannedAmount,
                'actual' => $actual,
                'paid' => $categoryBookings->sum(fn (Booking $booking): float => $booking->paidAmount()),
                'difference' => $plannedAmount - $actual,
                'bookings' => $categoryBookings,
            ];
        });

        $budget = (float) $wedding->budget;
        $totalActual = (float) $rows->sum('actual');
        $money = fn (float $value): string => 'RM'.number_format($value);

        return view('customer.budget', [
            'wedding' => $wedding,
            'props' => VueProps::for([
                'action' => route('weddings.budget.update', $wedding),
                'budget' => $budget,
                'stats' => [
                    ['label' => __('props.couple.jumlah_bajet'), 'value' => $money($budget), 'hint' => __('props.couple.diagih').$money((float) $planned->sum())],
                    ['label' => __('props.couple.ditempah_2'), 'value' => $money($totalActual), 'hint' => __('props.couple.jumlah_semua_booking_aktif')],
                    ['label' => __('props.couple.dibayar'), 'value' => $money((float) $rows->sum('paid')), 'hint' => __('props.couple.baki_bayaran').$money($totalActual - (float) $rows->sum('paid'))],
                    ['label' => __('props.couple.baki_bajet'), 'value' => $money($budget - $totalActual), 'hint' => $budget - $totalActual < 0 ? 'Melebihi bajet' : 'Masih ada ruang'],
                ],
                'progress' => [
                    'caption' => $money($totalActual).' / '.$money($budget),
                    'percent' => $budget > 0 ? min(100, (int) round($totalActual / $budget * 100)) : 0,
                    'over' => $totalActual > $budget,
                ],
                'charts' => [
                    'committed' => $this->formatted($this->committedByMonth($bookings, $period), $money),
                    'paid' => $this->formatted($this->paidByMonth($bookings, $period), $money),
                    'categories' => $this->formatted(
                        $rows->filter(fn (array $row): bool => $row['actual'] > 0)
                            ->map(fn (array $row): array => ['label' => $row['category']->name, 'value' => $row['actual']])
                            ->sortByDesc('value')
                            ->values()
                            ->all(),
                        $money,
                    ),
                ],
                'rows' => $rows->map(fn (array $row): array => [
                    'id' => $row['category']->id,
                    'category' => $row['category']->name,
                    'icon' => $row['category']->icon,
                    'illustration' => $row['category']->illustrationUrl(),
                    'planned' => (int) $row['planned'],
                    'actual' => $money($row['actual']),
                    'paid' => $money($row['paid']),
                    'has_bookings' => $row['actual'] > 0,
                    'over' => $row['difference'] < 0,
                    'difference' => ($row['difference'] < 0 ? '+' : '−').'RM'.number_format(abs($row['difference'])),
                    'find_vendors_url' => route('vendors.index', ['category' => $row['category']->slug]),
                    'bookings' => $row['bookings']->map(fn (Booking $booking): array => [
                        'vendor' => $booking->vendor->name,
                        'url' => route('bookings.show', $booking),
                    ])->values(),
                ])->values(),
                'totals' => [
                    'actual' => $money($totalActual),
                    'over' => $budget - $totalActual < 0,
                    'remaining' => $money(abs($budget - $totalActual)),
                    'remaining_label' => $budget - $totalActual < 0 ? __('props.common.over') : __('props.common.remaining'),
                ],
            ]),
        ]);
    }

    /**
     * A series with each value formatted the way the page shows it.
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
     * What the couple committed by month, so spending is visible as a shape and
     * not only as a total.
     *
     * @param  Collection<int, Booking>  $bookings
     * @return array<int, array{label: string, value: float}>
     */
    private function committedByMonth(Collection $bookings, AnalyticsPeriod $period): array
    {
        $totals = $bookings
            ->groupBy(fn (Booking $booking): string => $booking->created_at->format('Y-m'))
            ->map(fn (Collection $group): float => (float) $group->sum('total_amount'));

        return $period->series($totals);
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     * @return array<int, array{label: string, value: float}>
     */
    private function paidByMonth(Collection $bookings, AnalyticsPeriod $period): array
    {
        $totals = $bookings
            ->flatMap(fn (Booking $booking) => $booking->payments->where('status', PaymentStatus::Paid)->whereNotNull('paid_at'))
            ->groupBy(fn (Payment $payment): string => $payment->paid_at->format('Y-m'))
            ->map(fn (Collection $group): float => (float) $group->sum('amount'));

        return $period->series($totals);
    }

    /**
     * Save the planned amount for every category in one submission.
     */
    public function update(Request $request, Wedding $wedding): RedirectResponse
    {
        Gate::authorize('update', $wedding);

        $validated = $request->validate([
            'budget' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'planned' => ['array'],
            'planned.*' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ], attributes: ['budget' => 'jumlah bajet']);

        $wedding->update(['budget' => $validated['budget']]);

        foreach ($validated['planned'] ?? [] as $categoryId => $amount) {
            $wedding->budgetItems()->updateOrCreate(
                ['category_id' => (int) $categoryId],
                ['planned_amount' => (float) ($amount ?? 0)],
            );
        }

        return back()->with('status', 'Bajet dikemas kini.');
    }
}
