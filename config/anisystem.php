<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AniSystem integration
    |--------------------------------------------------------------------------
    |
    | btc-check and AniSystem share a database. A few values have to travel
    | between them; they live here rather than being hard-coded.
    |
    */

    /*
    | Shared secret used to encrypt the AI provider key that this app writes and
    | AniSystem reads. It must be the identical string in both apps' .env files.
    | Without it the AI settings page refuses to store a key rather than putting
    | a provider credential in the database in the clear.
    */
    'ai_key_secret' => env('ANISYSTEM_AI_KEY_SECRET'),

    'url' => env('ANISYSTEM_URL', 'http://anisystem.test'),

    // AniSystem's public storage disk, where the AI avatar is written so the
    // client app can serve it from its own /storage URL.
    'storage_path' => env('ANISYSTEM_STORAGE_PATH', 'C:\\xampp\\htdocs\\anisystem\\storage\\app\\public'),

];
