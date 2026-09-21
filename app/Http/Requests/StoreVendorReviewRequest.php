<?php

namespace App\Http\Requests;

use App\Models\Review;
use App\Rules\Turnstile;
use App\Support\ImageSettings;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A review written straight on a vendor's profile, signed in or not.
 */
class StoreVendorReviewRequest extends FormRequest
{
    /**
     * The vendor page carries a booking form as well, and both share one
     * $errors. Without a bag of its own, a rejected review would light up the
     * booking card instead of the form the visitor was filling in.
     */
    protected $errorBag = 'review';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(ImageSettings $images): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:10', 'max:1000'],
            // An account signs its own name. Only a guest is asked for one.
            'author_name' => [$this->user() ? 'nullable' : 'required', 'string', 'min:2', 'max:80'],
            // Never published; it is how an admin reaches the author about a
            // review the vendor has disputed.
            'author_email' => ['nullable', 'email', 'max:255'],
            'photos' => ['nullable', 'array', 'max:'.Review::MAX_PHOTOS],
            'photos.*' => $images->uploadRules(),
            'cf-turnstile-response' => [app(Turnstile::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'rating' => __('fields.bintang'),
            'comment' => __('fields.ulasan'),
            'author_name' => __('fields.nama'),
            'author_email' => __('fields.emel'),
            'photos' => __('fields.gambar'),
            'photos.*' => __('fields.gambar'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photos.max' => 'Maksimum '.Review::MAX_PHOTOS.' gambar setiap review.',
        ];
    }
}
