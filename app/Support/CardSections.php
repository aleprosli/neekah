<?php

namespace App\Support;

/**
 * The sections a kad is built from, in the order they are printed.
 *
 * The hero at the top and the signature at the bottom are what make the sheet
 * a kad rather than a scrolling page, so neither can be moved or switched off.
 * Everything between them is the couple's to arrange.
 *
 * The arrangement is stored as a list of {key, on} rather than a list of the
 * ones that are on, because those two cannot be told apart later: a key that
 * is missing would mean both "switched off" and "added to Neekah after this
 * couple last saved", and a new section would silently never appear.
 *
 * A section that is on still prints nothing when it has no data — each partial
 * keeps its own guard — so switching one on is an invitation, not a hole.
 */
class CardSections
{
    /**
     * @var array<string, string>
     */
    public const ARRANGEABLE = [
        'salutation' => 'Kata aluan & ibu bapa',
        'countdown' => 'Menghitung hari',
        'venue' => 'Lokasi majlis',
        'itinerary' => 'Atur cara',
        'gallery' => 'Galeri gambar',
        'rsvp' => 'RSVP kehadiran',
        'gift' => 'Salam kaut',
        'wishes' => 'Ucapan & doa',
        'contacts' => 'Hubungi',
    ];

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::ARRANGEABLE);
    }

    public static function label(string $key): string
    {
        return self::ARRANGEABLE[$key] ?? $key;
    }

    /**
     * Every section with its label and whether it is on, in the couple's
     * order — what the editor's list is drawn from, and what the card is
     * rendered from once the off ones are dropped.
     *
     * Null means they never arranged anything: every section, catalogue order,
     * all on. A key the catalogue has gained since they saved is appended and
     * on, so a new section arrives rather than going missing.
     *
     * @param  array<int, mixed>|null  $arrangement
     * @return array<int, array{key: string, label: string, on: bool}>
     */
    public static function forEditor(?array $arrangement): array
    {
        $saved = collect($arrangement ?? [])
            ->filter(fn (mixed $row): bool => is_array($row) && array_key_exists($row['key'] ?? null, self::ARRANGEABLE))
            ->keyBy(fn (array $row): string => $row['key']);

        $ordered = $saved->keys()->merge(self::keys())->unique()->values();

        return $ordered
            ->map(fn (string $key): array => [
                'key' => $key,
                'label' => self::label($key),
                'on' => (bool) ($saved[$key]['on'] ?? true),
            ])
            ->all();
    }

    /**
     * The keys to print, in order.
     *
     * @param  array<int, mixed>|null  $arrangement
     * @return array<int, string>
     */
    public static function resolve(?array $arrangement): array
    {
        return collect(self::forEditor($arrangement))
            ->filter(fn (array $section): bool => $section['on'])
            ->pluck('key')
            ->all();
    }

    /**
     * What a posted arrangement is narrowed to before it is stored.
     *
     * @param  array<int, mixed>  $arrangement
     * @return array<int, array{key: string, on: bool}>
     */
    public static function sanitise(array $arrangement): array
    {
        return collect(self::forEditor($arrangement))
            ->map(fn (array $section): array => ['key' => $section['key'], 'on' => $section['on']])
            ->all();
    }
}
