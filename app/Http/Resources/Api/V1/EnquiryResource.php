<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Enquiry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * One enquiry in a list. Expects `user` and `package` loaded.
 *
 * @mixin Enquiry
 */
class EnquiryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_name' => $this->user?->name,
            'preview' => Str::limit(Str::squish($this->message), 140),
            'event_date' => $this->event_date?->toDateString(),
            'package_name' => $this->package?->name,
            'status' => Status::of($this->status),
            'created_at' => $this->created_at->toIso8601String(),
            'replied_at' => $this->replied_at?->toIso8601String(),
        ];
    }
}
