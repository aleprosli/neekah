<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin Vendor
 */
class VendorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'public_url' => route('vendors.show', $this->resource),
            'logo_url' => $this->logoUrl(),
            'initial' => Str::upper(Str::substr($this->name, 0, 1)),
            'category' => $this->category?->name,
            'city' => $this->city,
            'state' => $this->state,
            'tier' => ['value' => $this->tier->value, 'label' => $this->tier->label()],
            'score' => round((float) $this->score, 2),
            'points_total' => (int) $this->points_total,
            'rating_avg' => $this->reviews_count ? round((float) $this->rating_avg, 1) : null,
            'reviews_count' => (int) $this->reviews_count,
            'is_pro' => $this->isPro(),
            'pro_until' => $this->pro_until?->toIso8601String(),
            'is_elite' => $this->isElite(),
            'boost_tokens' => (int) $this->boost_tokens,
        ];
    }
}
