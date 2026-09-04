<?php

namespace App\Http\Requests;

use App\Enums\BookingStatus;
use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTimelineItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('wedding')) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Wedding $wedding */
        $wedding = $this->route('wedding');

        return [
            'title' => ['required', 'string', 'max:120'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after:starts_at'],
            'vendor_id' => ['nullable', Rule::in($this->bookedVendorIds($wedding))],
            'location' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<int, int>
     */
    private function bookedVendorIds(Wedding $wedding): array
    {
        return $wedding->bookings()
            ->whereIn('status', [BookingStatus::PendingPayment, BookingStatus::Confirmed, BookingStatus::Completed])
            ->pluck('vendor_id')
            ->unique()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'aktiviti',
            'starts_at' => 'masa mula',
            'ends_at' => 'masa tamat',
            'vendor_id' => 'vendor',
            'location' => 'lokasi',
            'notes' => 'nota',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vendor_id.in' => 'Anda hanya boleh menugaskan vendor yang telah ditempah untuk majlis ini.',
        ];
    }
}
