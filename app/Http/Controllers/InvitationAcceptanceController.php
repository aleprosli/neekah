<?php

namespace App\Http\Controllers;

use App\Actions\AcceptWeddingInvitation;
use App\Models\WeddingInvitation;
use App\Support\Seo;
use App\Support\VueProps;
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

        $wedding = $invitation->wedding;

        return view('invitations.show', [
            'props' => VueProps::for([
                'invitation' => ['inviter' => $invitation->inviter->name],
                'state' => match (true) {
                    $wedding->hasMember($request->user()) => 'member',
                    ! $invitation->isPending() => 'expired',
                    default => 'open',
                },
                'facts' => [
                    ['label' => 'Majlis', 'value' => $wedding->title],
                    ['label' => 'Tarikh', 'value' => $wedding->event_date->translatedFormat('j F Y')],
                    ['label' => 'Lokasi', 'value' => $wedding->city.', '.$wedding->state],
                    ['label' => 'Bajet', 'value' => 'RM'.number_format((float) $wedding->budget)],
                ],
                'acceptUrl' => route('invitations.accept', $invitation),
                'dashboardUrl' => route('dashboard'),
                'browseUrl' => route('vendors.index'),
                'footnote' => 'Jemputan dihantar ke '.$invitation->email.'. Anda log masuk sebagai '.$request->user()->email.'.',
            ]),
        ]);
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
