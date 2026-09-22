<?php

namespace App\Support\Card;

/**
 * The type faces a card may draw with.
 *
 * A design asks for four roles — display, script, serif and sans — and every text
 * layer stores the role ("role:d"), never a family name. The family is resolved at
 * render time, so a couple swapping a font changes four CSS variables instead of
 * every layer in the card.
 */
class Fonts
{
    /** display · script · serif (running text) · sans (small caps, labels). */
    public const ROLES = ['d', 's', 'r', 'n'];

    /**
     * family => kind. The weights are bundled by vite.config.js, which is the one
     * place that decides which files are actually shipped.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            'Cormorant Garamond' => 'serif',
            'Playfair Display' => 'serif',
            'Bodoni Moda' => 'serif',
            'DM Serif Display' => 'serif',
            'Cinzel' => 'serif',
            'Libre Baskerville' => 'serif',
            'Lora' => 'serif',
            'Abril Fatface' => 'serif',
            'Special Elite' => 'serif',
            'Montserrat' => 'sans',
            'Poppins' => 'sans',
            'Lato' => 'sans',
            'Inter' => 'sans',
            'Great Vibes' => 'script',
            'Allura' => 'script',
            'Parisienne' => 'script',
            'Caveat' => 'script',
            'Amiri' => 'arabic',
        ];
    }

    /**
     * The four families a design asked for, keyed by role.
     *
     * @param  array<int, string>  $families  display, script, serif, sans
     * @return array<string, string>
     */
    public static function roles(array $families): array
    {
        $display = self::family($families[0] ?? null, 'Cormorant Garamond');

        return [
            'd' => $display,
            's' => self::family($families[1] ?? null, $display),
            'r' => self::family($families[2] ?? null, $display),
            'n' => self::family($families[3] ?? null, 'Montserrat'),
        ];
    }

    /**
     * The four families of a stored, role-keyed set, with anything missing or
     * retired falling back to a face we still ship.
     *
     * @param  array<string, mixed>  $roles
     * @return array<string, string>
     */
    public static function byRole(array $roles): array
    {
        $display = self::family(is_string($roles['d'] ?? null) ? $roles['d'] : null, 'Cormorant Garamond');

        return [
            'd' => $display,
            's' => self::family(is_string($roles['s'] ?? null) ? $roles['s'] : null, $display),
            'r' => self::family(is_string($roles['r'] ?? null) ? $roles['r'] : null, $display),
            'n' => self::family(is_string($roles['n'] ?? null) ? $roles['n'] : null, 'Montserrat'),
        ];
    }

    public static function family(?string $font, string $default): string
    {
        return self::isValid($font) ? $font : $default;
    }

    public static function isValid(?string $font): bool
    {
        return $font !== null && array_key_exists($font, self::all());
    }

    public static function isRole(mixed $value): bool
    {
        return is_string($value) && str_starts_with($value, 'role:') && in_array(substr($value, 5), self::ROLES, true);
    }

    public static function stack(string $font): string
    {
        $fallback = match (self::all()[$font] ?? 'sans') {
            'serif' => 'Georgia, serif',
            'arabic' => '"Traditional Arabic", serif',
            'script' => 'cursive',
            default => 'system-ui, sans-serif',
        };

        return '"'.$font.'", '.$fallback;
    }

    /**
     * The four role families as CSS variables the renderer reads.
     *
     * @param  array<string, string>  $roles
     * @return array<string, string>
     */
    public static function cssVariables(array $roles): array
    {
        $vars = [];
        foreach (self::ROLES as $role) {
            if (isset($roles[$role]) && self::isValid($roles[$role])) {
                $vars['--f-'.$role] = self::stack($roles[$role]);
            }
        }

        return $vars;
    }

    /**
     * Picker options, grouped so a couple is not handed eighteen names at once.
     *
     * @return array<int, array{kind: string, label: string, fonts: array<int, array{value: string, label: string}>}>
     */
    public static function options(): array
    {
        $labels = ['serif' => 'Serif', 'sans' => 'Sans', 'script' => 'Tulisan tangan', 'arabic' => 'Arab'];
        $groups = [];

        foreach (self::all() as $family => $kind) {
            $groups[$kind][] = ['value' => $family, 'label' => $family];
        }

        return collect($labels)
            ->filter(fn (string $label, string $kind): bool => isset($groups[$kind]))
            ->map(fn (string $label, string $kind): array => ['kind' => $kind, 'label' => $label, 'fonts' => $groups[$kind]])
            ->values()
            ->all();
    }
}
