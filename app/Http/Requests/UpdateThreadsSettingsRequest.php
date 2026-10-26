<?php

namespace App\Http\Requests;

use App\Enums\ThreadsPostType;
use App\Support\ThreadsSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateThreadsSettingsRequest extends FormRequest
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
            'enabled' => ['nullable', 'boolean'],
            'topic_tag' => StoreThreadsPostRequest::topicRules(),
            'days_ahead' => ['required', 'integer', 'min:1', 'max:'.ThreadsSettings::MAX_DAYS_AHEAD],
            'listing_cooldown_days' => ['required', 'integer', 'min:1', 'max:365'],
            'slots' => ['required', 'array', 'min:1', 'max:'.ThreadsSettings::MAX_SLOTS],
            // Two posts at the same minute would go out one after the other anyway.
            'slots.*.time' => ['required', 'date_format:H:i', 'distinct'],
            'slots.*.type' => ['required', Rule::enum(ThreadsPostType::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'topic_tag' => __('fields.topik'),
            'days_ahead' => __('fields.hari_ke_depan'),
            'listing_cooldown_days' => __('fields.tempoh_rehat_vendor'),
            'slots' => __('fields.slot_harian'),
            'slots.*.time' => __('fields.masa_post'),
        ];
    }

    /**
     * @return list<array{time: string, type: string}>
     */
    public function slots(): array
    {
        return collect($this->validated('slots'))
            ->map(fn (array $slot): array => ['time' => $slot['time'], 'type' => $slot['type']])
            ->sortBy('time')
            ->values()
            ->all();
    }
}
