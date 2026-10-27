<?php

namespace Tests\Feature\Games;

use App\Models\GameSetting;
use Illuminate\Support\Facades\Http;

class SalpakanAnalyticsTest extends GamesTestCase
{
    private function save(array $values)
    {
        return $this->actingAs($this->admin())->postJson(route('games-salpakan-settings.analytics'), $values);
    }

    public function test_saving_sends_the_codes_to_the_game_and_records_that_it_took_them(): void
    {
        $this->fakeServices();

        $response = $this->save([
            'enabled' => 1,
            'meta_pixel_id' => '123456789012345',
            'ga4_measurement_id' => 'g-abc1234',
            'gtm_container_id' => 'GTM-ABCD12',
            'tiktok_pixel_id' => 'CABCDEFGHIJ1234567',
            'google_ads_id' => 'AW-123456789',
            'google_ads_label' => 'AbCdEf_12',
            'ga4_api_secret' => 'super-secret-value',
            'meta_access_token' => '',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        // What reached the game: every code, upper-cased IDs, a typed secret,
        // and no blank secret (which would have cleared the game's one).
        $sent = $this->game->lastPut;
        $this->assertTrue($sent['enabled']);
        $this->assertSame('G-ABC1234', $sent['ga4_measurement_id']);
        $this->assertSame('123456789012345', $sent['meta_pixel_id']);
        $this->assertSame('AW-123456789', $sent['google_ads_id']);
        $this->assertSame('super-secret-value', $sent['ga4_api_secret']);
        $this->assertArrayNotHasKey('meta_access_token', $sent);
        Http::assertSent(fn ($request) => $request->method() === 'PUT'
            && $request->url() === FakeGameServer::BASE . '/api/axis/v1/analytics'
            && $request->hasHeader('Authorization', 'Bearer ' . FakeGameServer::TOKEN));

        // What AXIS keeps: the game's answer, never the secret.
        $kept = GameSetting::getJson('salpakan', 'analytics');
        $this->assertSame('G-ABC1234', $kept['ga4_measurement_id']);
        $this->assertTrue($kept['has_ga4_api_secret']);
        $this->assertSame('accepted', GameSetting::getValue('salpakan', 'analyticsPushStatus'));
        $this->assertNotNull(GameSetting::getTime('salpakan', 'analyticsPushedAt'));
        $this->assertStringNotContainsString('super-secret-value', json_encode(\DB::table('games_settings')->get()));

        // A secret is cleared only when asked to.
        $this->save(['enabled' => 1, 'clear_ga4_api_secret' => 1])->assertOk();
        $this->assertSame('', $this->game->lastPut['ga4_api_secret']);
        $this->assertFalse(GameSetting::getJson('salpakan', 'analytics')['has_ga4_api_secret']);
    }

    public function test_codes_in_the_wrong_format_are_refused_before_anything_is_sent(): void
    {
        $this->fakeServices();

        $this->save(['enabled' => 1, 'ga4_measurement_id' => 'UA-12345-1', 'google_ads_id' => '12345'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['ga4_measurement_id', 'google_ads_id']);

        $this->assertSame([], $this->game->requests);
    }

    public function test_a_refusal_from_the_game_is_shown_and_remembered(): void
    {
        // A game stricter than AXIS: the format is right, the property is not.
        Http::fake(['salpakan.test/*' => Http::response([
            'message' => 'The ga4 measurement id is not a property we know.',
            'errors' => ['ga4_measurement_id' => ['The ga4 measurement id is not a property we know.']],
        ], 422)]);

        $this->save(['enabled' => 1, 'ga4_measurement_id' => 'G-NOTMINE1'])
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonPath('errors.ga4_measurement_id.0', 'The ga4 measurement id is not a property we know.');

        $this->assertSame('rejected', GameSetting::getValue('salpakan', 'analyticsPushStatus'));
        $this->assertStringContainsString('not a property we know', GameSetting::getValue('salpakan', 'analyticsPushMessage'));
    }

    public function test_a_wrong_token_is_reported_as_not_saved(): void
    {
        config(['games.salpakan.sync_token' => 'wrong']);
        $this->fakeServices();

        $this->save(['enabled' => 1, 'ga4_measurement_id' => 'G-ABC1234'])
            ->assertStatus(502)
            ->assertJson(['success' => false]);

        $this->assertSame('failed', GameSetting::getValue('salpakan', 'analyticsPushStatus'));
        $this->assertStringContainsString('401', GameSetting::getValue('salpakan', 'analyticsPushMessage'));
        $this->assertSame([], GameSetting::getJson('salpakan', 'analytics'));
    }

    public function test_read_from_the_game_brings_back_its_codes(): void
    {
        $this->game->analytics['gtm_container_id'] = 'GTM-READ123';
        $this->fakeServices();

        $this->actingAs($this->admin())->postJson(route('games-salpakan-settings.analytics-refresh'))
            ->assertOk()
            ->assertJsonPath('data.gtm_container_id', 'GTM-READ123');
    }
}
