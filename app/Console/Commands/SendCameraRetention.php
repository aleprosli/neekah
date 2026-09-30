<?php

namespace App\Console\Commands;

use App\Actions\PurgeCameraAlbum;
use App\Actions\ReserveCameraUpload;
use App\Enums\CameraMediaStatus;
use App\Models\CameraAlbum;
use App\Models\CameraMedia;
use App\Models\User;
use App\Notifications\CameraRetentionNotice;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class SendCameraRetention extends Command
{
    protected $signature = 'neekah:camera-retention';

    protected $description = 'Remind couples to download their Kamera Majlis album, delete expired albums, and clear abandoned uploads';

    /** Hours a reserved upload may wait for its file before its place is given back. */
    public const RESERVED_HOURS = 2;

    /** Hours a received upload may wait for the queue before it counts as failed. */
    public const PROCESSING_HOURS = 24;

    /**
     * Matched on the calendar day, so a daily run sends each message once:
     * the day after the event, a week and a day before the album is deleted,
     * and when it has been.
     */
    public function handle(PurgeCameraAlbum $purge): int
    {
        $sent = 0;
        $notify = function (CameraAlbum $album, string $moment) use (&$sent): void {
            $album->wedding->members->each(fn (User $member) => $member->notify(new CameraRetentionNotice($album, $moment)));
            $sent++;
        };

        $this->kept()
            ->whereHas('wedding', fn (Builder $query) => $query->whereDate('event_date', today()->subDay()))
            ->where('expires_at', '>', now())
            ->each(fn (CameraAlbum $album) => $notify($album, 'after_event'));

        foreach ([7, 1] as $days) {
            $this->kept()
                ->whereDate('expires_at', today()->addDays($days))
                ->each(fn (CameraAlbum $album) => $notify($album, "expiring_{$days}"));
        }

        $purged = 0;
        $this->kept()->where('expires_at', '<=', now())->each(function (CameraAlbum $album) use ($purge, $notify, &$purged): void {
            $purge->handle($album);
            $notify($album, 'purged');
            $purged++;
        });

        $abandoned = $this->clearAbandonedUploads();

        $this->components->info("{$sent} notice(s) sent, {$purged} album(s) deleted, {$abandoned} abandoned upload(s) cleared.");

        return self::SUCCESS;
    }

    /** Paid albums whose files are still kept. */
    private function kept(): Builder
    {
        return CameraAlbum::query()->whereNotNull('activated_at')->whereNull('purged_at')->with('wedding.members');
    }

    /**
     * A phone that reserved a place and never sent the file (closed the tab,
     * lost signal), or a file the queue never got to: give the place back so
     * the album's cap is not eaten, and drop whatever half arrived.
     */
    private function clearAbandonedUploads(): int
    {
        $disk = Storage::disk('public');
        $cleared = 0;

        CameraMedia::query()
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query->where('status', CameraMediaStatus::Reserved)->where('updated_at', '<', now()->subHours(self::RESERVED_HOURS)))
                ->orWhere(fn (Builder $query) => $query->where('status', CameraMediaStatus::Processing)->where('updated_at', '<', now()->subHours(self::PROCESSING_HOURS))))
            ->lazyById()
            ->each(function (CameraMedia $media) use ($disk, &$cleared): void {
                if ($media->incoming_path) {
                    $disk->delete($media->incoming_path);
                }

                ReserveCameraUpload::release($media);
                $media->update(['status' => CameraMediaStatus::Failed]);
                $cleared++;
            });

        return $cleared;
    }
}
