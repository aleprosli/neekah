<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasWedding
{
    /**
     * Usage: ->middleware('wedding').
     *
     * Every planning tool reads the couple's latest wedding. A couple who has
     * not created one yet used to hit firstOrFail() and get a bare 404, which
     * reads as a broken site rather than a missing first step.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user !== null, 401);

        if (! $user->weddings()->exists()) {
            return redirect()
                ->route('weddings.create')
                ->with('status', 'Cipta majlis anda dahulu sebelum menggunakan alat perancangan.');
        }

        return $next($request);
    }
}
