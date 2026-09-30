<?php

namespace App\Support;

/**
 * The negeri list, and the flag that goes beside each name.
 *
 * The list itself is static data in config/states.php so it can be edited
 * without touching a model, and the sixteen SVGs in public/img/flag are named
 * after each entry's slug. A negeri with no artwork simply shows its name, so
 * the list may grow before the flags do.
 */
class States
{
    /**
     * @return array<int, array{name: string, slug: string, area: string, districts: array<int, string>}>
     */
    public static function all(): array
    {
        return config('states', []);
    }

    /**
     * Every negeri's name, which is what the database stores.
     *
     * @return array<int, string>
     */
    public static function names(): array
    {
        return array_column(self::all(), 'name');
    }

    public static function has(?string $name): bool
    {
        return $name !== null && in_array($name, self::names(), true);
    }

    public static function slug(?string $name): ?string
    {
        foreach (self::all() as $state) {
            if ($state['name'] === $name) {
                return $state['slug'];
            }
        }

        return null;
    }

    /**
     * The flag's URL, or null when there is no artwork for that negeri.
     */
    public static function flagUrl(?string $name): ?string
    {
        $slug = self::slug($name);

        return $slug ? asset('img/flag/'.$slug.'.svg') : null;
    }

    /**
     * Every negeri as a dropdown option, ready to hand to a Vue component.
     *
     * @return array<int, array{value: string, label: string, flag: string|null}>
     */
    public static function options(): array
    {
        return array_map(fn (array $state): array => [
            'value' => $state['name'],
            'label' => $state['name'],
            'flag' => self::flagUrl($state['name']),
        ], self::all());
    }

    /**
     * The daerah (or bahagian, or main areas) inside one negeri; empty for
     * anything that is not a negeri.
     *
     * @return array<int, string>
     */
    public static function districts(?string $name): array
    {
        foreach (self::all() as $state) {
            if ($state['name'] === $name) {
                return $state['districts'] ?? [];
            }
        }

        return [];
    }

    /**
     * Every negeri's areas, keyed by negeri, with what that list is called
     * ("Daerah", "Bahagian", "Kawasan"), for a field that follows the negeri.
     *
     * @return array<string, array{label: string, options: array<int, string>}>
     */
    public static function districtOptions(): array
    {
        return collect(self::all())
            ->mapWithKeys(fn (array $state): array => [$state['name'] => [
                'label' => __('ui.areas.'.($state['area'] ?? 'daerah')),
                'options' => $state['districts'] ?? [],
            ]])
            ->all();
    }
}
