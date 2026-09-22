<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One card, one day, two counters. Nothing about who opened it lives here.
 */
#[Fillable(['wedding_site_id', 'viewed_on', 'views', 'guest_views'])]
class WeddingSiteView extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        // Stored as a plain date, so a lookup by "2026-09-22" matches the row that
        // was written — with the default serialisation it would not, and every
        // second opening of a card tried to insert the day again.
        return ['viewed_on' => 'date:Y-m-d'];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(WeddingSite::class, 'wedding_site_id');
    }
}
