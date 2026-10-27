<?php

/*
|--------------------------------------------------------------------------
| Games
|--------------------------------------------------------------------------
|
| Every game DS AXIS keeps players, play and sales for. Each one has a key
| (salpakan) and every row in the games_* tables carries that key in its
| gameKey column, so the next game is a new entry here and its own pages,
| not new tables.
|
| Secrets come from the environment only: the sync token the game server
| checks, and the Acumbamail key (config/services.php). Neither belongs in
| code, a migration, a seeder or a test.
|
*/

return [

    'salpakan' => [

        // What the admin pages, /ecom-orders and the e-commerce store list call it.
        'name' => 'Salpakan Tactics!',

        // The game server, and the token it expects in "Authorization: Bearer".
        'api_url' => rtrim((string) env('SALPAKAN_API_URL', 'https://games-of-the-generals-web-production-6zraos.laravel.cloud'), '/'),
        'sync_token' => env('SALPAKAN_SYNC_TOKEN'),

        // Rows asked for per page (the game server allows up to 500).
        'per_page' => (int) env('SALPAKAN_SYNC_PER_PAGE', 500),

        // Seconds one request to the game server may take.
        'timeout' => 20,

        // A sync stops starting new pages after this many requests per data
        // type; the cursor is kept after every page, so the next run carries on.
        'max_pages' => 200,

        // Players and orders are fetched from (last cursor - this many seconds),
        // so a row written a moment before the cursor was taken is not missed.
        'overlap_seconds' => 120,

        // The first day of play the very first sync asks for.
        'activity_start' => env('SALPAKAN_ACTIVITY_START', '2025-01-01'),

        // The currency the game's prices are in; revenue totals and reports are
        // in it. Orders in any other currency are listed but not added up.
        'currency' => 'USD',

        // /ecom-orders is in pesos. A game order is logged there at this rate
        // unless one is set on the Settings page.
        'usd_to_php_rate' => (float) env('SALPAKAN_USD_TO_PHP_RATE', 58),

        // The Acumbamail list every player with a real email is added to.
        'acumbamail_list_id' => (int) env('ACUMBAMAIL_SALPAKAN_LIST_ID', 1571430),
        'acumbamail_list_name' => 'games_salpakan',

        // The game order number in /ecom-orders: SAL-{the game's order id}.
        'ecom_order_prefix' => 'SAL',
    ],

];
