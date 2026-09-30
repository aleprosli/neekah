<?php

namespace App\Actions;

use App\Enums\BoostTokenReason;
use App\Models\Category;
use App\Models\Vendor;
use App\Models\VendorBoost;
use App\Support\BoostSettings;
use App\Support\ContentVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Spend tokens to lift a vendor to the top of one of their categories, one
 * token a day. Boosting a category that is already boosted extends it. A
 * boost runs from the start of the current hour, so the hourly listing cache
 * (VendorController::listing) picks the change up exactly.
 */
class StartVendorBoost
{
    public function __construct(private GrantBoostTokens $tokens, private BoostSettings $settings) {}

    public function handle(Vendor $vendor, Category $category, int $days): VendorBoost
    {
        if (! $vendor->categories()->whereKey($category->getKey())->exists()) {
            throw ValidationException::withMessages(['category_id' => __('validation.custom.boost_category')]);
        }

        if ($days < 1 || $days > $this->settings->maxDays()) {
            throw ValidationException::withMessages(['days' => __('validation.custom.boost_days', ['max' => $this->settings->maxDays()])]);
        }

        $boost = DB::transaction(function () use ($vendor, $category, $days): VendorBoost {
            $boost = VendorBoost::query()
                ->where('vendor_id', $vendor->getKey())
                ->where('category_id', $category->getKey())
                ->where('ends_at', '>', now())
                ->lockForUpdate()
                ->first();

            if ($boost) {
                $boost->update(['ends_at' => $boost->ends_at->addDays($days), 'tokens' => $boost->tokens + $days]);
            } else {
                $boost = VendorBoost::query()->create([
                    'vendor_id' => $vendor->getKey(),
                    'category_id' => $category->getKey(),
                    'starts_at' => now()->startOfHour(),
                    'ends_at' => now()->startOfHour()->addDays($days),
                    'tokens' => $days,
                ]);
            }

            $this->tokens->handle($vendor, -$days, BoostTokenReason::Spend, $boost);

            return $boost;
        });

        ContentVersion::bumpGlobal();

        return $boost;
    }
}
