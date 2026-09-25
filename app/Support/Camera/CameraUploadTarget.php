<?php

namespace App\Support\Camera;

use App\Models\CameraAlbum;
use App\Models\CameraMedia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Where a guest's phone sends the file. In production the media disk is R2
 * and the file goes straight there on a presigned PUT, which is signed for
 * its content type, so PHP's upload limits never apply and a 100 MB video
 * never passes through the web server. Anywhere else (local, tests) it goes
 * to a signed route here that streams it onto the disk.
 */
class CameraUploadTarget
{
    /** Minutes a presigned address stays usable. */
    public const MINUTES = 20;

    /**
     * @return array{url: string, method: string, headers: array<string, string>}
     */
    public static function for(CameraMedia $media): array
    {
        $disk = Storage::disk('public');

        if (config('filesystems.disks.public.driver') === 's3') {
            ['url' => $url, 'headers' => $headers] = $disk->temporaryUploadUrl($media->incoming_path, now()->addMinutes(self::MINUTES), [
                'ContentType' => $media->mime,
                'CacheControl' => CameraAlbum::CACHE_CONTROL,
            ]);

            return [
                'url' => $url,
                'method' => 'PUT',
                'headers' => collect($headers)->map(fn (mixed $value): string => is_array($value) ? implode(',', $value) : (string) $value)
                    ->except(['Host', 'host'])
                    ->put('Content-Type', (string) $media->mime)
                    ->put('Cache-Control', CameraAlbum::CACHE_CONTROL)
                    ->all(),
            ];
        }

        return [
            'url' => URL::temporarySignedRoute('camera.upload.file', now()->addMinutes(self::MINUTES), [
                'album' => $media->album->token,
                'media' => $media->id,
            ]),
            'method' => 'PUT',
            'headers' => ['Content-Type' => (string) $media->mime, 'X-CSRF-TOKEN' => csrf_token()],
        ];
    }
}
