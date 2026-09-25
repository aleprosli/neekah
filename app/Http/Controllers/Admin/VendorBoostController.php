<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GrantBoostTokens;
use App\Enums\BoostTokenReason;
use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorBoost;
use App\Notifications\BoostTokensReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VendorBoostController extends Controller
{
    /** The most tokens one admin entry may add or take away. */
    public const MAX_CHANGE = 1000;

    /**
     * The boost card on a vendor's admin page: the balance, what is running,
     * and where the form posts.
     *
     * @return array<string, mixed>
     */
    public static function card(Vendor $vendor): array
    {
        return [
            'balance' => (int) $vendor->boost_tokens,
            'running' => $vendor->boosts()->where('ends_at', '>', now())->with('category')->orderBy('ends_at')->get()
                ->map(fn (VendorBoost $boost): array => [
                    'id' => $boost->id,
                    'category' => $boost->category->name,
                    'ends' => $boost->ends_at->translatedFormat('j M Y, g:i A'),
                ])->values()->all(),
            'url' => route('admin.vendors.boost', $vendor),
            'max' => self::MAX_CHANGE,
        ];
    }

    /** Give tokens (a promotion, a goodwill gesture) or take some back. */
    public function store(Request $request, Vendor $vendor, GrantBoostTokens $grant): RedirectResponse
    {
        $validated = $request->validate([
            'change' => ['required', 'integer', 'between:-'.self::MAX_CHANGE.','.self::MAX_CHANGE, 'not_in:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $change = (int) $validated['change'];
        $grant->handle($vendor, $change, BoostTokenReason::Admin, admin: $request->user(), note: $validated['note'] ?? null);

        if ($change > 0) {
            $vendor->user->notify(new BoostTokensReceived($change, BoostTokenReason::Admin, $vendor->boost_tokens));
        }

        return back()->with('status', __('flash.admin.boost_tokens_changed', ['vendor' => $vendor->name, 'balance' => $vendor->boost_tokens]));
    }
}
