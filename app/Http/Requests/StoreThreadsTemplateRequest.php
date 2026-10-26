<?php

namespace App\Http\Requests;

use App\Enums\ThreadsPostType;
use App\Models\ThreadsPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreThreadsTemplateRequest extends FormRequest
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
            // A listing caption grows when {nama} and {pautan} are filled in;
            // ThreadsComposer trims what still does not fit.
            'body' => ['required', 'string', StoreThreadsPostRequest::fitsThreads()],
            'topic_tag' => StoreThreadsPostRequest::topicRules(),
            'poll_options' => ['nullable', 'array', 'max:4'],
            'poll_options.*' => ['nullable', 'string', 'max:'.ThreadsPost::MAX_POLL_OPTION_LENGTH],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'body' => __('fields.teks_post'),
            'topic_tag' => __('fields.topik'),
            'poll_options.*' => __('fields.pilihan_undian'),
        ];
    }

    /**
     * @return array{type: string, body: string, topic_tag: string|null, poll_options: list<string>|null, is_active: bool}
     */
    public function attributesForTemplate(): array
    {
        $options = array_values(array_filter(array_map('trim', (array) $this->input('poll_options', [])), fn (string $option): bool => $option !== ''));

        return [
            'type' => $this->string('type')->toString(),
            'body' => trim($this->string('body')->toString()),
            'topic_tag' => $this->filled('topic_tag') ? trim($this->string('topic_tag')->toString()) : null,
            // A poll needs two options, and only goes on a text post.
            'poll_options' => count($options) >= 2 ? $options : null,
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
