<?php

namespace Tests\Feature\Streamr;

use App\Services\Streamr\StreamrSignature;
use Illuminate\Support\Facades\DB;

/**
 * StreamrApiToken: 503 until the token is set, 401 for a wrong bearer, a
 * stale timestamp or a bad signature, and the module's own 120/min limit
 * (the api group's 60/min is excluded). Every call is logged, refusals too.
 */
class StreamrApiAuthTest extends StreamrTestCase
{
    public function test_without_a_token_the_api_answers_503_not_configured(): void
    {
        config(['streamr.api_token' => '']);

        $this->signedGet('/api/streamr/v1/ping')
            ->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'not_configured');
    }

    public function test_a_missing_or_wrong_bearer_is_401_unauthorized(): void
    {
        $this->getJson('/api/streamr/v1/ping')->assertStatus(401)->assertJsonPath('error.code', 'unauthorized');

        $server = $this->transformHeadersToServerVars(['Authorization' => 'Bearer not-the-secret'] + $this->signedHeaders('GET', '/api/streamr/v1/ping'));
        $this->call('GET', '/api/streamr/v1/ping', [], [], [], $server)->assertStatus(401)->assertJsonPath('error.code', 'unauthorized');
    }

    public function test_a_timestamp_301_seconds_old_is_401_stale_timestamp(): void
    {
        $this->signedGet('/api/streamr/v1/ping', [], [], time() - 301)
            ->assertStatus(401)->assertJsonPath('error.code', 'stale_timestamp');

        $this->signedGet('/api/streamr/v1/ping', [], [], time() - 290)->assertOk();

        $server = $this->transformHeadersToServerVars(['X-Streamr-Timestamp' => 'yesterday'] + $this->signedHeaders('GET', '/api/streamr/v1/ping'));
        $this->call('GET', '/api/streamr/v1/ping', [], [], [], $server)->assertStatus(401)->assertJsonPath('error.code', 'stale_timestamp');
    }

    public function test_a_tampered_body_is_401_bad_signature(): void
    {
        $snapshot = $this->snapshot();
        $signedBody = (string) json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $tampered = str_replace('"Lia"', '"Eve"', $signedBody);
        $server = $this->transformHeadersToServerVars($this->signedHeaders('PUT', '/api/streamr/v1/clients/42', $signedBody) + ['Content-Type' => 'application/json']);

        $this->call('PUT', '/api/streamr/v1/clients/42', [], [], [], $server, $tampered)
            ->assertStatus(401)->assertJsonPath('error.code', 'bad_signature');
        $this->assertSame(0, DB::table('streamr_clients')->count());

        // A tampered query string is refused the same way.
        $headers = $this->signedHeaders('GET', '/api/streamr/v1/orders', '', ['numbers' => ['STR-20261108-7KQ4']]);
        $this->call('GET', '/api/streamr/v1/orders?' . StreamrSignature::query(['numbers' => ['STR-20261108-9ZZZ']]), [], [], [], $this->transformHeadersToServerVars($headers))
            ->assertStatus(401)->assertJsonPath('error.code', 'bad_signature');
    }

    public function test_a_correctly_signed_request_passes(): void
    {
        $this->signedGet('/api/streamr/v1/ping')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.app', 'ds-axis')
            ->assertJsonPath('data.module_version', '1.0.0')
            ->assertJsonPath('data.store.name', 'Streamr')
            ->assertJsonPath('data.store.id', null);

        $this->signedJson('PUT', '/api/streamr/v1/clients/42', $this->snapshot())->assertOk()->assertJsonPath('data.stored', true);
    }

    public function test_every_call_is_logged_without_bodies_or_secrets(): void
    {
        $this->signedGet('/api/streamr/v1/ping')->assertOk();
        $this->getJson('/api/streamr/v1/ping')->assertStatus(401);

        $rows = DB::table('streamr_api_requests')->orderBy('id')->get();
        $this->assertCount(2, $rows);
        $this->assertSame(['in', 'GET', '/api/streamr/v1/ping', 200], [$rows[0]->direction, $rows[0]->method, $rows[0]->path, (int) $rows[0]->status]);
        $this->assertSame('b7d1c1a4-5a1e-4f0e-9a51-7a3f2f0b1c2d', $rows[0]->requestId);
        $this->assertSame([401, 'unauthorized'], [(int) $rows[1]->status, $rows[1]->errorCode]);
        $this->assertStringNotContainsString(self::SECRET, json_encode($rows));
    }

    public function test_the_module_limit_is_120_a_minute_not_the_api_groups_60(): void
    {
        for ($i = 1; $i <= 61; $i++) {
            $response = $this->signedGet('/api/streamr/v1/ping');
        }
        $response->assertOk();   // the 61st request in the minute

        for ($i = 62; $i <= 120; $i++) {
            $this->signedGet('/api/streamr/v1/ping')->assertOk();
        }
        // The 121st: throttled (Streamr's outbox reads the status and honours Retry-After).
        $this->signedGet('/api/streamr/v1/ping')
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_streamrs_limit_is_its_own_counter_not_shared_with_the_games_site_api(): void
    {
        // Laravel keys plain throttle:N,M limiters by domain|ip alone: without
        // the "streamr" prefix both APIs would count the same hits per address.
        $limiter = app(\Illuminate\Cache\RateLimiter::class);
        $shared = sha1('|127.0.0.1');
        for ($i = 0; $i < 150; $i++) {
            $limiter->hit($shared, 60);   // as if the games website had called 150 times this minute
        }

        $this->signedGet('/api/streamr/v1/ping')->assertOk();
        $this->assertSame(150, $limiter->attempts($shared));
        $this->assertSame(1, $limiter->attempts('streamr' . $shared));
    }

    public function test_unknown_order_numbers_never_reach_the_controller(): void
    {
        $this->signedGet('/api/streamr/v1/orders/ORD-20261108-ABCD')->assertNotFound();
    }
}
