<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        /*
         * Uploaded media: vendor photos, card covers, post images.
         *
         * Local on a laptop, Cloudflare R2 in production, under the one disk
         * name either way, so nothing that stores or reads an image has to
         * know which it is talking to - and so Storage::fake('public') keeps
         * working in the tests.
         *
         * Stored paths are relative ("vendors/7/abc.webp") and identical on
         * both, which is what lets the files be copied across as they are.
         */
        'public' => env('MEDIA_DISK', 'local') === 'r2'
            ? [
                'driver' => 's3',
                'key' => env('R2_ACCESS_KEY_ID'),
                'secret' => env('R2_SECRET_ACCESS_KEY'),
                // R2 has one region and calls it this.
                'region' => 'auto',
                'bucket' => env('R2_BUCKET'),
                'endpoint' => env('R2_ENDPOINT'),
                // Where the browser fetches an image from: the bucket's custom
                // domain, so Cloudflare caches it at the edge and the origin
                // server never serves an image again.
                'url' => env('R2_URL'),
                // R2 does not answer to bucket.endpoint, only endpoint/bucket.
                'use_path_style_endpoint' => true,
                // Sent with every object written, which is what makes a browser
                // and Cloudflare's edge both keep it. Safe to say immutable:
                // StoreOptimizedImage names every file with forty random
                // characters and writes a new name rather than overwriting, so
                // what lives at a URL never changes. Replacing an image gives a
                // new URL, and the old one is deleted.
                'options' => [
                    'CacheControl' => 'public, max-age=31536000, immutable',
                ],
                'throw' => false,
                'report' => false,
            ]
            : [
                'driver' => 'local',
                'root' => storage_path('app/public'),
                'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
                'visibility' => 'public',
                'throw' => false,
                'report' => false,
            ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
