<?php

namespace App\Jobs;

use App\Actions\ReserveCameraUpload;
use App\Actions\StoreOptimizedImage;
use App\Enums\CameraMediaStatus;
use App\Enums\CameraMediaType;
use App\Enums\CameraTier;
use App\Models\CameraAlbum;
use App\Models\CameraMedia;
use App\Support\CameraSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Turn an arrived upload into album media.
 *
 * A photo is re-encoded at the tier's size and quality through
 * StoreOptimizedImage, which also strips EXIF (the phone's GPS location) and
 * writes the thumbnail. A video keeps its bytes; when ffmpeg is installed its
 * metadata (location included) is stripped first. Then the place held for it
 * becomes real use of the album.
 */
class ProcessCameraMedia implements ShouldQueue
{
    use Queueable;

    /** Grid thumbnails: three to six across a phone or a dashboard, never wider. */
    public const THUMBNAIL_WIDTH = 480;

    /** The copy the full-screen viewer opens, sharp on a laptop screen. */
    public const DISPLAY_DIMENSION = 1600;

    /** Thumbnails and display copies are for looking, not keeping. */
    public const PREVIEW_QUALITY = 78;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(public CameraMedia $media) {}

    public function handle(StoreOptimizedImage $images): void
    {
        $media = $this->media->fresh(['album']);

        if (! $media || $media->status !== CameraMediaStatus::Processing || $media->album->purged_at) {
            return;
        }

        $disk = Storage::disk('public');
        $album = $media->album;
        $directory = 'camera/'.$album->id;

        if ($media->type === CameraMediaType::Photo) {
            $limits = $album->limits();
            $path = $images->storeContents(
                (string) $disk->get($media->incoming_path),
                $directory,
                maxDimension: $limits->photoPixels,
                quality: $limits->photoQuality,
                cacheControl: CameraAlbum::CACHE_CONTROL,
                thumbnailWidth: self::THUMBNAIL_WIDTH,
                displayDimension: self::DISPLAY_DIMENSION,
                previewQuality: self::PREVIEW_QUALITY,
            );
            $displayPath = StoreOptimizedImage::displayPath($path);
            $bytes = (int) $disk->size($path) + (int) $disk->size(StoreOptimizedImage::thumbnailPath($path)) + (int) $disk->size($displayPath);
            $size = @getimagesizefromstring((string) $disk->get($path)) ?: [null, null];
            $disk->delete($media->incoming_path);
        } else {
            $extension = match ($media->mime) {
                'video/quicktime' => 'mov', 'video/webm' => 'webm', default => 'mp4'
            };
            $path = $directory.'/'.Str::random(40).'.'.$extension;
            $this->storeVideo($media->incoming_path, $path, $extension);
            $bytes = (int) $disk->size($path);
            $size = [null, null];
            $displayPath = null;
        }

        DB::transaction(function () use ($media, $album, $path, $displayPath, $bytes, $size): void {
            $media->update([
                'status' => CameraMediaStatus::Ready,
                'path' => $path,
                'display_path' => $displayPath,
                'incoming_path' => null,
                'bytes' => $bytes,
                'width' => $size[0],
                'height' => $size[1],
            ]);

            ReserveCameraUpload::release($media);

            CameraAlbum::query()->whereKey($album->id)->update([
                $media->type === CameraMediaType::Photo ? 'photos_count' : 'videos_count' => DB::raw(($media->type === CameraMediaType::Photo ? 'photos_count' : 'videos_count').' + 1'),
                'bytes_used' => DB::raw('bytes_used + '.$bytes),
            ]);
        });

        $this->alertFairUse($album->fresh());
    }

    public function failed(?Throwable $exception): void
    {
        $media = $this->media->fresh();

        if (! $media || $media->status === CameraMediaStatus::Ready) {
            return;
        }

        if ($media->incoming_path) {
            Storage::disk('public')->delete($media->incoming_path);
        }

        $media->update(['status' => CameraMediaStatus::Failed]);
        ReserveCameraUpload::release($media);
    }

    /**
     * Move the video to its final name. With ffmpeg configured, copy the
     * streams without their metadata instead, so the location a phone embeds
     * does not travel with it.
     */
    private function storeVideo(string $incoming, string $path, string $extension): void
    {
        $disk = Storage::disk('public');
        $ffmpeg = config('services.ffmpeg.path');

        if (! $ffmpeg || ! is_executable($ffmpeg)) {
            $disk->move($incoming, $path);

            return;
        }

        $source = tempnam(sys_get_temp_dir(), 'cam').'.'.$extension;
        $target = tempnam(sys_get_temp_dir(), 'cam').'.'.$extension;

        try {
            file_put_contents($source, $disk->readStream($incoming));
            $result = Process::timeout(240)->run([$ffmpeg, '-y', '-i', $source, '-map_metadata', '-1', '-c', 'copy', $target]);

            if ($result->successful() && filesize($target) > 0) {
                $disk->writeStream($path, fopen($target, 'r'), ['CacheControl' => CameraAlbum::CACHE_CONTROL]);
                $disk->delete($incoming);
            } else {
                $disk->move($incoming, $path);
            }
        } finally {
            @unlink($source);
            @unlink($target);
        }
    }

    /**
     * Pro has no cap, but one album passing the fair-use size is worth an
     * admin's look. Said once per album.
     */
    private function alertFairUse(CameraAlbum $album): void
    {
        if ($album->tier !== CameraTier::Pro) {
            return;
        }

        $limit = app(CameraSettings::class)->proFairUseGigabytes() * 1024 ** 3;

        if ($album->bytes_used >= $limit && Cache::add('camera-fair-use:'.$album->id, true, now()->addDays(30))) {
            SendTelegramAlert::about('📸 <b>Neekah Kenangan melepasi had guna wajar</b>', [
                'Majlis' => $album->wedding->title,
                'Storan' => round($album->bytes_used / 1024 ** 3, 1).' GB',
            ]);
        }
    }
}
