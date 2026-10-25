<?php

namespace App\Models;

use App\Actions\StoreOptimizedImage;
use App\Enums\ThreadsPostStatus;
use App\Enums\ThreadsPostType;
use Database\Factories\ThreadsPostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * One post on Neekah's Threads account, planned by PlanThreadsPosts or written
 * by an admin, and sent by PublishThreadsPost when its time comes.
 */
#[Fillable([
    'type', 'status', 'text', 'image_path', 'image_url', 'link_url', 'topic_tag', 'poll_options',
    'vendor_id', 'post_id', 'threads_template_id', 'user_id', 'scheduled_at', 'slot_at',
    'container_id', 'container_created_at', 'attempts', 'threads_media_id', 'permalink', 'published_at', 'error',
])]
class ThreadsPost extends Model
{
    /** @use HasFactory<ThreadsPostFactory> */
    use HasFactory;

    /**
     * Threads refuses a post longer than this, and counts it in UTF-8 bytes,
     * so one emoji takes four. Malay text is one byte a letter.
     */
    public const MAX_TEXT_BYTES = 500;

    /** Threads takes two to four poll options of up to this many characters. */
    public const MAX_POLL_OPTION_LENGTH = 25;

    /** @var list<string> */
    public const POLL_KEYS = ['option_a', 'option_b', 'option_c', 'option_d'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ThreadsPostType::class,
            'status' => ThreadsPostStatus::class,
            'poll_options' => 'array',
            'scheduled_at' => 'datetime',
            'slot_at' => 'datetime',
            'container_created_at' => 'datetime',
            'published_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ThreadsTemplate::class, 'threads_template_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Posts still waiting for their time. */
    #[Scope]
    protected function scheduled(Builder $query): Builder
    {
        return $query->where('status', ThreadsPostStatus::Scheduled);
    }

    /**
     * The address Threads downloads the image from. Our own JPEG copy when
     * there is one (Threads takes no WebP), otherwise an address an admin gave.
     */
    public function imageUrl(): ?string
    {
        if ($this->image_path) {
            return Storage::disk('public')->url($this->image_path);
        }

        return $this->image_url ?: null;
    }

    public function previewUrl(): ?string
    {
        return $this->image_path ? StoreOptimizedImage::thumbnailUrl($this->image_path) : $this->imageUrl();
    }

    /**
     * The options in the shape Threads' poll_attachment takes, or null when
     * there is no poll. Polls only go on a text post, so an image drops it.
     *
     * @return array<string, string>|null
     */
    public function pollAttachment(): ?array
    {
        $options = array_values(array_filter($this->poll_options ?? [], fn (?string $option): bool => trim((string) $option) !== ''));

        if (count($options) < 2 || $this->imageUrl()) {
            return null;
        }

        return array_combine(array_slice(self::POLL_KEYS, 0, count($options)), array_slice($options, 0, 4));
    }
}
