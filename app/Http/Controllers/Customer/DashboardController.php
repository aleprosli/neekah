<?php

namespace App\Http\Controllers\Customer;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ChecklistItem;
use App\Models\Wedding;
use App\Support\CoupleNextSteps;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    /**
     * The couple's home, kept to what moves them forward: the countdown, the
     * next step or two, four shortcuts and the vendors they have booked. The
     * stat cards and the category checklist it used to carry made the page a
     * wall of numbers, and couples stopped coming back to it.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $wedding = $user->weddings()->with(['members', 'site', 'invitations' => fn ($query) => $query->pending()])->latest('event_date')->first();

        if ($wedding === null) {
            return view('customer.dashboard', [
                'wedding' => null,
                'props' => VueProps::for([
                    'hasWedding' => false,
                    'createUrl' => route('weddings.create'),
                    'checklistCount' => ChecklistItem::query()->count(),
                    // A vendor may have recorded a booking before the couple made
                    // a wedding; with no sidebar, this is their way to it.
                    'bookingsCount' => Booking::query()->forCustomer($user)->count(),
                    'bookingsUrl' => route('bookings.index'),
                    // Someone who signed up as a couple but runs a business.
                    'convertUrl' => $user->canBecomeVendor() ? route('vendor.convert') : null,
                ]),
            ]);
        }

        $bookings = Booking::query()
            ->forCustomer($user)
            ->with(['vendor.category', 'payments'])
            ->whereIn('status', [BookingStatus::PendingPayment, BookingStatus::Confirmed, BookingStatus::Completed])
            ->orderBy('event_date')
            ->get();

        $steps = new CoupleNextSteps($wedding);

        return view('customer.dashboard', [
            'wedding' => $wedding,
            'props' => VueProps::for([
                'hasWedding' => true,
                'createUrl' => route('weddings.create'),
                'steps' => $steps->open(),
                'stepsDone' => $steps->completed(),
                'stepsTotal' => count($steps->all()),
                'shortcuts' => $this->shortcuts($wedding, $bookings),
                'bookings' => $bookings->map(fn (Booking $booking): array => [
                    'reference' => $booking->reference,
                    'url' => route('bookings.show', $booking),
                    'vendor' => $booking->vendor->name,
                    'summary' => $booking->vendor->category->name.' · '.$booking->package_name,
                    'total' => 'RM'.number_format((float) $booking->total_amount),
                    'status_label' => $booking->status->label(),
                    'status_tone' => $booking->status->tone(),
                    'category' => [
                        'tone' => $booking->vendor->cover_tone,
                        'icon' => $booking->vendor->category->icon,
                        'illustration' => $booking->vendor->category->illustrationUrl(),
                    ],
                ])->values(),
                'findVendorsUrl' => route('vendors.index'),
                'convertUrl' => $user->canBecomeVendor() ? route('vendor.convert') : null,
            ]),
        ]);
    }

    /**
     * The four places a couple goes most, each saying where it stands.
     *
     * @param  Collection<int, Booking>  $bookings
     * @return list<array{key: string, title: string, value: string, hint: string, url: string, icon: string}>
     */
    private function shortcuts(Wedding $wedding, Collection $bookings): array
    {
        $site = $wedding->site;
        $guests = $wedding->guests()->toBase()->selectRaw('count(*) as total, coalesce(sum(pax_invited), 0) as pax')->first();
        $tasks = $wedding->tasks()->toBase()->selectRaw('count(*) as total, count(completed_at) as done')->first();
        $budget = (float) $wedding->budget;
        $committed = (float) $bookings->sum('total_amount');

        return [
            [
                'key' => 'card',
                'title' => __('pages.shortcuts.card'),
                'value' => match (true) {
                    $site === null => __('pages.shortcuts.card_none'),
                    $site->is_published => __('pages.shortcuts.card_published'),
                    default => __('pages.shortcuts.card_draft'),
                },
                'hint' => $site?->is_published ? trans_choice('pages.shortcuts.card_views', (int) $site->views, ['count' => number_format((int) $site->views)]) : __('pages.shortcuts.card_hint'),
                'url' => route('site.edit'),
                'icon' => 'mail',
            ],
            [
                'key' => 'guests',
                'title' => __('pages.shortcuts.guests'),
                'value' => number_format((int) $guests->total),
                'hint' => (int) $guests->total > 0 ? __('pages.shortcuts.guests_pax', ['count' => number_format((int) $guests->pax)]) : __('pages.shortcuts.guests_hint'),
                'url' => route('guests.index'),
                'icon' => 'users',
            ],
            [
                'key' => 'checklist',
                'title' => __('pages.shortcuts.checklist'),
                'value' => ((int) $tasks->done).' / '.((int) $tasks->total),
                'hint' => __('pages.shortcuts.checklist_hint', ['percent' => $wedding->planningProgress()]),
                'url' => route('checklist.index'),
                'icon' => 'check',
            ],
            [
                'key' => 'budget',
                'title' => __('pages.shortcuts.budget'),
                'value' => $budget > 0 ? 'RM'.number_format($budget - $committed) : __('pages.shortcuts.budget_none'),
                'hint' => $budget > 0 ? __('pages.shortcuts.budget_hint', ['budget' => 'RM'.number_format($budget)]) : __('pages.shortcuts.budget_set'),
                'url' => route('budget.index'),
                'icon' => 'wallet',
            ],
        ];
    }
}
