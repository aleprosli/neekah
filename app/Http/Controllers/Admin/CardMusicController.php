<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCardMusicTrackRequest;
use App\Models\CardMusicTrack;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class CardMusicController extends Controller
{
    /**
     * The library of background tracks couples may choose from.
     *
     * Admin uploads every file, so nothing on a card is hotlinked from elsewhere and
     * nothing arrives from a guest. A track already chosen by a couple can be turned
     * off, which stops it being offered, but deleting it is refused — the card that
     * plays it would go quiet without anyone being told.
     */
    public function index(): View
    {
        $tracks = CardMusicTrack::ordered()->withCount('sites')->get();

        return view('admin.card-music', [
            'props' => VueProps::for([
                'storeUrl' => route('admin.card-music.store'),
                'stats' => [
                    ['label' => 'Jumlah trek', 'value' => $tracks->count()],
                    ['label' => 'Aktif', 'value' => $tracks->where('is_active', true)->count()],
                    ['label' => 'Digunakan pada kad', 'value' => $tracks->sum('sites_count')],
                ],
                'tracks' => $tracks->map(fn (CardMusicTrack $track): array => [
                    'id' => $track->id,
                    'title' => $track->title,
                    'artist' => $track->artist,
                    'length' => $track->lengthLabel(),
                    'url' => $track->url(),
                    'is_active' => $track->is_active,
                    'sort_order' => $track->sort_order,
                    'cards' => $track->sites_count,
                    'update_url' => route('admin.card-music.update', $track),
                    'destroy_url' => route('admin.card-music.destroy', $track),
                ])->values(),
            ]),
        ]);
    }

    public function store(StoreCardMusicTrackRequest $request): RedirectResponse
    {
        $attributes = $request->trackAttributes();
        $attributes['path'] = $request->file('audio')->store('card-music', 'public');
        $attributes['sort_order'] = $request->integer('sort_order') ?: (int) CardMusicTrack::max('sort_order') + 1;

        CardMusicTrack::create($attributes);

        return back()->with('status', __('flash.admin.card_music_added'));
    }

    public function update(StoreCardMusicTrackRequest $request, CardMusicTrack $track): RedirectResponse
    {
        $attributes = $request->trackAttributes();

        if ($request->hasFile('audio')) {
            Storage::disk('public')->delete($track->path);
            $attributes['path'] = $request->file('audio')->store('card-music', 'public');
        }

        $track->update($attributes);

        return back()->with('status', __('flash.admin.card_music_updated'));
    }

    public function destroy(CardMusicTrack $track): RedirectResponse
    {
        if ($track->sites()->exists()) {
            return back()->withErrors(['track' => __('flash.admin.card_music_in_use')]);
        }

        Storage::disk('public')->delete($track->path);
        $track->delete();

        return back()->with('status', __('flash.admin.card_music_deleted'));
    }
}
