<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resort_guru' => [
        'frontend_url' => env('RG_FRONTEND_URL', 'http://localhost:8001'),
    ],


    /*
     * The secret AniSystem presents when it stores a file here. Set the same
     * value in that app's ANISYSTEM_MEDIA_TOKEN; empty means the endpoint is
     * closed rather than open.
     */
    /*
     * The map the schedule manager draws on.
     *
     * The same Google project the farmer app uses — one product, one key —
     * but read from this app's own environment, so an admin does not lose the
     * map because the other app's environment changed.
     */
    'google_maps' => [
        'key' => env('GOOGLE_MAPS_KEY'),
    ],

    'anisystem_media' => [
        'token' => env('ANISYSTEM_MEDIA_TOKEN', ''),
    ],

];
