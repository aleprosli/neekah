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
        // copy: the show/hide password button in every password field.
        'auth' => ['common', 'auth', 'vendor_signup', 'copy'],
        // vendor_signup is not here: the sign-up form is an auth page, and its
        // placeholder examples were turning up in the markup of every public
        // page, where two tests reasonably assert that a vendor's name is not.
        // date_picker: the booking calendar on a vendor page taking online bookings.
        'site' => ['common', 'auth', 'blog', 'invitation', 'notifications', 'gallery', 'report', 'date_picker', 'copy'],
        // The Kamera Majlis page a guest opens from the QR.
        'camera' => ['common', 'copy', 'camera'],
        // A printable invoice or receipt: no islands at all.
        'document' => [],
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
