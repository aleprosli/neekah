<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CardNfcCard;
use App\Models\WeddingSite;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CardNfcController extends Controller
{
    /** How many cards one batch may mint, which is a roll of tags. */
    private const MAX_BATCH = 200;

    /**
     * The physical cards: which uid is on which tag, which invitation it opens and
     * how often it has been tapped.
     *
     * A card is minted before it is assigned, because that is the order things
     * happen in: tags are printed in a batch, then handed to whoever ordered them.
     */
    public function index(): View
    {
        $cards = CardNfcCard::query()->with('site')->latest('id')->get();

        return view('admin.card-nfc', [
            'props' => VueProps::for([
                'storeUrl' => route('admin.card-nfc.store'),
                'maxBatch' => self::MAX_BATCH,
                'stats' => [
                    ['label' => 'Jumlah kad', 'value' => $cards->count()],
                    ['label' => 'Sudah ditetapkan', 'value' => $cards->whereNotNull('wedding_site_id')->count()],
                    ['label' => 'Jumlah tap', 'value' => $cards->sum('taps')],
                ],
                'sites' => WeddingSite::query()->orderBy('subdomain')->get()
                    ->map(fn (WeddingSite $site): array => [
                        'id' => $site->id,
                        'label' => $site->subdomain.' — '.$site->coupleNames(),
                    ])->values(),
                'cards' => $cards->map(fn (CardNfcCard $card): array => [
                    'id' => $card->id,
                    'uid' => $card->uid,
                    'url' => route('nfc.tap', $card->uid),
                    'label' => $card->label,
                    'site_id' => $card->wedding_site_id,
                    'site' => $card->site?->subdomain,
                    'is_active' => $card->is_active,
                    'taps' => $card->taps,
                    'last_tapped' => $card->last_tapped_at?->diffForHumans(),
                    'update_url' => route('admin.card-nfc.update', $card),
                    'destroy_url' => route('admin.card-nfc.destroy', $card),
                ])->values(),
            ]),
        ]);
    }

    /**
     * Mint a batch of blank cards.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:'.self::MAX_BATCH],
            'label' => ['nullable', 'string', 'max:120'],
        ]);

        foreach (range(1, $validated['quantity']) as $number) {
            CardNfcCard::create([
                'uid' => CardNfcCard::freshUid(),
                'label' => $validated['label'] ?? null,
            ]);
        }

        return back()->with('status', __('flash.admin.nfc_cards_created', ['count' => $validated['quantity']]));
    }

    /**
     * Point a card at an invitation, rename it, or take it out of service.
     */
    public function update(Request $request, CardNfcCard $card): RedirectResponse
    {
        $validated = $request->validate([
            'wedding_site_id' => ['nullable', 'exists:wedding_sites,id'],
            'label' => ['nullable', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $card->update([
            'wedding_site_id' => $validated['wedding_site_id'] ?? null,
            'label' => $validated['label'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', __('flash.admin.nfc_card_updated'));
    }

    public function destroy(CardNfcCard $card): RedirectResponse
    {
        // A tapped card is out in the world on someone's table; deleting the row
        // turns it into a 404 with nobody to tell. Deactivate it instead.
        if ($card->taps > 0) {
            return back()->withErrors(['card' => __('flash.admin.nfc_card_in_use')]);
        }

        $card->delete();

        return back()->with('status', __('flash.admin.nfc_card_deleted'));
    }
}
