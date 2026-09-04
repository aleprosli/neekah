<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\InviteWeddingPartnerRequest;
use App\Models\Wedding;
use App\Models\WeddingInvitation;
use App\Notifications\WeddingPartnerInvited;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

class WeddingInvitationController extends Controller
{
    public function store(InviteWeddingPartnerRequest $request, Wedding $wedding): RedirectResponse
    {
        $invitation = $wedding->invitations()->create([
            'invited_by' => $request->user()->id,
            'email' => $request->string('email')->lower()->toString(),
            'token' => WeddingInvitation::generateToken(),
            'expires_at' => now()->addDays(WeddingInvitation::EXPIRES_AFTER_DAYS),
        ]);

        $invitation->setRelation('wedding', $wedding);
        $invitation->setRelation('inviter', $request->user());

        Notification::route('mail', $invitation->email)->notify(new WeddingPartnerInvited($invitation));

        return back()->with('status', 'Jemputan dihantar ke '.$invitation->email.'.');
    }

    public function destroy(Request $request, Wedding $wedding, WeddingInvitation $invitation): RedirectResponse
    {
        Gate::authorize('manageMembers', $wedding);
        abort_unless($invitation->wedding_id === $wedding->id, 404);

        $invitation->delete();

        return back()->with('status', 'Jemputan dibatalkan.');
    }
}
