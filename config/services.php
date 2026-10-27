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

    /*
     * Acumbamail (email marketing). The key is the account's auth token from
     * acumbamail.com/apidoc; empty means nobody is added to any list and the
     * Games settings page says so. Lists are chosen per use (config/games.php).
     *
     *   double_optin  1 = Acumbamail emails each new subscriber to confirm first
     *   per_run       most subscribers one sync adds, so a backlog drains in steps
     *   pause_ms      wait between two calls, to stay under the API's rate limit
     *   max_attempts  a subscriber the API keeps refusing is left alone after this
     */
    'acumbamail' => [
        'key' => env('ACUMBAMAIL_API_KEY'),
        'url' => rtrim((string) env('ACUMBAMAIL_API_URL', 'https://acumbamail.com/api/1'), '/'),
        'double_optin' => (int) env('ACUMBAMAIL_DOUBLE_OPTIN', 0),
        'per_run' => (int) env('ACUMBAMAIL_PER_RUN', 100),
        'pause_ms' => (int) env('ACUMBAMAIL_PAUSE_MS', 250),
        'max_attempts' => 5,
        'timeout' => 15,
    ],

];
