<?php

namespace App\Http\Controllers\Auth;

use App\Actions\AcceptWeddingInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AcceptWeddingInvitation $accept): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        if ($invitation = $accept->fromSession($request->user())) {
            return redirect()
                ->route('dashboard')
                ->with('status', 'Anda kini menguruskan "'.$invitation->wedding->title.'" bersama '.$invitation->inviter->name.'.');
        }

        return redirect()->intended($request->user()->homeRoute());
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('vendors.index');
    }
}
