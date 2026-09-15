<?php

namespace App\Models;

use App\Actions\StoreOptimizedImage;
use Carbon\CarbonInterface;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A blog article. It is public once published_at has passed, so an admin can
 * schedule one by setting a future date.
 */
#[Fillable(['user_id', 'title', 'slug', 'excerpt', 'body', 'cover_image', 'meta_title', 'meta_description', 'published_at'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    /**
     * The app stores times in UTC, but an admin picks a publish time and a
     * reader sees a date in Malaysian time.
     */
    public const LOCAL_TIMEZONE = 'Asia/Kuala_Lumpur';

    /** A comfortable reading pace, for the "5 min bacaan" label. */
    private const WORDS_PER_MINUTE = 200;

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->lte(now());
    }

    public function isScheduled(): bool
    {
        return $this->published_at !== null && $this->published_at->isFuture();
    }

    public function url(): string
    {
        return route('blog.show', $this);
    }

    /**
     * A copy, so converting it for display never changes the stored value.
     */
    public function localPublishedAt(): ?CarbonInterface
    {
        return $this->published_at?->copy()->setTimezone(self::LOCAL_TIMEZONE);
    }

    public function coverUrl(): ?string
    {
        return $this->cover_image ? Storage::disk('public')->url($this->cover_image) : null;
    }

    public function coverThumbnailUrl(): ?string
    {
        return StoreOptimizedImage::thumbnailUrl($this->cover_image);
    }

    /**
     * The excerpt, or the opening of the article when none was written.
     */
    public function summary(): string
    {
        return $this->excerpt ?: Str::limit($this->plainText(), 200);
    }

    public function readingMinutes(): int
    {
        $words = preg_split('/\s+/u', $this->plainText(), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return max(1, (int) ceil(count($words) / self::WORDS_PER_MINUTE));
    }

    private function plainText(): string
    {
        return Str::of(strip_tags(str_replace('<', ' <', (string) $this->body)))->squish()->value();
    }
}
