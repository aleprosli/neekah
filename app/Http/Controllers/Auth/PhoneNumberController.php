<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePhoneNumberRequest;
use App\Jobs\SendTelegramAlert;
use App\Models\User;
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
                'submitLabel' => __('props.copy.save_and_continue'),
                'fields' => [
                    [
                        'name' => 'phone',
                        'label' => __('auth_pages.fields.phone'),
                        'type' => 'tel',
                        'autocomplete' => 'tel',
                        'placeholder' => '012-345 6789',
                        'required' => true,
                        'value' => old('phone'),
                        'help' => __('auth_pages.fields.phone_help'),
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
        $phone = $request->validated('phone');

        // Only the request that actually fills the empty number finishes the
        // signup. A second tap while the first is still running used to read
        // the same session flag before either had written it back, and sent
        // the welcome email and the Telegram alert twice. The database update
        // is atomic, so exactly one request can see it succeed.
        $firstToFinish = User::whereKey($user->id)->whereNull('phone')->update(['phone' => $phone]) === 1;
        $user->refresh();

        if (! $firstToFinish && $user->phone !== $phone) {
            $user->update(['phone' => $phone]);
        }

        $pendingSignup = $request->session()->pull(self::NEW_SIGNUP_KEY);

        if ($firstToFinish && $pendingSignup) {
            $user->notify(new CustomerRegistered);

            SendTelegramAlert::about('💍 <b>New user has been registered</b>', [
                'Nama' => $user->name,
                'Emel' => $user->email,
                'Telefon' => $user->phone,
                'WhatsApp' => $user->whatsappUrl(),
                'Daftar melalui' => 'Google',
            ]);
        }

        return redirect()->intended($user->homeRoute())->with('status', __('flash.account.phone_saved'));
    }
}
