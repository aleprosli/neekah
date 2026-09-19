<?php

namespace App\Http\Requests;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Booking $booking */
        $booking = $this->route('booking');

        return ($this->user()?->can('review', $booking) ?? false) && $booking->canBeReviewed();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'comment' => ['required', 'string', 'min:10', 'max:1000'],
            'rating' => ['required', 'integer', 'between:1,5'],
        ];

        // The five aspects are the long form, kept for couples who have just
        // finished a booking and have something detailed to say. A star and a
        // sentence is what the review is; the rest is optional.
        foreach (Review::ASPECTS as $aspect) {
            $rules[$aspect] = ['nullable', 'integer', 'between:1,5'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'rating' => 'rating keseluruhan',
            'quality' => 'kualiti',
            'service' => 'servis',
            'communication' => 'komunikasi',
            'value' => 'nilai',
            'punctuality' => 'ketepatan masa',
            'comment' => 'ulasan',
        ];
    }
}
