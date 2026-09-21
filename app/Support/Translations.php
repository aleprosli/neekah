<?php

namespace App\Support;

use Illuminate\Support\Facades\Lang;

/**
 * The strings the browser is given.
 *
 * Vue islands cannot call __(), so the page carries a dictionary they read
 * instead. Only the "ui" group goes over the wire: validation messages,
 * notification bodies and email copy are the server's business and have no
 * reason to be downloaded by every visitor.
 */
class Translations
{
    /** The one group Vue components read from. */
    public const CLIENT_GROUP = 'ui';

    /**
     * @return array<string, mixed>
     */
    public static function forClient(?string $locale = null): array
    {
        $locale ??= Locales::current();

        $strings = Lang::get(self::CLIENT_GROUP, [], $locale);

        // Lang::get hands back the key itself when the file is missing, which
        // would ship the string "ui" as the whole dictionary.
        return is_array($strings) ? $strings : [];
    }
}
