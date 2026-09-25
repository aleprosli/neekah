<?php

namespace App\Models;

use App\Actions\StoreOptimizedImage;
use App\Enums\CameraMediaStatus;
use App\Enums\CameraMediaType;
use Database\Factories\CameraMediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'camera_album_id', 'type', 'status', 'incoming_path', 'path', 'poster_path', 'bytes', 'declared_bytes',
    'mime', 'width', 'height', 'duration_seconds', 'uploader_name', 'device_hash', 'reported_at', 'report_reason',
])]
class CameraMedia extends Model
{
    /** @use HasFactory<CameraMediaFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CameraMediaType::class,
            'status' => CameraMediaStatus::class,
            'bytes' => 'integer',
            'declared_bytes' => 'integer',
            'reported_at' => 'datetime',
        ];
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(CameraAlbum::class, 'camera_album_id');
    }

    public function url(): ?string
    {
        return $this->path ? Storage::disk('public')->url($this->path) : null;
    }

    public function thumbnailUrl(): ?string
    {
        return match ($this->type) {
            CameraMediaType::Photo => StoreOptimizedImage::thumbnailUrl($this->path),
            CameraMediaType::Video => $this->poster_path ? Storage::disk('public')->url($this->poster_path) : null,
        };
    }
}
