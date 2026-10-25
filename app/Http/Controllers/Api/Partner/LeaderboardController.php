<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Partner\LeaderboardVendorResource;
use App\Models\Category;
use App\Models\Vendor;
use App\Support\ContentVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

/**
 * Every active category with every approved vendor in it, ranked, in one
 * call. Ranked by score, the figure the kertas kerja earns from reviews,
 * completed work and points; boosts and Pro never touch it, so this is not
 * the shuffled "Disyorkan" order of the marketplace.
 *
 * A vendor working in two categories is listed in both, as on Neekah.
 */
class LeaderboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $categories = Cache::remember(
            'partner:leaderboard:'.App::getLocale().':'.ContentVersion::global(),
            ContentVersion::TTL,
            fn (): array => $this->build($request),
        );

        return response()->json(['data' => $categories]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function build(Request $request): array
    {
        $vendors = Vendor::query()
            ->approved()
            ->with(['category', 'categories'])
            ->orderByDesc('score')
            ->orderByDesc('rating_avg')
            ->orderBy('id')
            ->get();

        return Category::active()->ordered()->get()
            ->map(function (Category $category) use ($vendors, $request): array {
                /** @var Collection<int, Vendor> $ranked */
                $ranked = $vendors->filter(fn (Vendor $vendor): bool => $vendor->categories->contains($category))->values();

                return [
                    'slug' => $category->slug,
                    'name' => $category->name,
                    'icon' => $category->icon,
                    'image_url' => $category->illustrationUrl(),
                    'vendors_count' => $ranked->count(),
                    'vendors' => $ranked->map(fn (Vendor $vendor, int $index): array => (new LeaderboardVendorResource($vendor, $index + 1))->resolve($request))->all(),
                ];
            })
            ->values()
            ->all();
    }
}
