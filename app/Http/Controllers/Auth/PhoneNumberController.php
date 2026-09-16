<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePhoneNumberRequest;
use App\Jobs\SendTelegramAlert;
use App\Notifications\CustomerRegistered;
use App\Support\AuthForm;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PhoneNumberController extends Controller
{
    /** Set by GoogleController when the sign-in created the account. */
    public const NEW_SIGNUP_KEY = 'signup.pending';

    public function create(): View
    {
        return view('auth.phone', [
            'props' => AuthForm::for([
                'action' => route('phone.store'),
                'submitLabel' => 'Simpan dan teruskan',
                'fields' => [
                    [
                        'name' => 'phone',
                        'label' => 'Nombor telefon',
                        'type' => 'tel',
                        'autocomplete' => 'tel',
                        'placeholder' => '012-345 6789',
                        'required' => true,
                        'value' => old('phone'),
                        'help' => 'Nombor WhatsApp anda. Kami tidak memaparkannya kepada sesiapa selain vendor yang anda tempah.',
                    ],
                ],
            ]),
        ]);
    }

    /**
     * Save the number, then finish the signup that was waiting on it: the
     * welcome email and the lead alert are sent here, so the alert carries a
     * WhatsApp link instead of a name and an email address alone.
     */
    public function store(StorePhoneNumberRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->update(['phone' => $request->validated('phone')]);

        if ($request->session()->pull(self::NEW_SIGNUP_KEY)) {
            $user->notify(new CustomerRegistered);

            SendTelegramAlert::about('💍 <b>New user has been registered</b>', [
                'Nama' => $user->name,
                'Emel' => $user->email,
                'Telefon' => $user->phone,
                'WhatsApp' => $user->whatsappUrl(),
                'Daftar melalui' => 'Google',
            ]);
        }

        return redirect()->intended($user->homeRoute())->with('status', 'Terima kasih! Nombor telefon anda disimpan.');
    }
}
