<?php

namespace App\Support\Card;

use App\Models\SiteTemplate;
use App\Models\WeddingSite;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;

/**
 * Everything the card renderer needs, in one array.
 *
 * The public card, the editor's live preview and the design gallery all mount the
 * same Vue component on this shape, so what a couple edits is exactly what a guest
 * opens. Text layers keep their {{tokens}} and the browser resolves them against
 * `content`, which is what makes typing a name redraw the card instantly.
 */
class CardProps
{
    /**
     * @param  array{name: string, pax_invited: int, token: string, has_responded: bool}|null  $guest
     * @return array<string, mixed>
     */
    public static function forSite(WeddingSite $site, SiteTemplate $template, bool $preview = false, ?array $guest = null): array
    {
        $palette = $template->palette($site->palette);
        $fonts = $template->fonts($site->fonts);

        return [
            'preview' => $preview,
            'experience' => $template->experience === 'motion' ? 'motion' : 'scroll',
            'canvases' => $template->canvases(),
            'widgets' => CardWidgetData::forSite($site, $preview, $guest),
            'content' => self::content($site, $guest),
            'photos' => self::photos($site, $template),
            'palette' => $palette,
            'fonts' => $fonts,
            'vars' => self::cssVariables($palette, $fonts),
            'music' => self::music($site, $template),
            'gate' => [
                // The card opens from a sealed cover, unless we are showing it inside
                // the editor, where a gate would hide the thing being edited.
                'enabled' => ! $preview,
                'label' => __('card.open'),
                'names' => $site->shortName('groom').' & '.$site->shortName('bride'),
                'initials' => $site->initials(),
                'seal' => match ($template->slug) {
                    'taman-bulan', 'melur-purnama', 'taman-zaitun', 'gerbang-wisteria', 'taman-embun' => '/img/layers/taman-bulan-wax-seal.webp',
                    'lili-kasih', 'kasih-sutera', 'mihrab-kasih' => '/img/layers/lili-wax-seal.webp',
                    default => null,
                },
                'texture' => in_array($template->slug, ['lili-kasih', 'kasih-sutera'], true) ? '/img/layers/lili-gate-paper.webp' : null,
            ],
            'guest' => $guest,
            // A card ships no translation dictionary (see App\Support\Translations),
            // so every word the renderer prints travels with the props.
            'labels' => Lang::get('card'),
            'design' => [
                'slug' => $template->slug,
                'name' => $template->name,
                'dark' => $template->isDark(),
            ],
        ];
    }

    /**
     * A design shown with sample content, for the public gallery and its previews.
     *
     * @return array<string, mixed>
     */
    public static function forSample(SiteTemplate $template, WeddingSite $sample): array
    {
        return self::forSite($sample, $template, preview: true);
    }

    /**
     * Just the cover, for a gallery thumbnail.
     *
     * @return array<string, mixed>
     */
    public static function forThumbnail(SiteTemplate $template, WeddingSite $sample): array
    {
        $props = self::forSample($template, $sample);
        $props['canvases'] = array_slice($props['canvases'], 0, 1);
        $props['widgets'] = [];
        $props['thumbnail'] = true;

        return $props;
    }

    /**
     * The values {{tokens}} in the artwork resolve against. Dates stay machine
     * readable; the browser formats them, so changing the date redraws at once.
     *
     * @param  array{name: string, pax_invited: int, token: string, has_responded: bool}|null  $guest
     * @return array<string, string>
     */
    protected static function content(WeddingSite $site, ?array $guest = null): array
    {
        return [
            'groom' => (string) $site->groom_name,
            'bride' => (string) $site->bride_name,
            'groom_short' => $site->shortName('groom'),
            'bride_short' => $site->shortName('bride'),
            'date' => $site->event_date?->toDateString() ?? '',
            'time' => self::time($site->starts_at),
            'end_time' => self::time($site->ends_at),
            'venue' => (string) $site->venue_name,
            'address' => (string) $site->venue_address,
            'invite_message' => (string) ($site->invitation_note ?: $site->salutation),
            'salutation' => (string) $site->salutation,
            'groom_father' => (string) $site->groom_father,
            'groom_mother' => (string) $site->groom_mother,
            'bride_father' => (string) $site->bride_father,
            'bride_mother' => (string) $site->bride_mother,
            'groom_bio' => (string) $site->groom_bio,
            'bride_bio' => (string) $site->bride_bio,
            'closing_note' => (string) $site->closing_note,
            'guest' => (string) ($guest['name'] ?? ''),
        ];
    }

    /**
     * The photo in each slot the design asks for. A slot with no photo renders as
     * the artwork's own placeholder in a preview, and as nothing on a live card.
     *
     * @return array<string, string|null>
     */
    protected static function photos(WeddingSite $site, SiteTemplate $template): array
    {
        $photos = [];

        foreach ($template->photoSlots() as $slot) {
            $photos[$slot] = $site->slotImage($slot);
        }

        return $photos;
    }

    /**
     * @return array{url: string, title: string}|null
     */
    protected static function music(WeddingSite $site, SiteTemplate $template): ?array
    {
        if (! $site->music_enabled) {
            return null;
        }

        if ($site->musicTrack) {
            return ['url' => $site->musicTrack->url(), 'title' => $site->musicTrack->label()];
        }

        if (in_array($template->slug, ['sutera-zaitun', 'taman-bulan', 'melur-purnama', 'taman-zaitun', 'gerbang-wisteria', 'taman-embun'], true)) {
            return ['url' => asset('sutera-zaitun-music.mp3'), 'title' => 'Wedding Harp — Francisco Alvear'];
        }

        if (in_array($template->slug, ['lili-kasih', 'kasih-sutera', 'mihrab-kasih'], true)) {
            return ['url' => asset('lili-kasih-music.mp3'), 'title' => 'Satu Shaf'];
        }

        return null;
    }

    /**
     * The design's colours and faces as the CSS variables every layer refers to.
     *
     * @param  array<string, string>  $palette
     * @param  array<string, string>  $fonts
     * @return array<string, string>
     */
    protected static function cssVariables(array $palette, array $fonts): array
    {
        return [...Palettes::cssVariables($palette), ...Fonts::cssVariables($fonts)];
    }

    protected static function time(mixed $value): string
    {
        return $value ? Carbon::parse($value)->format('H:i') : '';
    }
}
