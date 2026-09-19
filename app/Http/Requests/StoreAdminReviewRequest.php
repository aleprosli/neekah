<?php

namespace App\Http\Requests;

use App\Models\Review;
use App\Models\Vendor;
use App\Support\ImageSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An admin typing in a review the vendor already had somewhere else.
 *
 * It is stored as an open review — no booking behind it, so no points and no
 * ranking — and stamped with the admin who entered it, because a review the
 * platform typed itself is not the same thing as one a customer submitted.
 */
class StoreAdminReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(ImageSettings $images): array
    {
        return [
            'vendor_id' => ['required', Rule::exists(Vendor::class, 'id')],
            'author_name' => ['required', 'string', 'min:2', 'max:80'],
            'author_email' => ['nullable', 'email', 'max:255'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:10', 'max:1000'],
            // Reviews being carried over are usually old, and a wall of them
            // all dated today reads as exactly what it would be.
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
            'vendor_id' => 'vendor',
            'author_name' => 'nama penulis',
            'author_email' => 'emel penulis',
            'rating' => 'bintang',
            'comment' => 'ulasan',
            'written_on' => 'tarikh review',
            'photos.*' => 'gambar',
        ];
    }
}
