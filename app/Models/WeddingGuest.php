<?php

namespace App\Models;

use App\Enums\GuestGroup;
use App\Enums\GuestSide;
use App\Enums\GuestStatus;
use App\Support\PhoneNumber;
use Database\Factories\WeddingGuestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable(['wedding_id', 'name', 'phone', 'side', 'group', 'pax_invited', 'notes'])]
class WeddingGuest extends Model
{
    /** @use HasFactory<WeddingGuestFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'side' => GuestSide::class,
            'group' => GuestGroup::class,
            'shared_at' => 'datetime',
            'first_opened_at' => 'datetime',
            'last_opened_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (WeddingGuest $guest): void {
            $guest->phone_normalised = PhoneNumber::normalise($guest->phone);

            if (blank($guest->token)) {
                $guest->token = static::freshToken();
            }
        });
    }

    public static function freshToken(): string
    {
        do {
            $token = Str::lower(Str::random(16));
        } while (static::query()->where('token', $token)->exists());

        return $token;
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function rsvp(): HasOne
    {
        return $this->hasOne(WeddingRsvp::class);
    }

    /**
     * The card address carrying this guest's personal token.
     */
    public function inviteUrl(): ?string
    {
        $site = $this->wedding->site;

        return $site?->is_published ? $site->url().'/?u='.$this->token : null;
    }

    /**
     * A prefilled WhatsApp message. Sending it is still the couple's own action.
     */
    public function whatsappUrl(): ?string
    {
        $url = $this->inviteUrl();

        if ($url === null) {
            return null;
        }

        $site = $this->wedding->site;

        $text = "Assalamualaikum {$this->name},\n\n"
            ."Dengan penuh kesyukuran, kami menjemput anda ke majlis perkahwinan {$site->coupleNames()} "
            ."pada {$site->event_date->translatedFormat('j F Y')}"
            .($site->venue_name ? " bertempat di {$site->venue_name}" : '').".\n\n"
            ."Kad jemputan penuh: {$url}";

        return 'https://wa.me/'.($this->phone_normalised ?? '').'?text='.rawurlencode($text);
    }

    /**
     * Derived from what we actually observed, never stored, so the badge can
     * never disagree with the rows beneath it.
     */
    public function status(): GuestStatus
    {
        if ($this->rsvp) {
            return $this->rsvp->attending ? GuestStatus::Attending : GuestStatus::Declined;
        }

        if ($this->first_opened_at) {
            return GuestStatus::Opened;
        }

        return $this->shared_at ? GuestStatus::Shared : GuestStatus::Pending;
    }

    /**
     * Record a visit made with this guest's personal link.
     */
    public function recordOpen(): void
    {
        $this->forceFill([
            'first_opened_at' => $this->first_opened_at ?? now(),
            'last_opened_at' => now(),
            'open_count' => $this->open_count + 1,
        ])->saveQuietly();
    }
}
