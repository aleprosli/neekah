<?php

namespace App\Actions;

use App\Enums\CameraMediaStatus;
use App\Jobs\ProcessCameraMedia;
use App\Models\CameraMedia;
use App\Support\Camera\MediaSniffer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * The phone says the file has been sent. It is only accepted when it is there,
 * is exactly the size declared, and its first bytes say it is the photo or
 * video it claimed to be; anything else is deleted and its place given back.
 */
class CompleteCameraUpload
{
    public function handle(CameraMedia $media): CameraMedia
    {
        if ($media->status !== CameraMediaStatus::Reserved) {
            return $media;
        }

        $disk = Storage::disk('public');
        $path = (string) $media->incoming_path;

        $sniffed = null;

        if ($disk->exists($path) && $disk->size($path) === $media->declared_bytes) {
            $stream = $disk->readStream($path);
            $head = $stream ? (string) fread($stream, 32) : '';

            if (is_resource($stream)) {
                fclose($stream);
            }

            $sniffed = MediaSniffer::detect($head);
        }

        if ($sniffed === null || $sniffed['type'] !== $media->type) {
            $disk->delete($path);
            $media->update(['status' => CameraMediaStatus::Failed]);
            ReserveCameraUpload::release($media);

            throw ValidationException::withMessages(['file' => __('validation.custom.camera_file_type')]);
        }

        $media->update(['status' => CameraMediaStatus::Processing, 'mime' => $sniffed['mime']]);

        ProcessCameraMedia::dispatch($media);

        return $media;
    }
}
