<?php

namespace App\Http\Requests;

use App\Models\Review;
use App\Support\ImageSettings;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A vendor carrying one of their own reviews over from somewhere else.
 *
 * The vendor is taken from the signed-in account, never from the request, so
 * this can only ever land on their own profile. It is stored as an open review
 * — no rating, no points, no ranking — and the public page labels it as added
 * by the vendor, because a reader cannot otherwise tell the business's words
 * from a customer's.
 */
class StoreVendorAddedReviewRequest extends FormRequest
{
    protected $errorBag = 'addReview';

    public function authorize(): bool
    {
        return (bool) $this->user()?->vendor;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(ImageSettings $images): array
    {
        return [
            'author_name' => ['required', 'string', 'min:2', 'max:80'],
            'author_email' => ['nullable', 'email', 'max:255'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:10', 'max:1000'],
            'written_on' => ['nullable', 'date', 'before_or_equal:today'],
            'photos' => ['nullable', 'array', 'max:'.Review::MAX_PHOTOS],
            'photos.*' => $images->uploadRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'author_name' => 'nama pelanggan',
            'author_email' => 'emel pelanggan',
            'rating' => 'bintang',
            'comment' => 'ulasan',
            'written_on' => 'tarikh review',
            'photos.*' => 'gambar',
        ];
    }
}
