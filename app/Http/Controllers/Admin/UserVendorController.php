<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RegisterVendor;
use App\Actions\RemoveVendorProfile;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConvertToVendorRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Fixing an account that signed up on the wrong side, for the cases the owner
 * cannot switch themselves.
 */
class UserVendorController extends Controller
{
    public function store(ConvertToVendorRequest $request, User $user, RegisterVendor $registerVendor): RedirectResponse
    {
        $vendor = $registerVendor->convert($user, $request->validated());

        return redirect()
            ->route('admin.vendors.show', $vendor)
            ->with('status', $user->name.' kini vendor. Semak dan luluskan profilnya di sini.');
    }

    public function destroy(User $user, RemoveVendorProfile $removeVendorProfile): RedirectResponse
    {
        Gate::authorize('switchToCouple', $user);

        DB::transaction(function () use ($user, $removeVendorProfile): void {
            $removeVendorProfile->handle($user->vendor);
            $user->update(['role' => UserRole::Customer]);
        });

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', $user->name.' kini akaun pengantin. Profil vendornya telah dipadam.');
    }
}
