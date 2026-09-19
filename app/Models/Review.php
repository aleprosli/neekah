<?php

namespace App\Models;

use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'booking_id', 'user_id', 'vendor_id', 'author_name', 'author_email',
    'rating', 'quality', 'service', 'communication', 'value', 'punctuality', 'comment',
    'hidden_at', 'hidden_by', 'hidden_reason', 'reported_at', 'reported_reason',
    'reply', 'replied_at', 'added_by',
])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    public const ASPECTS = ['quality', 'service', 'communication', 'value', 'punctuality'];

    /** As many photos as a phone screen can show without becoming a gallery. */
    public const MAX_PHOTOS = 6;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hidden_at' => 'datetime',
            'reported_at' => 'datetime',
            'replied_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ReviewPhoto::class)->orderBy('sort_order');
    }

    public function hiddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_by');
    }

    /**
     * The admin who typed this review in on someone else's behalf, if any.
     */
    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * What the public sees. Hiding is the only way a review leaves a page, so
     * every list a visitor can reach goes through here.
     */
    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->whereNull('hidden_at');
    }

    /**
     * Backed by a completed booking on Neekah. These alone feed rating_avg,
     * the vendor's points and the ranking tier, because they are the only
     * ones the platform can prove happened.
     */
    #[Scope]
    protected function verified(Builder $query): Builder
    {
        return $query->whereNotNull('booking_id');
    }

    /**
     * Written by anyone, with or without an account. Shown on the profile with
     * its own average, and deliberately kept out of every ranking input.
     */
    #[Scope]
    protected function open(Builder $query): Builder
    {
        return $query->whereNull('booking_id');
    }

    public function isVerified(): bool
    {
        return $this->booking_id !== null;
    }

    public function isHidden(): bool
    {
        return $this->hidden_at !== null;
    }

    public function isReported(): bool
    {
        return $this->reported_at !== null && ! $this->isHidden();
    }

    /**
     * Whoever signed it: the account that wrote it, or the name an anonymous
     * author typed. Never empty, because a review with neither is not storable.
     */
    public function authorName(): string
    {
        return $this->user?->name ?? (string) $this->author_name;
    }

    public function authorInitial(): string
    {
        return mb_strtoupper(mb_substr($this->authorName(), 0, 1)) ?: '?';
    }

    public function hasReply(): bool
    {
        return filled($this->reply);
    }
}
