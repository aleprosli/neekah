<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\WeddingTimelineItem;
use Illuminate\Http\Request;

/**
 * One booking in full, with what the vendor may do to it now. Vendors see
 * only the timeline slots assigned to them, as the kertas kerja specifies.
 *
 * @mixin Booking
 */
class BookingDetailResource extends BookingResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::toArray($request),
            'created_at' => $this->created_at->toIso8601String(),
            'notes' => $this->notes,
            'deposit_amount' => $this->deposit_amount !== null ? (float) $this->deposit_amount : null,
            'outstanding' => $this->outstandingAmount(),
            'hold_expires_at' => $this->hold_expires_at?->toIso8601String(),
            'customer' => [
                'name' => $this->user?->name,
                'email' => $this->user?->email,
                'phone' => $this->user?->phone,
                'whatsapp_url' => $this->user?->whatsappUrl(),
            ],
            'payments' => PaymentResource::collection($this->payments->sortBy('created_at')->values()),
            'timeline' => $this->wedding_id
                ? $this->wedding->timelineItems()->where('vendor_id', $this->vendor_id)->get()->map(fn (WeddingTimelineItem $item): array => [
                    'id' => $item->id,
                    'time' => $item->startsAtLabel(),
                    'title' => $item->title,
                    'location' => $item->location,
                    'notes' => $item->notes,
                ])->values()
                : [],
            'review' => $this->review ? ['rating' => $this->review->rating, 'comment' => $this->review->comment] : null,
            'can' => [
                'complete' => $this->status === BookingStatus::Confirmed && ! $this->event_date->isFuture(),
                'cancel' => $user->can('vendorCancel', $this->resource),
            ],
        ];
    }
}
