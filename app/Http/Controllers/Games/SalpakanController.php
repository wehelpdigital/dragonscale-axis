<?php

namespace App\Http\Controllers\Games;

use App\Http\Controllers\Controller;
use App\Models\GameOrder;
use App\Models\GamePlayer;
use App\Services\Games\SalpakanSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * What the Games > Salpakan Tactics! pages share: the game key, the filters
 * the Players, Inactive and Sales pages apply, dates, and CSV output.
 *
 * Every page is read-only over the synced games_* tables (games:sync writes
 * them), shared by all admins like the other venture modules, behind 'auth'.
 */
abstract class SalpakanController extends Controller
{
    protected const GAME = SalpakanSyncService::GAME;

    protected function gameName(): string
    {
        return SalpakanSyncService::gameName();
    }

    /** The game's currency (revenue is added up in it). */
    protected function currency(): string
    {
        return (string) config('games.salpakan.currency', 'USD');
    }

    /**
     * Players, with the Players page's filters applied.
     *
     *   searchFilter   username, email or the game's player id
     *   signUpMethod   password | google | steam
     *   country        ISO code
     *   platform       the platform last used
     *   inactiveDays   has not played for at least this many days
     *   hasPurchases   yes | no
     *   marketing      an Acumbamail status, or "reachable" (real email, not opted out)
     *   registeredFrom/registeredTo, activeFrom/activeTo   Y-m-d
     */
    protected function filteredPlayers(Request $request)
    {
        $query = GamePlayer::forGame(self::GAME)->active();

        if ($request->filled('searchFilter')) {
            $search = trim((string) $request->input('searchFilter'));
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
                if (ctype_digit($search)) {
                    $q->orWhere('remotePlayerId', (int) $search);
                }
            });
        }
        if ($request->filled('signUpMethod')) {
            $query->where('signUpMethod', $request->input('signUpMethod'));
        }
        if ($request->filled('country')) {
            $query->where('countryCode', strtoupper((string) $request->input('country')));
        }
        if ($request->filled('platform')) {
            $query->where('lastPlatform', $request->input('platform'));
        }
        if ((int) $request->input('inactiveDays') > 0) {
            $query->inactiveFor((int) $request->input('inactiveDays'));
        }
        if ($request->input('hasPurchases') === 'yes') {
            $query->where('purchasesCount', '>', 0);
        } elseif ($request->input('hasPurchases') === 'no') {
            $query->where('purchasesCount', 0);
        }
        if ($request->input('marketing') === 'reachable') {
            $query->reachable();
        } elseif ($request->filled('marketing') && array_key_exists($request->input('marketing'), GamePlayer::ACUMBAMAIL_STATUSES)) {
            $query->where('acumbamailStatus', $request->input('marketing'));
        }
        $this->whereDay($query, 'registeredAt', $request->input('registeredFrom'), $request->input('registeredTo'));
        $this->whereDay($query, 'lastActiveAt', $request->input('activeFrom'), $request->input('activeTo'));

        return $query;
    }

    /** Orders, with the Sales page's filters applied. */
    protected function filteredOrders(Request $request)
    {
        $query = GameOrder::query()
            ->leftJoin('games_players as p', function ($join) {
                $join->on('p.gameKey', '=', 'games_orders.gameKey')
                    ->on('p.remotePlayerId', '=', 'games_orders.remotePlayerId');
            })
            ->where('games_orders.gameKey', self::GAME)
            ->where('games_orders.delete_status', 'active')
            ->select('games_orders.*', 'p.id as playerRowId', 'p.username as playerUsername', 'p.email as playerEmail', 'p.countryCode as playerCountry');

        if ($request->filled('searchFilter')) {
            $search = trim((string) $request->input('searchFilter'));
            $query->where(function ($q) use ($search) {
                $q->where('games_orders.remoteOrderId', 'like', '%' . $search . '%')
                    ->orWhere('games_orders.storeOrderId', 'like', '%' . $search . '%')
                    ->orWhere('games_orders.itemName', 'like', '%' . $search . '%')
                    ->orWhere('games_orders.productId', 'like', '%' . $search . '%')
                    ->orWhere('p.username', 'like', '%' . $search . '%')
                    ->orWhere('p.email', 'like', '%' . $search . '%');
            });
        }
        foreach (['store' => 'store', 'status' => 'orderStatus', 'kind' => 'orderKind', 'category' => 'itemCategory'] as $param => $column) {
            if ($request->filled($param)) {
                $query->where('games_orders.' . $column, $request->input($param));
            }
        }
        // Store test purchases are left out unless asked for.
        $test = $request->input('test', 'exclude');
        if ($test === 'only') {
            $query->where('games_orders.isTestPurchase', true);
        } elseif ($test !== 'include') {
            $query->where('games_orders.isTestPurchase', false);
        }
        if ($request->filled('player')) {
            $query->where('games_orders.remotePlayerId', (int) $request->input('player'));
        }
        $this->whereDay($query, 'games_orders.orderedAt', $request->input('dateFrom'), $request->input('dateTo'));

        return $query;
    }

    protected function whereDay($query, string $column, $from, $to): void
    {
        if ($day = $this->day($from)) {
            $query->where($column, '>=', $day . ' 00:00:00');
        }
        if ($day = $this->day($to)) {
            $query->where($column, '<=', $day . ' 23:59:59');
        }
    }

    /** A Y-m-d date from the request, or null. */
    protected function day($value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        try {
            return Carbon::createFromFormat('Y-m-d', $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * The report period: from/to (Y-m-d), defaulting to the last $days days,
     * never longer than two years.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function period(Request $request, int $days = 30): array
    {
        $to = $this->day($request->input('dateTo')) ? Carbon::parse($request->input('dateTo')) : now();
        $from = $this->day($request->input('dateFrom')) ? Carbon::parse($request->input('dateFrom')) : $to->copy()->subDays($days - 1);
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }
        if ($from->diffInDays($to, true) > 731) {
            $from = $to->copy()->subDays(731);
        }

        return [$from->startOfDay(), $to->endOfDay()];
    }

    protected function formatTime($value, string $format = 'M j, Y g:i A'): ?string
    {
        return $value ? Carbon::parse($value)->format($format) : null;
    }

    /** Stream rows as a CSV file Excel opens as UTF-8. */
    protected function csv(string $filename, array $headers, $query, callable $row, string $orderColumn)
    {
        return response()->streamDownload(function () use ($headers, $query, $row, $orderColumn) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);
            try {
                $query->orderBy($orderColumn)->chunk(500, function ($records) use ($handle, $row) {
                    foreach ($records as $record) {
                        fputcsv($handle, $row($record));
                    }
                });
            } catch (\Throwable $e) {
                // The headers are already sent; the file ends where it ends.
                Log::error('[Games] CSV export failed: ' . $e->getMessage());
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /** Countries players are in, for filter lists: code => name. */
    protected function countries(): array
    {
        return GamePlayer::forGame(self::GAME)->active()
            ->whereNotNull('countryCode')
            ->selectRaw('countryCode, MAX(country) as countryName, COUNT(*) as players')
            ->groupBy('countryCode')
            ->orderByDesc('players')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->countryCode => ($row->countryName ?: $row->countryCode)])
            ->all();
    }
}
