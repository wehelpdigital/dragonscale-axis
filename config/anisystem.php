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

    /*
    | Where files THIS app keeps for AniSystem are served from.
    |
    | They are stored on the public disk and normally served from this app's
    | own URL. A developer's copy shares the deployed database but not the
    | deployed disk, so every one of those paths points at a file that copy
    | has never held. Point this at the deployed app and the same database
    | shows the same pictures from anywhere. Empty = this app's own URL,
    | which is what a deployment wants.
    */
    'media_base' => rtrim((string) env('ANISYSTEM_MEDIA_BASE', ''), '/'),

    /*
    | And where files ANISYSTEM keeps are served from.
    |
    | Separate from `url` above, which is where the client app IS — the thing
    | a "preview" link should open. A developer's copy wants those links to
    | stay local while the pictures come from the deployed disk, and one
    | setting cannot be both. Empty = the same host as `url`.
    */
    'client_media_base' => rtrim((string) env('ANISYSTEM_CLIENT_MEDIA_BASE', ''), '/'),

    // AniSystem's public storage disk, where the AI avatar is written so the
    // client app can serve it from its own /storage URL.
    'storage_path' => env('ANISYSTEM_STORAGE_PATH', 'C:\\xampp\\htdocs\\anisystem\\storage\\app\\public'),

];
