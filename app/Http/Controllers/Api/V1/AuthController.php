<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureApiProVendor;
use App\Http\Requests\ApiLoginRequest;
use App\Http\Resources\Api\V1\VendorResource;
use App\Models\User;
use App\Rules\AccessCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signing in to the Neekah Pro app, and who is signed in. Only an approved
 * vendor with Pro running gets a token; anyone else is told why, with a
 * code the app turns into the right screen.
 */
class AuthController extends Controller
{
    /** What the sign-in screen needs to know before anyone signs in. */
    public function config(): JsonResponse
    {
        return response()->json([
            'access_code_required' => AccessCode::isRequired(),
            'pro_url' => route('vendor.pro.index'),
            'register_url' => route('vendor.register'),
            'forgot_password_url' => route('password.request'),
        ]);
    }

    public function login(ApiLoginRequest $request): Response
    {
        $user = $request->authenticatedUser();

        if ($user->isDeactivated()) {
            return EnsureApiProVendor::refuse('account_inactive', __('api.errors.account_inactive'), 403);
        }

        if ($reason = EnsureApiProVendor::reasonToRefuse($user)) {
            return EnsureApiProVendor::refuse($reason, __('api.errors.'.$reason), 403);
        }

        $token = $user->createToken($request->string('device_name')->limit(100, '')->toString());
        $minutes = (int) config('sanctum.expiration');

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $minutes > 0 ? now()->addMinutes($minutes)->toIso8601String() : null,
            ...$this->profile($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => __('api.signed_out')]);
    }

    /**
     * Who is signed in and whether Pro is still running. Open after Pro ends,
     * so the app can say so instead of failing.
     */
    public function me(Request $request): Response
    {
        $user = $request->user();

        if ($user->isVendor() && $user->vendor?->isApproved()) {
            $vendor = $user->vendor;

            return response()->json([
                ...$this->profile($user),
                'pro' => [
                    'active' => $vendor->isPro(),
                    'until' => $vendor->pro_until?->toIso8601String(),
                    'days_left' => $vendor->isPro() ? (int) ceil(now()->diffInDays($vendor->pro_until)) : null,
                    'pro_url' => route('vendor.pro.index'),
                ],
            ]);
        }

        $reason = EnsureApiProVendor::reasonToRefuse($user);

        return EnsureApiProVendor::refuse($reason, __('api.errors.'.$reason), 403);
    }

    /**
     * @return array{user: array<string, mixed>, vendor: VendorResource}
     */
    private function profile(User $user): array
    {
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'locale' => $user->locale,
            ],
            'vendor' => new VendorResource($user->vendor->loadMissing('category')),
        ];
    }
}
