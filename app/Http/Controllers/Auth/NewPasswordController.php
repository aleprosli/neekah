<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuthForm;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class NewPasswordController extends Controller
{
    public function create(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'props' => AuthForm::for([
                'action' => route('password.store'),
                'submitLabel' => 'Simpan kata laluan',
                'hidden' => ['token' => $token],
                'fields' => [
                    ['name' => 'email', 'label' => __('auth_pages.fields.email'), 'type' => 'email', 'autocomplete' => 'email', 'required' => true, 'value' => old('email', $request->string('email')->toString())],
                    ['name' => 'password', 'label' => __('auth_pages.fields.password_new'), 'type' => 'password', 'autocomplete' => 'new-password', 'help' => __('auth_pages.fields.password_help'), 'required' => true],
                    ['name' => 'password_confirmation', 'label' => __('auth_pages.fields.password_confirm'), 'type' => 'password', 'autocomplete' => 'new-password', 'required' => true],
                ],
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], attributes: ['email' => 'emel', 'password' => 'kata laluan']);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages([
                'email' => __('validation.custom.reset_link_invalid'),
            ]);
        }

        return redirect()->route('login')->with('status', __('flash.account.password_reset'));
    }
}
