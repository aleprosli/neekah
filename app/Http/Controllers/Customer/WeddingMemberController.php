<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class WeddingMemberController extends Controller
{
    /**
     * The owner removes their partner. The owner cannot remove themselves.
     */
    public function destroy(Wedding $wedding, User $member): RedirectResponse
    {
        Gate::authorize('manageMembers', $wedding);

        if ($wedding->isOwnedBy($member)) {
            return back()->withErrors(['member' => __('flash.couple.owner_cannot_be_removed')]);
        }

        $wedding->members()->detach($member->id);

        return back()->with('status', __('flash.couple.member_removed', ['name' => $member->name]));
    }
}
