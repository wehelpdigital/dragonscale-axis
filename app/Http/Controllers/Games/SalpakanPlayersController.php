<?php

namespace App\Http\Controllers\Games;

use App\Models\GameOrder;
use App\Models\GamePlayer;
use App\Models\GamePlayerActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

/**
 * Games > Salpakan Tactics! > Players: everyone registered in the game, with
 * filters, a CSV export, and one player's page (profile, play by day, orders).
 */
class SalpakanPlayersController extends SalpakanController
{
    public function index()
    {
        $base = GamePlayer::forGame(self::GAME)->active();
        $stats = [
            'players' => (clone $base)->count(),
            'active7' => (clone $base)->where('lastActiveAt', '>=', now()->subDays(7))->count(),
            'paying' => (clone $base)->where('purchasesCount', '>', 0)->count(),
            'subscribed' => (clone $base)->where('acumbamailStatus', 'subscribed')->count(),
        ];

        return view('games.salpakan.players', [
            'gameName' => $this->gameName(),
            'stats' => $stats,
            'countries' => $this->countries(),
        ]);
    }

    /** DataTables (server-side) rows for the Players table. */
    public function data(Request $request)
    {
        $currency = $this->currency();

        return DataTables::of($this->filteredPlayers($request))
            ->addColumn('signUpLabel', fn (GamePlayer $p) => $p->signUpLabel())
            ->addColumn('platformLabel', fn (GamePlayer $p) => $p->lastPlatform ? $p->platformLabel() : null)
            ->addColumn('registeredFormatted', fn (GamePlayer $p) => $this->formatTime($p->registeredAt, 'M j, Y'))
            ->addColumn('lastActiveFormatted', fn (GamePlayer $p) => $this->formatTime($p->lastActiveAt))
            ->addColumn('daysInactive', fn (GamePlayer $p) => $p->daysInactive())
            ->addColumn('location', fn (GamePlayer $p) => $p->location())
            ->addColumn('winRate', fn (GamePlayer $p) => $p->winRate())
            ->addColumn('totalSpentFormatted', fn (GamePlayer $p) => GameOrder::money($p->totalSpentCents, $currency))
            ->addColumn('marketingLabel', fn (GamePlayer $p) => $p->acumbamailLabel())
            ->addColumn('realEmail', fn (GamePlayer $p) => $p->hasRealEmail())
            ->addColumn('showUrl', fn (GamePlayer $p) => route('games-salpakan-players.show', ['id' => $p->id]))
            ->escapeColumns([])
            ->make(true);
    }

    /** The filtered players as CSV. */
    public function export(Request $request)
    {
        $currency = $this->currency();

        return $this->csv(
            'salpakan-players-' . now()->format('Ymd-His') . '.csv',
            ['Player ID', 'Username', 'Email', 'Sign-up method', 'Registered', 'Last active', 'Days inactive', 'Last platform',
                'Country code', 'Country', 'Region', 'City', 'Matches played', 'Wins', 'Losses', 'Draws', 'Elo', 'Rank',
                'Purchases', 'Total spent (' . $currency . ')', 'Acumbamail', 'Opted out of marketing', 'Banned'],
            $this->filteredPlayers($request),
            fn (GamePlayer $p) => [
                $p->remotePlayerId, $p->username, $p->email, $p->signUpMethod,
                $this->formatTime($p->registeredAt, 'Y-m-d H:i:s'), $this->formatTime($p->lastActiveAt, 'Y-m-d H:i:s'),
                $p->daysInactive(), $p->lastPlatform, $p->countryCode, $p->country, $p->region, $p->city,
                $p->gamesPlayed, $p->wins, $p->losses, $p->draws, $p->elo, $p->rankName,
                $p->purchasesCount, number_format($p->totalSpentCents / 100, 2, '.', ''),
                $p->acumbamailStatus, $p->marketingOptOut ? 'yes' : 'no', $p->isBanned ? 'yes' : 'no',
            ],
            'games_players.id'
        );
    }

    /** One player: /games-salpakan-players-show?id={games_players.id}. */
    public function show(Request $request)
    {
        try {
            $player = GamePlayer::forGame(self::GAME)->active()->find((int) $request->query('id'));
            if (! $player) {
                return redirect()->route('games-salpakan-players.index')->with('error', 'That player was not found.');
            }

            $activity = GamePlayerActivity::forGame(self::GAME)->active()
                ->where('remotePlayerId', $player->remotePlayerId)
                ->orderByDesc('activityDate')
                ->limit(365)
                ->get();

            $orders = GameOrder::forGame(self::GAME)->active()
                ->where('remotePlayerId', $player->remotePlayerId)
                ->orderByDesc('orderedAt')
                ->get();

            $totals = [
                'days' => $activity->count(),
                'sessions' => (int) $activity->sum('sessions'),
                'matches' => (int) $activity->sum('matchesPlayed'),
                'minutes' => (int) $activity->sum('minutesPlayed'),
            ];

            // Oldest first for the chart.
            $chart = $activity->sortBy('activityDate')->values()->map(fn (GamePlayerActivity $a) => [
                'date' => $a->activityDate->toDateString(),
                'matches' => $a->matchesPlayed,
                'won' => $a->matchesWon,
                'minutes' => $a->minutesPlayed,
                'sessions' => $a->sessions,
            ]);

            return view('games.salpakan.player', [
                'gameName' => $this->gameName(),
                'player' => $player,
                'activity' => $activity,
                'orders' => $orders,
                'totals' => $totals,
                'chart' => $chart,
                'currency' => $this->currency(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[Games] Salpakan player page failed: ' . $e->getMessage());

            return redirect()->route('games-salpakan-players.index')->with('error', 'Could not open that player.');
        }
    }
}
