<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\RecalculateVendorStats;
use App\Enums\PointReason;
use App\Http\Controllers\Controller;
use App\Models\VendorPoint;
use App\Support\TierProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Where the vendor stands: points, score, tier and what the next tier needs,
 * with the same numbers as the website's points page.
 */
class PointController extends Controller
{
    public function __invoke(Request $request, RecalculateVendorStats $recalculateStats): JsonResponse
    {
        $vendor = $request->user()->vendor;
        $recalculateStats->handle($vendor);
        $vendor->refresh();

        $next = TierProgress::next($vendor->tier);
        $earned = $vendor->points()
            ->selectRaw('reason, sum(points) as total, count(*) as awards')
            ->groupBy('reason')
            ->get()
            ->keyBy(fn ($row) => $row->reason->value);

        return response()->json([
            'points_total' => (int) $vendor->points_total,
            'penalty_points' => (int) $vendor->penalty_points,
            'score' => round((float) $vendor->score, 2),
            'completion_rate' => $vendor->completion_rate,
            'response_rate_label' => $vendor->responseRateLabel(),
            'completed_bookings' => (int) $vendor->completed_bookings_count,
            'tier' => ['value' => $vendor->tier->value, 'label' => $vendor->tier->label()],
            'next_tier' => $next ? ['value' => $next->value, 'label' => $next->label()] : null,
            'tier_locked' => (bool) $vendor->tier_locked,
            'requirements' => TierProgress::requirements($vendor),
            'breakdown' => collect(PointReason::cases())
                ->reject(fn (PointReason $reason): bool => $reason === PointReason::ViolationPenalty)
                ->map(fn (PointReason $reason): array => [
                    'reason' => $reason->value,
                    'label' => $reason->label(),
                    'points_each' => $reason->points(),
                    'total' => (int) ($earned[$reason->value]->total ?? 0),
                    'awards' => (int) ($earned[$reason->value]->awards ?? 0),
                ])->values(),
            'recent' => $vendor->points()->latest()->limit(25)->get()->map(fn (VendorPoint $point): array => [
                'id' => $point->id,
                'points' => $point->points,
                'label' => $point->reason->label(),
                'created_at' => $point->created_at->toIso8601String(),
            ])->values(),
        ]);
    }
}
