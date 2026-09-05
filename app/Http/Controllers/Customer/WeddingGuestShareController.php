<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Wedding;
use App\Models\WeddingGuest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class WeddingGuestShareController extends Controller
{
    /**
     * Record that the couple opened WhatsApp for this guest and hand them the
     * prefilled message. This is a note of our own action: WhatsApp tells us
     * nothing about whether the message was sent, delivered or read.
     */
    public function store(Wedding $wedding, WeddingGuest $guest): RedirectResponse
    {
        Gate::authorize('update', $wedding);
        abort_unless($guest->wedding_id === $wedding->id, 404);

        $url = $guest->whatsappUrl();

        if ($url === null) {
            return back()->with('status', 'Terbitkan kad jemputan dahulu sebelum berkongsi pautan.');
        }

        $guest->forceFill(['shared_at' => $guest->shared_at ?? now()])->save();

        return redirect()->away($url);
    }

    /**
     * Undo the mark, because opening WhatsApp is not sending a message.
     */
    public function destroy(Wedding $wedding, WeddingGuest $guest): RedirectResponse
    {
        Gate::authorize('update', $wedding);
        abort_unless($guest->wedding_id === $wedding->id, 404);

        $guest->forceFill(['shared_at' => null])->save();

        return back()->with('status', 'Tanda hantar dibuang.');
    }
}
