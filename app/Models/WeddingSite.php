<?php

namespace App\Models;

use App\Support\Card\Widgets;
use Database\Factories\WeddingSiteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo as EloquentBelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'wedding_id', 'subdomain', 'template', 'is_published', 'salutation', 'bride_name', 'groom_name',
    'bride_short', 'groom_short', 'bride_father', 'bride_mother', 'groom_father', 'groom_mother',
    'bride_bio', 'groom_bio', 'invitation_note', 'event_date', 'starts_at', 'ends_at',
    'venue_name', 'venue_address', 'map_url', 'itinerary', 'contacts', 'cover_image',
    'palette', 'fonts', 'slot_images', 'widgets', 'music_track_id', 'music_enabled',
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
            'itinerary' => 'array',
            'contacts' => 'array',
            'gift_accounts' => 'array',
            'gift_enabled' => 'boolean',
            'wishes_enabled' => 'boolean',
            'palette' => 'array',
            'fonts' => 'array',
            'slot_images' => 'array',
            'widgets' => 'array',
            'music_enabled' => 'boolean',
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
            ?? throw new \RuntimeException('No invitation card template is active.');
    }

    public function musicTrack(): EloquentBelongsTo
    {
        return $this->belongsTo(CardMusicTrack::class, 'music_track_id');
    }

    public function dailyViews(): HasMany
    {
        return $this->hasMany(WeddingSiteView::class)->orderBy('viewed_on');
    }

    public function nfcCards(): HasMany
    {
        return $this->hasMany(CardNfcCard::class);
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(WeddingRsvp::class)->latest();
    }

    /**
     * Count one opening: the running total on the card and the day's counter the
     * insights page draws. A guest arriving on their personal link is counted apart,
     * so the couple can tell their own list reading it from a forward.
     */
    public function recordView(bool $fromGuestLink = false): void
    {
        $this->increment('views');

        $date = today()->toDateString();

        // Two guests opening the card in the same second both try to start the day's
        // row; the loser of that race reads the winner's rather than failing the page.
        try {
            $today = $this->dailyViews()->firstOrCreate(['viewed_on' => $date]);
        } catch (QueryException) {
            $today = $this->dailyViews()->where('viewed_on', $date)->firstOrFail();
        }

        $today->increment('views');

        if ($fromGuestLink) {
            $today->increment('guest_views');
        }
    }

    /**
     * The slots that show the couple themselves. Both fall back to cover_image, so
     * the one photo a card carried before the layered designs still appears: most
     * designs ask for couple_image, and their photo was stored as the cover.
     *
     * @var array<int, string>
     */
    public const MAIN_PHOTO_SLOTS = ['cover_image', 'couple_image'];

    /**
     * The photo the couple put in one of the design's slots (couple_image,
     * cover_image, groom_image…).
     */
    public function slotImage(string $slot): ?string
    {
        $path = $this->slot_images[$slot] ?? null;

        if (blank($path) && in_array($slot, self::MAIN_PHOTO_SLOTS, true)) {
            $path = $this->cover_image;
        }

        return is_string($path) && $path !== '' ? Storage::disk('public')->url($path) : null;
    }

    /**
     * Stored paths of every slot photo, so they can be deleted with the card.
     *
     * @return array<int, string>
     */
    public function slotImagePaths(): array
    {
        return collect($this->slot_images ?? [])->filter(fn (mixed $path): bool => is_string($path) && $path !== '')->values()->all();
    }

    /**
     * The sections that follow the designed canvases, in the couple's order.
     *
     * @return array<int, string>
     */
    public function widgetKeys(): array
    {
        return Widgets::sanitize($this->widgets);
    }

    /**
     * The short name a cover prints, falling back to the first name.
     */
    public function shortName(string $side): string
    {
        $short = trim((string) ($side === 'bride' ? $this->bride_short : $this->groom_short));

        if ($short !== '') {
            return $short;
        }

        $full = trim((string) ($side === 'bride' ? $this->bride_name : $this->groom_name));

        return preg_split('/\s+/', $full)[0] ?? $full;
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
     * The couple's initials for the monogram, groom first as the cards print them.
     *
     * Taken from the short names: "Muhammad Hakim" and "Nur Aina" are on the card
     * as Hakim and Aina, and a seal reading "MN" belongs to nobody.
     */
    public function initials(): string
    {
        return mb_strtoupper(mb_substr($this->shortName('groom'), 0, 1).mb_substr($this->shortName('bride'), 0, 1));
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
