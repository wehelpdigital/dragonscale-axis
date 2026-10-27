<?php

namespace App\Http\Controllers\Games;

use App\Models\GameOrder;
use App\Models\GamePlayer;
use App\Models\GamePlayerActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Games > Salpakan Tactics! > Reports: sales (revenue by day/week/month, by
 * store, by item category, top items), usage (sign-ups, daily/weekly/monthly
 * active players, matches, platforms) and location (players and revenue by
 * country, then region and city). Charts are ApexCharts, like the E-commerce
 * Sales Report.
 *
 * Revenue is completed, non-test orders in the game's currency, dated when
 * they completed (Asia/Manila, like every AXIS date). Play is dated by the
 * game server's day (UTC). Sums are grouped by day in SQL and gathered into
 * weeks or months here, so the same queries run on MySQL and SQLite.
 */
class SalpakanReportsController extends SalpakanController
{
    public function index()
    {
        return view('games.salpakan.reports', [
            'gameName' => $this->gameName(),
            'currency' => $this->currency(),
            'countries' => $this->countries(),
        ]);
    }

    public function sales(Request $request)
    {
        try {
            [$from, $to] = $this->period($request);
            $group = $this->group($request);
            $paidAt = 'COALESCE(completedAt, orderedAt)';

            $base = GameOrder::forGame(self::GAME)->active()->counted()
                ->where('currency', $this->currency())
                ->whereRaw($paidAt . ' >= ?', [$from->format('Y-m-d H:i:s')])
                ->whereRaw($paidAt . ' <= ?', [$to->format('Y-m-d H:i:s')]);

            $daily = (clone $base)
                ->selectRaw('DATE(' . $paidAt . ') as day, COUNT(*) as orders, SUM(priceCents) as cents')
                ->groupByRaw('DATE(' . $paidAt . ')')
                ->get()
                ->keyBy(fn ($row) => substr((string) $row->day, 0, 10));

            $buckets = $this->buckets($from, $to, $group);
            $revenue = array_fill_keys(array_keys($buckets), 0);
            $orders = array_fill_keys(array_keys($buckets), 0);
            foreach ($daily as $day => $row) {
                $key = $this->bucketOf($day, $group);
                if (isset($revenue[$key])) {
                    $revenue[$key] += (int) $row->cents;
                    $orders[$key] += (int) $row->orders;
                }
            }

            $byStore = (clone $base)->selectRaw('store, COUNT(*) as orders, SUM(priceCents) as cents')
                ->groupBy('store')->orderByDesc('cents')->get()
                ->map(fn ($row) => ['label' => GameOrder::STORES[$row->store] ?? $row->store, 'orders' => (int) $row->orders, 'amount' => $row->cents / 100]);

            $byCategory = (clone $base)->selectRaw("COALESCE(itemCategory, orderKind) as category, COUNT(*) as orders, SUM(priceCents) as cents")
                ->groupByRaw('COALESCE(itemCategory, orderKind)')->orderByDesc('cents')->get()
                ->map(fn ($row) => ['label' => $this->categoryLabel($row->category), 'orders' => (int) $row->orders, 'amount' => $row->cents / 100]);

            $topItems = (clone $base)->selectRaw('itemName, productId, COUNT(*) as orders, SUM(priceCents) as cents')
                ->groupBy('itemName', 'productId')->orderByDesc('cents')->limit(10)->get()
                ->map(fn ($row) => ['label' => $row->itemName ?: ($row->productId ?: 'Unknown'), 'productId' => $row->productId, 'orders' => (int) $row->orders, 'amount' => $row->cents / 100]);

            $refunds = GameOrder::forGame(self::GAME)->active()->where('orderStatus', 'refunded')->where('isTestPurchase', false)
                ->where('currency', $this->currency())
                ->whereBetween('remoteUpdatedAt', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')]);

            $totalCents = array_sum($revenue);
            $totalOrders = array_sum($orders);

            return response()->json([
                'success' => true,
                'data' => [
                    'currency' => $this->currency(),
                    'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'group' => $group],
                    'kpis' => [
                        'revenue' => GameOrder::money($totalCents, $this->currency()),
                        'orders' => $totalOrders,
                        'average' => GameOrder::money($totalOrders ? intdiv($totalCents, $totalOrders) : 0, $this->currency()),
                        'payers' => (clone $base)->distinct()->count('remotePlayerId'),
                        'refunds' => (clone $refunds)->count(),
                        'refunded' => GameOrder::money((int) (clone $refunds)->sum('priceCents'), $this->currency()),
                    ],
                    'trend' => [
                        'labels' => array_values($buckets),
                        'revenue' => array_map(fn ($cents) => round($cents / 100, 2), array_values($revenue)),
                        'orders' => array_values($orders),
                    ],
                    'byStore' => $byStore,
                    'byCategory' => $byCategory,
                    'topItems' => $topItems,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->failed('sales', $e);
        }
    }

    public function usage(Request $request)
    {
        try {
            [$from, $to] = $this->period($request);
            $group = $this->group($request);
            $buckets = $this->buckets($from, $to, $group);
            $fromDay = $from->toDateString();
            $toDay = $to->toDateString();

            $activity = GamePlayerActivity::forGame(self::GAME)->active()->whereBetween('activityDate', [$fromDay, $toDay]);

            // Sign-ups, gathered into the buckets.
            $signups = array_fill_keys(array_keys($buckets), 0);
            GamePlayer::forGame(self::GAME)->active()
                ->whereBetween('registeredAt', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
                ->selectRaw('DATE(registeredAt) as day, COUNT(*) as players')
                ->groupByRaw('DATE(registeredAt)')
                ->get()
                ->each(function ($row) use (&$signups, $group) {
                    $key = $this->bucketOf(substr((string) $row->day, 0, 10), $group);
                    if (isset($signups[$key])) {
                        $signups[$key] += (int) $row->players;
                    }
                });

            // Matches and play per day.
            $daily = (clone $activity)
                ->selectRaw('activityDate as day, COUNT(DISTINCT remotePlayerId) as players, SUM(matchesPlayed) as matches, SUM(minutesPlayed) as minutes')
                ->groupBy('activityDate')
                ->get();
            $matches = array_fill_keys(array_keys($buckets), 0);
            $minutes = array_fill_keys(array_keys($buckets), 0);
            $active = array_fill_keys(array_keys($buckets), 0);
            foreach ($daily as $row) {
                $key = $this->bucketOf(substr((string) $row->day, 0, 10), $group);
                if (isset($matches[$key])) {
                    $matches[$key] += (int) $row->matches;
                    $minutes[$key] += (int) $row->minutes;
                    if ($group === 'day') {
                        $active[$key] = (int) $row->players;
                    }
                }
            }
            // Weekly/monthly active players: distinct players in each week or month.
            if ($group !== 'day') {
                foreach (array_keys($buckets) as $key) {
                    [$start, $end] = $this->bucketRange($key, $group, $fromDay, $toDay);
                    $active[$key] = (clone $activity)->whereBetween('activityDate', [$start, $end])->distinct()->count('remotePlayerId');
                }
            }

            $platforms = (clone $activity)
                ->selectRaw('platform, COUNT(DISTINCT remotePlayerId) as players')
                ->groupBy('platform')->orderByDesc('players')->get()
                ->map(fn ($row) => ['label' => GamePlayer::PLATFORMS[$row->platform] ?? ($row->platform ? ucfirst($row->platform) : 'Unknown'), 'players' => (int) $row->players]);

            $todayUtc = now('UTC');
            $allActivity = GamePlayerActivity::forGame(self::GAME)->active();

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => ['from' => $fromDay, 'to' => $toDay, 'group' => $group],
                    'kpis' => [
                        'dau' => (clone $allActivity)->where('activityDate', $todayUtc->toDateString())->distinct()->count('remotePlayerId'),
                        'wau' => (clone $allActivity)->where('activityDate', '>=', $todayUtc->copy()->subDays(6)->toDateString())->distinct()->count('remotePlayerId'),
                        'mau' => (clone $allActivity)->where('activityDate', '>=', $todayUtc->copy()->subDays(29)->toDateString())->distinct()->count('remotePlayerId'),
                        'signups' => array_sum($signups),
                        'matches' => array_sum($matches),
                        'hours' => round(array_sum($minutes) / 60, 1),
                    ],
                    'trend' => [
                        'labels' => array_values($buckets),
                        'signups' => array_values($signups),
                        'active' => array_values($active),
                        'matches' => array_values($matches),
                    ],
                    'activeLabel' => ['day' => 'Daily active players', 'week' => 'Weekly active players', 'month' => 'Monthly active players'][$group],
                    'platforms' => $platforms,
                    'lastPlatforms' => GamePlayer::forGame(self::GAME)->active()
                        ->selectRaw('lastPlatform, COUNT(*) as players')->groupBy('lastPlatform')->orderByDesc('players')->get()
                        ->map(fn ($row) => ['label' => GamePlayer::PLATFORMS[$row->lastPlatform] ?? ($row->lastPlatform ? ucfirst($row->lastPlatform) : 'Unknown'), 'players' => (int) $row->players]),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->failed('usage', $e);
        }
    }

    public function location(Request $request)
    {
        try {
            [$from, $to] = $this->period($request);
            $country = strtoupper(trim((string) $request->input('country')));
            $country = preg_match('/^[A-Z]{2}$/', $country) ? $country : null;

            $players = GamePlayer::forGame(self::GAME)->active();
            $revenue = GameOrder::query()
                ->join('games_players as p', function ($join) {
                    $join->on('p.gameKey', '=', 'games_orders.gameKey')->on('p.remotePlayerId', '=', 'games_orders.remotePlayerId');
                })
                ->where('games_orders.gameKey', self::GAME)
                ->where('games_orders.delete_status', 'active')
                ->where('games_orders.orderStatus', 'completed')
                ->where('games_orders.isTestPurchase', false)
                ->where('games_orders.currency', $this->currency())
                ->whereRaw('COALESCE(games_orders.completedAt, games_orders.orderedAt) >= ?', [$from->format('Y-m-d H:i:s')])
                ->whereRaw('COALESCE(games_orders.completedAt, games_orders.orderedAt) <= ?', [$to->format('Y-m-d H:i:s')]);

            $byCountry = $this->merge(
                (clone $players)->selectRaw('countryCode as code, MAX(country) as name, COUNT(*) as players, SUM(CASE WHEN purchasesCount > 0 THEN 1 ELSE 0 END) as payers')
                    ->groupBy('countryCode')->get(),
                (clone $revenue)->selectRaw('p.countryCode as code, SUM(games_orders.priceCents) as cents, COUNT(*) as orders')
                    ->groupBy('p.countryCode')->get()
            );

            $detail = null;
            if ($country) {
                $detail = [
                    'country' => $country,
                    'regions' => $this->merge(
                        (clone $players)->where('countryCode', $country)->selectRaw('region as code, region as name, COUNT(*) as players, SUM(CASE WHEN purchasesCount > 0 THEN 1 ELSE 0 END) as payers')
                            ->groupBy('region')->get(),
                        (clone $revenue)->where('p.countryCode', $country)->selectRaw('p.region as code, SUM(games_orders.priceCents) as cents, COUNT(*) as orders')
                            ->groupBy('p.region')->get()
                    ),
                    // A city is told apart by its region too (two regions can share a city name).
                    'cities' => $this->merge(
                        (clone $players)->where('countryCode', $country)->selectRaw('city as name, region, COUNT(*) as players, SUM(CASE WHEN purchasesCount > 0 THEN 1 ELSE 0 END) as payers')
                            ->groupBy('city', 'region')->get()
                            ->each(fn ($row) => $row->code = $row->name . '|' . $row->region),
                        (clone $revenue)->where('p.countryCode', $country)->selectRaw('p.city as city, p.region as region, SUM(games_orders.priceCents) as cents, COUNT(*) as orders')
                            ->groupBy('p.city', 'p.region')->get()
                            ->each(fn ($row) => $row->code = $row->city . '|' . $row->region)
                    ),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'currency' => $this->currency(),
                    'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
                    'countries' => $byCountry,
                    'detail' => $detail,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->failed('location', $e);
        }
    }

    /** Join player counts and revenue rows on their code; most players first. */
    private function merge($playerRows, $revenueRows): array
    {
        $revenue = $revenueRows->keyBy(fn ($row) => (string) $row->code);
        $rows = [];
        foreach ($playerRows as $row) {
            $money = $revenue->get((string) $row->code);
            $rows[(string) $row->code] = [
                'code' => $row->code,
                'name' => $row->name ?: ($row->code && ! str_contains((string) $row->code, '|') ? $row->code : 'Unknown'),
                'region' => $row->region ?? null,
                'players' => (int) $row->players,
                'payers' => (int) $row->payers,
                'orders' => (int) ($money->orders ?? 0),
                'revenue' => round(((int) ($money->cents ?? 0)) / 100, 2),
            ];
        }

        uasort($rows, fn ($a, $b) => [$b['players'], $b['revenue']] <=> [$a['players'], $a['revenue']]);

        return array_values($rows);
    }

    private function group(Request $request): string
    {
        return in_array($request->input('group'), ['day', 'week', 'month'], true) ? $request->input('group') : 'day';
    }

    /** Bucket key => label, every bucket in the period, in order. */
    private function buckets(Carbon $from, Carbon $to, string $group): array
    {
        $buckets = [];
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            $key = $this->bucketOf($cursor->toDateString(), $group);
            $buckets[$key] ??= match ($group) {
                'week' => 'Week of ' . Carbon::parse($key)->format('M j'),
                'month' => Carbon::parse($key . '-01')->format('M Y'),
                default => $cursor->format('M j'),
            };
            $cursor->addDay();
        }

        return $buckets;
    }

    private function bucketOf(string $day, string $group): string
    {
        return match ($group) {
            'week' => Carbon::parse($day)->startOfWeek(Carbon::MONDAY)->toDateString(),
            'month' => substr($day, 0, 7),
            default => substr($day, 0, 10),
        };
    }

    /** First and last day of a bucket, kept inside the period. */
    private function bucketRange(string $key, string $group, string $fromDay, string $toDay): array
    {
        [$start, $end] = $group === 'week'
            ? [$key, Carbon::parse($key)->addDays(6)->toDateString()]
            : [$key . '-01', Carbon::parse($key . '-01')->endOfMonth()->toDateString()];

        return [max($start, $fromDay), min($end, $toDay)];
    }

    private function categoryLabel(?string $category): string
    {
        if ($category === 'remove_ads') {
            return 'Remove Ads';
        }

        return $category ? ucwords(str_replace(['_', '.'], ' ', $category)) : 'Other';
    }

    private function failed(string $report, \Throwable $e)
    {
        Log::error('[Games] Salpakan ' . $report . ' report failed: ' . $e->getMessage());

        return response()->json(['success' => false, 'message' => 'Could not build the ' . $report . ' report.', 'error' => $e->getMessage()], 500);
    }
}
