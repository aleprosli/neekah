<?php

namespace App\Http\Resources\Api\Partner;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of a partner's leaderboard: enough to draw a card and open the
 * vendor's detail. Its rank inside the category is set by the caller.
 *
 * @mixin Vendor
 */
class LeaderboardVendorResource extends JsonResource
{
    public function __construct(Vendor $resource, private int $rank)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'rank' => $this->rank,
            ...VendorSummary::for($this->resource),
        ];
    }
}
