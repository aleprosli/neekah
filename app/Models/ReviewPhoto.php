<?php

namespace App\Models;

use App\Actions\StoreOptimizedImage;
use App\Support\ContentVersion;
use Database\Factories\ReviewPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['review_id', 'path', 'sort_order'])]
class ReviewPhoto extends Model
{
    /** @use HasFactory<ReviewPhotoFactory> */
    use HasFactory;

    /**
     * A review photo is read on the vendor's page, through the cached payload
     * that carries its URL. The photo has no vendor_id of its own, so the
     * review is asked for one - a query on a write that happens rarely, which
     * buys the page not showing a picture that has been deleted.
     *
     * Without this, `neekah:optimize-images --thumbnails` renames a photo and
     * the vendor's page goes on pointing at the old name until the cache TTL
     * runs out.
     */
    protected static function booted(): void
    {
        $bump = function (self $photo): void {
            ContentVersion::bumpVendor($photo->review()->value('vendor_id'));
        };

        static::saved($bump);
        static::deleted($bump);
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    /** The narrow copy StoreOptimizedImage wrote next to the original. */
    public function thumbnailUrl(): string
    {
        return StoreOptimizedImage::thumbnailUrl($this->path) ?? $this->url();
    }
}
