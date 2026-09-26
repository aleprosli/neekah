<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * The app has no URL to carry its language, so it says which one it wants
 * in Accept-Language ("ms" or "en"). Anything else is Malay, as the site is.
 */
class SetApiLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $wanted = strtolower(substr((string) $request->header('Accept-Language'), 0, 2));

        App::setLocale(Locales::supported($wanted) ? $wanted : Locales::DEFAULT);

        return $next($request);
    }
}
