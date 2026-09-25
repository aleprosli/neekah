<?php

namespace App\Actions;

use App\Enums\CameraMediaStatus;
use App\Enums\CameraMediaType;
use App\Jobs\PurgeCdnUrls;
use App\Models\CameraAlbum;
use App\Models\CameraMedia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Take a photo or video out of the album: its files (and thumbnail) from
 * storage and the CDN, its share of the album's counters, and the row.
 */
class DeleteCameraMedia
{
    public function __construct(private StoreOptimizedImage $images) {}

    public function handle(CameraMedia $media): void
    {
        $disk = Storage::disk('public');
        PurgeCdnUrls::for([$media->url(), $media->thumbnailUrl()]);

        if ($media->type === CameraMediaType::Photo) {
            $this->images->delete($media->path);
        } else {
            $disk->delete(array_filter([$media->path, $media->poster_path]));
        }

        if ($media->incoming_path) {
            $disk->delete($media->incoming_path);
        }

        DB::transaction(function () use ($media): void {
            if ($media->status === CameraMediaStatus::Ready) {
                $counter = $media->type === CameraMediaType::Photo ? 'photos_count' : 'videos_count';

                CameraAlbum::query()->whereKey($media->camera_album_id)->update([
                    $counter => DB::raw("CASE WHEN {$counter} > 0 THEN {$counter} - 1 ELSE 0 END"),
                    'bytes_used' => DB::raw('CASE WHEN bytes_used > '.(int) $media->bytes.' THEN bytes_used - '.(int) $media->bytes.' ELSE 0 END'),
                ]);
            } elseif (in_array($media->status, [CameraMediaStatus::Reserved, CameraMediaStatus::Processing], true)) {
                ReserveCameraUpload::release($media);
            }

            $media->delete();
        });
    }
}
