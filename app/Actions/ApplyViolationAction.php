<?php

namespace App\Actions;

use App\Enums\VendorStatus;
use App\Enums\VendorTier;
use App\Enums\ViolationAction;
use App\Enums\ViolationStatus;
use App\Models\User;
use App\Models\VendorViolation;
use App\Notifications\VendorViolationRecorded;
use Illuminate\Support\Facades\DB;

class ApplyViolationAction
{
    public function __construct(private RecalculateVendorStats $recalculateStats) {}

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

            if ($action === ViolationAction::PointDeduction) {
                $vendor->tier = $this->demote($vendor->tier);
            }

            if ($action === ViolationAction::Suspension) {
                $vendor->tier = $this->demote($vendor->tier);
                $vendor->status = VendorStatus::Suspended;
            }

            if ($action === ViolationAction::Removal) {
                $vendor->tier = VendorTier::New;
                $vendor->status = VendorStatus::Rejected;
            }

            $vendor->save();
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

    private function demote(VendorTier $tier): VendorTier
    {
        return match ($tier) {
            VendorTier::Recommended => VendorTier::Top,
            VendorTier::Top => VendorTier::Trusted,
            VendorTier::Trusted => VendorTier::Verified,
            default => VendorTier::New,
        };
    }
}
