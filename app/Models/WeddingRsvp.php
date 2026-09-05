<?php

namespace App\Models;

use Database\Factories\WeddingRsvpFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['wedding_site_id', 'name', 'phone', 'attending', 'pax', 'message'])]
class WeddingRsvp extends Model
{
    /** @use HasFactory<WeddingRsvpFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attending' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(WeddingSite::class, 'wedding_site_id');
    }
}
