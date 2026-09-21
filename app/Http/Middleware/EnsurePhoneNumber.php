<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneNumber
{
    /** Routes a couple may still reach while they have no number on file. */
    private const ALLOWED = ['phone.*', 'logout', 'impersonate.stop'];

    /**
     * Someone who signed in with Google never typed a phone number, so the lead
     * alert about them carries no WhatsApp link and nobody can follow up. They
     * are held at the phone form until they give one.
     *
     * Only couples signed in through Google are asked: a vendor gives a number
     * when registering, an admin is not a lead, and a couple who filled the
     * register form was already asked there.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $mustAsk = $user
            && $user->role === UserRole::Customer
            && filled($user->google_id)
            && blank($user->phone)
            && ! Locales::routeIs(...self::ALLOWED);

        return $mustAsk ? redirect()->route('phone.create') : $next($request);
    }
}
