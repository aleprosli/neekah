<?php

namespace App\Http\Requests;

use App\Models\Post;
use App\Support\HtmlSanitizer;
use App\Support\ImageSettings;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * A blank slug is made from the title, and any slug typed in is normalised
     * the same way, so the URL is always lowercase words joined by hyphens.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->string('slug')->trim()->toString() ?: $this->string('title')->toString()),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(ImageSettings $images): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:180', Rule::unique(Post::class, 'slug')->ignore($this->route('post'))],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string', 'max:200000'],
            'cover_image' => ['nullable', ...$images->uploadRules()],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:170'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForPost(HtmlSanitizer $sanitizer): array
    {
        return [
            ...$this->safe()->only(['title', 'slug', 'excerpt', 'meta_title', 'meta_description']),
            'body' => $sanitizer->clean($this->string('body')->toString()),
            'published_at' => $this->publishedAt(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'tajuk',
            'slug' => 'slug URL',
            'excerpt' => 'ringkasan',
            'body' => 'isi artikel',
            'cover_image' => 'gambar utama',
            'meta_title' => 'tajuk SEO',
            'meta_description' => 'deskripsi SEO',
            'published_at' => 'tarikh siaran',
        ];
    }

    /**
     * A draft has no date. Publishing keeps an article's original date on
     * later edits, so fixing a typo does not push it back to the top.
     */
    private function publishedAt(): ?CarbonInterface
    {
        if ($this->input('status') !== 'published') {
            return null;
        }

        // The form sends a bare local time, "2026-10-01T09:00", meant as Malaysian time.
        if ($this->filled('published_at')) {
            return $this->date('published_at', null, Post::LOCAL_TIMEZONE)?->setTimezone(config('app.timezone'));
        }

        return $this->route('post')?->published_at ?? now();
    }
}
