<?php

namespace App\Http\Controllers\Auth;

use App\Actions\AcceptWeddingInvitation;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuthForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

class GoogleController extends Controller
{
    public function redirect(): SymfonyRedirect|RedirectResponse
    {
        if (! AuthForm::offersGoogle()) {
            return redirect()->route('login')->withErrors(['email' => __('flash.account.google_not_configured')]);
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Link the Google account to an existing user by email, or register a new customer.
     */
    public function callback(AcceptWeddingInvitation $accept): RedirectResponse
    {
        if (! AuthForm::offersGoogle()) {
            return redirect()->route('login')->withErrors(['email' => __('flash.account.google_not_configured')]);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('login')->withErrors(['email' => __('flash.account.google_failed')]);
        }

        if (! $googleUser->getEmail()) {
            return redirect()->route('login')->withErrors(['email' => __('flash.account.google_no_email')]);
        }

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?: Str::before($googleUser->getEmail(), '@'),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'role' => UserRole::Customer,
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();

            // The welcome and the lead alert wait for the phone number, which
            // PhoneNumberController collects next; Google never gives us one.
            request()->session()->put(PhoneNumberController::NEW_SIGNUP_KEY, true);
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        if ($invitation = $accept->fromSession($user)) {
            return redirect()
                ->route('dashboard')
                ->with('status', __('flash.account.now_planning', ['wedding' => $invitation->wedding->title, 'inviter' => $invitation->inviter->name]));
        }

        return redirect()->intended($user->homeRoute());
    }
}
