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
     * @return array<int, array{name: string, slug: string}>
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
}
