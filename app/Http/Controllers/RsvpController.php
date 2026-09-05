<?php

namespace App\Http\Controllers;

use App\Actions\RecordRsvp;
use App\Http\Requests\StoreRsvpRequest;
use App\Models\WeddingSite;
use Illuminate\Http\RedirectResponse;

class RsvpController extends Controller
{
    public function store(StoreRsvpRequest $request, RecordRsvp $recordRsvp, string $subdomain): RedirectResponse
    {
        $site = WeddingSite::query()->published()->where('subdomain', $subdomain)->firstOrFail();

        abort_unless($site->acceptsRsvps(), 403, 'RSVP untuk majlis ini telah ditutup.');

        $attending = $request->boolean('attending');

        $recordRsvp->handle($site, [
            'name' => $request->string('name')->toString(),
            'phone' => $request->string('phone')->toString() ?: null,
            'attending' => $attending,
            'pax' => $attending ? $request->integer('pax') : 0,
            'message' => $request->string('message')->toString() ?: null,
        ], $request->string('u')->toString() ?: null);

        return back()->with('rsvp', $attending
            ? 'Terima kasih! Kehadiran anda telah direkod.'
            : 'Terima kasih atas maklum balas anda.');
    }
}
