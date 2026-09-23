<?php

namespace App\Http\Requests;

use App\Enums\SongMoment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWeddingSongRequest extends FormRequest
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
        return [
            'moment' => ['required', Rule::enum(SongMoment::class)],
            'title' => ['required', 'string', 'max:120'],
            'artist' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'moment' => __('fields.momen'),
            'title' => __('fields.tajuk_lagu'),
            'artist' => __('fields.artis'),
            'notes' => __('fields.nota'),
        ];
    }
}
