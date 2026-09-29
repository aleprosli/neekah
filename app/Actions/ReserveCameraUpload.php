<?php

namespace App\Actions;

use App\Enums\CameraMediaStatus;
use App\Enums\CameraMediaType;
use App\Models\CameraAlbum;
use App\Models\CameraMedia;
use App\Support\CameraSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Hold a place in the album for a file a guest is about to send.
 *
 * The place is taken in one conditional UPDATE, so two phones sending the
 * 500th Basic photo at the same moment cannot both get it. What was declared
 * (type, size) is checked here against the tier, and checked again against
 * the real file when it arrives (CompleteCameraUpload).
 */
class ReserveCameraUpload
{
    /** Largest photo accepted before resizing. */
    public const MAX_PHOTO_BYTES = 25 * 1024 * 1024;

    public const PHOTO_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public const VIDEO_MIMES = ['video/mp4', 'video/quicktime', 'video/webm'];

    public function handle(CameraAlbum $album, CameraMediaType $type, string $mime, int $bytes, ?int $seconds, string $deviceHash, ?string $uploaderName): CameraMedia
    {
        $limits = $album->limits();

        if (! $album->acceptsUploads()) {
            throw ValidationException::withMessages(['file' => __('validation.custom.camera_album_closed')]);
        }

        if ($type === CameraMediaType::Video && ! $limits->allowsVideo) {
            throw ValidationException::withMessages(['file' => __('validation.custom.camera_video_not_allowed')]);
        }

        $allowed = $type === CameraMediaType::Photo ? self::PHOTO_MIMES : self::VIDEO_MIMES;
        $maxBytes = $type === CameraMediaType::Photo ? self::MAX_PHOTO_BYTES : $limits->videoMaxMegabytes * 1024 * 1024;

        if (! in_array($mime, $allowed, true) || $bytes < 1) {
            throw ValidationException::withMessages(['file' => __('validation.custom.camera_file_type')]);
        }

        if ($bytes > $maxBytes) {
            throw ValidationException::withMessages(['file' => __('validation.custom.camera_file_too_large', ['mb' => intdiv($maxBytes, 1024 * 1024)])]);
        }

        if ($type === CameraMediaType::Video && $seconds !== null && $seconds > $limits->videoMaxSeconds) {
            throw ValidationException::withMessages(['file' => __('validation.custom.camera_video_too_long', ['minutes' => intdiv($limits->videoMaxSeconds, 60)])]);
        }

        $key = 'camera-upload:'.$album->id.':'.$deviceHash;

        if (RateLimiter::tooManyAttempts($key, app(CameraSettings::class)->deviceUploadsPerHour())) {
            throw ValidationException::withMessages(['file' => __('validation.custom.camera_slow_down')]);
        }

        RateLimiter::hit($key, 3600);

        return DB::transaction(function () use ($album, $type, $mime, $bytes, $seconds, $deviceHash, $uploaderName, $limits): CameraMedia {
            $query = CameraAlbum::query()->whereKey($album->id);

            if ($type === CameraMediaType::Photo && $limits->maxPhotos !== null) {
                $query->whereRaw('photos_count + reserved_count < ?', [$limits->maxPhotos]);
            }

            $taken = $query->increment('reserved_count', 1, ['bytes_reserved' => DB::raw('bytes_reserved + '.(int) $bytes)]);

            if ($taken === 0) {
                throw ValidationException::withMessages(['file' => __('validation.custom.camera_limit_reached', ['count' => $limits->maxPhotos])]);
            }

            return $album->media()->create([
                'type' => $type,
                'status' => CameraMediaStatus::Reserved,
                'incoming_path' => 'camera/'.$album->id.'/incoming/'.Str::random(40),
                'declared_bytes' => $bytes,
                'mime' => $mime,
                'duration_seconds' => $type === CameraMediaType::Video ? $seconds : null,
                'uploader_name' => $uploaderName,
                'device_hash' => $deviceHash,
            ]);
        });
    }

    /** Give a reserved place back: the upload failed or never came. */
    public static function release(CameraMedia $media): void
    {
        CameraAlbum::query()->whereKey($media->camera_album_id)->where('reserved_count', '>', 0)
            ->decrement('reserved_count', 1, ['bytes_reserved' => DB::raw('CASE WHEN bytes_reserved > '.(int) $media->declared_bytes.' THEN bytes_reserved - '.(int) $media->declared_bytes.' ELSE 0 END')]);
    }
}
