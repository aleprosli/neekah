<?php

namespace App\Http\Resources\Api\Partner;

use App\Actions\StoreOptimizedImage;
use App\Models\Vendor;
use Illuminate\Support\Facades\Storage;

/**
 * The fields a partner gets for a vendor everywhere: on the leaderboard and
 * at the top of the detail. Ratings are the booking-backed ones only, the
 * same figure that ranks the vendor on Neekah.
 */
class VendorSummary
{
    /**
     * @return array<string, mixed>
     */
    public static function for(Vendor $vendor): array
    {
        return [
            'id' => $vendor->id,
            'slug' => $vendor->slug,
            'name' => $vendor->name,
            'tagline' => $vendor->tagline,
            'category' => $vendor->category ? ['slug' => $vendor->category->slug, 'name' => $vendor->category->name] : null,
            'city' => $vendor->city,
            'state' => $vendor->state,
            'logo_url' => $vendor->logoUrl(),
            'cover_url' => $vendor->cover_image ? Storage::disk('public')->url($vendor->cover_image) : null,
            'cover_thumbnail_url' => StoreOptimizedImage::thumbnailUrl($vendor->cover_image),
            'tier' => ['value' => $vendor->tier->value, 'label' => $vendor->tier->label()],
            'is_elite' => $vendor->isElite(),
            'is_pro' => $vendor->isPro(),
            'score' => round((float) $vendor->score, 2),
            'rating_avg' => $vendor->reviews_count ? round((float) $vendor->rating_avg, 1) : null,
            'reviews_count' => (int) $vendor->reviews_count,
            'price_from' => (float) $vendor->price_from,
            'price_unit' => ['value' => $vendor->price_unit->value, 'label' => $vendor->price_unit->label()],
            'neekah_url' => route('vendors.show', $vendor),
        ];
    }
}
