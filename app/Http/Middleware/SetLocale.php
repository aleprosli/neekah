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
        // A route that names its language is one of the two language sets, and
        // opening it is the reader choosing. A route that applies this with no
        // language is only pinning one — the sitemaps and the wedding cards,
        // which sit outside both sets and would otherwise inherit whatever
        // language the last request in this process left behind.
        $chosen = Locales::supported($locale);
        $locale = $chosen ? $locale : $this->locale;

        App::setLocale($locale);

        // What a signed-in person is reading the site in is what we write to
        // them in. Only on a GET: a redirect after a form post should not be
        // what decides the language of their next email.
        $user = $request->user();

        if ($chosen && $user && $request->isMethod('GET') && $user->locale !== $locale) {
            $user->forceFill(['locale' => $locale])->saveQuietly();
        }

        return $next($request);
    }
}
