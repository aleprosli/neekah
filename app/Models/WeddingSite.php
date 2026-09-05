<?php

namespace App\Models;

use Database\Factories\WeddingSiteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo as EloquentBelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'wedding_id', 'subdomain', 'template', 'is_published', 'salutation', 'bride_name', 'groom_name',
    'bride_parents', 'groom_parents', 'invitation_note', 'event_date', 'starts_at', 'ends_at',
    'venue_name', 'venue_address', 'map_url', 'itinerary', 'contacts', 'cover_image',
    'rsvp_enabled', 'rsvp_deadline', 'closing_note',
])]
class WeddingSite extends Model
{
    /** @use HasFactory<WeddingSiteFactory> */
    use HasFactory;

    /**
     * Subdomains nobody may claim, because the platform uses them.
     *
     * @var array<int, string>
     */
    public const RESERVED_SUBDOMAINS = ['www', 'app', 'admin', 'api', 'mail', 'vendor', 'vendors', 'neekah', 'blog', 'help', 'support', 'status', 'assets', 'static', 'cdn'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'rsvp_enabled' => 'boolean',
            'event_date' => 'date',
            'rsvp_deadline' => 'date',
            'itinerary' => 'array',
            'contacts' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'subdomain';
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function siteTemplate(): EloquentBelongsTo
    {
        return $this->belongsTo(SiteTemplate::class, 'template', 'slug');
    }

    /**
     * The chosen design, falling back to the first active one if it was retired.
     */
    public function design(): SiteTemplate
    {
        return $this->siteTemplate
            ?? SiteTemplate::active()->ordered()->first()
            ?? throw new \RuntimeException('Tiada template kad jemputan yang aktif.');
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(WeddingRsvp::class)->latest();
    }

    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * The public address of this invitation.
     */
    public function url(): string
    {
        $port = config('neekah.site_port');

        return config('neekah.site_scheme').'://'
            .$this->subdomain.'.'.config('neekah.site_domain')
            .($port ? ':'.$port : '');
    }

    public function coupleNames(): string
    {
        return $this->bride_name.' & '.$this->groom_name;
    }

    public function templateName(): string
    {
        return $this->design()->name;
    }

    public function startsAtLabel(): ?string
    {
        return $this->starts_at ? Carbon::parse($this->starts_at)->format('g:i A') : null;
    }

    public function endsAtLabel(): ?string
    {
        return $this->ends_at ? Carbon::parse($this->ends_at)->format('g:i A') : null;
    }

    /**
     * Whether guests can still respond.
     */
    public function acceptsRsvps(): bool
    {
        if (! $this->rsvp_enabled) {
            return false;
        }

        return $this->rsvp_deadline === null || $this->rsvp_deadline->isFuture();
    }

    public function daysUntil(): int
    {
        return max(0, (int) today()->diffInDays($this->event_date, false));
    }
}
