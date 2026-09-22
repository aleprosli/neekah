<?php

namespace App\Models;

use Database\Factories\CardNfcCardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A physical card whose NFC tag (or printed QR) opens an invitation.
 *
 * The tag holds /n/{uid} and nothing else, so the stack can be printed before the
 * couple has chosen their address, and changing that address never bricks a card.
 */
#[Fillable(['uid', 'wedding_site_id', 'label', 'is_active'])]
class CardNfcCard extends Model
{
    /** @use HasFactory<CardNfcCardFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_tapped_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    /**
     * Unambiguous when read off a card or typed in: no 0/O, 1/l or similar pairs.
     */
    public static function freshUid(): string
    {
        do {
            $uid = Str::lower(Str::password(10, letters: true, numbers: true, symbols: false, spaces: false));
            $uid = str_replace(['0', 'o', '1', 'l', 'i'], ['2', '3', '4', '5', '6'], $uid);
        } while (static::query()->where('uid', $uid)->exists());

        return $uid;
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(WeddingSite::class, 'wedding_site_id');
    }

    #[Scope]
    protected function usable(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNotNull('wedding_site_id');
    }

    public function recordTap(): void
    {
        // Neither column is fillable: they are ours to write, never posted.
        $this->increment('taps');
        $this->forceFill(['last_tapped_at' => now()])->save();
    }
}
