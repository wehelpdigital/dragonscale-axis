<?php

namespace Tests\Feature\Games;

use App\Models\GamePlayer;
use App\Models\GamePlayerActivity;
use App\Models\GameSyncState;
use App\Services\Games\SalpakanSyncService;

/**
 * Accounts players delete in the game (its /deletions list): forgotten here, off the reports and
 * off the mailing list, while their orders stay as sales.
 */
class SalpakanDeletionsTest extends GamesTestCase
{
    private function sync(?array $only = null): array
    {
        return app(SalpakanSyncService::class)->run($only, 60, 'console');
    }

    private function player(int $id): GamePlayer
    {
        return GamePlayer::forGame('salpakan')->where('remotePlayerId', $id)->firstOrFail();
    }

    public function test_a_deleted_account_is_forgotten_and_taken_off_the_list(): void
    {
        $this->game->players = [
            FakeGameServer::player(1, ['email' => 'kapitan@example.com']),
            FakeGameServer::player(2, ['email' => 'stays@example.com']),
        ];
        $this->game->activity = [FakeGameServer::day(1, '2026-10-26'), FakeGameServer::day(2, '2026-10-26')];
        $this->game->orders = [FakeGameServer::order('ord-1', 1)];
        $this->fakeServices();
        $this->sync();
        $this->assertSame('subscribed', $this->player(1)->acumbamailStatus);

        // Player 1 deletes their account in the game.
        $this->game->deletions = [['player_id' => 1, 'deleted_at' => '2026-10-28T08:00:00Z']];
        $results = $this->sync();

        $this->assertSame('ok', $results['deletions']['status']);
        $this->assertSame('1 deleted account fetched, 1 forgotten here.', $results['deletions']['message']);
        $gone = $this->player(1);
        $this->assertSame('deleted', $gone->delete_status);
        $this->assertNull($gone->username);
        $this->assertNull($gone->email);
        $this->assertNull($gone->city);
        $this->assertSame('unsubscribed', $gone->acumbamailStatus);
        $this->assertNull($gone->acumbamailEmail);
        $this->assertSame('Account deleted in the game, so taken off the list.', $gone->acumbamailMessage);

        $calls = $this->acumbamailCalls();
        $this->assertSame('unsubscribeSubscriber', end($calls)[0]);
        $this->assertSame('kapitan@example.com', end($calls)[1]['email']);

        $this->assertSame(0, GamePlayer::forGame('salpakan')->active()->where('remotePlayerId', 1)->count());
        $this->assertSame('deleted', GamePlayerActivity::query()->where('remotePlayerId', 1)->value('delete_status'));
        $this->assertSame('active', GamePlayerActivity::query()->where('remotePlayerId', 2)->value('delete_status'));
        $this->assertSame('active', $this->player(2)->delete_status);

        // The next syncs see the same deletion again (the walk overlaps), and even a stray row for
        // the player, and change nothing.
        $this->game->players[0]['updated_at'] = '2026-10-28T09:00:00Z';
        $this->sync();
        $this->sync();
        $this->assertCount(count($calls), $this->acumbamailCalls());
        $this->assertSame('unsubscribed', $this->player(1)->acumbamailStatus);
        $this->assertNull($this->player(1)->username);
        $this->assertSame('2026-10-28T08:00:00Z', GameSyncState::for('salpakan', 'deletions')->cursorValue);
    }

    public function test_a_deletion_of_someone_never_synced_is_counted_and_ignored(): void
    {
        $this->game->deletions = [['player_id' => 99, 'deleted_at' => '2026-10-28T08:00:00Z']];
        $this->fakeServices();

        $results = $this->sync(['players', 'deletions']);

        $this->assertSame('1 deleted account fetched, 0 forgotten here.', $results['deletions']['message']);
        $this->assertSame(0, GamePlayer::query()->count());
    }
}
