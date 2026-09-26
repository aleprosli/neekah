<?php

namespace App\Jobs;

use App\Enums\CameraMediaType;
use App\Enums\CameraWishType;
use App\Models\CameraAlbum;
use App\Models\CameraMedia;
use App\Models\CameraWish;
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
 * Everything in a Neekah Kenangan album, wishes included, as ZIP files the couple can keep
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

        if ($album->purged_at) {
            Cache::forget(self::buildingKey($album));

            return;
        }

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
                $name = self::entryName($media);
                $zip->addFile($tmp, $name);
                $zip->setCompressionName($name, ZipArchive::CM_STORE);
                $size += $media->bytes;
                $this->pending[] = $tmp;
            });

            if ($album->wishes()->exists()) {
                if ($zip === null) {
                    $number++;
                    $zip = new ZipArchive;
                    $zip->open("{$local}/part{$number}.zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);
                }

                $this->addWishes($zip, $album, $local);
            }

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

    /**
     * Where a photo or video sits inside a ZIP: by type, then when it was
     * taken and by whom, so the files sort in the order of the day.
     */
    public static function entryName(CameraMedia $media): string
    {
        return ($media->type === CameraMediaType::Photo ? 'Gambar/' : 'Video/')
            .$media->created_at->format('Ymd-His').'-'.Str::slug($media->uploader_name ?: 'tetamu').'-'.$media->id.'.'.pathinfo((string) $media->path, PATHINFO_EXTENSION);
    }

    /**
     * The written wishes as one text file, and each recording beside it, in
     * the last part.
     */
    private function addWishes(ZipArchive $zip, CameraAlbum $album, string $local): void
    {
        $disk = Storage::disk('public');
        $lines = [];

        $album->wishes()->orderBy('id')->lazyById()->each(function (CameraWish $wish) use ($zip, $disk, $local, &$lines): void {
            $by = $wish->guest_name ?: __('pages.camera.export_guest');
            $at = $wish->created_at->translatedFormat('j M Y, g:i A');

            if ($wish->type === CameraWishType::Text) {
                $lines[] = "{$by} · {$at}\n{$wish->message}\n";

                return;
            }

            if (! $wish->audio_path || ! $disk->exists($wish->audio_path)) {
                return;
            }

            $tmp = "{$local}/".Str::random(16);
            file_put_contents($tmp, $disk->readStream($wish->audio_path));
            $name = __('pages.camera.export_voice_folder').'/'.$wish->created_at->format('Ymd-His').'-'.Str::slug($wish->guest_name ?: 'tetamu').'-'.$wish->id.'.'.pathinfo($wish->audio_path, PATHINFO_EXTENSION);
            $zip->addFile($tmp, $name);
            $zip->setCompressionName($name, ZipArchive::CM_STORE);
            $this->pending[] = $tmp;
        });

        if ($lines !== []) {
            $zip->addFromString(__('pages.camera.export_wishes_file'), implode("\n", $lines));
        }
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
