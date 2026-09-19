<?php

namespace App\Models;

use App\Actions\StoreOptimizedImage;
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
