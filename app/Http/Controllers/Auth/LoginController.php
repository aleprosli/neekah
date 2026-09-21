<?php

namespace App\Http\Controllers\Auth;

use App\Actions\AcceptWeddingInvitation;
use App\Enums\AuthAudience;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Support\AuthForm;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * The chooser first, then the form for the side the visitor picked. The
     * choice only changes the wording and where "daftar" leads: the account
     * itself decides which dashboard a sign-in lands on, so a couple who
     * taps "Vendor" by mistake still gets into their own account.
     */
    public function create(Request $request): View
    {
        $audience = AuthForm::audience($request);

        if (! $audience && ! AuthForm::isReturningWithInput($request)) {
            return view('auth.login', [
                'chooser' => AuthForm::chooser(registering: false, footer: [
                    'prefix' => __('auth_pages.links.no_account'),
                    'label' => __('auth_pages.links.register_free'),
                    'url' => route('register'),
                ]),
            ]);
        }

        $vendor = $audience === AuthAudience::Vendor;

        return view('auth.login', [
            'audience' => $audience,
            'props' => AuthForm::for([
                'action' => route('login'),
                'submitLabel' => __('auth_pages.login.submit'),
                'captcha' => true,
                'remember' => true,
                'forgotUrl' => route('password.request'),
                // Google signs a newcomer up as a couple, so a vendor is not
                // offered a button that would open the wrong kind of account.
                'googleUrl' => $vendor ? null : route('auth.google'),
                'notice' => session('status'),
                'fields' => [
                    ['name' => 'email', 'label' => __('auth_pages.fields.email'), 'type' => 'email', 'autocomplete' => 'email', 'required' => true, 'value' => old('email')],
                    ['name' => 'password', 'label' => __('auth_pages.fields.password'), 'type' => 'password', 'autocomplete' => 'current-password', 'required' => true],
                ],
                'links' => [
                    $vendor
                        ? ['prefix' => __('auth_pages.links.not_registered'), 'label' => __('auth_pages.links.register_vendor'), 'url' => AuthAudience::Vendor->registerUrl()]
                        : ['prefix' => __('auth_pages.links.no_account'), 'label' => __('auth_pages.links.register_free'), 'url' => $audience?->registerUrl() ?? route('register')],
                ],
            ]),
        ]);
    }

    public function store(LoginRequest $request, AcceptWeddingInvitation $accept): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        if ($invitation = $accept->fromSession($request->user())) {
            return redirect()
                ->route('dashboard')
                ->with('status', __('flash.account.now_planning', ['wedding' => $invitation->wedding->title, 'inviter' => $invitation->inviter->name]));
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
