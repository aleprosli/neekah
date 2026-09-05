<?php

namespace App\Http\Controllers\Customer;

use App\Enums\GuestGroup;
use App\Enums\GuestSide;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWeddingGuestRequest;
use App\Models\Wedding;
use App\Models\WeddingGuest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class WeddingGuestController extends Controller
{
    public function index(Request $request): View
    {
        $wedding = $request->user()->weddings()->latest('event_date')->firstOrFail();
        Gate::authorize('view', $wedding);

        $site = $wedding->site;
        $guests = $wedding->guests()->with('rsvp')->get();

        return view('customer.guests', [
            'wedding' => $wedding,
            'site' => $site,
            'guests' => $guests,
            'walkIns' => $site ? $site->rsvps()->whereNull('wedding_guest_id')->get() : collect(),
            'sides' => GuestSide::cases(),
            'groups' => GuestGroup::cases(),
            'confirmedPax' => $site?->confirmedPax() ?? 0,
            'awaitingPax' => $site?->awaitingPax() ?? 0,
            'declinedCount' => $site?->declinedCount() ?? 0,
            'duplicateNames' => $this->duplicateNames($guests),
        ]);
    }

    public function store(StoreWeddingGuestRequest $request, Wedding $wedding): RedirectResponse
    {
        $wedding->guests()->create($request->validated());

        return back()->with('status', 'Tetamu ditambah ke senarai.');
    }

    public function update(StoreWeddingGuestRequest $request, Wedding $wedding, WeddingGuest $guest): RedirectResponse
    {
        abort_unless($guest->wedding_id === $wedding->id, 404);

        $guest->update($request->validated());

        return back()->with('status', 'Maklumat tetamu dikemas kini.');
    }

    public function destroy(Wedding $wedding, WeddingGuest $guest): RedirectResponse
    {
        Gate::authorize('update', $wedding);
        abort_unless($guest->wedding_id === $wedding->id, 404);

        $guest->delete();

        return back()->with('status', 'Tetamu dipadam dari senarai.');
    }

    /**
     * Names appearing more than once are flagged, never merged. Only the couple
     * knows whether two "Kak Ani" rows are one person or two.
     *
     * @param  Collection<int, WeddingGuest>  $guests
     * @return Collection<int, string>
     */
    private function duplicateNames(Collection $guests): Collection
    {
        return $guests->countBy(fn (WeddingGuest $guest): string => mb_strtolower($guest->name))
            ->filter(fn (int $count): bool => $count > 1)
            ->keys();
    }
}
