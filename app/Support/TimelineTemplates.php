<?php

namespace App\Support;

/**
 * Ready-made running orders for the wedding day. An empty timeline was a
 * blank form nobody knew how to start; a couple picks one of these, then
 * moves the times and assigns their vendors. Titles are written in the
 * couple's language when they apply it.
 */
class TimelineTemplates
{
    /** @var array<string, list<array{0: string, 1: string|null, 2: string}>> start, end, lang key under pages.timeline_templates.items */
    private const TEMPLATES = [
        'akad' => [
            ['08:00', '08:30', 'groom_arrives'],
            ['08:30', '09:30', 'akad'],
            ['09:30', '10:00', 'family_photos'],
            ['10:00', '11:00', 'light_meal'],
        ],
        'resepsi' => [
            ['11:00', null, 'guests_arrive'],
            ['12:30', '12:45', 'couple_arrives'],
            ['12:45', '13:15', 'bersanding'],
            ['13:15', '13:30', 'cake_speech'],
            ['13:30', '15:30', 'guest_photos'],
            ['16:00', null, 'ends'],
        ],
    ];

    /**
     * The template keys a couple can pick, the combined one last.
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        return ['akad', 'resepsi', 'penuh'];
    }

    /**
     * @return list<array{starts_at: string, ends_at: string|null, title: string}>
     */
    public static function items(string $key): array
    {
        $rows = $key === 'penuh'
            ? [...self::TEMPLATES['akad'], ...self::TEMPLATES['resepsi']]
            : self::TEMPLATES[$key];

        return array_map(fn (array $row): array => [
            'starts_at' => $row[0],
            'ends_at' => $row[1],
            'title' => __('pages.timeline_templates.items.'.$row[2]),
        ], $rows);
    }
}
