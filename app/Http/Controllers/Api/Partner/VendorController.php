<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Partner\VendorDetailResource;
use App\Models\Vendor;
use App\Support\ContentVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class VendorController extends Controller
{
    /** How many reviews come with the detail, newest first. */
    private const LATEST_REVIEWS = 12;

    /**
     * A vendor's full public profile, held against the vendor's own version
     * like their page on Neekah, so a new review or price shows at once.
     */
    public function show(Request $request, Vendor $vendor): JsonResponse
    {
        abort_unless($vendor->isApproved(), 404);

        $partner = (string) $request->attributes->get('partner');

        $detail = Cache::remember(
            'partner:vendor:'.$vendor->getKey().':'.$partner.':'.App::getLocale().':'.ContentVersion::forVendor($vendor->getKey()),
            ContentVersion::TTL,
            function () use ($vendor, $partner, $request): array {
                $vendor->load([
                    'category',
                    'categories',
                    'packages' => fn ($query) => $query->active(),
                    'portfolioItems' => fn ($query) => $query->visible(),
                    'reviews' => fn ($query) => $query->published()->with(['user:id,name', 'photos'])->latest()->limit(self::LATEST_REVIEWS),
                ]);

                $vendor->reviews->each->setRelation('vendor', $vendor);

                $open = $vendor->reviews()->open()->published()
                    ->selectRaw('count(*) as total, avg(rating) as average')
                    ->first();

                return (new VendorDetailResource($vendor, $partner, [
                    'total' => (int) $open->total,
                    'average' => round((float) $open->average, 1),
                ]))->resolve($request);
            },
        );

        return response()->json(['data' => $detail]);
    }
}
