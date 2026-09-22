<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCardMusicTrackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'artist' => ['nullable', 'string', 'max:120'],
            // An invitation plays one quiet track in the background, so a few
            // megabytes of mp3 is the whole budget.
            'audio' => [$this->routeIs('*admin.card-music.store') ? 'required' : 'nullable', 'file', 'mimetypes:audio/mpeg,audio/mp4,audio/aac,audio/ogg', 'max:8192'],
            'seconds' => ['nullable', 'integer', 'min:5', 'max:1800'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function trackAttributes(): array
    {
        return [
            'title' => $this->string('title')->toString(),
            'artist' => $this->string('artist')->toString() ?: null,
            'seconds' => $this->integer('seconds') ?: null,
            'is_active' => $this->boolean('is_active'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => __('fields.tajuk'),
            'audio' => __('fields.fail_audio'),
        ];
    }
}
