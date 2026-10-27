<?php

namespace Tests\Feature\Games;

use App\Models\GamePlayer;
use App\Models\GameSyncState;
use App\Services\Games\SalpakanSyncService;
use Illuminate\Support\Facades\Http;

class SalpakanAcumbamailTest extends GamesTestCase
{
    private function sync(?array $only = null): array
    {
        return app(SalpakanSyncService::class)->run($only, 60, 'console');
    }

    private function player(int $id): GamePlayer
    {
        return GamePlayer::forGame('salpakan')->where('remotePlayerId', $id)->firstOrFail();
    }

    public function test_every_player_with_a_real_email_is_added_once_and_the_rest_are_skipped(): void
    {
        $this->game->players = [
            FakeGameServer::player(1, ['email' => 'kapitan@example.com']),
            FakeGameServer::player(2, ['email' => null]),
            FakeGameServer::player(3, ['email' => '76561198000000000@steam.invalid', 'sign_up_method' => 'steam']),
            FakeGameServer::player(4, ['email' => 'quiet@example.com', 'marketing_opt_out' => true]),
            FakeGameServer::player(5, ['email' => 'not-an-email']),
        ];
        $this->fakeServices();

        $results = $this->sync();
        $this->assertSame('ok', $results['acumbamail']['status']);

        $calls = $this->acumbamailCalls();
        $this->assertCount(1, $calls);
        [$function, $form] = $calls[0];
        $this->assertSame('addSubscriber', $function);
        $this->assertSame('test-acumbamail-key', $form['auth_token']);
        $this->assertSame('1571430', $form['list_id']);
        $this->assertSame('kapitan@example.com', $form['merge_fields']['email']);
        $this->assertSame('1', $form['update_subscriber']);
        $this->assertSame('1', $form['complete_json']);
        $this->assertSame('0', $form['double_optin']);
        Http::assertSent(fn ($request) => $request->url() === 'https://acumbamail.com/api/1/addSubscriber/' && $request->method() === 'POST');

        $subscribed = $this->player(1);
        $this->assertSame('subscribed', $subscribed->acumbamailStatus);
        $this->assertSame('kapitan@example.com', $subscribed->acumbamailEmail);
        $this->assertSame('555', $subscribed->acumbamailSubscriberId);
        $this->assertNotNull($subscribed->acumbamailSubscribedAt);

        $this->assertSame('skipped', $this->player(2)->acumbamailStatus);
        $this->assertSame('No email address.', $this->player(2)->acumbamailMessage);
        $this->assertSame('skipped', $this->player(3)->acumbamailStatus);
        $this->assertStringContainsString('Steam', $this->player(3)->acumbamailMessage);
        $this->assertSame('skipped', $this->player(4)->acumbamailStatus);
        $this->assertStringContainsString('Opted out', $this->player(4)->acumbamailMessage);
        $this->assertSame('skipped', $this->player(5)->acumbamailStatus);

        // Later syncs, even with the player fetched again, call nothing.
        $this->game->players[0]['updated_at'] = '2026-10-27T00:00:00Z';
        $this->game->players[0]['elo'] = 1300;
        $this->sync();
        $this->sync();
        $this->assertCount(1, $this->acumbamailCalls());
        $this->assertSame(1300, $this->player(1)->elo);
    }

    public function test_a_new_address_is_added_and_an_opt_out_takes_them_off_the_list(): void
    {
        $this->game->players = [FakeGameServer::player(1, ['email' => 'old@example.com'])];
        $this->fakeServices();
        $this->sync();

        $this->game->players[0] = FakeGameServer::player(1, ['email' => 'new@example.com', 'updated_at' => '2026-10-27T00:00:00Z']);
        $this->sync();

        $player = $this->player(1);
        $this->assertSame('subscribed', $player->acumbamailStatus);
        $this->assertSame('new@example.com', $player->acumbamailEmail);
        $this->assertSame('new@example.com', $this->acumbamailCalls()[1][1]['merge_fields']['email']);

        // They opt out in the game: off the list, and not added back.
        $this->game->players[0] = FakeGameServer::player(1, ['email' => 'new@example.com', 'marketing_opt_out' => true, 'updated_at' => '2026-10-27T01:00:00Z']);
        $this->sync();
        $this->sync();

        $calls = $this->acumbamailCalls();
        $this->assertCount(3, $calls);
        $this->assertSame('unsubscribeSubscriber', $calls[2][0]);
        $this->assertSame('new@example.com', $calls[2][1]['email']);
        $this->assertSame('1571430', $calls[2][1]['list_id']);
        $player = $this->player(1);
        $this->assertSame('unsubscribed', $player->acumbamailStatus);
        $this->assertNotNull($player->acumbamailUnsubscribedAt);
    }

    public function test_a_refused_subscriber_is_tried_again_on_later_syncs(): void
    {
        $this->game->players = [FakeGameServer::player(1, ['email' => 'flaky@example.com'])];
        $answers = [Http::response('Invalid email', 400), Http::response(['email' => 'flaky@example.com', 'id' => 777], 201)];
        $this->fakeServices(function () use (&$answers) {
            return array_shift($answers);
        });

        $this->sync();
        $player = $this->player(1);
        $this->assertSame('failed', $player->acumbamailStatus);
        $this->assertSame(1, $player->acumbamailAttempts);
        $this->assertStringContainsString('400', $player->acumbamailMessage);

        $this->sync(['acumbamail']);
        $player = $this->player(1);
        $this->assertSame('subscribed', $player->acumbamailStatus);
        $this->assertSame('777', $player->acumbamailSubscriberId);
        $this->assertNull($player->acumbamailMessage);
    }

    public function test_a_failed_subscriber_is_left_alone_after_the_most_attempts(): void
    {
        config(['services.acumbamail.max_attempts' => 2]);
        $this->game->players = [FakeGameServer::player(1, ['email' => 'never@example.com'])];
        $this->fakeServices(fn () => Http::response('Invalid email', 400));

        foreach (range(1, 4) as $run) {
            $this->sync(['players', 'acumbamail']);
        }

        $this->assertCount(2, $this->acumbamailCalls());
        $this->assertSame('failed', $this->player(1)->acumbamailStatus);
        $this->assertSame(2, $this->player(1)->acumbamailAttempts);
    }

    public function test_a_wrong_key_or_the_rate_limit_stops_the_batch_without_marking_players(): void
    {
        $this->game->players = [FakeGameServer::player(1), FakeGameServer::player(2)];
        $this->fakeServices(fn () => Http::response('Too many requests', 429));

        $results = $this->sync();
        $this->assertSame('partial', $results['acumbamail']['status']);
        $this->assertStringContainsString('429', $results['acumbamail']['message']);
        $this->assertCount(1, $this->acumbamailCalls());
        $this->assertSame('pending', $this->player(1)->acumbamailStatus);
        $this->assertSame(0, $this->player(1)->acumbamailAttempts);
    }

    public function test_a_wrong_key_is_reported(): void
    {
        $this->game->players = [FakeGameServer::player(1)];
        $this->fakeServices(fn () => Http::response('Unauthorized', 401));

        $results = $this->sync();

        $this->assertSame('failed', $results['acumbamail']['status']);
        $this->assertStringContainsString('ACUMBAMAIL_API_KEY', $results['acumbamail']['message']);
        $this->assertSame('pending', $this->player(1)->acumbamailStatus);
    }

    public function test_without_a_key_nobody_is_added_and_settings_can_say_why(): void
    {
        config(['services.acumbamail.key' => null]);
        $this->game->players = [FakeGameServer::player(1)];
        $this->fakeServices();

        $results = $this->sync();

        $this->assertSame('skipped', $results['acumbamail']['status']);
        $this->assertStringContainsString('ACUMBAMAIL_API_KEY is not set', $results['acumbamail']['message']);
        $this->assertSame([], $this->acumbamailCalls());
        $this->assertSame('pending', $this->player(1)->acumbamailStatus);
        $this->assertSame('skipped', GameSyncState::forGame('salpakan')->where('dataType', 'acumbamail')->value('lastStatus'));

        // Once the key is there, the waiting players are added.
        config(['services.acumbamail.key' => 'test-acumbamail-key']);
        $this->sync(['acumbamail']);
        $this->assertSame('subscribed', $this->player(1)->acumbamailStatus);
    }
}
