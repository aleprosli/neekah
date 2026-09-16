<?php

namespace App\Http\Requests;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Support\ImageSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RecordManualPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('recordPayment', $this->booking()) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(ImageSettings $images): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:160'],
            'receipt' => array_merge(['nullable'], $images->uploadRules()),
        ];
    }

    /**
     * The couple can only ever have paid what the booking is worth; anything
     * beyond it is a typo, and a typo the vendor would have to chase.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $booking = $this->booking();
                $remaining = $booking->outstandingAmount() - $this->pendingAmount($booking);

                if ($this->float('amount') > $remaining + 0.01) {
                    $validator->errors()->add('amount', 'Baki yang belum direkod hanya RM'.number_format(max($remaining, 0), 2).'.');
                }
            },
        ];
    }

    /** Amounts already recorded and still waiting on the vendor to check. */
    private function pendingAmount(Booking $booking): float
    {
        return (float) $booking->payments->where('status', PaymentStatus::AwaitingVerification)->sum('amount');
    }

    private function booking(): Booking
    {
        return $this->route('booking');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'amount' => 'jumlah',
            'paid_on' => 'tarikh bayaran',
            'note' => 'nota',
            'receipt' => 'resit',
        ];
    }
}
