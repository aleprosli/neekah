<?php

namespace App\Models;

use App\Enums\SongMoment;
use Database\Factories\WeddingSongFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One song on the couple's playlist, filed under the moment of the day it is
 * played at, so the emcee or DJ can be handed the list as it stands.
 */
#[Fillable(['wedding_id', 'moment', 'title', 'artist', 'notes'])]
class WeddingSong extends Model
{
    /** @use HasFactory<WeddingSongFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'moment' => SongMoment::class,
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }
}
