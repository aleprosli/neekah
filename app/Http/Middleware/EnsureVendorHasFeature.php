<?php

namespace App\Http\Middleware;

use App\Enums\VendorFeature;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendorHasFeature
{
    /**
     * Usage: ->middleware('vendor.feature:bookings').
     *
     * A Pro feature is locked on the server too, not only in the sidebar.
     * Opening one on Basic is answered with the Pro page; a write to it is
     * refused.
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $feature = VendorFeature::from($feature);
        $vendor = $request->user()?->vendor;

        // Waiting for approval, the setup page is the whole vendor area and it
        // needs packages and portfolio whatever the plan will open later.
        if ($vendor?->isAwaitingApproval() || $vendor?->hasFeature($feature)) {
            return $next($request);
        }

        abort_unless($request->isMethod('GET') && ! $request->expectsJson(), 403);

        return redirect()->route('vendor.pro.index')->with('status', __('flash.vendor.feature_needs_pro', ['feature' => $feature->label()]));
    }
}
