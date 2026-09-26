<?php

namespace App\Enums;

use App\Models\CameraAlbum;
use Illuminate\Database\Eloquent\Builder;

/**
 * The shelves on the admin Kamera Majlis list. Each owns its own query, so
 * the count on the chip and the rows under it always ask the same question.
 */
enum CameraAlbumFilter: string
{
    case Active = 'active';
    case Reported = 'reported';
    case Purged = 'purged';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('enums.camera_album_filter.active'),
            self::Reported => __('enums.camera_album_filter.reported'),
            self::Purged => __('enums.camera_album_filter.purged'),
        };
    }

    /**
     * @param  Builder<CameraAlbum>  $albums
     * @return Builder<CameraAlbum>
     */
    public function apply(Builder $albums): Builder
    {
        return match ($this) {
            self::Active => $albums->whereNotNull('activated_at')->whereNull('purged_at')->where('expires_at', '>', now()),
            self::Reported => $albums->whereHas('media', fn (Builder $media) => $media->whereNotNull('reported_at')),
            self::Purged => $albums->where(fn (Builder $query) => $query->whereNotNull('purged_at')->orWhere('expires_at', '<=', now())),
        };
    }
}
