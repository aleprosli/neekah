<?php

namespace App\Support\Card;

use App\Models\WeddingSite;
use Illuminate\Support\Carbon;

/**
 * The sections that follow the designed canvases, filled from the couple's own data.
 *
 * A widget with nothing to show is left out rather than rendered empty: a card with
 * an empty "Salam kaut" heading reads as a broken card, not as a card without a gift
 * section. In the editor (preview) they are kept so the couple can see what they are
 * turning on.
 */
class CardWidgetData
{
    /**
     * @param  array{name: string, pax_invited: int, token: string, has_responded: bool}|null  $guest
     * @return array<int, array<string, mixed>>
     */
    public static function forSite(WeddingSite $site, bool $preview = false, ?array $guest = null): array
    {
        $widgets = [];

        foreach ($site->widgetKeys() as $key) {
            $data = self::one($key, $site, $guest);

            if ($data === null) {
                continue;
            }

            if (! $preview && ($data['empty'] ?? false)) {
                continue;
            }

            $widgets[] = ['key' => $key, 'heading' => self::heading($key), ...$data];
        }

        return $widgets;
    }

    /**
     * @return array<string, string>
     */
    public static function headings(): array
    {
        return [
            'countdown' => __('card.countdown'),
            'itinerary' => __('card.itinerary'),
            'location' => __('card.location'),
            'gallery' => __('card.gallery'),
            'rsvp' => __('card.rsvp'),
            'wishes' => __('card.wishes'),
            'gift' => __('card.gift'),
            'contacts' => __('card.contacts'),
            'closing' => __('card.closing'),
        ];
    }

    protected static function heading(string $key): string
    {
        return self::headings()[$key] ?? '';
    }

    /**
     * @param  array{name: string, pax_invited: int, token: string, has_responded: bool}|null  $guest
     * @return array<string, mixed>|null
     */
    protected static function one(string $key, WeddingSite $site, ?array $guest): ?array
    {
        return match ($key) {
            'countdown' => [
                'target' => self::eventStart($site)?->toIso8601String(),
                'empty' => $site->event_date === null || $site->daysUntil() === 0,
            ],
            'itinerary' => [
                'rows' => array_values($site->itinerary ?? []),
                'empty' => blank($site->itinerary),
            ],
            'location' => [
                'venue' => (string) $site->venue_name,
                'address' => (string) $site->venue_address,
                'mapUrl' => self::mapUrl($site),
                'calendarUrl' => $site->exists && $site->subdomain ? route('sites.calendar', ['subdomain' => $site->subdomain]) : null,
                'empty' => blank($site->venue_name) && blank($site->venue_address),
            ],
            'gallery' => [
                'photos' => $site->exists
                    ? $site->photos->map(fn ($photo): array => ['url' => $photo->url(), 'caption' => (string) $photo->caption])->values()->all()
                    : [],
                'empty' => ! $site->exists || $site->photos->isEmpty(),
            ],
            'rsvp' => [
                'open' => $site->acceptsRsvps(),
                'deadline' => $site->rsvp_deadline?->toDateString(),
                'action' => $site->exists && $site->subdomain ? route('sites.rsvp', ['subdomain' => $site->subdomain]) : null,
                'guest' => $guest,
                'empty' => ! $site->rsvp_enabled,
            ],
            'wishes' => [
                'items' => $site->exists
                    ? $site->approvedWishes->map(fn ($wish): array => ['name' => (string) $wish->name, 'message' => (string) $wish->message])->values()->all()
                    : [],
                // Guests write wishes in the RSVP form, so this section never has a
                // write endpoint of its own to abuse.
                'note' => __('card.wishes_note'),
                'empty' => ! $site->wishes_enabled,
            ],
            'gift' => [
                'note' => (string) $site->gift_note,
                'accounts' => array_values($site->gift_accounts ?? []),
                'qrUrl' => $site->giftQrUrl(),
                'empty' => ! $site->showsGift(),
            ],
            'contacts' => [
                'items' => collect($site->contacts ?? [])
                    ->map(fn (array $row): array => [
                        'name' => (string) ($row['name'] ?? ''),
                        'phone' => (string) ($row['phone'] ?? ''),
                        'whatsapp' => self::whatsapp($row['phone'] ?? ''),
                    ])
                    ->values()
                    ->all(),
                'empty' => blank($site->contacts),
            ],
            'closing' => [
                'note' => (string) $site->closing_note,
                'signature' => $site->shortName('groom').' & '.$site->shortName('bride'),
                'empty' => blank($site->closing_note),
            ],
            default => null,
        };
    }

    protected static function eventStart(WeddingSite $site): ?Carbon
    {
        if ($site->event_date === null) {
            return null;
        }

        return $site->event_date->copy()->setTimeFromTimeString($site->starts_at ? Carbon::parse($site->starts_at)->format('H:i:s') : '00:00:00');
    }

    protected static function mapUrl(WeddingSite $site): ?string
    {
        if (filled($site->map_url)) {
            return $site->map_url;
        }

        $query = trim($site->venue_name.' '.$site->venue_address);

        return $query === '' ? null : 'https://www.google.com/maps/search/?api=1&query='.urlencode($query);
    }

    /**
     * A wa.me link, so a guest calling the family does not have to retype a number.
     */
    protected static function whatsapp(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) < 9) {
            return null;
        }

        $digits = str_starts_with($digits, '60') ? $digits : '60'.ltrim($digits, '0');

        return 'https://wa.me/'.$digits;
    }
}
