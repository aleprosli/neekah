<?php

namespace App\Http\Controllers;

use App\Models\CardNfcCard;
use Illuminate\Http\RedirectResponse;

class NfcCardController extends Controller
{
    /**
     * What a physical card does when a guest taps it.
     *
     * The tag holds only this uid, so it keeps working after the couple changes
     * their web address, and a stack of cards can be printed before a single card
     * has been designed. A card not yet pointed at an invitation — or pointed at one
     * that is still a draft — sends the guest to Neekah rather than to a 404, because
     * the guest is holding something real and an error page reads as a broken card.
     */
    public function __invoke(string $uid): RedirectResponse
    {
        $card = CardNfcCard::query()->where('uid', $uid)->first();

        abort_if($card === null || ! $card->is_active, 404, __('validation.custom.card_not_found'));

        $card->recordTap();

        $site = $card->site;

        if ($site === null || ! $site->is_published) {
            return redirect()->route('landing')->with('status', __('flash.couple.nfc_card_not_ready'));
        }

        return redirect()->away($site->url());
    }
}
