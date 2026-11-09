<?php

namespace Tests\Feature\Streamr;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Streamr > Link status: what is set up (names only, never the secret),
 * the traffic both ways, and Test link (a signed GET /api/mother/ping).
 */
class StreamrHealthTest extends StreamrTestCase
{
    public function test_test_link_pings_streamr_signed(): void
    {
        $this->fakeStreamr(['/ping' => Http::response(['success' => true, 'data' => ['app' => 'streamr', 'version' => '1.4.0', 'time' => '2026-11-09T10:00:00+08:00']])]);

        $this->actingAs($this->admin())->postJson(route('streamr-health.ping'))
            ->assertOk()->assertJsonPath('data', ['app' => 'streamr', 'version' => '1.4.0', 'time' => '2026-11-09T10:00:00+08:00'])
            ->assertJsonPath('message', 'Streamr answered: streamr 1.4.0.');

        [$method, $path, , $request] = $this->streamrCalls()[0];
        $this->assertSame(['GET', '/api/mother/ping'], [$method, $path]);
        $this->assertSignedForStreamr($request);
        $this->assertSame('ping', DB::table('streamr_api_requests')->where('direction', 'out')->value('note'));
    }

    public function test_test_link_says_what_went_wrong(): void
    {
        $this->fakeStreamr(['/ping' => Http::response(['success' => false, 'error' => ['code' => 'bad_signature', 'message' => 'The request signature does not match.']], 401)]);
        $this->actingAs($this->admin())->postJson(route('streamr-health.ping'))->assertStatus(502)
            ->assertJsonPath('message', "Streamr refused the signature: check STREAMR_API_TOKEN and both servers' clocks. (The request signature does not match.)");

        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['streamr.test/*' => fn () => throw new ConnectionException('Could not resolve host')]);
        $this->actingAs($this->admin())->postJson(route('streamr-health.ping'))->assertStatus(502)->assertJsonPath('message', 'Streamr is not reachable — try again.');

        config(['streamr.api_token' => null]);
        $this->actingAs($this->admin())->postJson(route('streamr-health.ping'))->assertStatus(503)
            ->assertJsonPath('message', 'Streamr is not linked yet: set STREAMR_APP_URL and STREAMR_API_TOKEN.');
    }

    public function test_the_page_names_what_is_missing_and_never_shows_the_secret(): void
    {
        config(['streamr.api_token' => '', 'streamr.app_url' => '']);

        $this->actingAs($this->admin())->get(route('streamr-health.index'))->assertOk()
            ->assertSee('Streamr is not linked yet.')
            ->assertSee('STREAMR_APP_URL')
            ->assertSee('STREAMR_API_TOKEN')
            ->assertSee('MOTHER_API_TOKEN')
            ->assertSee('not set: the API answers 503 and nothing is sent to Streamr')
            ->assertSee('not readable: Streamr hides the QR method');
    }

    public function test_the_page_shows_the_traffic_and_never_the_secret(): void
    {
        $this->fakeStreamr();
        $this->postOrder()->assertOk();
        $this->getJson('/api/streamr/v1/ping')->assertStatus(401);   // a refused call

        $response = $this->actingAs($this->admin())->get(route('streamr-health.index'))->assertOk();
        $response->assertSee('set (streamr.test)')
            ->assertSee('set (never shown)')
            ->assertSee('GET /api/streamr/v1/ping')   // the last request in (refused)
            ->assertSee('1 waiting in /ecom-orders')
            ->assertSee('unauthorized')
            ->assertSee('upload_max_filesize')
            ->assertDontSee(self::SECRET);
    }
}
