<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * A deactivated account is signed out on its next request, whether it just
     * logged in or already had a session open when an admin deactivated it.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isDeactivated()) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'Akaun anda telah dinyahaktifkan. Sila hubungi admin Neekah.');
    }
}
