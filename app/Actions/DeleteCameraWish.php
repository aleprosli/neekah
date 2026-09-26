<?php

namespace App\Actions;

use App\Jobs\PurgeCdnUrls;
use App\Models\CameraWish;
use Illuminate\Support\Facades\Storage;

/**
 * Take a wish out of the album: its recording from storage and the CDN, and
 * the row.
 */
class DeleteCameraWish
{
    public function handle(CameraWish $wish): void
    {
        if ($wish->audio_path) {
            PurgeCdnUrls::for([$wish->audioUrl()]);
            Storage::disk('public')->delete($wish->audio_path);
        }

        $wish->delete();
    }
}
