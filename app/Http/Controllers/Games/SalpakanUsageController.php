<?php

namespace App\Http\Controllers\Games;

use App\Models\GamePlayer;
use App\Models\GamePlayerActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

/**
 * Games > Salpakan Tactics! > Usage history (who played on which day, from
 * games_player_activity) and Inactive players (who has not played for 7, 14,
 * 30 or 60+ days, with their emails, to write to later; CSV export).
 */
class SalpakanUsageController extends SalpakanController
{
    /** The inactivity thresholds the Inactive page offers, in days. */
    public const THRESHOLDS = [7, 14, 30, 60];

    public function index()
    {
        $today = now('UTC')->toDateString();
        $activity = GamePlayerActivity::forGame(self::GAME)->active();

        $stats = [
            'today' => (clone $activity)->where('activityDate', $today)->distinct()->count('remotePlayerId'),
            'week' => (clone $activity)->where('activityDate', '>=', now('UTC')->subDays(6)->toDateString())->distinct()->count('remotePlayerId'),
            'month' => (clone $activity)->where('activityDate', '>=', now('UTC')->subDays(29)->toDateString())->distinct()->count('remotePlayerId'),
            'matches30' => (int) (clone $activity)->where('activityDate', '>=', now('UTC')->subDays(29)->toDateString())->sum('matchesPlayed'),
        ];

        return view('games.salpakan.usage', [
            'gameName' => $this->gameName(),
            'stats' => $stats,
            'player' => request()->filled('player')
                ? GamePlayer::forGame(self::GAME)->where('remotePlayerId', (int) request('player'))->first()
                : null,
        ]);
    }

    /** DataTables rows: one per player per day played. */
    public function data(Request $request)
    {
        $query = GamePlayerActivity::query()
            ->leftJoin('games_players as p', function ($join) {
                $join->on('p.gameKey', '=', 'games_player_activity.gameKey')
                    ->on('p.remotePlayerId', '=', 'games_player_activity.remotePlayerId');
            })
            ->where('games_player_activity.gameKey', self::GAME)
            ->where('games_player_activity.delete_status', 'active')
            ->select('games_player_activity.*', 'p.id as playerRowId', 'p.username as playerUsername', 'p.email as playerEmail');

        if ($day = $this->day($request->input('dateFrom'))) {
            $query->where('games_player_activity.activityDate', '>=', $day);
        }
        if ($day = $this->day($request->input('dateTo'))) {
            $query->where('games_player_activity.activityDate', '<=', $day);
        }
        if ($request->filled('platform')) {
            $query->where('games_player_activity.platform', $request->input('platform'));
        }
        if ($request->filled('player')) {
            $query->where('games_player_activity.remotePlayerId', (int) $request->input('player'));
        }
        if ($request->filled('searchFilter')) {
            $search = trim((string) $request->input('searchFilter'));
            $query->where(function ($q) use ($search) {
                $q->where('p.username', 'like', '%' . $search . '%')->orWhere('p.email', 'like', '%' . $search . '%');
            });
        }

        return DataTables::of($query)
            ->addColumn('dateFormatted', fn ($a) => $a->activityDate ? $a->activityDate->format('D, M j, Y') : null)
            ->addColumn('platformLabel', fn ($a) => GamePlayer::PLATFORMS[$a->platform] ?? ($a->platform ? ucfirst($a->platform) : null))
            ->addColumn('firstSeenFormatted', fn ($a) => $this->formatTime($a->firstSeenAt, 'g:i A'))
            ->addColumn('lastSeenFormatted', fn ($a) => $this->formatTime($a->lastSeenAt, 'g:i A'))
            ->addColumn('showUrl', fn ($a) => $a->playerRowId ? route('games-salpakan-players.show', ['id' => $a->playerRowId]) : null)
            ->escapeColumns([])
            ->make(true);
    }

    public function inactive(Request $request)
    {
        $days = $this->threshold($request);
        $counts = [];
        foreach (self::THRESHOLDS as $threshold) {
            $counts[$threshold] = [
                'all' => GamePlayer::forGame(self::GAME)->active()->inactiveFor($threshold)->count(),
                'reachable' => GamePlayer::forGame(self::GAME)->active()->inactiveFor($threshold)->reachable()->count(),
            ];
        }

        return view('games.salpakan.inactive', [
            'gameName' => $this->gameName(),
            'days' => $days,
            'counts' => $counts,
            'thresholds' => self::THRESHOLDS,
            'countries' => $this->countries(),
        ]);
    }

    public function inactiveData(Request $request)
    {
        return DataTables::of($this->inactivePlayers($request))
            ->addColumn('lastSeenFormatted', fn (GamePlayer $p) => $this->formatTime($p->lastSeen(), 'M j, Y'))
            ->addColumn('neverPlayed', fn (GamePlayer $p) => $p->lastActiveAt === null)
            ->addColumn('daysInactive', fn (GamePlayer $p) => $p->daysInactive())
            ->addColumn('platformLabel', fn (GamePlayer $p) => $p->lastPlatform ? $p->platformLabel() : null)
            ->addColumn('location', fn (GamePlayer $p) => $p->location())
            ->addColumn('marketingLabel', fn (GamePlayer $p) => $p->acumbamailLabel())
            ->addColumn('realEmail', fn (GamePlayer $p) => $p->hasRealEmail())
            ->addColumn('showUrl', fn (GamePlayer $p) => route('games-salpakan-players.show', ['id' => $p->id]))
            ->escapeColumns([])
            ->make(true);
    }

    /** The inactive segment as CSV, ready for a mailing. */
    public function inactiveExport(Request $request)
    {
        $days = $this->threshold($request);

        return $this->csv(
            'salpakan-inactive-' . $days . 'd-' . now()->format('Ymd') . '.csv',
            ['Player ID', 'Username', 'Email', 'Last played', 'Days inactive', 'Never played', 'Last platform', 'Country', 'Region', 'City',
                'Matches played', 'Purchases', 'Opted out of marketing', 'Acumbamail'],
            $this->inactivePlayers($request),
            fn (GamePlayer $p) => [
                $p->remotePlayerId, $p->username, $p->email, $this->formatTime($p->lastSeen(), 'Y-m-d'), $p->daysInactive(),
                $p->lastActiveAt === null ? 'yes' : 'no', $p->lastPlatform, $p->country ?: $p->countryCode, $p->region, $p->city,
                $p->gamesPlayed, $p->purchasesCount, $p->marketingOptOut ? 'yes' : 'no', $p->acumbamailStatus,
            ],
            'games_players.id'
        );
    }

    /**
     * Players inactive for the chosen days. "reachable" (on unless 0) keeps
     * only real emails of players who have not opted out of marketing.
     */
    private function inactivePlayers(Request $request)
    {
        $query = GamePlayer::forGame(self::GAME)->active()->inactiveFor($this->threshold($request));

        if ($request->input('reachable', '1') !== '0') {
            $query->reachable();
        }
        if ($request->filled('country')) {
            $query->where('countryCode', strtoupper((string) $request->input('country')));
        }
        if ($request->input('hasPurchases') === 'yes') {
            $query->where('purchasesCount', '>', 0);
        } elseif ($request->input('hasPurchases') === 'no') {
            $query->where('purchasesCount', 0);
        }
        if ($request->filled('searchFilter')) {
            $search = trim((string) $request->input('searchFilter'));
            $query->where(fn ($q) => $q->where('username', 'like', '%' . $search . '%')->orWhere('email', 'like', '%' . $search . '%'));
        }

        // Longest gone first.
        return $query->orderBy(DB::raw('COALESCE(lastActiveAt, registeredAt)'));
    }

    private function threshold(Request $request): int
    {
        $days = (int) $request->input('days', 30);

        return in_array($days, self::THRESHOLDS, true) ? $days : 30;
    }
}
