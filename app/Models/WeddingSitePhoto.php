<?php

namespace App\Models;

use Database\Factories\WeddingSitePhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['wedding_site_id', 'path', 'caption', 'sort_order'])]
class WeddingSitePhoto extends Model
{
    /** @use HasFactory<WeddingSitePhotoFactory> */
    use HasFactory;

    public function site(): BelongsTo
    {
        return $this->belongsTo(WeddingSite::class, 'wedding_site_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
