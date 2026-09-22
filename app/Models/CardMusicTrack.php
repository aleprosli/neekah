<?php

namespace App\Models;

use Database\Factories\CardMusicTrackFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * A background track a couple may play on their card. Admin uploads the library, so
 * every file on a card is one Neekah is allowed to serve.
 */
#[Fillable(['title', 'artist', 'path', 'seconds', 'is_active', 'sort_order'])]
class CardMusicTrack extends Model
{
    /** @use HasFactory<CardMusicTrackFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The cards playing this track. A track in use may be turned off but not deleted.
     */
    public function sites(): HasMany
    {
        return $this->hasMany(WeddingSite::class, 'music_track_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function label(): string
    {
        return $this->artist ? $this->title.' — '.$this->artist : $this->title;
    }

    public function lengthLabel(): ?string
    {
        if (! $this->seconds) {
            return null;
        }

        return intdiv($this->seconds, 60).':'.str_pad((string) ($this->seconds % 60), 2, '0', STR_PAD_LEFT);
    }
}
