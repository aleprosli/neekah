<?php

namespace App\Http\Controllers;

use App\Actions\RecordRsvp;
use App\Http\Requests\StoreRsvpRequest;
use App\Models\WeddingSite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class RsvpController extends Controller
{
    /**
     * A guest's reply.
     *
     * The card sends this in the background, so a reply answers JSON when JSON was
     * asked for: a redirect would make the browser fetch the whole card again, and
     * the card the guest just opened would close. The form still works without
     * JavaScript, which is what the redirect is for.
     */
    public function store(StoreRsvpRequest $request, RecordRsvp $recordRsvp, string $subdomain): RedirectResponse|JsonResponse
    {
        $site = WeddingSite::query()->published()->where('subdomain', $subdomain)->firstOrFail();

        abort_unless($site->acceptsRsvps(), 403, __('validation.custom.rsvp_closed'));

        $attending = $request->boolean('attending');

        $recordRsvp->handle($site, [
            'name' => $request->string('name')->toString(),
            'phone' => $request->string('phone')->toString() ?: null,
            'attending' => $attending,
            'pax' => $attending ? $request->integer('pax') : 0,
            'message' => $request->string('message')->toString() ?: null,
        ], $request->string('u')->toString() ?: null);

        $message = $attending
            ? __('props.couple.terima_kasih_kehadiran')
            : __('props.couple.terima_kasih_maklum_balas');

        if ($request->wantsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('rsvp', $message);
    }
}
