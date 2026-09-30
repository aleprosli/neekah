<?php

namespace App\Http\Controllers\Auth;

use App\Actions\AcceptWeddingInvitation;
use App\Enums\AuthAudience;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Jobs\SendTelegramAlert;
use App\Models\User;
use App\Models\WeddingInvitation;
use App\Notifications\CustomerRegistered;
use App\Support\AuthForm;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    /**
     * Pengantin or vendor first, because the two sign up through different
     * forms. Someone accepting a partner's invitation skips the question:
     * they are joining a wedding, so they are a couple by definition.
     */
    public function create(Request $request): View
    {
        $invitation = $this->pendingInvitation($request);
        $audience = AuthForm::audience($request);

        if (! $invitation && ! $audience && ! AuthForm::isReturningWithInput($request)) {
            return view('auth.register', [
                'chooser' => AuthForm::chooser(registering: true, footer: [
                    'prefix' => __('auth_pages.links.have_account'),
                    'label' => __('auth_pages.links.log_in'),
                    'url' => route('login'),
                ]),
            ]);
        }

        return view('auth.register', [
            'invitation' => $invitation,
            'audience' => $invitation ? null : AuthAudience::Couple,
            'props' => AuthForm::for([
                'action' => route('register'),
                'submitLabel' => 'Daftar',
                'captcha' => true,
                'googleUrl' => AuthForm::googleUrl(),
                // Someone accepting an invitation is the partner already; the
                // tip is for the one starting the account.
                'tip' => $invitation ? null : [
                    'title' => __('auth_pages.tip.one_account_title'),
                    'body' => __('auth_pages.tip.one_account_body'),
                ],
                'invitation' => $invitation ? [
                    'initial' => mb_substr($invitation->inviter->name, 0, 1),
                    'inviter' => $invitation->inviter->name,
                    'wedding' => $invitation->wedding->title.' · '.$invitation->wedding->event_date->translatedFormat('j F Y'),
                ] : null,
                'fields' => [
                    // One account is one person. The example used to read "Aina &
                    // Hakim", so people typed both names into an account that
                    // was only ever meant for one of them. Both are still fine.
                    ['name' => 'name', 'label' => __('auth_pages.fields.name'), 'autocomplete' => 'name', 'placeholder' => __('auth_pages.fields.name_placeholder'), 'help' => __('auth_pages.fields.name_help'), 'required' => true, 'value' => old('name')],
                    ['name' => 'email', 'label' => __('auth_pages.fields.email'), 'type' => 'email', 'autocomplete' => 'email', 'required' => true, 'value' => old('email', $invitation?->email)],
                    ['name' => 'phone', 'label' => __('auth_pages.fields.phone'), 'type' => 'tel', 'autocomplete' => 'tel', 'placeholder' => '012-345 6789', 'value' => old('phone')],
                    ['name' => 'password', 'label' => __('auth_pages.fields.password'), 'type' => 'password', 'autocomplete' => 'new-password', 'help' => __('auth_pages.fields.password_help'), 'required' => true],
                    ['name' => 'password_confirmation', 'label' => __('auth_pages.fields.password_confirm'), 'type' => 'password', 'autocomplete' => 'new-password', 'required' => true],
                    ...AuthForm::accessCodeFields(),
                ],
                'links' => [
                    ['prefix' => __('auth_pages.links.have_account'), 'label' => __('auth_pages.links.log_in'), 'url' => AuthAudience::Couple->loginUrl()],
                ],
            ]),
        ]);
    }

    public function store(RegisterRequest $request, AcceptWeddingInvitation $accept): RedirectResponse
    {
        $user = User::create([
            ...$request->safe()->only(['name', 'email', 'phone', 'password']),
            'role' => UserRole::Customer,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        $user->notify(new CustomerRegistered);

        SendTelegramAlert::about('💍 <b>New user has been registered</b>', [
            'Nama' => $user->name,
            'Emel' => $user->email,
            'Telefon' => $user->phone,
            'WhatsApp' => $user->whatsappUrl(),
        ]);

        if ($invitation = $accept->fromSession($user)) {
            return redirect()
                ->route('dashboard')
                ->with('status', __('flash.account.welcome_partner', ['name' => $user->name, 'wedding' => $invitation->wedding->title, 'inviter' => $invitation->inviter->name]));
        }

        return redirect()
            ->intended($user->homeRoute())
            ->with('status', __('flash.account.welcome', ['name' => $user->name]));
    }

    /**
     * The invitation whose link brought this visitor here, if any.
     */
    private function pendingInvitation(Request $request): ?WeddingInvitation
    {
        $token = $request->session()->get(AcceptWeddingInvitation::SESSION_KEY);

        return $token
            ? WeddingInvitation::with(['wedding', 'inviter'])->where('token', $token)->first()
            : null;
    }
}
