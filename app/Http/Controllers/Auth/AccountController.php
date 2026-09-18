<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAccountPasswordRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The one page every role edits their own account on. A vendor's business
 * profile lives in the vendor area; this is the person behind it.
 */
class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('account.edit', [
            'user' => $user,
            'props' => VueProps::for([
                'user' => [
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'has_password' => $user->hasPassword(),
                ],
                'updateUrl' => route('account.update'),
                'passwordUrl' => route('account.password'),
                'profileUrl' => $user->isVendor() ? route('vendor.profile.edit') : null,
            ]),
        ]);
    }

    public function update(UpdateAccountRequest $request): RedirectResponse
    {
        $request->user()->update($request->safe()->only(['name', 'phone', 'email']));

        return redirect()->route('account.edit')->with('status', 'Maklumat akaun dikemas kini.');
    }

    /**
     * Other sessions are signed out, so a password changed because someone else
     * had access actually ends their access.
     */
    public function updatePassword(UpdateAccountPasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => $request->string('password')->toString()]);

        Auth::logoutOtherDevices($request->string('password')->toString());
        $request->session()->regenerate();

        return redirect()->route('account.edit')->with('status', 'Kata laluan dikemas kini.');
    }
}
