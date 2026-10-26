<?php

namespace App\Http\Requests;

use App\Enums\ThreadsPostType;
use App\Models\ThreadsPost;
use App\Support\ImageSettings;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A Threads post an admin writes or edits by hand. The limits are Threads'
 * own: 500 bytes of text, a topic without full stops or ampersands, and two
 * to four poll options of 25 characters.
 */
class StoreThreadsPostRequest extends FormRequest
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
            'type' => ['required', Rule::enum(ThreadsPostType::class)],
            'text' => ['required', 'string', self::fitsThreads()],
            'scheduled_at' => ['required', 'date'],
            'link_url' => ['nullable', 'url:https,http', 'max:2048'],
            'topic_tag' => self::topicRules(),
            'poll_options' => ['nullable', 'array', 'max:4'],
            'poll_options.*' => ['nullable', 'string', 'max:'.ThreadsPost::MAX_POLL_OPTION_LENGTH],
            'image' => ['nullable', ...app(ImageSettings::class)->uploadRules()],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'text' => __('fields.teks_post'),
            'scheduled_at' => __('fields.masa_post'),
            'link_url' => __('fields.pautan'),
            'topic_tag' => __('fields.topik'),
            'poll_options.*' => __('fields.pilihan_undian'),
        ];
    }

    /**
     * The options filled in, in order; fewer than two is no poll at all.
     *
     * @return list<string>|null
     */
    public function pollOptions(): ?array
    {
        $options = array_values(array_filter(array_map('trim', (array) $this->input('poll_options', [])), fn (string $option): bool => $option !== ''));

        return count($options) >= 2 ? $options : null;
    }

    /**
     * Threads counts in UTF-8 bytes, so an emoji costs four of the 500.
     */
    public static function fitsThreads(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (strlen((string) $value) > ThreadsPost::MAX_TEXT_BYTES) {
                $fail(__('validation.custom.threads_text_too_long', ['max' => ThreadsPost::MAX_TEXT_BYTES]));
            }
        };
    }

    /**
     * @return array<int, string>
     */
    public static function topicRules(): array
    {
        return ['nullable', 'string', 'max:50', 'not_regex:/[.&]/'];
    }
}
