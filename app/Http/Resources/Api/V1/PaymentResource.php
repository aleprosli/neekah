<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * A payment on one of the vendor's bookings, and what they may do with it:
 * confirm or reject a transfer the couple recorded, or note that a paid
 * deposit was given back.
 *
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'amount' => (float) $this->amount,
            'status' => Status::of($this->status),
            'paid_on' => $this->paid_on?->toDateString(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'method_label' => $this->isOnline()
                ? collect([Str::headline($this->gateway), $this->method])->filter()->implode(' · ')
                : __('pages.receipt.method_transfer'),
            'note' => $this->note,
            'recorded_by' => $this->recorder?->name,
            'slip_url' => $this->receiptUrl(),
            'receipt_url' => $this->isPaid() ? route('payments.document', $this->resource) : null,
            'can' => [
                'verify' => $this->isAwaitingVerification(),
                'reject' => $this->isAwaitingVerification(),
                'mark_refunded' => $this->isPaid(),
            ],
        ];
    }
}
