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

];
