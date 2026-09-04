<?php

namespace App\Http\Controllers;

use App\Enums\WeddingRole;
use App\Models\WeddingInvitation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvitationAcceptanceController extends Controller
{
    /**
     * Show the invitation. A guest is sent to sign in first and returns here afterwards.
     */
    public function show(Request $request, WeddingInvitation $invitation): View|RedirectResponse
    {
        $invitation->load(['wedding', 'inviter']);

        if (! $request->user()) {
            $request->session()->put('url.intended', route('invitations.show', $invitation));

            return redirect()->route('login')->with('status', 'Log masuk atau daftar dengan emel '.$invitation->email.' untuk menerima jemputan ini.');
        }

        return view('invitations.show', ['invitation' => $invitation]);
    }

    public function store(Request $request, WeddingInvitation $invitation): RedirectResponse
    {
        $user = $request->user();
        $wedding = $invitation->wedding;

        if ($wedding->hasMember($user)) {
            return redirect()->route('dashboard')->with('status', 'Anda sudah menjadi ahli majlis ini.');
        }

        if (! $invitation->isPending()) {
            return redirect()->route('dashboard')->withErrors(['invitation' => 'Jemputan ini sudah tamat tempoh atau telah digunakan.']);
        }

        if ($wedding->isFull()) {
            return redirect()->route('dashboard')->withErrors(['invitation' => 'Majlis ini sudah ada dua ahli.']);
        }

        if (! $user->isCustomer()) {
            return redirect()->route('dashboard')->withErrors(['invitation' => 'Hanya akaun pengantin boleh menyertai wedding project.']);
        }

        $wedding->addMember($user, WeddingRole::Partner);

        $invitation->update([
            'accepted_at' => now(),
            'accepted_by' => $user->id,
        ]);

        return redirect()->route('dashboard')->with('status', 'Anda kini menguruskan "'.$wedding->title.'" bersama '.$invitation->inviter->name.'.');
    }
}
