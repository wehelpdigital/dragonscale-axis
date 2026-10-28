<?php

namespace App\Services\Games;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * The Salpakan Tactics! game server's AXIS API (/api/axis/v1).
 *
 * Every call sends "Authorization: Bearer {SALPAKAN_SYNC_TOKEN}" and asks for
 * JSON. Lists come a page at a time: {"data": [...], "meta": {"page", "per_page",
 * "total", "last_page"}}. Anything but a usable answer is a SalpakanApiException
 * (401 = the token is wrong or missing; 422 carries the game's field errors).
 */
class SalpakanApiClient
{
    public const PREFIX = '/api/axis/v1';

    private string $baseUrl;

    private ?string $token;

    private int $timeout;

    public function __construct(?string $baseUrl = null, ?string $token = null, ?int $timeout = null)
    {
        $this->baseUrl = rtrim((string) ($baseUrl ?? config('games.salpakan.api_url')), '/');
        $this->token = $token ?? config('games.salpakan.sync_token');
        $this->timeout = $timeout ?? (int) config('games.salpakan.timeout', 20);
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && trim((string) $this->token) !== '';
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    /** Players whose updated_at >= $updatedSince (all of them when null), oldest change first. */
    public function players(?string $updatedSince, int $page, int $perPage): array
    {
        return $this->list('/players', ['updated_since' => $updatedSince, 'page' => $page, 'per_page' => $perPage]);
    }

    /** One row per player per day played, from $since (Y-m-d, inclusive). */
    public function activity(string $since, int $page, int $perPage): array
    {
        return $this->list('/activity', ['since' => $since, 'page' => $page, 'per_page' => $perPage]);
    }

    /** Orders whose updated_at >= $updatedSince (all of them when null), oldest change first. */
    public function orders(?string $updatedSince, int $page, int $perPage): array
    {
        return $this->list('/orders', ['updated_since' => $updatedSince, 'page' => $page, 'per_page' => $perPage]);
    }

    /** Accounts players deleted in the game since $since (all when null), oldest first: player_id, deleted_at. */
    public function deletions(?string $since, int $page, int $perPage): array
    {
        return $this->list('/deletions', ['since' => $since, 'page' => $page, 'per_page' => $perPage]);
    }

    /** The game's analytics codes; secrets come back only as has_* flags. */
    public function analytics(): array
    {
        return $this->dataOf($this->request('GET', '/analytics'));
    }

    /**
     * Change the game's analytics codes. Only the keys given are changed; a
     * secret is set by sending it, cleared by sending "", kept by leaving it out.
     */
    public function updateAnalytics(array $values): array
    {
        return $this->dataOf($this->request('PUT', '/analytics', [], $values));
    }

    /**
     * A web order by its checkout token: status, payable, the player, the item,
     * price_cents and price_php. A SalpakanApiException with status 404 when
     * there is none.
     */
    public function checkout(string $token): array
    {
        return $this->dataOf($this->request('GET', '/checkouts/' . rawurlencode($token)));
    }

    /**
     * The payment for a web order was checked: the game completes the order and
     * grants the item, once. ['already' => bool, 'data' => the order].
     */
    public function fulfill(string $token, string $method, ?string $reference): array
    {
        $json = $this->request('POST', '/checkouts/' . rawurlencode($token) . '/fulfill', [], ['method' => $method, 'reference' => $reference]);

        return ['already' => (bool) ($json['already'] ?? false), 'data' => $this->dataOf($json)];
    }

    /** Settings AXIS owns on the game server: php_per_usd (the peso rate of the web game's prices). */
    public function updateSettings(array $values): array
    {
        return $this->dataOf($this->request('PUT', '/settings', [], $values));
    }

    private function list(string $path, array $query): array
    {
        $json = $this->request('GET', $path, array_filter($query, fn ($v) => $v !== null && $v !== ''));

        if (! array_key_exists('data', $json) || ! is_array($json['data'])) {
            throw new SalpakanApiException('The game server answered ' . $path . ' without a "data" list.', 200);
        }
        $meta = is_array($json['meta'] ?? null) ? $json['meta'] : [];

        return [
            'data' => array_values($json['data']),
            'meta' => [
                'page' => (int) ($meta['page'] ?? $query['page'] ?? 1),
                'per_page' => (int) ($meta['per_page'] ?? $query['per_page'] ?? count($json['data'])),
                'total' => isset($meta['total']) ? (int) $meta['total'] : null,
                'last_page' => max(1, (int) ($meta['last_page'] ?? 1)),
            ],
        ];
    }

    private function dataOf(array $json): array
    {
        $data = $json['data'] ?? null;
        if (! is_array($data)) {
            throw new SalpakanApiException('The game server answered without a "data" object.', 200);
        }

        return $data;
    }

    private function request(string $method, string $path, array $query = [], ?array $body = null): array
    {
        if (! $this->isConfigured()) {
            throw new SalpakanApiException('SALPAKAN_SYNC_TOKEN is not set, so AXIS cannot talk to the game server.', 0);
        }

        $url = $this->baseUrl . self::PREFIX . $path;

        try {
            $pending = Http::withToken((string) $this->token)
                ->acceptJson()
                ->connectTimeout(10)
                ->timeout($this->timeout)
                // A dropped connection is tried once more; an answer is never repeated.
                ->retry(2, 500, fn ($e) => $e instanceof ConnectionException, false);

            $response = match ($method) {
                'PUT' => $pending->asJson()->put($url, $body ?? []),
                'POST' => $pending->asJson()->post($url, $body ?? []),
                default => $pending->get($url, $query),
            };
        } catch (ConnectionException $e) {
            throw new SalpakanApiException('Could not reach the game server at ' . $this->baseUrl . ': ' . $e->getMessage(), 0);
        }

        $status = $response->status();

        if ($status === 401) {
            throw new SalpakanApiException(
                'The game server refused the sync token (401). Check that SALPAKAN_SYNC_TOKEN in AXIS matches the token the game server expects.',
                401
            );
        }

        if ($status === 422) {
            $errors = $response->json('errors');
            throw new SalpakanApiException(
                (string) ($response->json('message') ?: 'The game server did not accept the values (422).'),
                422,
                is_array($errors) ? $errors : []
            );
        }

        if (! $response->successful()) {
            $said = trim((string) $response->json('message'));
            throw new SalpakanApiException(
                'The game server answered HTTP ' . $status . ' for ' . $path . ($said !== '' ? ': ' . mb_substr($said, 0, 200) : '.'),
                $status
            );
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new SalpakanApiException('The game server did not answer ' . $path . ' with JSON.', $status);
        }

        return $json;
    }
}
