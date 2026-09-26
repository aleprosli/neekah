<?php

namespace App\Actions;

use App\Jobs\PurgeCdnUrls;
use App\Models\CameraAlbum;
use App\Models\CameraMedia;
use App\Models\CameraWish;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Delete everything a Kamera Majlis album holds: every photo, video, poster,
 * unfinished upload and ZIP (all live under camera/{id}/), and ask the CDN
 * to forget them. The album and its purchases stay as the record, with
 * purged_at set and the counters at zero, so it takes no more uploads.
 */
class PurgeCameraAlbum
{
    public function handle(CameraAlbum $album): void
    {
        $disk = Storage::disk('public');
        $urls = [];

        $album->media()->lazyById()->each(function (CameraMedia $media) use (&$urls): void {
            $urls[] = $media->url();
            $urls[] = $media->thumbnailUrl();
            $urls[] = $media->display_path ? $media->displayUrl() : null;
        });
        $album->wishes()->whereNotNull('audio_path')->lazyById()->each(function (CameraWish $wish) use (&$urls): void {
            $urls[] = $wish->audioUrl();
        });
        $urls = array_values(array_filter($urls));
        $urls = [...$urls, ...array_map(fn (string $path): string => $disk->url($path), $album->export_paths ?? [])];

        $disk->deleteDirectory(self::directory($album));
        PurgeCdnUrls::for($urls);

        DB::transaction(function () use ($album): void {
            $album->media()->delete();
            $album->wishes()->delete();
            $album->forceFill([
                'purged_at' => now(),
                'photos_count' => 0,
                'videos_count' => 0,
                'reserved_count' => 0,
                'bytes_used' => 0,
                'bytes_reserved' => 0,
                'export_paths' => null,
                'exported_at' => null,
            ])->save();
        });
    }

    public static function directory(CameraAlbum $album): string
    {
        return 'camera/'.$album->id;
    }
}
