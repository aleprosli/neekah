<?php

namespace App\Http\Controllers\Auth;

use App\Actions\AcceptWeddingInvitation;
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
    public function create(Request $request): View
    {
        $invitation = $this->pendingInvitation($request);

        return view('auth.register', [
            'invitation' => $invitation,
            'props' => AuthForm::for([
                'action' => route('register'),
                'submitLabel' => 'Daftar',
                'captcha' => true,
                'googleUrl' => route('auth.google'),
                'invitation' => $invitation ? [
                    'initial' => mb_substr($invitation->inviter->name, 0, 1),
                    'inviter' => $invitation->inviter->name,
                    'wedding' => $invitation->wedding->title.' · '.$invitation->wedding->event_date->translatedFormat('j F Y'),
                ] : null,
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama', 'autocomplete' => 'name', 'placeholder' => 'Aina & Hakim', 'required' => true, 'value' => old('name')],
                    ['name' => 'email', 'label' => 'Emel', 'type' => 'email', 'autocomplete' => 'email', 'required' => true, 'value' => old('email', $invitation?->email)],
                    ['name' => 'phone', 'label' => 'Nombor telefon', 'type' => 'tel', 'autocomplete' => 'tel', 'placeholder' => '012-345 6789', 'value' => old('phone')],
                    ['name' => 'password', 'label' => 'Kata laluan', 'type' => 'password', 'autocomplete' => 'new-password', 'help' => 'Sekurang-kurangnya 8 aksara.', 'required' => true],
                    ['name' => 'password_confirmation', 'label' => 'Sahkan kata laluan', 'type' => 'password', 'autocomplete' => 'new-password', 'required' => true],
                ],
                'links' => [
                    ['prefix' => 'Sudah ada akaun?', 'label' => 'Log masuk', 'url' => route('login')],
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
                ->with('status', 'Selamat datang, '.$user->name.'! Anda kini menguruskan "'.$invitation->wedding->title.'" bersama '.$invitation->inviter->name.'.');
        }

        return redirect()
            ->intended($user->homeRoute())
            ->with('status', 'Selamat datang ke Neekah, '.$user->name.'!');
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
