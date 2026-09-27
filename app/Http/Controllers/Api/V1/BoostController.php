<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\StartVendorBoost;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\VendorBoost;
use App\Models\VendorBoostEntry;
use App\Support\BoostSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Boost tokens: the balance, what is lifted now, and lifting a category.
 * Buying a pack stays on the website, where the payment page is.
 */
class BoostController extends Controller
{
    public function index(Request $request, BoostSettings $settings): JsonResponse
    {
        $vendor = $request->user()->vendor;
        $running = $vendor->boosts()->where('ends_at', '>', now())->with('category')->orderBy('ends_at')->get();

        return response()->json([
            'balance' => (int) $vendor->boost_tokens,
            'max_days' => $settings->maxDays(),
            'pro_monthly_tokens' => $settings->proMonthlyTokens(),
            'buy_url' => route('vendor.boost.index'),
            'categories' => $vendor->categories->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'boosted_until' => $running->firstWhere('category_id', $category->id)?->ends_at->toIso8601String(),
            ])->values(),
            'running' => $running->map(fn (VendorBoost $boost): array => [
                'id' => $boost->id,
                'category' => $boost->category->name,
                'ends_at' => $boost->ends_at->toIso8601String(),
            ])->values(),
            'history' => $vendor->boostEntries()->latest('id')->limit(30)->get()->map(fn (VendorBoostEntry $entry): array => [
                'id' => $entry->id,
                'change' => $entry->change,
                'reason' => $entry->reason->label(),
                'note' => $entry->note,
                'date' => $entry->created_at->toIso8601String(),
            ])->values(),
        ]);
    }

    public function store(Request $request, StartVendorBoost $start, BoostSettings $settings): JsonResponse
    {
        $vendor = $request->user()->vendor;
        $validated = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'days' => ['required', 'integer', 'min:1', 'max:'.$settings->maxDays()],
        ]);

        if ($vendor->boost_tokens < (int) $validated['days']) {
            throw ValidationException::withMessages(['days' => __('validation.custom.boost_not_enough', ['count' => (int) $vendor->boost_tokens])]);
        }

        $category = Category::query()->findOrFail($validated['category_id']);
        $boost = $start->handle($vendor, $category, (int) $validated['days']);

        return response()->json([
            'message' => __('flash.vendor.boost_started', ['category' => $category->name, 'date' => $boost->ends_at->translatedFormat('j M Y, g:i A')]),
            'balance' => (int) $vendor->fresh()->boost_tokens,
            'boost' => ['category' => $category->name, 'ends_at' => $boost->ends_at->toIso8601String()],
        ]);
    }
}
