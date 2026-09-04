<?php

namespace App\Actions;

use App\Enums\PointReason;
use App\Enums\VendorStatus;
use App\Enums\ViolationAction;
use App\Enums\ViolationStatus;
use App\Models\User;
use App\Models\VendorViolation;
use App\Notifications\VendorViolationRecorded;
use Illuminate\Support\Facades\DB;

class ApplyViolationAction
{
    public function __construct(
        private RecalculateVendorStats $recalculateStats,
        private AwardVendorPoints $awardPoints,
    ) {}

    /**
     * Uphold a violation and apply the escalation step for the vendor's offence count.
     * Warning, then point deduction with a ranking drop, then suspension, then removal.
     */
    public function uphold(VendorViolation $violation, User $admin, ?string $note = null): VendorViolation
    {
        return DB::transaction(function () use ($violation, $admin, $note): VendorViolation {
            $vendor = $violation->vendor;
            $offenceNumber = $vendor->violations()->upheld()->count() + 1;
            $action = ViolationAction::forOffence($offenceNumber);

            $violation->update([
                'status' => ViolationStatus::Upheld,
                'action' => $action,
                'offence_number' => $offenceNumber,
                'admin_note' => $note,
                'resolved_by' => $admin->id,
                'resolved_at' => now(),
            ]);

            $vendor->penalty_points += $action->penaltyPoints();
            $vendor->violations_count = $offenceNumber;

            // The ranking engine applies the tier drop from the violation count,
            // so it is not undone by the next recalculation.
            if ($action === ViolationAction::Suspension) {
                $vendor->status = VendorStatus::Suspended;
            }

            if ($action === ViolationAction::Removal) {
                $vendor->status = VendorStatus::Rejected;
            }

            $vendor->tier_locked = false;
            $vendor->save();

            if ($action->penaltyPoints() > 0) {
                $this->awardPoints->award($vendor, PointReason::ViolationPenalty, $violation, -$action->penaltyPoints());
            }

            $this->recalculateStats->handle($vendor);

            $vendor->user->notify(new VendorViolationRecorded($violation->fresh()));

            return $violation;
        });
    }

    public function dismiss(VendorViolation $violation, User $admin, ?string $note = null): VendorViolation
    {
        $violation->update([
            'status' => ViolationStatus::Dismissed,
            'admin_note' => $note,
            'resolved_by' => $admin->id,
            'resolved_at' => now(),
        ]);

        return $violation;
    }
}
