<?php

/*
 * Streamr (wehelpdigital/streamr): the livestream-selling SaaS whose sales,
 * clients, payment reviews and support desk live in the Streamr module.
 *
 * Streamr calls this app at /api/streamr/v1 (App\Http\Middleware\StreamrApiToken);
 * this app calls Streamr at {app_url}/api/mother (App\Services\Streamr\StreamrCallbacks).
 * Both directions sign with the one shared secret below. Secrets live only in the
 * environment; nothing here is ever shown on a page.
 */
return [

    // The Streamr app: signed callbacks and the "Open in Streamr" links. A bare host gets https://.
    'app_url' => (static function (): string {
        $url = rtrim(trim((string) env('STREAMR_APP_URL', '')), '/');

        return $url === '' || preg_match('#^https?://#i', $url) ? $url : 'https://' . $url;
    })(),

    // The shared secret (Streamr's MOTHER_API_TOKEN). Empty: the module's API answers 503 and nothing is sent to Streamr.
    'api_token' => env('STREAMR_API_TOKEN'),

    // THE match key: ecom_order_items.productStore = ecom_product_stores.storeName. Never rename it, never use it as a label.
    'store_name' => env('STREAMR_STORE_NAME', 'Streamr'),

    // Optional: the E-commerce > Stores id whose payment settings Streamr shows (else found by store_name).
    'store_id' => env('STREAMR_STORE_ID') ? (int) env('STREAMR_STORE_ID') : null,

    // Streamr's order numbers: STR-YYYYMMDD-XXXX. The order hook checks this before it reads anything.
    'order_prefix' => 'STR-',

    'module_version' => '1.0.0',

    // Seconds a signed request stays valid either way (X-Streamr-Timestamp).
    'signature_window' => 300,

    // Receipts: at most 8 MB each, at most 3 per order.
    'proof_max_kb' => 8192,
    'max_proofs' => 3,

    // Seconds: doorbells and support pushes (after the response), and client actions (the admin waits).
    'doorbell_timeout' => 8,
    'action_timeout' => 15,

    // The categories Streamr's support form offers (GET /config).
    'support_categories' => [
        ['key' => 'general', 'label' => 'General'],
        ['key' => 'billing', 'label' => 'Billing'],
        ['key' => 'live', 'label' => 'Livestreams'],
        ['key' => 'store', 'label' => 'Store & site'],
        ['key' => 'technical', 'label' => 'Technical'],
    ],
];
