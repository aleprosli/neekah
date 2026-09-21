<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * The languages Neekah is served in.
 *
 * Malay is the default and sits at the root, because every URL Google has
 * indexed is a Malay one and moving them would throw that away. English is
 * served under /en, as its own set of pages rather than the same page with the
 * words swapped — that is what lets both be indexed and pointed at each other
 * with hreflang.
 */
class Locales
{
    public const DEFAULT = 'ms';

    /**
     * @var array<string, array{label: string, html: string, hreflang: string}>
     */
    public const ALL = [
        'ms' => ['label' => 'Bahasa Melayu', 'html' => 'ms', 'hreflang' => 'ms-MY'],
        'en' => ['label' => 'English', 'html' => 'en', 'hreflang' => 'en-MY'],
    ];

    /**
     * @return array<int, string>
     */
    public static function codes(): array
    {
        return array_keys(self::ALL);
    }

    public static function supported(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, self::ALL);
    }

    public static function current(): string
    {
        $locale = app()->getLocale();

        return self::supported($locale) ? $locale : self::DEFAULT;
    }

    /**
     * The URL prefix a locale is served under. The default has none: its pages
     * are the site itself.
     */
    public static function prefix(string $locale): string
    {
        return $locale === self::DEFAULT ? '' : $locale;
    }

    public static function label(string $locale): string
    {
        return self::ALL[$locale]['label'] ?? $locale;
    }

    public static function hreflang(string $locale): string
    {
        return self::ALL[$locale]['hreflang'] ?? $locale;
    }

    public static function html(string $locale): string
    {
        return self::ALL[$locale]['html'] ?? $locale;
    }

    /**
     * The current page in every language, keyed by code — what hreflang and the
     * language switcher are both built from.
     *
     * A page with no route name (an error page, a one-off) has no counterpart
     * to point at, and returns nothing rather than guessing at a URL.
     *
     * @return array<string, string>
     */
    public static function alternates(): array
    {
        $route = request()->route();
        $name = self::baseRouteName($route?->getName());

        if ($name === null) {
            return [];
        }

        $parameters = collect($route->parameters())->except('locale')->all();

        return collect(self::codes())
            ->mapWithKeys(fn (string $code): array => [$code => url()->routeIn($code, $name, $parameters)])
            ->filter()
            ->all();
    }

    /**
     * A route name as it is registered for a locale. Routes are registered
     * once per language, and the non-default ones carry their code as a name
     * prefix so the two sets can live side by side.
     */
    public static function routeName(string $name, ?string $locale = null): string
    {
        $locale ??= self::current();

        return $locale === self::DEFAULT ? $name : $locale.'.'.$name;
    }

    /**
     * Whether the current route is one of these, ignoring the language.
     *
     * Request::routeIs() compares the registered name, which now carries the
     * language as a prefix — so on an English page every nav highlight and
     * every guard written against "vendors.*" would quietly stop matching.
     * This is what those call sites ask instead. It cannot be a macro:
     * routeIs() is a real method, so a macro would never be reached.
     */
    public static function routeIs(mixed ...$patterns): bool
    {
        $name = self::baseRouteName(request()->route()?->getName());

        // Callers pass a list of patterns or one array of them, the way
        // Request::routeIs() accepts both.
        return $name !== null && Str::is(Arr::flatten($patterns), $name);
    }

    /**
     * A route name with any locale prefix taken off, which is what code
     * comparing route names actually wants to know.
     */
    public static function baseRouteName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        foreach (self::codes() as $code) {
            if ($code !== self::DEFAULT && str_starts_with($name, $code.'.')) {
                return substr($name, strlen($code) + 1);
            }
        }

        return $name;
    }
}
