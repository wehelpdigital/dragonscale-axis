<?php

namespace Tests\Feature\Games;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * The Salpakan Tactics! game server's AXIS API (/api/axis/v1), as the tests
 * need it: players, activity and orders filtered, sorted and paged the way
 * the real one promises, the analytics codes, a wrong token answered with 401,
 * and any one path made to fail on demand. Every request it saw is kept.
 */
class FakeGameServer
{
    public const BASE = 'https://salpakan.test';

    public const TOKEN = 'test-sync-token';

    public array $players = [];

    public array $activity = [];

    public array $orders = [];

    /** Accounts deleted in the game: ['player_id' => int, 'deleted_at' => ISO time]. */
    public array $deletions = [];

    public array $analytics = [
        'enabled' => true,
        'meta_pixel_id' => '',
        'ga4_measurement_id' => '',
        'gtm_container_id' => '',
        'tiktok_pixel_id' => '',
        'google_ads_id' => '',
        'google_ads_label' => '',
        'has_ga4_api_secret' => false,
        'has_meta_access_token' => false,
        'updated_at' => '2026-10-20T00:00:00Z',
    ];

    /** Every request: ['method', 'path', 'query' => [...], 'body' => [...]]. */
    public array $requests = [];

    /** path (players, activity, orders, analytics) => HTTP status to answer instead. */
    public array $failures = [];

    /** Body sent with the last PUT /analytics. */
    public ?array $lastPut = null;

    /** Web orders by checkout token, as GET /checkouts/{token} answers them. */
    public array $checkouts = [];

    /** Bodies of every POST /checkouts/{token}/fulfill, by token. */
    public array $fulfilled = [];

    /** Body of the last PUT /settings. */
    public ?array $settingsPut = null;

    public function handle(Request $request)
    {
        $path = trim(str_replace('/api/axis/v1', '', (string) parse_url($request->url(), PHP_URL_PATH)), '/');
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
        $body = in_array($request->method(), ['PUT', 'POST'], true) ? (json_decode((string) $request->body(), true) ?: []) : [];
        $this->requests[] = ['method' => $request->method(), 'path' => $path, 'query' => $query, 'body' => $body];

        if (($request->header('Authorization')[0] ?? '') !== 'Bearer ' . self::TOKEN) {
            return Http::response(['message' => 'Unauthenticated.'], 401);
        }
        if (isset($this->failures[$path])) {
            return Http::response(['message' => 'Server error.'], $this->failures[$path]);
        }

        if (preg_match('#^checkouts/([^/]+)(/fulfill)?$#', $path, $m)) {
            return $this->checkoutPath($m[1], ! empty($m[2]), $body);
        }
        if ($path === 'settings' && $request->method() === 'PUT') {
            $this->settingsPut = $body;

            return Http::response(['data' => ['php_per_usd' => (float) ($body['php_per_usd'] ?? 0)]]);
        }

        return match ($path) {
            'players' => $this->page($this->updatedSince($this->players, $query), $query, 'id'),
            'orders' => $this->page($this->updatedSince($this->orders, $query), $query, 'order_id'),
            'activity' => $this->page($this->activitySince($query), $query, null),
            'deletions' => $this->page($this->deletedSince($query), $query, null),
            'analytics' => $request->method() === 'PUT' ? $this->putAnalytics($body) : Http::response(['data' => $this->analytics]),
            default => Http::response(['message' => 'Not found.'], 404),
        };
    }

    private function checkoutPath(string $token, bool $fulfill, array $body)
    {
        if (! isset($this->checkouts[$token])) {
            return Http::response(['message' => 'There is no such order.'], 404);
        }
        if (! $fulfill) {
            return Http::response(['data' => $this->checkouts[$token]]);
        }
        $already = $this->checkouts[$token]['status'] === 'completed';
        $this->fulfilled[$token][] = $body;
        $this->checkouts[$token]['status'] = 'completed';
        $this->checkouts[$token]['payable'] = false;
        $this->checkouts[$token]['payment_reference'] = $body['reference'] ?? null;

        return Http::response(['already' => $already, 'data' => $this->checkouts[$token]]);
    }

    /** A web order the website can take payment for. */
    public static function checkout(string $token, array $overrides = []): array
    {
        return array_merge([
            'order_id' => '7a1c2f4e-1111-4b8a-9d55-' . substr(md5($token), 0, 12),
            'status' => 'pending',
            'payable' => true,
            'player_id' => 42,
            'username' => 'Gabriela',
            'email' => 'gabriela@example.com',
            'kind' => 'item',
            'item_slug' => 'arena.lava_pit',
            'item_name' => 'Lava Pit',
            'item_category' => 'arena',
            'item_rarity' => 'legendary',
            'price_cents' => 500,
            'currency' => 'USD',
            'price_php' => 290,
            'payment_method' => null,
            'payment_reference' => null,
            'created_at' => now()->subMinutes(5)->toIso8601String(),
            'expires_at' => now()->addHours(47)->toIso8601String(),
            'completed_at' => null,
        ], $overrides);
    }

    /** Requests to one path. */
    public function requestsTo(string $path): array
    {
        return array_values(array_filter($this->requests, fn ($r) => $r['path'] === $path));
    }

    public static function player(int $id, array $overrides = []): array
    {
        return array_merge([
            'id' => $id,
            'username' => 'Player' . $id,
            'email' => 'player' . $id . '@example.com',
            'sign_up_method' => 'password',
            'created_at' => '2026-10-01T00:00:00Z',
            'updated_at' => sprintf('2026-10-26T10:00:%02dZ', $id % 60),
            'last_active_at' => '2026-10-26T09:30:00Z',
            'last_platform' => 'android',
            'country_code' => 'PH',
            'country' => 'Philippines',
            'region' => 'Calabarzon',
            'city' => 'Lipa',
            'games_played' => 40,
            'wins' => 22,
            'losses' => 16,
            'draws' => 2,
            'elo' => 1288,
            'rank_tier' => 5,
            'rank_name' => 'Major',
            'xp' => 1820,
            'credits' => 4850,
            'ad_free' => false,
            'banned' => false,
            'marketing_opt_out' => false,
        ], $overrides);
    }

    public static function order(string $orderId, int $playerId, array $overrides = []): array
    {
        return array_merge([
            'order_id' => $orderId,
            'player_id' => $playerId,
            'store' => 'google_play',
            'kind' => 'item',
            'product_id' => 'item_tier_3',
            'item_slug' => 'arena.lava_pit',
            'item_name' => 'Lava Pit',
            'item_category' => 'arena',
            'price_cents' => 300,
            'currency' => 'USD',
            'status' => 'completed',
            'test_purchase' => false,
            'store_order_id' => 'GPA.1234-5678-9012-34567',
            'created_at' => '2026-10-25T02:00:00Z',
            'completed_at' => '2026-10-25T02:01:00Z',
            'updated_at' => '2026-10-25T02:01:00Z',
        ], $overrides);
    }

    public static function day(int $playerId, string $date, array $overrides = []): array
    {
        return array_merge([
            'player_id' => $playerId,
            'date' => $date,
            'platform' => 'android',
            'sessions' => 3,
            'matches_played' => 4,
            'matches_won' => 2,
            'minutes_played' => 37,
            'first_seen_at' => $date . 'T01:00:00Z',
            'last_seen_at' => $date . 'T03:00:00Z',
        ], $overrides);
    }

    private function updatedSince(array $rows, array $query): array
    {
        if (! empty($query['updated_since'])) {
            $since = Carbon::parse($query['updated_since']);
            $rows = array_filter($rows, fn ($row) => Carbon::parse($row['updated_at'])->gte($since));
        }
        usort($rows, function ($a, $b) {
            return [Carbon::parse($a['updated_at'])->getTimestamp(), $a['id'] ?? $a['order_id']]
                <=> [Carbon::parse($b['updated_at'])->getTimestamp(), $b['id'] ?? $b['order_id']];
        });

        return array_values($rows);
    }

    private function deletedSince(array $query): array
    {
        $rows = $this->deletions;
        if (! empty($query['since'])) {
            $since = Carbon::parse($query['since']);
            $rows = array_filter($rows, fn ($row) => Carbon::parse($row['deleted_at'])->gte($since));
        }
        usort($rows, fn ($a, $b) => [Carbon::parse($a['deleted_at'])->getTimestamp(), $a['player_id']] <=> [Carbon::parse($b['deleted_at'])->getTimestamp(), $b['player_id']]);

        return array_values($rows);
    }

    private function activitySince(array $query): array
    {
        $since = (string) ($query['since'] ?? '0000-00-00');
        $rows = array_filter($this->activity, fn ($row) => $row['date'] >= $since);
        usort($rows, fn ($a, $b) => [$a['date'], $a['player_id']] <=> [$b['date'], $b['player_id']]);

        return array_values($rows);
    }

    private function page(array $rows, array $query, ?string $key)
    {
        $perPage = min(500, max(1, (int) ($query['per_page'] ?? 500)));
        $page = max(1, (int) ($query['page'] ?? 1));
        $total = count($rows);

        return Http::response([
            'data' => array_slice($rows, ($page - 1) * $perPage, $perPage),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ],
        ]);
    }

    private function putAnalytics(array $body)
    {
        $this->lastPut = $body;

        if (isset($body['ga4_measurement_id']) && $body['ga4_measurement_id'] !== '' && ! preg_match('/^G-[A-Z0-9]+$/', $body['ga4_measurement_id'])) {
            return Http::response([
                'message' => 'The ga4 measurement id field format is invalid.',
                'errors' => ['ga4_measurement_id' => ['The ga4 measurement id field format is invalid.']],
            ], 422);
        }

        foreach (['enabled', 'meta_pixel_id', 'ga4_measurement_id', 'gtm_container_id', 'tiktok_pixel_id', 'google_ads_id', 'google_ads_label'] as $field) {
            if (array_key_exists($field, $body)) {
                $this->analytics[$field] = $body[$field];
            }
        }
        if (array_key_exists('ga4_api_secret', $body)) {
            $this->analytics['has_ga4_api_secret'] = $body['ga4_api_secret'] !== '';
        }
        if (array_key_exists('meta_access_token', $body)) {
            $this->analytics['has_meta_access_token'] = $body['meta_access_token'] !== '';
        }
        $this->analytics['updated_at'] = '2026-10-27T05:00:00Z';

        return Http::response(['data' => $this->analytics]);
    }
}
