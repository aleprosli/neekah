<?php

namespace App\Http\Controllers\Customer;

use App\Enums\GuestGroup;
use App\Enums\GuestSide;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWeddingGuestRequest;
use App\Models\Wedding;
use App\Models\WeddingGuest;
use App\Support\VueProps;
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

        $duplicates = $this->duplicateNames($guests);

        return view('customer.guests', [
            'wedding' => $wedding,
            'props' => VueProps::for([
                'storeUrl' => route('weddings.guests.store', $wedding),
                'importUrl' => route('weddings.guests.import', $wedding),
                'importErrors' => session('importErrors', []),
                'cardNotice' => $site?->is_published ? null : ['url' => route('site.edit')],
                'stats' => [
                    ['label' => __('props.couple.sah_hadir'), 'value' => __('props.units.people', ['count' => $site?->confirmedPax() ?? 0]), 'hint' => __('props.couple.dijumlahkan_dari_jawapan_rsvp_sahaja')],
                    ['label' => __('props.couple.belum_jawab'), 'value' => __('props.units.up_to_people', ['count' => $site?->awaitingPax() ?? 0]), 'hint' => __('props.couple.had_atas_jemputan_yang_belum')],
                    ['label' => __('props.couple.tidak_hadir'), 'value' => __('props.units.replies', ['count' => $site?->declinedCount() ?? 0]), 'hint' => __('props.units.guests_on_list', ['count' => $guests->count()])],
                ],
                'sides' => collect(GuestSide::cases())->map(fn (GuestSide $case): array => ['value' => $case->value, 'label' => $case->label()])->all(),
                'groups' => collect(GuestGroup::cases())->map(fn (GuestGroup $case): array => ['value' => $case->value, 'label' => $case->label()])->all(),
                'guests' => $guests->map(fn (WeddingGuest $guest): array => [
                    'id' => $guest->id,
                    'name' => $guest->name,
                    'phone' => $guest->phone,
                    'side' => $guest->side->label(),
                    'group' => $guest->group->label(),
                    'pax_invited' => $guest->pax_invited,
                    'status' => $guest->status()->label(),
                    'status_tone' => $guest->status()->tone(),
                    'duplicate' => $duplicates->contains(mb_strtolower($guest->name)),
                    'shared_at' => $guest->shared_at?->translatedFormat('j M Y'),
                    'invite_url' => $guest->inviteUrl(),
                    'share_url' => route('weddings.guests.share', [$wedding, $guest]),
                    'unshare_url' => route('weddings.guests.share.destroy', [$wedding, $guest]),
                    'destroy_url' => route('weddings.guests.destroy', [$wedding, $guest]),
                    'rsvp' => $guest->rsvp ? [
                        'summary' => 'Jawapan: '.($guest->rsvp->attending ? $guest->rsvp->pax.' orang hadir' : 'tidak hadir')
                            .' · dikemas kini '.$guest->rsvp->updated_at->translatedFormat('j M, g:i A'),
                        'soft_matched' => $guest->rsvp->isSoftMatched(),
                        'message' => $guest->rsvp->message,
                        'detach_url' => route('weddings.rsvps.update', [$wedding, $guest->rsvp]),
                    ] : null,
                ])->values(),
                'walkIns' => ($site ? $site->rsvps()->whereNull('wedding_guest_id')->get() : collect())
                    ->map(fn ($rsvp): array => [
                        'id' => $rsvp->id,
                        'name' => $rsvp->name,
                        'counted' => (bool) $rsvp->counted,
                        'message' => $rsvp->message,
                        'summary' => ($rsvp->attending ? $rsvp->pax.' orang hadir' : 'Tidak hadir').($rsvp->phone ? ' · '.$rsvp->phone : ''),
                        'update_url' => route('weddings.rsvps.update', [$wedding, $rsvp]),
                    ])->values(),
            ]),
        ]);
    }

    public function store(StoreWeddingGuestRequest $request, Wedding $wedding): RedirectResponse
    {
        $wedding->guests()->create($request->validated());

        return back()->with('status', __('flash.couple.guest_added'));
    }

    public function update(StoreWeddingGuestRequest $request, Wedding $wedding, WeddingGuest $guest): RedirectResponse
    {
        abort_unless($guest->wedding_id === $wedding->id, 404);

        $guest->update($request->validated());

        return back()->with('status', __('flash.couple.guest_updated'));
    }

    public function destroy(Wedding $wedding, WeddingGuest $guest): RedirectResponse
    {
        Gate::authorize('update', $wedding);
        abort_unless($guest->wedding_id === $wedding->id, 404);

        $guest->delete();

        return back()->with('status', __('flash.couple.guest_deleted'));
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
