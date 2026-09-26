<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Enquiry;
use Illuminate\Http\Request;

/**
 * One enquiry in full: what the couple asked, about which wedding, and the
 * reply so far. Expects `user`, `package` and `wedding` loaded.
 *
 * @mixin Enquiry
 */
class EnquiryDetailResource extends EnquiryResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'message' => $this->message,
            'reply' => $this->reply,
            'customer' => [
                'name' => $this->user?->name,
                'email' => $this->user?->email,
                'phone' => $this->user?->phone,
                'whatsapp_url' => $this->user?->whatsappUrl(),
            ],
            'wedding' => $this->wedding ? [
                'title' => $this->wedding->title,
                'city' => $this->wedding->city,
                'state' => $this->wedding->state,
                'budget' => $this->wedding->budget !== null ? (float) $this->wedding->budget : null,
            ] : null,
        ];
    }
}
