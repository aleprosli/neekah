<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Wedding invitation site domain
    |--------------------------------------------------------------------------
    | Published invitations are served from a subdomain of this host, for
    | example ainahakim.neekah.test. Defaults to the host of APP_URL.
    */

    'site_domain' => env('NEEKAH_SITE_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),

    /*
    | Invitation subdomains are usually served by the web server on the standard
    | port, even when the app itself is reached on another one. Keep these
    | separate from APP_URL so a published card never links to :8000.
    */

    'site_scheme' => env('NEEKAH_SITE_SCHEME', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_SCHEME) ?: 'http'),

    'site_port' => env('NEEKAH_SITE_PORT'),

    /*
    |--------------------------------------------------------------------------
    | Brand artwork
    |--------------------------------------------------------------------------
    | Every logo on the site reads from here, so swapping the brand is a change
    | in one place. Paths are relative to public/ and resolved with asset().
    |
    | Supply artwork already trimmed to its own edges. A file with built-in
    | padding renders small inside its box, because the layout sizes the file
    | and cannot know where the artwork stops.
    |
    | lockup: mark plus wordmark, transparent, used in the header and preloader.
    | mark:   the square tile, used where only an icon fits.
    | icon:   the browser tab and home screen icon.
    */

    'brand' => [
        'lockup' => env('NEEKAH_BRAND_LOCKUP', 'img/logo/neekah-lockup.png'),
        'mark' => env('NEEKAH_BRAND_MARK', 'img/logo/neekah-mark-512.png'),
        'icon' => env('NEEKAH_BRAND_ICON', 'img/logo/neekah-mark-192.png'),
        'apple_icon' => env('NEEKAH_BRAND_APPLE_ICON', 'img/logo/neekah-mark-180.png'),
    ],

];
