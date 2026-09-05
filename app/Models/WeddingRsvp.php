<?php

namespace App\Models;

use App\Support\PhoneNumber;
use Database\Factories\WeddingRsvpFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['wedding_site_id', 'wedding_guest_id', 'matched_by', 'name', 'phone', 'attending', 'pax', 'counted', 'message'])]
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
            'counted' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (WeddingRsvp $rsvp): void {
            $rsvp->phone_normalised = PhoneNumber::normalise($rsvp->phone);
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(WeddingSite::class, 'wedding_site_id');
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(WeddingGuest::class, 'wedding_guest_id');
    }

    /**
     * Whether the link to a named guest was inferred rather than proven by a
     * personal link. The couple sees this qualified, never as fact.
     */
    public function isSoftMatched(): bool
    {
        return $this->matched_by === 'phone';
    }
}
