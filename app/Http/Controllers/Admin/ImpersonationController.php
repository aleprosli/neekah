<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ImpersonationController extends Controller
{
    /**
     * Sign in as a customer or vendor to reproduce a complaint. Admins are never impersonated.
     */
    public function store(Request $request, User $user): RedirectResponse
    {
        $admin = $request->user();

        if (! $admin->canImpersonate() || ! $user->canBeImpersonated()) {
            abort(403);
        }

        Log::warning('Admin started impersonation', [
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'target_id' => $user->id,
            'target_email' => $user->email,
            'ip' => $request->ip(),
        ]);

        $admin->impersonate($user);

        return redirect($user->homeRoute())
            ->with('status', 'Anda kini melihat Neekah sebagai '.$user->name.'.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isImpersonated()) {
            return redirect()->route('admin.users.index');
        }

        Log::warning('Admin stopped impersonation', [
            'admin_id' => session('impersonated_by'),
            'target_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        $user->leaveImpersonation();

        return redirect()->route('admin.users.index')->with('status', 'Anda kembali sebagai admin.');
    }
}
