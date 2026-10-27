<?php

namespace Tests\Feature\Games;

use App\Models\GameOrder;
use App\Models\GamePlayer;
use App\Models\GamePlayerActivity;
use App\Models\GameSetting;
use App\Models\GameSyncState;
use App\Services\Games\SalpakanSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SalpakanSyncTest extends GamesTestCase
{
    private function sync(?array $only = null): array
    {
        return app(SalpakanSyncService::class)->run($only, 60, 'console');
    }

    private function state(string $type): GameSyncState
    {
        return GameSyncState::forGame('salpakan')->where('dataType', $type)->firstOrFail();
    }

    public function test_the_first_sync_brings_over_players_activity_and_orders_page_by_page(): void
    {
        config(['games.salpakan.per_page' => 2]);
        $yesterday = now('UTC')->subDay()->toDateString();
        $today = now('UTC')->toDateString();

        foreach (range(1, 5) as $id) {
            $this->game->players[] = FakeGameServer::player($id);
        }
        $this->game->players[1]['email'] = null;
        $this->game->activity = [
            FakeGameServer::day(1, '2026-10-20'),
            FakeGameServer::day(1, $yesterday),
            FakeGameServer::day(2, $yesterday),
            FakeGameServer::day(1, $today),
            FakeGameServer::day(3, $today, ['platform' => 'windows']),
        ];
        $this->game->orders = [
            FakeGameServer::order('order-a', 1),
            FakeGameServer::order('order-b', 1, ['price_cents' => 100, 'updated_at' => '2026-10-25T03:00:00Z']),
            FakeGameServer::order('order-c', 2, ['status' => 'pending', 'completed_at' => null, 'updated_at' => '2026-10-25T04:00:00Z']),
        ];
        $this->fakeServices();

        $results = $this->sync();

        foreach (['players', 'activity', 'orders', 'ecom', 'acumbamail', 'analytics'] as $type) {
            $this->assertSame('ok', $results[$type]['status'], $type . ': ' . $results[$type]['message']);
        }

        // Players, as the game describes them, times kept in the app's timezone.
        $this->assertSame(5, GamePlayer::forGame('salpakan')->count());
        $player = GamePlayer::forGame('salpakan')->where('remotePlayerId', 1)->first();
        $this->assertSame('Player1', $player->username);
        $this->assertSame('player1@example.com', $player->email);
        $this->assertSame('PH', $player->countryCode);
        $this->assertSame('Lipa', $player->city);
        $this->assertSame('Major', $player->rankName);
        $this->assertSame(1288, $player->elo);
        $this->assertSame('2026-10-26 17:30:00', $player->getRawOriginal('lastActiveAt'));
        $this->assertNull(GamePlayer::forGame('salpakan')->where('remotePlayerId', 2)->value('email'));

        // Purchases: completed ones only, in cents.
        $this->assertSame(2, $player->purchasesCount);
        $this->assertSame(400, $player->totalSpentCents);
        $this->assertSame(0, GamePlayer::forGame('salpakan')->where('remotePlayerId', 2)->value('purchasesCount'));

        // Activity and orders, once each.
        $this->assertSame(5, GamePlayerActivity::forGame('salpakan')->count());
        $this->assertSame('windows', GamePlayerActivity::forGame('salpakan')->where('remotePlayerId', 3)->value('platform'));
        $this->assertSame(3, GameOrder::forGame('salpakan')->count());
        $this->assertSame('pending', GameOrder::forGame('salpakan')->where('remoteOrderId', 'order-c')->value('orderStatus'));

        // It asked for every page, with the token, and kept where it got to.
        $this->assertGreaterThanOrEqual(3, count($this->game->requestsTo('players')));
        $this->assertSame('2026-10-26T10:00:05Z', $this->state('players')->cursorValue);
        $this->assertSame('2026-10-25T04:00:00Z', $this->state('orders')->cursorValue);
        $this->assertSame($yesterday, $this->state('activity')->cursorValue);
        $this->assertSame('2025-01-01', $this->game->requestsTo('activity')[0]['query']['since']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'salpakan.test')
            && $request->hasHeader('Authorization', 'Bearer ' . FakeGameServer::TOKEN)
            && $request->hasHeader('Accept', 'application/json'));

        $this->assertNotNull(GameSetting::getTime('salpakan', 'lastRunAt'));
        $this->assertSame('console', GameSetting::getValue('salpakan', 'lastRunBy'));
    }

    public function test_later_syncs_ask_only_for_what_changed_and_update_rows_in_place(): void
    {
        $this->game->players = [FakeGameServer::player(1), FakeGameServer::player(2)];
        $this->game->orders = [FakeGameServer::order('order-a', 1, ['status' => 'pending', 'completed_at' => null])];
        $this->fakeServices();
        $this->sync();

        // The game changes one player and one order, and gains a player.
        $this->game->players[0] = FakeGameServer::player(1, ['rank_name' => 'Colonel', 'elo' => 1400, 'updated_at' => '2026-10-27T01:00:00Z']);
        $this->game->players[] = FakeGameServer::player(3, ['updated_at' => '2026-10-27T01:05:00Z']);
        $this->game->orders[0] = FakeGameServer::order('order-a', 1, ['updated_at' => '2026-10-27T02:00:00Z']);
        $this->game->requests = [];

        $this->sync();

        // From the cursor, less the two-minute overlap.
        $this->assertSame('2026-10-26T09:58:02Z', $this->game->requestsTo('players')[0]['query']['updated_since']);
        $this->assertSame('2026-10-25T01:59:00Z', $this->game->requestsTo('orders')[0]['query']['updated_since']);

        $this->assertSame(3, GamePlayer::forGame('salpakan')->count());
        $player = GamePlayer::forGame('salpakan')->where('remotePlayerId', 1)->first();
        $this->assertSame('Colonel', $player->rankName);
        $this->assertSame(1400, $player->elo);
        $this->assertSame(1, GameOrder::forGame('salpakan')->count());
        $this->assertSame('completed', GameOrder::forGame('salpakan')->value('orderStatus'));
        $this->assertSame(1, $player->purchasesCount);
        $this->assertSame('2026-10-27T01:05:00Z', $this->state('players')->cursorValue);

        // Nothing new: nothing changes.
        $before = DB::table('games_players')->orderBy('id')->get()->toArray();
        $this->sync();
        $this->assertEquals($before, DB::table('games_players')->orderBy('id')->get()->toArray());
    }

    public function test_activity_is_fetched_again_from_yesterday_so_todays_row_grows_instead_of_doubling(): void
    {
        $yesterday = now('UTC')->subDay()->toDateString();
        $today = now('UTC')->toDateString();
        $this->game->players = [FakeGameServer::player(1)];
        $this->game->activity = [FakeGameServer::day(1, '2026-09-01'), FakeGameServer::day(1, $today, ['sessions' => 1, 'matches_played' => 1])];
        $this->fakeServices();
        $this->sync(['players', 'activity']);

        $this->game->activity[1] = FakeGameServer::day(1, $today, ['sessions' => 4, 'matches_played' => 6, 'minutes_played' => 80]);
        $this->game->requests = [];
        $this->sync(['activity']);

        $this->assertSame($yesterday, $this->game->requestsTo('activity')[0]['query']['since']);
        $this->assertSame(2, GamePlayerActivity::forGame('salpakan')->count());
        $row = GamePlayerActivity::forGame('salpakan')->where('activityDate', $today)->first();
        $this->assertSame(4, $row->sessions);
        $this->assertSame(6, $row->matchesPlayed);
        $this->assertSame(80, $row->minutesPlayed);
    }

    public function test_a_run_cut_short_carries_on_where_it_stopped(): void
    {
        config(['games.salpakan.per_page' => 2, 'games.salpakan.max_pages' => 1]);
        foreach (range(1, 5) as $id) {
            $this->game->players[] = FakeGameServer::player($id);
        }
        $this->game->activity = [
            FakeGameServer::day(1, '2026-09-01'),
            FakeGameServer::day(2, '2026-09-01'),
            FakeGameServer::day(3, '2026-09-02'),
            FakeGameServer::day(4, '2026-09-03'),
        ];
        $this->fakeServices();

        $first = $this->sync(['players', 'activity']);
        $this->assertSame('partial', $first['players']['status']);
        $this->assertSame('partial', $first['activity']['status']);
        $this->assertSame(2, GamePlayer::forGame('salpakan')->count());
        $this->assertSame('2026-10-26T10:00:02Z', $this->state('players')->cursorValue);
        $this->assertSame(2, $this->state('activity')->resumePage);

        // Each later run picks up exactly where the last one stopped.
        $runs = 1;
        do {
            $results = $this->sync(['players', 'activity']);
            $runs++;
        } while (($results['players']['status'] !== 'ok' || $results['activity']['status'] !== 'ok') && $runs < 10);

        // Five players two to a page: four requests, each starting at the last one kept.
        $this->assertSame(4, $runs);
        $this->assertSame(5, GamePlayer::forGame('salpakan')->count());
        $this->assertSame(4, GamePlayerActivity::forGame('salpakan')->count());
        $this->assertSame('ok', $this->state('players')->lastStatus);
        $this->assertSame('ok', $this->state('activity')->lastStatus);
        $this->assertNull($this->state('activity')->resumePage);
        $this->assertSame('2026-10-26T10:00:05Z', $this->state('players')->cursorValue);
    }

    public function test_a_wrong_token_fails_every_game_server_step_without_crashing(): void
    {
        config(['games.salpakan.sync_token' => 'not-the-token']);
        $this->app->forgetInstance(\App\Services\Games\SalpakanApiClient::class);
        $this->game->players = [FakeGameServer::player(1)];
        $this->fakeServices();

        $results = $this->sync();

        $this->assertSame('failed', $results['players']['status']);
        $this->assertStringContainsString('401', $results['players']['message']);
        foreach (['activity', 'orders', 'analytics'] as $type) {
            $this->assertSame('failed', $results[$type]['status']);
            $this->assertStringStartsWith('Not tried:', $results[$type]['message']);
        }
        // One call found out; the others were not made.
        $this->assertCount(1, $this->game->requests);
        $this->assertSame('ok', $results['ecom']['status']);
        $this->assertSame(1, $this->state('players')->failCount);
        $this->assertSame(0, GamePlayer::count());

        // And the command still finishes cleanly.
        $this->artisan('games:sync', ['--seconds' => 30])->assertExitCode(0);
    }

    public function test_no_token_skips_the_game_server_quietly(): void
    {
        config(['games.salpakan.sync_token' => null]);
        $this->fakeServices();

        $results = $this->sync();

        foreach (['players', 'activity', 'orders', 'analytics'] as $type) {
            $this->assertSame('skipped', $results[$type]['status']);
            $this->assertStringContainsString('SALPAKAN_SYNC_TOKEN', $results[$type]['message']);
        }
        Http::assertNothingSent();
    }

    public function test_one_failing_step_does_not_stop_the_others(): void
    {
        $this->game->players = [FakeGameServer::player(1)];
        $this->game->orders = [FakeGameServer::order('order-a', 1)];
        $this->game->failures['orders'] = 500;
        $this->fakeServices();

        $results = $this->sync();

        $this->assertSame('ok', $results['players']['status']);
        $this->assertSame('failed', $results['orders']['status']);
        $this->assertStringContainsString('500', $results['orders']['message']);
        $this->assertSame('ok', $results['analytics']['status']);
        $this->assertNull($this->state('orders')->cursorValue);

        // When the game server is back, the orders come over.
        unset($this->game->failures['orders']);
        $this->sync(['orders']);
        $this->assertSame(1, GameOrder::forGame('salpakan')->count());
        $this->assertSame('ok', $this->state('orders')->lastStatus);
    }

    public function test_the_analytics_codes_are_read_back_without_secrets(): void
    {
        $this->game->analytics['ga4_measurement_id'] = 'G-ABC123';
        $this->game->analytics['has_meta_access_token'] = true;
        $this->game->analytics['meta_access_token'] = 'should-never-be-kept';
        $this->fakeServices();

        $this->sync(['analytics']);

        $stored = GameSetting::getJson('salpakan', 'analytics');
        $this->assertSame('G-ABC123', $stored['ga4_measurement_id']);
        $this->assertTrue($stored['has_meta_access_token']);
        $this->assertArrayNotHasKey('meta_access_token', $stored);
        $this->assertStringNotContainsString('should-never-be-kept', (string) GameSetting::getValue('salpakan', 'analytics'));
    }
}
