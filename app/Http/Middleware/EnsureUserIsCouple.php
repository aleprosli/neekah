<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsCouple
{
    /**
     * The couple's pages belong to couples. A vendor or an admin who opens one
     * (typing /dashboard, an old bookmark) is taken to their own dashboard;
     * anything that would change a couple's data is refused outright.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->isCustomer()) {
            return $next($request);
        }

        abort_unless($request->isMethod('GET') && ! $request->expectsJson(), 403);

        return redirect()->to($user->homeRoute());
    }
}
