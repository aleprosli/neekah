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
     * Which parts of the dictionary each kind of page needs.
     *
     * A wedding guest opening a couple's card was being handed nine kilobytes
     * of the couple's own editor strings — every label in the guest list, the
     * budget and the booking screens, on a page that mounts no Vue at all.
     * Pages get what their islands read and nothing else.
     *
     * @var array<string, array<int, string>>
     */
    private const GROUPS_BY_SHELL = [
        'card' => [],
        'auth' => ['common', 'auth', 'vendor_signup'],
        'site' => ['common', 'auth', 'blog', 'invitation', 'notifications', 'gallery', 'report', 'vendor_signup'],
    ];

    /**
     * Everything, for the signed-in areas where almost any island may appear.
     */
    private const ALL = ['*'];

    /**
     * @return array<string, mixed>
     */
    public static function forClient(?string $shell = null, ?string $locale = null): array
    {
        $locale ??= Locales::current();

        $strings = Lang::get(self::CLIENT_GROUP, [], $locale);

        // Lang::get hands back the key itself when the file is missing, which
        // would ship the string "ui" as the whole dictionary.
        if (! is_array($strings)) {
            return [];
        }

        $wanted = self::GROUPS_BY_SHELL[$shell] ?? self::ALL;

        if ($wanted === self::ALL) {
            return $strings;
        }

        return array_intersect_key($strings, array_flip($wanted));
    }
}
