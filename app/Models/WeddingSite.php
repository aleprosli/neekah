<?php

namespace App\Models;

use Database\Factories\WeddingSiteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
     * Templates a couple can choose from. Each one is a Blade view under sites/templates.
     *
     * @var array<string, array{name: string, description: string, palette: string}>
     */
    public const TEMPLATES = [
        'klasik' => ['name' => 'Klasik', 'description' => 'Songket dan emas, sesuai untuk majlis tradisional.', 'palette' => 'from-brand-700 to-brand-900'],
        'moden' => ['name' => 'Moden', 'description' => 'Bersih dan lapang, tipografi besar.', 'palette' => 'from-slate-700 to-slate-900'],
        'bunga' => ['name' => 'Bunga', 'description' => 'Lembut dengan sentuhan bunga dan warna pastel.', 'palette' => 'from-rose-300 to-fuchsia-400'],
        'malam' => ['name' => 'Malam', 'description' => 'Latar gelap berkilau, sesuai untuk majlis malam.', 'palette' => 'from-indigo-800 to-slate-900'],
    ];

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
        $parts = parse_url(config('app.url'));
        $scheme = $parts['scheme'] ?? 'http';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return $scheme.'://'.$this->subdomain.'.'.config('neekah.site_domain').$port;
    }

    public function coupleNames(): string
    {
        return $this->bride_name.' & '.$this->groom_name;
    }

    public function templateName(): string
    {
        return self::TEMPLATES[$this->template]['name'] ?? $this->template;
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
