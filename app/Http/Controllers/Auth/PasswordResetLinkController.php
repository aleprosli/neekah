<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\AuthForm;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password', [
            'props' => AuthForm::for([
                'action' => route('password.email'),
                'submitLabel' => 'Hantar pautan',
                'notice' => session('status'),
                'fields' => [
                    ['name' => 'email', 'label' => __('auth_pages.fields.email'), 'type' => 'email', 'autocomplete' => 'email', 'required' => true, 'value' => old('email')],
                ],
                'links' => [
                    ['prefix' => __('auth_pages.links.remember_password'), 'label' => __('auth_pages.links.log_in'), 'url' => route('login')],
                ],
            ]),
        ]);
    }

    /**
     * Send a reset link. The response is identical whether or not the email exists.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        return back()->with('status', __('flash.account.reset_link_sent'));
    }
}
