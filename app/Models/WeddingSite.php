<?php

namespace App\Models;

use App\Support\CardDesign;
use App\Support\CardSections;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'wedding_id', 'subdomain', 'template', 'design_overrides', 'sections', 'is_published', 'salutation', 'bride_name', 'groom_name',
    'bride_parents', 'groom_parents', 'invitation_note', 'event_date', 'starts_at', 'ends_at',
    'venue_name', 'venue_address', 'map_url', 'itinerary', 'contacts', 'cover_image',
    'rsvp_enabled', 'rsvp_deadline', 'closing_note',
    'gift_enabled', 'gift_note', 'gift_qr_image', 'gift_accounts', 'wishes_enabled',
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
     * Free addresses built from a base, for the draft and for when the one a
     * couple typed is taken: the base itself, then with the year, then as walimah.
     *
     * @return array<int, string>
     */
    public static function suggestSubdomains(string $base, int $year, ?self $ignore = null, int $limit = 3): array
    {
        $base = Str::limit(Str::slug($base), 50, '');

        $candidates = collect([$base, $base.'-'.$year, 'walimah-'.$base, $base.'-kahwin', 'majlis-'.$base])
            ->filter(fn (string $candidate): bool => strlen($candidate) >= 3
                && strlen($candidate) <= 63
                && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $candidate) === 1
                && ! in_array($candidate, self::RESERVED_SUBDOMAINS, true))
            ->unique()
            ->values();

        $taken = self::query()
            ->whereIn('subdomain', $candidates)
            ->when($ignore?->exists, fn (Builder $query) => $query->whereKeyNot($ignore->getKey()))
            ->pluck('subdomain');

        return $candidates->diff($taken)->take($limit)->values()->all();
    }

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
            'design_overrides' => 'array',
            'sections' => 'array',
            'itinerary' => 'array',
            'contacts' => 'array',
            'gift_accounts' => 'array',
            'gift_enabled' => 'boolean',
            'wishes_enabled' => 'boolean',
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
    /**
     * The template the couple picked, with whatever they changed applied. This
     * is what every view renders from, so the gallery, the editor preview and
     * the published card cannot drift apart.
     */
    public function design(): CardDesign
    {
        return CardDesign::make($this->template(), $this->design_overrides);
    }

    public function template(): SiteTemplate
    {
        return $this->siteTemplate
            ?? SiteTemplate::active()->ordered()->first()
            ?? throw new \RuntimeException('Tiada template kad jemputan yang aktif.');
    }

    /**
     * The sections to print, in the couple's order.
     *
     * @return array<int, string>
     */
    public function sectionOrder(): array
    {
        return CardSections::resolve($this->sections);
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(WeddingRsvp::class)->latest();
    }

    /**
     * Heads we have actually been told about. This is the caterer figure, and
     * it is summed from replies only, never from the invitation list.
     */
    public function confirmedPax(): int
    {
        return (int) $this->rsvps()->where('attending', true)->where('counted', true)->sum('pax');
    }

    /**
     * The ceiling on the people who have not replied. A ceiling, not an
     * expectation, so it is never folded into the confirmed number.
     */
    public function awaitingPax(): int
    {
        return (int) $this->wedding->guests()->whereDoesntHave('rsvp')->sum('pax_invited');
    }

    public function declinedCount(): int
    {
        return $this->rsvps()->where('attending', false)->count();
    }

    public function photos(): HasMany
    {
        return $this->hasMany(WeddingSitePhoto::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Wishes the couple has approved for public display. Guests write these in
     * the RSVP form, so every wish is attached to a real reply and there is no
     * anonymous write endpoint on the card to abuse.
     */
    public function approvedWishes(): HasMany
    {
        return $this->hasMany(WeddingRsvp::class)
            ->whereNotNull('message')
            ->whereNotNull('message_approved_at')
            ->reorder('message_approved_at', 'desc');
    }

    public function showsGift(): bool
    {
        return $this->gift_enabled && (filled($this->gift_accounts) || filled($this->gift_qr_image));
    }

    public function giftQrUrl(): ?string
    {
        return $this->gift_qr_image ? Storage::disk('public')->url($this->gift_qr_image) : null;
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

    /**
     * The couple's initials for the monogram, bride first as the names are printed.
     */
    public function initials(): string
    {
        return mb_strtoupper(mb_substr(trim((string) $this->bride_name), 0, 1).mb_substr(trim((string) $this->groom_name), 0, 1));
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
