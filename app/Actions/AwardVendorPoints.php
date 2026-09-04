<?php

namespace App\Actions;

use App\Enums\PointReason;
use App\Models\Vendor;
use App\Models\VendorPoint;
use Illuminate\Database\Eloquent\Model;

class AwardVendorPoints
{
    /**
     * Award points once for a given reason and subject. Repeat calls are ignored,
     * so replaying an event never inflates a vendor's total.
     */
    public function award(Vendor $vendor, PointReason $reason, ?Model $subject = null, ?int $points = null): ?VendorPoint
    {
        $exists = $vendor->points()
            ->where('reason', $reason)
            ->where('pointable_type', $subject?->getMorphClass())
            ->where('pointable_id', $subject?->getKey())
            ->exists();

        if ($exists) {
            return null;
        }

        $point = $vendor->points()->create([
            'reason' => $reason,
            'points' => $points ?? $reason->points(),
            'pointable_type' => $subject?->getMorphClass(),
            'pointable_id' => $subject?->getKey(),
        ]);

        $vendor->increment('points_total', $point->points);

        return $point;
    }

    /**
     * Remove a milestone award that no longer holds, such as a profile that lost its description.
     */
    public function revokeMilestone(Vendor $vendor, PointReason $reason): void
    {
        $points = $vendor->points()->where('reason', $reason)->whereNull('pointable_id')->get();

        if ($points->isEmpty()) {
            return;
        }

        $vendor->decrement('points_total', $points->sum('points'));
        $vendor->points()->whereKey($points->modelKeys())->delete();
    }

    /**
     * Keep the profile and catalogue milestones in step with the vendor's current state.
     */
    public function syncMilestones(Vendor $vendor): void
    {
        $checks = [
            [PointReason::ProfileComplete, $vendor->hasCompleteProfile()],
            [PointReason::CatalogueComplete, $vendor->hasCompleteCatalogue()],
            [PointReason::HighCompletionRate, $vendor->completed_bookings_count >= 5 && $vendor->completion_rate >= 90],
        ];

        foreach ($checks as [$reason, $earned]) {
            $earned
                ? $this->award($vendor, $reason)
                : $this->revokeMilestone($vendor, $reason);
        }
    }
}
