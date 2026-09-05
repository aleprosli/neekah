<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Wedding;
use App\Models\WeddingRsvp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WeddingRsvpController extends Controller
{
    /**
     * Let the couple correct what the app inferred: detach a phone match that
     * was wrong, or strike a duplicate reply from the headcount without
     * deleting the person's message.
     */
    public function update(Request $request, Wedding $wedding, WeddingRsvp $rsvp): RedirectResponse
    {
        Gate::authorize('update', $wedding);
        abort_unless($rsvp->wedding_site_id === $wedding->site?->id, 404);

        $validated = $request->validate([
            'counted' => ['nullable', 'boolean'],
            'detach' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('detach')) {
            $rsvp->update(['wedding_guest_id' => null, 'matched_by' => null]);

            return back()->with('status', 'Padanan tetamu dibuang.');
        }

        $rsvp->update(['counted' => (bool) ($validated['counted'] ?? false)]);

        return back()->with('status', $rsvp->counted
            ? 'Jawapan dikira semula dalam jumlah kehadiran.'
            : 'Jawapan dikeluarkan dari jumlah kehadiran.');
    }
}
