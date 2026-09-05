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

        return view('customer.budget', [
            'wedding' => $wedding,
            'rows' => $rows,
            'totalPlanned' => (float) $planned->sum(),
            'totalActual' => (float) $rows->sum('actual'),
            'totalPaid' => (float) $rows->sum('paid'),
            'period' => $period,
            'committedSeries' => $this->committedByMonth($bookings, $period),
            'paidSeries' => $this->paidByMonth($bookings, $period),
            'categoryMix' => $rows->filter(fn (array $row): bool => $row['actual'] > 0)
                ->map(fn (array $row): array => ['label' => $row['category']->name, 'value' => $row['actual']])
                ->sortByDesc('value')
                ->values()
                ->all(),
        ]);
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
