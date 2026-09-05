<?php

namespace App\Http\Middleware;

use App\Support\Seo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BeginPageMetadata
{
    /**
     * Give every request its own description of the page.
     *
     * Without this the container hands the same Seo instance to the next
     * request whenever the application outlives a single one, under Octane or
     * in the test suite. One page marked noindex would then quietly mark every
     * page after it noindex too.
     */
    public function handle(Request $request, Closure $next): Response
    {
        app()->forgetInstance(Seo::class);

        return $next($request);
    }
}
