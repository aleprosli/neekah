<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The Neekah Pro app is for approved vendors while Pro is running. A token
 * outlives the plan, so this is asked on every call: once Pro ends, the app
 * gets a machine-readable reason and shows the way back instead of an error.
 */
class EnsureApiProVendor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->isDeactivated()) {
            $user?->currentAccessToken()?->delete();

            return self::refuse('account_inactive', __('api.errors.account_inactive'), 401);
        }

        $reason = self::reasonToRefuse($user);

        return $reason === null ? $next($request) : self::refuse($reason, __('api.errors.'.$reason), 403);
    }

    /** Why this user may not use the app, or null when they may. */
    public static function reasonToRefuse(User $user): ?string
    {
        $vendor = $user->isVendor() ? $user->vendor : null;

        return match (true) {
            $vendor === null => 'not_vendor',
            ! $vendor->isApproved() => 'not_approved',
            ! $vendor->isPro() => 'pro_required',
            default => null,
        };
    }

    public static function refuse(string $code, string $message, int $status): Response
    {
        return response()->json([
            'message' => $message,
            'code' => $code,
            'pro_url' => $code === 'pro_required' ? route('vendor.pro.index') : null,
        ], $status);
    }
}
