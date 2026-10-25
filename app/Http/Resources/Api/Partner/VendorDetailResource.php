<?php

namespace App\Http\Resources\Api\Partner;

use App\Models\Category;
use App\Models\Package;
use App\Models\PortfolioItem;
use App\Models\Review;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * Everything a vendor's public page shows, for a partner to draw its own.
 * Expects category, categories, packages (active), portfolioItems (visible)
 * and the latest published reviews with their photos to be loaded.
 *
 * @mixin Vendor
 */
class VendorDetailResource extends JsonResource
{
    /**
     * @param  array{total: int, average: float}  $openReviews
     */
    public function __construct(Vendor $resource, private string $partner, private array $openReviews)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...VendorSummary::for($this->resource),
            'description' => $this->description,
            'district' => $this->district,
            'service_states' => $this->serviceStates(),
            'categories' => $this->categories->map(fn (Category $category): array => [
                'slug' => $category->slug,
                'name' => $category->name,
            ])->values()->all(),
            'social_links' => $this->socialLinks(),
            'contact' => [
                // A Neekah link that counts the tap for the vendor, then opens
                // WhatsApp. Signed, so it needs no key in the player's browser.
                'whatsapp_url' => $this->whatsappUrl() ? URL::signedRoute('api.partner.v1.vendors.whatsapp', ['vendor' => $this->resource, 'partner' => $this->partner]) : null,
                'phone' => $this->phone ?: null,
            ],
            'gallery' => $this->portfolioItems->map(fn (PortfolioItem $item): array => [
                'url' => $item->url(),
                'thumbnail_url' => $item->thumbnailUrl(),
                'caption' => $item->caption,
            ])->values()->all(),
            'packages' => $this->packages->map(fn (Package $package): array => [
                'id' => $package->id,
                'name' => $package->name,
                'description' => $package->description,
                'price' => (float) $package->price,
                'duration' => $package->duration,
                'features' => array_values($package->features ?? []),
                'image_url' => $package->imageUrl(),
                'thumbnail_url' => $package->thumbnailUrl(),
            ])->values()->all(),
            'reviews' => [
                // Kept apart on purpose: only booking-backed reviews rank a
                // vendor, so the two averages are never folded into one.
                'verified' => [
                    'count' => (int) $this->reviews_count,
                    'average' => $this->reviews_count ? round((float) $this->rating_avg, 1) : null,
                ],
                'open' => [
                    'count' => $this->openReviews['total'],
                    'average' => $this->openReviews['total'] ? $this->openReviews['average'] : null,
                ],
                'latest' => $this->reviews->map(fn (Review $review): array => (new ReviewResource($review))->resolve($request))->values()->all(),
            ],
        ];
    }
}
