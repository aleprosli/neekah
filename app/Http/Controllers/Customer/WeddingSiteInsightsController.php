<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\WeddingSite;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WeddingSiteInsightsController extends Controller
{
    /** Four weeks is what a couple can act on; older than that is history. */
    private const DAYS = 28;

    /**
     * How the invitation is being read: opens per day, how many arrived on a
     * personal link, and what came back from the guest list.
     *
     * Nothing here identifies a guest. Who opened their own link is on the guest
     * list itself, which is the couple's own record; this page is about the card.
     */
    public function __invoke(Request $request): View
    {
        $wedding = $request->user()->weddings()->latest('event_date')->firstOrFail();
        Gate::authorize('view', $wedding);

        $site = $wedding->site;
        abort_unless($site !== null, 404);

        return view('customer.card-insights', [
            'wedding' => $wedding,
            'props' => VueProps::for([
                'site' => [
                    'published' => (bool) $site->is_published,
                    'url' => $site->url(),
                    'editUrl' => route('site.edit'),
                    'guestsUrl' => route('guests.index'),
                ],
                'totals' => [
                    'views' => $site->views,
                    'guest_views' => (int) $site->dailyViews()->sum('guest_views'),
                    'replies' => $site->rsvps()->count(),
                    'attending' => $site->rsvps()->where('attending', true)->count(),
                    'declined' => $site->declinedCount(),
                    'confirmed_pax' => $site->confirmedPax(),
                    'awaiting_pax' => $site->awaitingPax(),
                    'wishes' => $site->approvedWishes()->count(),
                    'invited' => $wedding->guests()->count(),
                    'opened' => $wedding->guests()->whereNotNull('opened_at')->count(),
                ],
                'daily' => $this->daily($site),
            ]),
        ]);
    }

    /**
     * Every day of the window, including the quiet ones: a chart that skips the
     * days nobody opened the card reads as steady interest when it was not.
     *
     * @return array<int, array{label: string, date: string, views: int, guest_views: int}>
     */
    private function daily(WeddingSite $site): array
    {
        $counters = $site->dailyViews()
            ->where('viewed_on', '>=', today()->subDays(self::DAYS - 1)->toDateString())
            ->get()
            ->keyBy(fn ($row): string => $row->viewed_on->toDateString());

        return collect(range(self::DAYS - 1, 0))
            ->map(function (int $back) use ($counters): array {
                $day = today()->subDays($back);
                $row = $counters->get($day->toDateString());

                return [
                    'date' => $day->toDateString(),
                    'label' => $day->translatedFormat('j M'),
                    'views' => (int) ($row?->views ?? 0),
                    'guest_views' => (int) ($row?->guest_views ?? 0),
                ];
            })
            ->values()
            ->all();
    }
}
