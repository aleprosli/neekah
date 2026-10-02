<?php

namespace App\Http\Controllers\Customer;

use App\Enums\SongMoment;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWeddingSongRequest;
use App\Models\Wedding;
use App\Models\WeddingSong;
use App\Support\SongSuggestions;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WeddingSongController extends Controller
{
    /**
     * The playlist, one card per moment of the day in the order they come,
     * with the songs couples usually pick alongside it to choose from.
     */
    public function index(Request $request): View
    {
        $wedding = $request->user()->weddings()->latest('event_date')->firstOrFail();
        Gate::authorize('view', $wedding);

        $songs = $wedding->songs()->get()->groupBy(fn (WeddingSong $song): string => $song->moment->value);

        return view('customer.playlist', [
            'wedding' => $wedding,
            'props' => VueProps::for([
                'storeUrl' => route('weddings.songs.store', $wedding),
                'moments' => collect(SongMoment::cases())->map(fn (SongMoment $moment): array => [
                    'value' => $moment->value,
                    'label' => $moment->label(),
                    'songs' => ($songs[$moment->value] ?? collect())->map(fn (WeddingSong $song): array => [
                        'id' => $song->id,
                        'title' => $song->title,
                        'artist' => $song->artist,
                        'notes' => $song->notes,
                        'destroy_url' => route('weddings.songs.destroy', [$wedding, $song]),
                    ])->values(),
                ])->values(),
                'suggestions' => SongSuggestions::all(),
            ]),
        ]);
    }

    public function store(StoreWeddingSongRequest $request, Wedding $wedding): RedirectResponse
    {
        $wedding->songs()->create($request->validated());

        return back()->with('status', __('flash.couple.song_added'));
    }

    public function destroy(Wedding $wedding, WeddingSong $song): RedirectResponse
    {
        Gate::authorize('update', $wedding);
        abort_unless($song->wedding_id === $wedding->id, 404);

        $song->delete();

        return back()->with('status', __('flash.couple.song_deleted'));
    }
}
