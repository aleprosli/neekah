<?php

namespace App\Models;

use App\Enums\CameraWishType;
use Database\Factories\CameraWishFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A wish a guest left in a Neekah Kenangan album: a written message, or a
 * short voice recording on Pro. Only the couple sees it.
 */
#[Fillable([
    'camera_album_id', 'type', 'message', 'audio_path', 'mime', 'bytes', 'duration_seconds', 'guest_name', 'device_hash',
])]
class CameraWish extends Model
{
    /** @use HasFactory<CameraWishFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CameraWishType::class,
            'bytes' => 'integer',
            'duration_seconds' => 'integer',
        ];
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(CameraAlbum::class, 'camera_album_id');
    }

    public function audioUrl(): ?string
    {
        return $this->audio_path ? Storage::disk('public')->url($this->audio_path) : null;
    }
}
