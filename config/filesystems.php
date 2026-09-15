<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        /*
         * Uploads outlive the container.
         *
         * This app now keeps AniSystem's media as well as its own, and a
         * container's filesystem is thrown away on every deploy — so the
         * photos both apps point at have to live on a mounted volume.
         * Railway sets RAILWAY_VOLUME_MOUNT_PATH itself when one is attached
         * (/axis-volume here), so nothing else needs configuring;
         * APP_STORAGE_ROOT covers any other host, and with neither set this
         * behaves exactly as it always did.
         */
        /*
         * Or on a bucket, which is where they go on a host with no disk to
         * mount at all (Laravel Cloud). MEDIA_DISK=s3 swaps this disk's
         * driver and nothing else: every Storage::disk('public') call in the
         * app, and every `/storage/<path>` address ever handed out, keeps
         * working -- the fallback route below /storage sends the browser on
         * to the bucket. The AWS_* values are the ones the host injects for
         * its bucket; AWS_URL is the bucket's public address and may be
         * blank for a private bucket, in which case the fallback signs a
         * temporary link instead.
         */
        'public' => env('MEDIA_DISK') === 's3' ? [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'auto'),
            'bucket' => env('AWS_BUCKET'),
            // The bucket's public address, when it has one that answers. The
            // host injects AWS_URL the moment a bucket is attached, before
            // public access is switched on -- and a 301 to an address that
            // answers 530 is a broken picture. MEDIA_SIGNED_LINKS=1 ignores
            // it and every read goes out as a signed link instead.
            'url' => env('MEDIA_SIGNED_LINKS') ? null : (env('AWS_URL') ?: null),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => (bool) env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => true,
        ] : [
            'driver' => 'local',
            'root' => env('APP_STORAGE_ROOT')
                ?: (env('RAILWAY_VOLUME_MOUNT_PATH')
                    ? rtrim((string) env('RAILWAY_VOLUME_MOUNT_PATH'), '/').'/public'
                    : storage_path('app/public')),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
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
