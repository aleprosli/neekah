<?php

namespace App\Actions;

use App\Enums\BoostTokenReason;
use App\Enums\VendorStatus;
use App\Enums\VendorTier;
use App\Models\Vendor;
use App\Notifications\BoostTokensReceived;
use App\Notifications\VendorStatusChanged;
use App\Support\BoostSettings;

/**
 * Approve, reject, suspend or re-queue a vendor.
 *
 * One place for it, because the admin does this both one vendor at a time and
 * to a whole batch of new registrations, and either way the vendor has to be
 * told, promoted out of New on approval, and re-scored. The first approval
 * also brings the welcome boost tokens (once, whatever happens later).
 */
class ChangeVendorStatus
{
    public function __construct(private GrantBoostTokens $boostTokens, private BoostSettings $boostSettings) {}

    public function handle(Vendor $vendor, VendorStatus $status): Vendor
    {
        $attributes = ['status' => $status];

        if ($status === VendorStatus::Approved) {
            $attributes['approved_at'] = $vendor->approved_at ?? now();

            if ($vendor->tier === VendorTier::New) {
                $attributes['tier'] = VendorTier::Verified;
            }
        }

        $vendor->update($attributes);
        $vendor->update(['score' => $vendor->calculateScore()]);

        $vendor->user->notify(new VendorStatusChanged($vendor->fresh()));

        if ($status === VendorStatus::Approved && $this->boostTokens->welcome($vendor, $tokens = $this->boostSettings->welcomeTokens())) {
            $vendor->user->notify(new BoostTokensReceived($tokens, BoostTokenReason::Welcome, $vendor->boost_tokens));
        }

        return $vendor;
    }
}
