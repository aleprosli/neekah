<?php

namespace App\Models;

use App\Enums\ThreadsPostType;
use Database\Factories\ThreadsTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A caption, question, tip or promo the Threads planner may write a post from.
 * Listing and blog templates carry placeholders (ThreadsPostType::placeholders)
 * that the vendor's or the blog post's own values replace.
 */
#[Fillable(['type', 'body', 'poll_options', 'topic_tag', 'is_active', 'used_count', 'last_used_at'])]
class ThreadsTemplate extends Model
{
    /** @use HasFactory<ThreadsTemplateFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ThreadsPostType::class,
            'poll_options' => 'array',
            'is_active' => 'boolean',
            'used_count' => 'integer',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Active templates of one type, the one used longest ago first and a
     * never-used one before all of them, so the bank goes round in turn.
     */
    #[Scope]
    protected function nextInTurn(Builder $query, ThreadsPostType $type): Builder
    {
        return $query
            ->where('type', $type)
            ->where('is_active', true)
            ->orderByRaw('last_used_at is not null')
            ->orderBy('last_used_at')
            ->orderBy('id');
    }

    /**
     * The body with each placeholder replaced.
     *
     * @param  array<string, string>  $values  keyed by placeholder, e.g. ['{nama}' => 'Studio Seri']
     */
    public function render(array $values): string
    {
        return trim(strtr($this->body, $values));
    }

    public function markUsed(): void
    {
        $this->forceFill(['used_count' => $this->used_count + 1, 'last_used_at' => now()])->save();
    }
}
