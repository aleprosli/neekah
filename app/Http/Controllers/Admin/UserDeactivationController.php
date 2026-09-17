<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UserDeactivationController extends Controller
{
    /**
     * Sign the account out and keep it out, without losing its records. A
     * vendor is also suspended, so the marketplace stops offering them.
     */
    public function store(User $user): RedirectResponse
    {
        Gate::authorize('deactivate', $user);

        DB::transaction(function () use ($user): void {
            $user->update(['deactivated_at' => now()]);
            $user->vendor?->update(['status' => VendorStatus::Suspended]);
        });

        return back()->with('status', 'Akaun '.$user->name.' dinyahaktifkan.');
    }

    /**
     * Let the account sign in again. A vendor stays suspended until an admin
     * approves the profile again, because that is a separate decision.
     */
    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('reactivate', $user);

        $user->update(['deactivated_at' => null]);

        return back()->with('status', $user->vendor
            ? 'Akaun '.$user->name.' diaktifkan semula. Profil vendornya masih digantung; luluskan semula di halaman vendor.'
            : 'Akaun '.$user->name.' diaktifkan semula.');
    }
}
