<?php

namespace App\Http\Requests;

use App\Models\Review;
use App\Support\ImageSettings;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A review sent by a partner on behalf of one of its players. The same rules
 * as the profile form, without Turnstile: the partner key and the per-player
 * throttle stand where the captcha would.
 */
class StorePartnerReviewRequest extends FormRequest
{
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
            'author_name' => ['required', 'string', 'min:2', 'max:80'],
            'author_email' => ['nullable', 'email', 'max:255'],
            // The partner's own id for the player. Never stored; it is what
            // the throttle counts, since every call comes from one server.
            'player_id' => ['required', 'string', 'max:100'],
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
            'rating' => __('fields.bintang'),
            'comment' => __('fields.ulasan'),
            'author_name' => __('fields.nama'),
            'author_email' => __('fields.emel'),
            'photos' => __('fields.gambar'),
            'photos.*' => __('fields.gambar'),
        ];
    }
}
