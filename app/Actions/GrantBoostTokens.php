<?php

namespace App\Actions;

use App\Enums\BoostTokenReason;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBoostEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only way a vendor's boost tokens change: a ledger line and the
 * balance together, under a lock on the vendor, never below zero.
 *
 * Written without touching the vendor's timestamps or firing its saved
 * event, so a token moving does not reorder the sitemap or clear caches.
 */
class GrantBoostTokens
{
    public function handle(Vendor $vendor, int $change, BoostTokenReason $reason, ?Model $source = null, ?User $admin = null, ?string $note = null): VendorBoostEntry
    {
        return DB::transaction(function () use ($vendor, $change, $reason, $source, $admin, $note): VendorBoostEntry {
            $balance = (int) Vendor::query()->whereKey($vendor->getKey())->lockForUpdate()->value('boost_tokens');

            if ($balance + $change < 0) {
                throw ValidationException::withMessages(['tokens' => __('validation.custom.boost_not_enough', ['count' => $balance])]);
            }

            $entry = VendorBoostEntry::query()->create([
                'vendor_id' => $vendor->getKey(),
                'change' => $change,
                'reason' => $reason,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'note' => $note,
                'added_by' => $admin?->getKey(),
            ]);

            Vendor::query()->whereKey($vendor->getKey())->toBase()->update(['boost_tokens' => $balance + $change]);
            $vendor->setAttribute('boost_tokens', $balance + $change)->syncOriginalAttribute('boost_tokens');

            return $entry;
        });
    }

    /**
     * Welcome tokens, once per vendor however many times it is approved.
     */
    public function welcome(Vendor $vendor, int $tokens): ?VendorBoostEntry
    {
        if ($tokens <= 0 || VendorBoostEntry::query()->where('vendor_id', $vendor->getKey())->where('reason', BoostTokenReason::Welcome)->exists()) {
            return null;
        }

        return $this->handle($vendor, $tokens, BoostTokenReason::Welcome);
    }
}
