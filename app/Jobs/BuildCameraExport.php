<?php

namespace App\Jobs;

use App\Enums\CameraMediaType;
use App\Models\CameraAlbum;
use App\Models\CameraMedia;
use App\Models\User;
use App\Notifications\CameraExportReady;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Everything in a Kamera Majlis album as ZIP files the couple can keep
 * before the album is deleted. Photos and videos are already compressed, so
 * they are stored, not deflated; a large album is split into parts so no
 * single download is unmanageable. Built in the queue, one at a time per
 * album, and each part is removed from local disk as soon as it is uploaded.
 */
class BuildCameraExport implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /** Largest ZIP part, in bytes. */
    public static int $partBytes = 2 * 1024 ** 3;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(public CameraAlbum $album) {}

    public function uniqueId(): string
    {
        return (string) $this->album->id;
    }

    public static function buildingKey(CameraAlbum $album): string
    {
        return 'camera-export-building:'.$album->id;
    }

    public function handle(): void
    {
        $album = $this->album->fresh(['wedding.members']);
        $disk = Storage::disk('public');
        $local = storage_path('app/private/camera-exports/'.$album->id);
        @mkdir($local, 0775, true);

        $disk->delete($album->export_paths ?? []);
        $parts = [];
        $zip = null;
        $size = 0;
        $number = 0;

        try {
            $album->readyMedia()->orderBy('id')->lazyById()->each(function (CameraMedia $media) use ($disk, $local, $album, &$parts, &$zip, &$size, &$number): void {
                if (! $media->path || ! $disk->exists($media->path)) {
                    return;
                }

                if ($zip === null || $size + $media->bytes > self::$partBytes) {
                    if ($zip) {
                        $parts[] = $this->finish($zip, $local, $number, $album);
                    }
                    $number++;
                    $zip = new ZipArchive;
                    $zip->open("{$local}/part{$number}.zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);
                    $size = 0;
                }

                $tmp = "{$local}/".Str::random(16);
                file_put_contents($tmp, $disk->readStream($media->path));
                $name = ($media->type === CameraMediaType::Photo ? 'Gambar/' : 'Video/')
                    .$media->created_at->format('Ymd-His').'-'.Str::slug($media->uploader_name ?: 'tetamu').'-'.$media->id.'.'.pathinfo($media->path, PATHINFO_EXTENSION);
                $zip->addFile($tmp, $name);
                $zip->setCompressionName($name, ZipArchive::CM_STORE);
                $size += $media->bytes;
                $this->pending[] = $tmp;
            });

            if ($zip) {
                $parts[] = $this->finish($zip, $local, $number, $album);
            }

            $album->update(['export_paths' => $parts, 'exported_at' => now()]);
        } finally {
            Cache::forget(self::buildingKey($album));
            foreach (glob("{$local}/*") ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($local);
        }

        $album->wedding->members->each(fn (User $member) => $member->notify(new CameraExportReady($album)));
    }

    public function failed(): void
    {
        Cache::forget(self::buildingKey($this->album));
    }

    /** @var list<string> files copied down for the part being written */
    private array $pending = [];

    private function finish(ZipArchive $zip, string $local, int $number, CameraAlbum $album): string
    {
        $zip->close();
        $path = 'camera/'.$album->id.'/export/'.Str::random(40).'-part'.$number.'.zip';

        if (! Storage::disk('public')->writeStream($path, fopen("{$local}/part{$number}.zip", 'r'))) {
            throw new RuntimeException('Could not write the export part '.$number.'.');
        }

        @unlink("{$local}/part{$number}.zip");
        foreach ($this->pending as $tmp) {
            @unlink($tmp);
        }
        $this->pending = [];

        return $path;
    }
}
