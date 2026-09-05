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

];
