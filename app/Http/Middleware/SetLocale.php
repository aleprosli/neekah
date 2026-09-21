<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Puts the request into the language its URL asks for.
 *
 * The language is the URL, not a session: /vendors is Malay and /en/vendors is
 * English, always, for everyone. A visitor's own preference decides where they
 * are sent once, not what a given address means — otherwise two people opening
 * the same link would see different pages, and a crawler would index whichever
 * it happened to get.
 */
class SetLocale
{
    public function __construct(private readonly string $locale = Locales::DEFAULT) {}

    public function handle(Request $request, Closure $next, ?string $locale = null): Response
    {
        $locale = Locales::supported($locale) ? $locale : $this->locale;

        App::setLocale($locale);

        // What a signed-in person is reading the site in is what we write to
        // them in. Only on a GET: a redirect after a form post should not be
        // what decides the language of their next email.
        $user = $request->user();

        if ($user && $request->isMethod('GET') && $user->locale !== $locale) {
            $user->forceFill(['locale' => $locale])->saveQuietly();
        }

        return $next($request);
    }
}
