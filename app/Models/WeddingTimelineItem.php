<?php

namespace App\Models;

use Database\Factories\WeddingTimelineItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['wedding_id', 'vendor_id', 'starts_at', 'ends_at', 'title', 'notes', 'location'])]
class WeddingTimelineItem extends Model
{
    /** @use HasFactory<WeddingTimelineItemFactory> */
    use HasFactory;

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Times are stored without a date, so format them from a parsed clock value.
     */
    public function startsAtLabel(): string
    {
        return Carbon::parse($this->starts_at)->format('g:i A');
    }

    public function endsAtLabel(): ?string
    {
        return $this->ends_at ? Carbon::parse($this->ends_at)->format('g:i A') : null;
    }
}
