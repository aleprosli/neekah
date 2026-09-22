<?php

namespace App\Http\Requests;

use App\Models\CardMusicTrack;
use App\Models\SiteTemplate;
use App\Models\WeddingSite;
use App\Support\Card\Catalog;
use App\Support\Card\Fonts;
use App\Support\Card\Palettes;
use App\Support\Card\Widgets;
use App\Support\ImageSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWeddingSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('wedding')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('subdomain')) {
            $this->merge(['subdomain' => mb_strtolower(trim($this->string('subdomain')->toString()))]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(ImageSettings $images): array
    {
        $site = $this->route('wedding')->site;

        return [
            'subdomain' => self::subdomainRules($site),
            'template' => ['required', Rule::exists(SiteTemplate::class, 'slug')->where('is_active', true)],
            'bride_name' => ['required', 'string', 'max:80'],
            'groom_name' => ['required', 'string', 'max:80'],
            'bride_short' => ['nullable', 'string', 'max:40'],
            'groom_short' => ['nullable', 'string', 'max:40'],
            'bride_father' => ['nullable', 'string', 'max:120'],
            'bride_mother' => ['nullable', 'string', 'max:120'],
            'groom_father' => ['nullable', 'string', 'max:120'],
            'groom_mother' => ['nullable', 'string', 'max:120'],
            'bride_bio' => ['nullable', 'string', 'max:300'],
            'groom_bio' => ['nullable', 'string', 'max:300'],
            'salutation' => ['nullable', 'string', 'max:200'],
            'invitation_note' => ['nullable', 'string', 'max:1000'],
            'event_date' => ['required', 'date'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after:starts_at'],
            'venue_name' => ['nullable', 'string', 'max:160'],
            'venue_address' => ['nullable', 'string', 'max:500'],
            'map_url' => ['nullable', 'url', 'max:500'],
            'itinerary' => ['nullable', 'array', 'max:12'],
            'itinerary.*.time' => ['nullable', 'string', 'max:20'],
            'itinerary.*.label' => ['nullable', 'string', 'max:80'],
            'contacts' => ['nullable', 'array', 'max:6'],
            'contacts.*.name' => ['nullable', 'string', 'max:80'],
            'contacts.*.phone' => ['nullable', 'string', 'max:30'],
            // The design's photo slots, and the colours and faces it is worn with.
            'photos' => ['nullable', 'array'],
            'photos.*' => ['nullable', ...$images->uploadRules()],
            'remove_photos' => ['nullable', 'array'],
            'remove_photos.*' => ['string', Rule::in(Catalog::photoSlotKeys())],
            'palette' => ['nullable', 'array'],
            'palette.*' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'fonts' => ['nullable', 'array'],
            'fonts.*' => ['nullable', 'string', Rule::in(array_keys(Fonts::all()))],
            'widgets' => ['nullable', 'array'],
            'widgets.*' => ['string', Rule::in(Widgets::keys())],
            'music_enabled' => ['nullable', 'boolean'],
            'music_track_id' => ['nullable', Rule::exists(CardMusicTrack::class, 'id')->where('is_active', true)],
            'rsvp_enabled' => ['nullable', 'boolean'],
            'rsvp_deadline' => ['nullable', 'date', 'before_or_equal:event_date'],
            'closing_note' => ['nullable', 'string', 'max:500'],
            'gift_enabled' => ['nullable', 'boolean'],
            'gift_note' => ['nullable', 'string', 'max:500'],
            'gift_qr_image' => ['nullable', ...$images->uploadRules()],
            'gift_accounts' => ['nullable', 'array', 'max:4'],
            'gift_accounts.*.bank' => ['nullable', 'string', 'max:60'],
            'gift_accounts.*.holder' => ['nullable', 'string', 'max:80'],
            'gift_accounts.*.number' => ['nullable', 'string', 'max:40'],
            'wishes_enabled' => ['nullable', 'boolean'],
        ];
    }

    /**
     * The address rules, shared with the live availability check in the editor
     * so the two can never disagree about what may be saved.
     *
     * @return array<int, mixed>
     */
    public static function subdomainRules(?WeddingSite $ignore = null): array
    {
        return [
            'required', 'string', 'min:3', 'max:63',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::notIn(WeddingSite::RESERVED_SUBDOMAINS),
            Rule::unique(WeddingSite::class, 'subdomain')->ignore($ignore),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function subdomainMessages(): array
    {
        return [
            'subdomain.regex' => __('validation.custom.subdomain_regex'),
            'subdomain.not_in' => __('validation.custom.subdomain_reserved'),
            'subdomain.unique' => __('validation.custom.subdomain_taken'),
        ];
    }

    /**
     * Drop empty itinerary and contact rows so blank inputs are not stored.
     *
     * @return array<string, mixed>
     */
    public function siteAttributes(): array
    {
        $data = $this->safe()->except(['gift_qr_image', 'photos', 'remove_photos']);

        // Only the roles the couple actually chose, so re-tuning a design later still
        // reaches the cards that never overrode it.
        $data['palette'] = Palettes::sanitize($data['palette'] ?? []) ?: null;
        $data['fonts'] = collect($data['fonts'] ?? [])
            ->only(Fonts::ROLES)
            ->filter(fn (mixed $family): bool => Fonts::isValid(is_string($family) ? $family : null))
            ->all() ?: null;
        $data['widgets'] = Widgets::sanitize($data['widgets'] ?? null);

        $data['itinerary'] = collect($data['itinerary'] ?? [])
            ->filter(fn (array $row): bool => filled($row['time'] ?? null) && filled($row['label'] ?? null))
            ->map(fn (array $row): array => ['time' => $row['time'], 'label' => $row['label']])
            ->values()
            ->all();

        $data['contacts'] = collect($data['contacts'] ?? [])
            ->filter(fn (array $row): bool => filled($row['name'] ?? null) && filled($row['phone'] ?? null))
            ->map(fn (array $row): array => ['name' => $row['name'], 'phone' => $row['phone']])
            ->values()
            ->all();

        $data['gift_accounts'] = collect($data['gift_accounts'] ?? [])
            ->filter(fn (array $row): bool => filled($row['bank'] ?? null) && filled($row['number'] ?? null))
            ->map(fn (array $row): array => ['bank' => $row['bank'], 'holder' => $row['holder'] ?? '', 'number' => $row['number']])
            ->values()
            ->all();

        $data['rsvp_enabled'] = $this->boolean('rsvp_enabled');
        $data['gift_enabled'] = $this->boolean('gift_enabled');
        $data['wishes_enabled'] = $this->boolean('wishes_enabled');
        $data['music_enabled'] = $this->boolean('music_enabled');

        return $data;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'subdomain' => __('fields.alamat_web'),
            'template' => __('fields.template'),
            'bride_name' => __('fields.nama_pengantin_perempuan'),
            'groom_name' => __('fields.nama_pengantin_lelaki'),
            'event_date' => __('fields.tarikh_majlis'),
            'starts_at' => __('fields.masa_mula'),
            'ends_at' => __('fields.masa_tamat'),
            'map_url' => __('fields.pautan_peta'),
            'bride_short' => __('fields.nama_pendek_perempuan'),
            'groom_short' => __('fields.nama_pendek_lelaki'),
            'rsvp_deadline' => __('fields.tarikh_akhir_rsvp'),
            'gift_note' => __('fields.nota_hadiah'),
            'gift_qr_image' => __('fields.kod_qr_duitnow'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return self::subdomainMessages();
    }
}
