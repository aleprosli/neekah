<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One booking in a list: enough to recognise it and see where it stands.
 * Expects `user` and `payments` loaded.
 *
 * @mixin Booking
 */
class BookingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'event_date' => $this->event_date->toDateString(),
            'customer_name' => $this->user?->name,
            'package_name' => $this->package_name,
            'total' => (float) $this->total_amount,
            'paid' => (float) $this->payments->where('status', PaymentStatus::Paid)->sum('amount'),
            'status' => Status::of($this->status),
            'is_online' => $this->isOnline(),
        ];
    }
}
