<?php

namespace App\Http\Controllers;

use App\Actions\AcceptWeddingInvitation;
use App\Models\WeddingInvitation;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvitationAcceptanceController extends Controller
{
    /**
     * Show the invitation. A guest is sent to sign in first and returns here afterwards.
     */
    public function show(Request $request, WeddingInvitation $invitation, Seo $seo): View|RedirectResponse
    {
        $seo->noindex();
        $invitation->load(['wedding', 'inviter']);

        if (! $request->user()) {
            $request->session()->put(AcceptWeddingInvitation::SESSION_KEY, $invitation->token);
            $request->session()->put('url.intended', route('invitations.show', $invitation));

            // A stranger with the link registers first; joining then happens automatically.
            return redirect()->route('register');
        }

        return view('invitations.show', ['invitation' => $invitation]);
    }

    public function store(Request $request, WeddingInvitation $invitation, AcceptWeddingInvitation $accept): RedirectResponse
    {
        $user = $request->user();
        $wedding = $invitation->wedding;

        if ($wedding->hasMember($user)) {
            return redirect()->route('dashboard')->with('status', 'Anda sudah menjadi ahli majlis ini.');
        }

        if (! $accept->handle($invitation, $user)) {
            return redirect()->route('dashboard')->withErrors([
                'invitation' => 'Jemputan ini tidak boleh digunakan lagi. Ia mungkin telah tamat tempoh, telah digunakan, atau majlis sudah ada dua ahli.',
            ]);
        }

        return redirect()->route('dashboard')->with('status', 'Anda kini menguruskan "'.$wedding->title.'" bersama '.$invitation->inviter->name.'.');
    }
}
