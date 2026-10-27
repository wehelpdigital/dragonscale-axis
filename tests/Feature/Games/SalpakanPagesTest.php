<?php

namespace Tests\Feature\Games;

use App\Models\GamePlayer;
use App\Services\Games\SalpakanSyncService;

class SalpakanPagesTest extends GamesTestCase
{
    /** A game with a few players, days played and orders, synced in. */
    private function seedGame(): void
    {
        $today = now('UTC')->toDateString();
        $this->game->players = [
            FakeGameServer::player(12, ['username' => 'KapitanTikbalang', 'email' => 'kapitan@example.com', 'last_active_at' => now('UTC')->subHour()->toIso8601ZuluString()]),
            FakeGameServer::player(13, ['username' => 'SteamSarhento', 'email' => '765@steam.invalid', 'sign_up_method' => 'steam', 'country_code' => 'US', 'country' => 'United States', 'region' => 'California', 'city' => 'San Jose', 'last_platform' => 'windows', 'last_active_at' => now('UTC')->subDays(10)->toIso8601ZuluString()]),
            FakeGameServer::player(14, ['username' => 'TahimikNaHeneral', 'email' => 'gone@example.com', 'last_active_at' => now('UTC')->subDays(40)->toIso8601ZuluString()]),
            FakeGameServer::player(15, ['username' => 'BagongSundalo', 'email' => 'new@example.com', 'last_active_at' => null, 'created_at' => now('UTC')->subDays(20)->toIso8601ZuluString()]),
        ];
        $this->game->activity = [
            FakeGameServer::day(12, $today),
            FakeGameServer::day(12, now('UTC')->subDays(2)->toDateString(), ['matches_played' => 7]),
            FakeGameServer::day(13, $today, ['platform' => 'windows']),
        ];
        $this->game->orders = [
            FakeGameServer::order('ord-1', 12, ['created_at' => now('UTC')->subDays(3)->toIso8601ZuluString(), 'completed_at' => now('UTC')->subDays(3)->toIso8601ZuluString()]),
            FakeGameServer::order('ord-2', 13, ['store' => 'steam', 'kind' => 'remove_ads', 'item_name' => 'No Ads', 'item_category' => null, 'item_slug' => null, 'price_cents' => 100,
                'created_at' => now('UTC')->subDay()->toIso8601ZuluString(), 'completed_at' => now('UTC')->subDay()->toIso8601ZuluString()]),
            FakeGameServer::order('ord-3', 12, ['status' => 'refunded', 'created_at' => now('UTC')->subDays(2)->toIso8601ZuluString()]),
            FakeGameServer::order('ord-4', 14, ['test_purchase' => true]),
        ];
        $this->fakeServices();
        app(SalpakanSyncService::class)->run(null, 60, 'console');
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        foreach (['games-salpakan-players.index', 'games-salpakan-usage.index', 'games-salpakan-inactive.index', 'games-salpakan-sales.index',
            'games-salpakan-reports.index', 'games-salpakan-settings.index', 'games-salpakan-players.data'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
        $this->post(route('games-salpakan-settings.sync'))->assertRedirect(route('login'));
    }

    public function test_every_page_renders_for_a_signed_in_admin(): void
    {
        $this->seedGame();
        $admin = $this->admin();
        $player = GamePlayer::where('remotePlayerId', 12)->firstOrFail();

        $this->actingAs($admin)->get(route('games-salpakan-players.index'))
            ->assertOk()->assertSee('Players')->assertSee('Salpakan Tactics!')->assertSee('Games')
            ->assertSee(route('games-salpakan-settings.index'), false);           // the sidebar's Games menu
        $this->actingAs($admin)->get(route('games-salpakan-players.show', ['id' => $player->id]))
            ->assertOk()->assertSee('KapitanTikbalang')->assertSee('kapitan@example.com')->assertSee('Lava Pit')->assertSee('gsPlayerChart', false);
        $this->actingAs($admin)->get(route('games-salpakan-usage.index'))->assertOk()->assertSee('Usage history');
        $this->actingAs($admin)->get(route('games-salpakan-usage.index', ['player' => 12]))->assertOk()->assertSee('Only KapitanTikbalang');
        $this->actingAs($admin)->get(route('games-salpakan-inactive.index'))->assertOk()->assertSee('Not played for');
        $this->actingAs($admin)->get(route('games-salpakan-sales.index'))->assertOk()->assertSee('Sales &amp; orders', false);
        $this->actingAs($admin)->get(route('games-salpakan-reports.index'))->assertOk()->assertSee('Top items');
        $this->actingAs($admin)->get(route('games-salpakan-settings.index'))
            ->assertOk()->assertSee('Analytics codes')->assertSee('games_salpakan')->assertSee('1571430')
            ->assertSee(FakeGameServer::BASE)->assertDontSee(FakeGameServer::TOKEN)->assertDontSee('test-acumbamail-key');

        // An unknown player goes back to the list.
        $this->actingAs($admin)->get(route('games-salpakan-players.show', ['id' => 999]))->assertRedirect(route('games-salpakan-players.index'));
    }

    public function test_the_settings_page_says_when_the_keys_are_missing(): void
    {
        config(['services.acumbamail.key' => null, 'games.salpakan.sync_token' => null]);

        $this->actingAs($this->admin())->get(route('games-salpakan-settings.index'))
            ->assertOk()
            ->assertSee('Not set, so nobody is being added.')
            ->assertSee('SALPAKAN_SYNC_TOKEN');
    }

    public function test_the_players_table_filters(): void
    {
        $this->seedGame();
        $admin = $this->admin();
        $rows = fn (array $query) => collect($this->actingAs($admin)->getJson(route('games-salpakan-players.data', $query + ['draw' => 1, 'start' => 0, 'length' => 25]))
            ->assertOk()->json('data'))->pluck('username')->sort()->values()->all();

        $this->assertCount(4, $rows([]));
        $this->assertSame(['SteamSarhento'], $rows(['signUpMethod' => 'steam']));
        $this->assertSame(['SteamSarhento'], $rows(['country' => 'US']));
        $this->assertSame(['KapitanTikbalang'], $rows(['searchFilter' => 'kapitan']));
        $this->assertSame(['KapitanTikbalang', 'SteamSarhento'], $rows(['hasPurchases' => 'yes']));
        $this->assertSame(['BagongSundalo', 'TahimikNaHeneral'], $rows(['inactiveDays' => 14]));
        $this->assertSame(['BagongSundalo', 'KapitanTikbalang', 'TahimikNaHeneral'], $rows(['marketing' => 'reachable']));
        $this->assertSame(['KapitanTikbalang'], $rows(['activeFrom' => now()->subDays(2)->toDateString()]));

        $first = $this->actingAs($admin)->getJson(route('games-salpakan-players.data', ['searchFilter' => 'kapitan', 'draw' => 1]))->json('data.0');
        $this->assertSame(1, $first['purchasesCount']);
        $this->assertSame('$3.00', $first['totalSpentFormatted']);
        $this->assertSame('subscribed', $first['acumbamailStatus']);
    }

    public function test_the_other_tables_and_totals_answer(): void
    {
        $this->seedGame();
        $admin = $this->admin();

        $usage = $this->actingAs($admin)->getJson(route('games-salpakan-usage.data', ['draw' => 1, 'player' => 12]))->assertOk()->json('data');
        $this->assertCount(2, $usage);
        $this->assertSame('KapitanTikbalang', $usage[0]['playerUsername']);

        $inactive = $this->actingAs($admin)->getJson(route('games-salpakan-inactive.data', ['draw' => 1, 'days' => 14]))->assertOk()->json('data');
        $this->assertSame(['TahimikNaHeneral', 'BagongSundalo'], array_column($inactive, 'username'));   // longest gone first
        $this->assertTrue($inactive[1]['neverPlayed']);

        $orders = $this->actingAs($admin)->getJson(route('games-salpakan-sales.data', ['draw' => 1]))->assertOk()->json('data');
        $this->assertCount(3, $orders);                                                                     // the test purchase is left out
        $this->assertCount(4, $this->actingAs($admin)->getJson(route('games-salpakan-sales.data', ['draw' => 1, 'test' => 'include']))->json('data'));
        $this->assertSame(['ord-2'], array_column($this->actingAs($admin)->getJson(route('games-salpakan-sales.data', ['draw' => 1, 'store' => 'steam']))->json('data'), 'remoteOrderId'));

        $totals = $this->actingAs($admin)->getJson(route('games-salpakan-sales.totals'))->assertOk()->json('data');
        $this->assertSame(['$4.00'], $totals['revenue']);
        $this->assertSame(['$3.00'], $totals['refunded']);
        $this->assertSame(2, $totals['byStatus']['completed']);
    }

    public function test_the_reports_answer_with_their_series(): void
    {
        $this->seedGame();
        $admin = $this->admin();
        $period = ['dateFrom' => now()->subDays(29)->toDateString(), 'dateTo' => now()->toDateString()];

        $sales = $this->actingAs($admin)->getJson(route('games-salpakan-reports.sales', $period))->assertOk()->json('data');
        $this->assertSame('$4.00', $sales['kpis']['revenue']);
        $this->assertSame(2, $sales['kpis']['orders']);
        $this->assertCount(30, $sales['trend']['labels']);
        $this->assertEqualsCanonicalizing(['Google Play', 'Steam'], array_column($sales['byStore'], 'label'));
        $this->assertEqualsCanonicalizing(['Arena', 'Remove Ads'], array_column($sales['byCategory'], 'label'));
        $this->assertSame('Lava Pit', $sales['topItems'][0]['label']);

        $weekly = $this->actingAs($admin)->getJson(route('games-salpakan-reports.sales', $period + ['group' => 'month']))->assertOk()->json('data');
        $this->assertSame(400.0, (float) array_sum($weekly['trend']['revenue']) * 100);

        $usage = $this->actingAs($admin)->getJson(route('games-salpakan-reports.usage', $period + ['group' => 'week']))->assertOk()->json('data');
        $this->assertSame(2, $usage['kpis']['dau']);
        $this->assertSame(2, $usage['kpis']['mau']);
        $this->assertSame(15, $usage['kpis']['matches']);
        $this->assertSame('Weekly active players', $usage['activeLabel']);
        $this->assertSame(2, max($usage['trend']['active']));

        $location = $this->actingAs($admin)->getJson(route('games-salpakan-reports.location', $period + ['country' => 'PH']))->assertOk()->json('data');
        $this->assertSame('PH', $location['countries'][0]['code']);
        $this->assertSame(3, $location['countries'][0]['players']);
        $this->assertSame(3.0, (float) $location['countries'][0]['revenue']);
        $this->assertSame('Calabarzon', $location['detail']['regions'][0]['name']);
        $this->assertSame('Lipa', $location['detail']['cities'][0]['name']);
    }

    public function test_csv_exports_carry_the_filtered_rows(): void
    {
        $this->seedGame();
        $admin = $this->admin();

        $players = $this->actingAs($admin)->get(route('games-salpakan-players.export', ['country' => 'PH']));
        $players->assertOk();
        $this->assertStringContainsString('text/csv', $players->headers->get('Content-Type'));
        $csv = $players->streamedContent();
        $this->assertStringContainsString('kapitan@example.com', $csv);
        $this->assertStringNotContainsString('765@steam.invalid', $csv);

        $inactive = $this->actingAs($admin)->get(route('games-salpakan-inactive.export', ['days' => 14]))->streamedContent();
        $this->assertStringContainsString('gone@example.com', $inactive);
        $this->assertStringContainsString('new@example.com', $inactive);
        $this->assertStringNotContainsString('kapitan@example.com', $inactive);

        $orders = $this->actingAs($admin)->get(route('games-salpakan-sales.export'))->streamedContent();
        $this->assertStringContainsString('ord-1', $orders);
        $this->assertStringNotContainsString('ord-4', $orders);
    }

    public function test_sync_now_runs_the_sync_and_reports_back(): void
    {
        $this->game->players = [FakeGameServer::player(1)];
        $this->fakeServices();

        $this->actingAs($this->admin())->postJson(route('games-salpakan-settings.sync'))
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Sync finished.'])
            ->assertJsonPath('data.players.status', 'ok');

        $this->assertSame(1, GamePlayer::count());
        $this->assertSame('admin', \App\Models\GameSetting::getValue('salpakan', 'lastRunBy'));
    }

    public function test_the_rate_and_the_acumbamail_retry_can_be_set(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(route('games-salpakan-settings.rate'), ['usdToPhpRate' => '57.5'])->assertOk();
        $this->assertSame(57.5, app(SalpakanSyncService::class)->usdToPhpRate());
        $this->actingAs($admin)->postJson(route('games-salpakan-settings.rate'), ['usdToPhpRate' => 'abc'])->assertStatus(422);
        $this->actingAs($admin)->postJson(route('games-salpakan-settings.rate'), ['usdToPhpRate' => ''])->assertOk();
        $this->assertSame(58.0, app(SalpakanSyncService::class)->usdToPhpRate());

        GamePlayer::create(['gameKey' => 'salpakan', 'remotePlayerId' => 9, 'acumbamailStatus' => 'failed', 'acumbamailAttempts' => 5, 'delete_status' => 'active']);
        $this->actingAs($admin)->postJson(route('games-salpakan-settings.acumbamail-retry'))->assertOk()->assertJsonPath('success', true);
        $this->assertSame('pending', GamePlayer::sole()->acumbamailStatus);
        $this->assertSame(0, GamePlayer::sole()->acumbamailAttempts);
    }
}
