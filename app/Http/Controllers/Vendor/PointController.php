<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\RecalculateVendorStats;
use App\Enums\PointReason;
use App\Enums\VendorTier;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PointController extends Controller
{
    /**
     * Show the vendor how their points, score and ranking are earned.
     */
    public function index(Request $request, RecalculateVendorStats $recalculateStats): View
    {
        $vendor = $request->user()->vendor;
        $recalculateStats->handle($vendor);
        $vendor->refresh();

        return view('vendor.points', [
            'vendor' => $vendor,
            'history' => $vendor->points()->with('pointable')->latest()->limit(25)->get(),
            'breakdown' => $vendor->points()
                ->selectRaw('reason, sum(points) as total, count(*) as awards')
                ->groupBy('reason')
                ->get()
                ->keyBy(fn ($row) => $row->reason->value),
            'earnable' => collect(PointReason::cases())->reject(fn (PointReason $reason) => $reason === PointReason::ViolationPenalty),
            'nextTier' => $this->nextTier($vendor->tier),
            'requirements' => $this->requirements($vendor),
        ]);
    }

    private function nextTier(VendorTier $tier): ?VendorTier
    {
        return match ($tier) {
            VendorTier::New, VendorTier::Verified => VendorTier::Trusted,
            VendorTier::Trusted => VendorTier::Top,
            VendorTier::Top => VendorTier::Recommended,
            VendorTier::Recommended => null,
        };
    }

    /**
     * What the vendor still needs for the tier above them.
     *
     * @return array<int, array{label: string, current: string, target: string, met: bool}>
     */
    private function requirements($vendor): array
    {
        $targets = match ($this->nextTier($vendor->tier)) {
            VendorTier::Trusted => ['completed' => 5, 'rating' => 4.0, 'reviews' => 3, 'response' => 0, 'completion' => 0],
            VendorTier::Top => ['completed' => 15, 'rating' => 4.5, 'reviews' => 8, 'response' => 90, 'completion' => 0],
            VendorTier::Recommended => ['completed' => 30, 'rating' => 4.7, 'reviews' => 15, 'response' => 95, 'completion' => 90],
            default => null,
        };

        if (! $targets) {
            return [];
        }

        $rows = [
            ['label' => 'Booking selesai', 'value' => $vendor->completed_bookings_count, 'target' => $targets['completed'], 'suffix' => ''],
            ['label' => 'Rating purata', 'value' => (float) $vendor->rating_avg, 'target' => $targets['rating'], 'suffix' => ''],
            ['label' => 'Jumlah review', 'value' => $vendor->reviews_count, 'target' => $targets['reviews'], 'suffix' => ''],
            ['label' => 'Response rate', 'value' => $vendor->response_rate, 'target' => $targets['response'], 'suffix' => '%'],
            ['label' => 'Completion rate', 'value' => $vendor->completion_rate, 'target' => $targets['completion'], 'suffix' => '%'],
        ];

        return collect($rows)
            ->reject(fn (array $row): bool => $row['target'] <= 0)
            ->map(fn (array $row): array => [
                'label' => $row['label'],
                'current' => $row['value'].$row['suffix'],
                'target' => $row['target'].$row['suffix'],
                'met' => $row['value'] >= $row['target'],
            ])
            ->values()
            ->all();
    }
}
