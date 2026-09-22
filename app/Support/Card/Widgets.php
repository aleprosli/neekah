<?php

namespace App\Support\Card;

/**
 * The sections that follow the three designed canvases.
 *
 * These are not artwork: each one is drawn from what the couple already keeps in
 * Neekah (tentatif, lokasi, galeri, RSVP, ucapan, hadiah), wearing the design's
 * palette and type. A couple picks which of them their card carries, and in what
 * order; the canvases always come first.
 */
class Widgets
{
    /**
     * Every section a card may carry, in the order it shows them unless the couple
     * reorders.
     *
     * @var array<int, string>
     */
    public const KEYS = ['countdown', 'itinerary', 'location', 'gallery', 'rsvp', 'wishes', 'gift', 'contacts', 'closing'];

    /**
     * key => label, in the order a card shows them unless the couple reorders.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        // The card itself is Malay, but the editor around it is not, so these come
        // from the dictionary rather than being written here.
        return collect(self::KEYS)
            ->mapWithKeys(fn (string $key): array => [$key => __('pages.card_widgets.'.$key)])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return self::KEYS;
    }

    /**
     * What a card carries before the couple touches anything.
     *
     * @return array<int, string>
     */
    public static function defaults(): array
    {
        return self::keys();
    }

    /**
     * The couple's choice, in their order, with anything unknown dropped.
     *
     * @param  array<int, mixed>|null  $chosen
     * @return array<int, string>
     */
    public static function sanitize(?array $chosen): array
    {
        if ($chosen === null) {
            return self::defaults();
        }

        return collect($chosen)
            ->filter(fn (mixed $key): bool => is_string($key) && in_array($key, self::keys(), true))
            ->unique()
            ->values()
            ->all();
    }
}
