<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendorIsApproved
{
    /**
     * A vendor still waiting for approval has nothing to run yet: no bookings,
     * no enquiries, no public page. Everything they need to do is on the
     * dashboard, so every other vendor page sends them back there.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $vendor = $request->user()?->vendor;

        if ($vendor === null || $vendor->isAwaitingApproval()) {
            return redirect()->route('vendor.dashboard');
        }

        return $next($request);
    }
}
