<?php

namespace App\Models;

use App\Enums\CameraMediaStatus;
use App\Enums\CameraTier;
use App\Enums\PaymentPurpose;
use App\Support\CameraLimits;
use App\Support\CameraSettings;
use Carbon\CarbonInterface;
use Database\Factories\CameraAlbumFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * One Neekah Kenangan album of a wedding, for one majlis; a wedding may buy
 * several. Guests open it through the QR (the token); it takes uploads until
 * expires_at, 14 days after its event by default, when every file is deleted
 * and purged_at is set. The row stays as the record.
 */
#[Fillable([
    'wedding_id', 'token', 'tier', 'activated_at', 'expires_at', 'purged_at', 'title', 'event_date', 'welcome_message',
    'passcode_hash', 'passcode_version', 'guests_can_view', 'uploads_open', 'photos_count', 'videos_count',
    'reserved_count', 'bytes_used', 'bytes_reserved', 'qr_design', 'qr_options', 'export_paths', 'exported_at',
])]
#[Hidden(['passcode_hash'])]
class CameraAlbum extends Model
{
    /** @use HasFactory<CameraAlbumFactory> */
    use HasFactory;

    /**
     * How long the CDN and browsers may keep an album file. A day, not the
     * media disk's immutable year: albums are deleted after the event, and a
     * guest's deleted photo must not linger at the edge.
     */
    public const CACHE_CONTROL = 'public, max-age=86400';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tier' => CameraTier::class,
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
            'purged_at' => 'datetime',
            'event_date' => 'date',
            'exported_at' => 'datetime',
            'guests_can_view' => 'boolean',
            'uploads_open' => 'boolean',
            'passcode_version' => 'integer',
            'photos_count' => 'integer',
            'videos_count' => 'integer',
            'reserved_count' => 'integer',
            'bytes_used' => 'integer',
            'bytes_reserved' => 'integer',
            'qr_options' => 'array',
            'export_paths' => 'array',
        ];
    }

    /**
     * A short address for the QR: twelve characters with nothing that reads
     * as another (no 0/o, 1/l/i), so a printed card can also be typed.
     */
    public static function freshToken(): string
    {
        do {
            $token = Str::lower(Str::password(12, letters: true, numbers: true, symbols: false, spaces: false));
            $token = str_replace(['0', 'o', '1', 'l', 'i'], ['2', '3', '4', '5', '6'], $token);
        } while (static::query()->where('token', $token)->exists());

        return $token;
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(CameraMedia::class);
    }

    /** What was paid for this album, the purchase that opened it and any upgrade. */
    public function purchases(): HasMany
    {
        return $this->hasMany(Payment::class)->where('purpose', PaymentPurpose::Kenangan);
    }

    /** Written and spoken wishes guests left for the couple. */
    public function wishes(): HasMany
    {
        return $this->hasMany(CameraWish::class);
    }

    public function readyMedia(): HasMany
    {
        return $this->media()->where('status', CameraMediaStatus::Ready);
    }

    /** The album's own majlis date, or the wedding's when it has none. */
    public function eventDate(): CarbonInterface
    {
        return $this->event_date ?? $this->wedding->event_date;
    }

    /** What guests and the couple see it called: its title, or the wedding's. */
    public function displayTitle(): string
    {
        return $this->title ?: $this->wedding->title;
    }

    public function isActive(): bool
    {
        return $this->activated_at !== null
            && $this->purged_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function acceptsUploads(): bool
    {
        return $this->isActive() && $this->uploads_open;
    }

    public function isRestricted(): bool
    {
        return filled($this->passcode_hash);
    }

    public function limits(): CameraLimits
    {
        return app(CameraSettings::class)->limitsFor($this->tier);
    }

    /**
     * A tag that changes whenever what the album holds changes: its counters
     * move on every ready, deleted or purged file. Media listings send it as
     * an ETag, so a phone or the couple's page asking again gets a 304
     * instead of the same page rebuilt.
     */
    public function contentsTag(string ...$context): string
    {
        return sha1(implode('|', [$this->id, $this->photos_count, $this->videos_count, $this->bytes_used, $this->purged_at?->timestamp, ...$context]));
    }

    public function url(): string
    {
        return route('camera.show', $this);
    }
}
