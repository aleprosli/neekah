<?php

namespace App\Http\Resources\Api\Partner;

use App\Models\Review;
use App\Models\ReviewPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A published review as a partner shows it. The author's email is never
 * included; it is only for Neekah to reach them.
 *
 * @mixin Review
 */
class ReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'author_name' => $this->authorName(),
            'rating' => (int) $this->rating,
            'comment' => $this->comment,
            'verified' => $this->isVerified(),
            'source' => $this->sourceLabel(),
            'photos' => $this->photos->map(fn (ReviewPhoto $photo): array => [
                'url' => $photo->url(),
                'thumbnail_url' => $photo->thumbnailUrl(),
            ])->values()->all(),
            'reply' => $this->hasReply() ? $this->reply : null,
            'replied_at' => $this->hasReply() ? $this->replied_at?->toIso8601String() : null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
