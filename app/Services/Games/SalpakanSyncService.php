<?php

namespace App\Services\Games;

use App\Models\GameOrder;
use App\Models\GamePlayer;
use App\Models\GamePlayerActivity;
use App\Models\GameSetting;
use App\Models\GameSyncState;
use App\Services\Acumbamail\AcumbamailClient;
use App\Services\Acumbamail\AcumbamailException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Keeps AXIS in step with the Salpakan Tactics! game server (games:sync, every
 * 15 minutes, and "Sync now" on the Settings page).
 *
 * One run, in order:
 *   players     fetched by updated_since; each page is upserted and the cursor
 *               (newest updated_at kept) is saved before the next page, so a run
 *               that dies part way carries on from there next time
 *   activity    fetched from the day after the last finished day, always again
 *               from yesterday (UTC), because today's rows still grow
 *   orders      like players; then each player's purchase count and total
 *   ecom        orders logged into /ecom-orders, or brought up to date there
 *               (GameEcomOrderLogger)
 *   acumbamail  every player with a real email added once to the game's list;
 *               people who opt out are taken off it again
 *   analytics   the game's analytics codes read back, so Settings shows what the
 *               game is really using
 *
 * Everything is an upsert keyed by the game's own ids, so running twice, or a
 * page fetched twice, changes nothing. A data type that fails is recorded
 * (games_sync_states) and the next one still runs, except that a 401 from the
 * game server stops the other game server calls (the token is wrong for all of
 * them). Nothing here throws: the result says what happened.
 */
class SalpakanSyncService
{
    public const GAME = 'salpakan';

    /** The data types that need the game server. */
    private const REMOTE = ['players', 'activity', 'orders', 'deletions', 'analytics'];

    /** The analytics fields kept locally. Secrets never are: only whether the game holds one. */
    public const ANALYTICS_FIELDS = [
        'enabled', 'meta_pixel_id', 'ga4_measurement_id', 'gtm_container_id', 'tiktok_pixel_id',
        'google_ads_id', 'google_ads_label', 'has_ga4_api_secret', 'has_meta_access_token', 'updated_at',
    ];

    public function __construct(
        private SalpakanApiClient $api,
        private AcumbamailClient $acumbamail,
        private GameEcomOrderLogger $ecomLogger,
    ) {
    }

    public static function gameName(): string
    {
        return (string) config('games.salpakan.name', 'Salpakan Tactics!');
    }

    public function api(): SalpakanApiClient
    {
        return $this->api;
    }

    public function acumbamail(): AcumbamailClient
    {
        return $this->acumbamail;
    }

    /**
     * Run the sync.
     *
     * @param  array|null  $only     data types to run (GameSyncState::TYPES keys); null = all
     * @param  int|null    $seconds  no new page or call is started after this many seconds
     * @param  string      $by       what started it: scheduler, admin, console
     * @return array<string, array{status: string, count: int, message: string}>
     */
    public function run(?array $only = null, ?int $seconds = null, string $by = 'scheduler'): array
    {
        $types = array_keys(GameSyncState::TYPES);
        if ($only) {
            $types = array_values(array_intersect($types, $only));
        }
        $deadline = microtime(true) + max(5, $seconds ?? 600);

        $lock = $this->lock();
        if ($lock === false) {
            return ['sync' => ['status' => 'skipped', 'count' => 0, 'message' => 'Another sync of ' . self::gameName() . ' is running; this one did nothing.']];
        }
        if ($lock) {
            // A run killed by a time limit still lets go of the lock, so the
            // next scheduled run is not turned away for the lock's lifetime.
            register_shutdown_function(static function () use ($lock) {
                try {
                    $lock->release();
                } catch (Throwable) {
                }
            });
        }

        $results = [];
        $authFailure = null;

        try {
            foreach ($types as $type) {
                $state = GameSyncState::for(self::GAME, $type);

                if (in_array($type, self::REMOTE, true)) {
                    if (! $this->api->isConfigured()) {
                        $state->markSkipped('SALPAKAN_SYNC_TOKEN is not set, so nothing is fetched from the game server.');
                        $results[$type] = $this->resultOf($state);

                        continue;
                    }
                    if ($authFailure !== null) {
                        $state->markFailed('Not tried: ' . $authFailure);
                        $results[$type] = $this->resultOf($state);

                        continue;
                    }
                }

                $state->markRunning();

                try {
                    $outcome = match ($type) {
                        'players' => $this->syncPlayers($state, $deadline),
                        'activity' => $this->syncActivity($state, $deadline),
                        'orders' => $this->syncOrders($state, $deadline),
                        'deletions' => $this->syncDeletions($state, $deadline),
                        'ecom' => $this->syncEcom($deadline),
                        'acumbamail' => $this->syncAcumbamail($deadline),
                        'analytics' => $this->syncAnalytics(),
                    };

                    match ($outcome['status']) {
                        'skipped' => $state->markSkipped($outcome['message']),
                        'failed' => $state->markFailed($outcome['message'], $outcome['count']),
                        default => $state->markDone($outcome['count'], $outcome['message'], $outcome['status'] === 'partial'),
                    };
                } catch (SalpakanApiException $e) {
                    if ($e->isAuthFailure()) {
                        $authFailure = $e->getMessage();
                    }
                    $state->refresh();
                    $state->markFailed($e->getMessage());
                    Log::warning('[Games] ' . self::gameName() . ' ' . $type . ' sync failed: ' . $e->getMessage());
                } catch (Throwable $e) {
                    $state->refresh();
                    $state->markFailed('Error: ' . $e->getMessage());
                    Log::error('[Games] ' . self::gameName() . ' ' . $type . ' sync error: ' . $e->getMessage(), [
                        'exception' => get_class($e),
                        'file' => $e->getFile() . ':' . $e->getLine(),
                    ]);
                }

                $results[$type] = $this->resultOf($state->refresh());
            }

            GameSetting::touchTime(self::GAME, 'lastRunAt');
            GameSetting::setValue(self::GAME, 'lastRunBy', $by);
        } finally {
            if ($lock) {
                $lock->release();
            }
        }

        return $results;
    }

    // ------------------------------------------------------------------
    // Players
    // ------------------------------------------------------------------

    private function syncPlayers(GameSyncState $state, float $deadline): array
    {
        $since = $this->startingPoint($state);
        $fullFetch = $since === null;
        $count = 0;
        $created = 0;
        $touched = [];

        $done = $this->walkUpdated($state, $since, $deadline,
            fn (?string $since, int $page) => $this->api->players($since, $page, $this->perPage()),
            function (array $rows, array $meta) use (&$count, &$created, &$touched, $fullFetch, $state) {
                [$newest, $ids, $new] = $this->upsertPlayers($rows);
                $count += count($rows);
                $created += $new;
                array_push($touched, ...$ids);
                if ($fullFetch && $meta['page'] === 1 && $meta['total'] !== null && $state->remoteTotal === null) {
                    $state->forceFill(['remoteTotal' => $meta['total']])->save();
                }

                return $newest;
            }
        );

        $this->recomputeTotals(array_values(array_unique($touched)));

        $message = number_format($count) . ' ' . ($count === 1 ? 'player' : 'players') . ' fetched, ' . number_format($created) . ' new.';

        return $this->outcome($done, $count, $message);
    }

    /** @return array{0: ?string, 1: int[], 2: int} newest updated_at, the game ids, how many were new */
    private function upsertPlayers(array $rows): array
    {
        $ids = [];
        foreach ($rows as $row) {
            if (is_array($row) && is_numeric($row['id'] ?? null)) {
                $ids[] = (int) $row['id'];
            }
        }
        $ids = array_values(array_unique($ids));

        $existing = GamePlayer::forGame(self::GAME)->whereIn('remotePlayerId', $ids)->get()->keyBy('remotePlayerId');
        $newest = null;
        $new = 0;

        foreach ($rows as $row) {
            if (! is_array($row) || ! is_numeric($row['id'] ?? null)) {
                continue;
            }
            $id = (int) $row['id'];
            $newest = $this->newer($newest, $row['updated_at'] ?? null);
            $attributes = $this->playerAttributes($row);

            $player = $existing->get($id);
            if ($player && $player->delete_status === 'deleted') {
                continue;  // They deleted their account in the game: never brought back.
            }
            if (! $player) {
                $player = new GamePlayer([
                    'gameKey' => self::GAME,
                    'remotePlayerId' => $id,
                    'acumbamailStatus' => 'pending',
                    'acumbamailAttempts' => 0,
                    'delete_status' => 'active',
                ]);
                $new++;
            }

            // A new address, or a change of mind about marketing, is looked at
            // again by the Acumbamail step.
            $emailChanged = $player->exists && strcasecmp(trim((string) $player->email), trim((string) $attributes['email'])) !== 0;
            $optOutChanged = $player->exists && (bool) $player->marketingOptOut !== $attributes['marketingOptOut'];

            $player->fill($attributes);
            if ($emailChanged || $optOutChanged) {
                $player->forceFill(['acumbamailStatus' => 'pending', 'acumbamailAttempts' => 0, 'acumbamailMessage' => null]);
            }
            if (! $player->exists || $player->isDirty()) {
                $player->save();
            }
            $existing->put($id, $player);
        }

        return [$newest, $ids, $new];
    }

    private function playerAttributes(array $row): array
    {
        $code = strtoupper(trim((string) ($row['country_code'] ?? '')));

        return [
            'username' => $this->text($row['username'] ?? null, 100),
            'email' => $this->text($row['email'] ?? null, 191),
            'signUpMethod' => $this->text($row['sign_up_method'] ?? null, 20),
            'registeredAt' => $this->time($row['created_at'] ?? null),
            'remoteUpdatedAt' => $this->time($row['updated_at'] ?? null),
            'lastActiveAt' => $this->time($row['last_active_at'] ?? null),
            'lastPlatform' => $this->text($row['last_platform'] ?? null, 20),
            'countryCode' => preg_match('/^[A-Z]{2}$/', $code) ? $code : null,
            'country' => $this->text($row['country'] ?? null, 100),
            'region' => $this->text($row['region'] ?? null, 100),
            'city' => $this->text($row['city'] ?? null, 100),
            'gamesPlayed' => $this->count($row['games_played'] ?? 0),
            'wins' => $this->count($row['wins'] ?? 0),
            'losses' => $this->count($row['losses'] ?? 0),
            'draws' => $this->count($row['draws'] ?? 0),
            'elo' => is_numeric($row['elo'] ?? null) ? (int) $row['elo'] : null,
            'rankTier' => is_numeric($row['rank_tier'] ?? null) ? $this->count($row['rank_tier']) : null,
            'rankName' => $this->text($row['rank_name'] ?? null, 50),
            'xp' => $this->count($row['xp'] ?? 0),
            'credits' => is_numeric($row['credits'] ?? null) ? (int) $row['credits'] : 0,
            'adFree' => (bool) ($row['ad_free'] ?? false),
            'isBanned' => (bool) ($row['banned'] ?? false),
            'marketingOptOut' => (bool) ($row['marketing_opt_out'] ?? false),
        ];
    }

    // ------------------------------------------------------------------
    // Daily activity
    // ------------------------------------------------------------------

    private function syncActivity(GameSyncState $state, float $deadline): array
    {
        $yesterday = now('UTC')->subDay()->toDateString();
        $start = $this->date($state->cursorValue)
            ?? $this->date((string) config('games.salpakan.activity_start'))
            ?? '2025-01-01';
        $since = min($start, $yesterday);

        // Days before yesterday never change, so a run cut short resumes at the
        // page it stopped on rather than starting the backfill over.
        $page = ($state->resumeCursor === $since && $state->resumePage > 1) ? $state->resumePage : 1;
        $count = 0;
        $requests = 0;
        $complete = false;

        while (true) {
            if (microtime(true) > $deadline || $requests >= $this->maxPages()) {
                break;
            }
            $requests++;

            $result = $this->api->activity($since, $page, $this->perPage());
            $rows = $result['data'];
            $count += $this->upsertActivity($rows);

            if (! $rows || $page >= $result['meta']['last_page']) {
                $complete = true;
                break;
            }
            $page++;
            $state->forceFill(['resumeCursor' => $since, 'resumePage' => $page])->save();
        }

        if ($complete) {
            $state->forceFill(['cursorValue' => $yesterday, 'resumeCursor' => null, 'resumePage' => null])->save();
        }

        $message = number_format($count) . ' player-days fetched from ' . $since . ' (UTC).';

        return $this->outcome($complete, $count, $message);
    }

    private function upsertActivity(array $rows): int
    {
        $values = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ! is_numeric($row['player_id'] ?? null) || ! $this->date($row['date'] ?? null)) {
                continue;
            }
            $key = (int) $row['player_id'] . '|' . $row['date'];
            $values[$key] = [
                'gameKey' => self::GAME,
                'remotePlayerId' => (int) $row['player_id'],
                'activityDate' => $row['date'],
                'platform' => $this->text($row['platform'] ?? null, 20),
                'sessions' => $this->count($row['sessions'] ?? 0),
                'matchesPlayed' => $this->count($row['matches_played'] ?? 0),
                'matchesWon' => $this->count($row['matches_won'] ?? 0),
                'minutesPlayed' => $this->count($row['minutes_played'] ?? 0),
                'firstSeenAt' => $this->dbTime($row['first_seen_at'] ?? null),
                'lastSeenAt' => $this->dbTime($row['last_seen_at'] ?? null),
                'delete_status' => 'active',
            ];
        }
        if (! $values) {
            return 0;
        }

        foreach (array_chunk(array_values($values), 250) as $chunk) {
            GamePlayerActivity::upsert(
                $chunk,
                ['gameKey', 'remotePlayerId', 'activityDate'],
                ['platform', 'sessions', 'matchesPlayed', 'matchesWon', 'minutesPlayed', 'firstSeenAt', 'lastSeenAt', 'delete_status']
            );
        }

        return count($values);
    }

    // ------------------------------------------------------------------
    // Orders
    // ------------------------------------------------------------------

    private function syncOrders(GameSyncState $state, float $deadline): array
    {
        $since = $this->startingPoint($state);
        $count = 0;
        $touched = [];

        $done = $this->walkUpdated($state, $since, $deadline,
            fn (?string $since, int $page) => $this->api->orders($since, $page, $this->perPage()),
            function (array $rows) use (&$count, &$touched) {
                [$newest, $players, $kept] = $this->upsertOrders($rows);
                $count += $kept;
                array_push($touched, ...$players);

                return $newest;
            }
        );

        $this->recomputeTotals(array_values(array_unique($touched)));

        $message = number_format($count) . ' ' . ($count === 1 ? 'order' : 'orders') . ' fetched.';

        return $this->outcome($done, $count, $message);
    }

    /** @return array{0: ?string, 1: int[], 2: int} newest updated_at, players touched, rows kept */
    private function upsertOrders(array $rows): array
    {
        $values = [];
        $players = [];
        $newest = null;

        foreach ($rows as $row) {
            $orderId = is_array($row) ? trim((string) ($row['order_id'] ?? '')) : '';
            if ($orderId === '' || mb_strlen($orderId) > 64) {
                continue;
            }
            $status = (string) ($row['status'] ?? 'pending');
            $playerId = is_numeric($row['player_id'] ?? null) ? (int) $row['player_id'] : null;
            $currency = strtoupper(trim((string) ($row['currency'] ?? '')));

            $values[$orderId] = [
                'gameKey' => self::GAME,
                'remoteOrderId' => $orderId,
                'remotePlayerId' => $playerId,
                'store' => $this->text($row['store'] ?? null, 20) ?? 'unknown',
                'orderKind' => $this->text($row['kind'] ?? null, 20) ?? 'item',
                'productId' => $this->text($row['product_id'] ?? null, 100),
                'itemSlug' => $this->text($row['item_slug'] ?? null, 100),
                'itemName' => $this->text($row['item_name'] ?? null, 191),
                'itemCategory' => $this->text($row['item_category'] ?? null, 50),
                'priceCents' => $this->count($row['price_cents'] ?? 0),
                'currency' => preg_match('/^[A-Z]{3}$/', $currency) ? $currency : 'USD',
                'orderStatus' => array_key_exists($status, GameOrder::STATUSES) ? $status : 'pending',
                'isTestPurchase' => ! empty($row['test_purchase']) ? 1 : 0,
                'storeOrderId' => $this->text($row['store_order_id'] ?? null, 191),
                'orderedAt' => $this->dbTime($row['created_at'] ?? null),
                'completedAt' => $this->dbTime($row['completed_at'] ?? null),
                'remoteUpdatedAt' => $this->dbTime($row['updated_at'] ?? null),
                'delete_status' => 'active',
            ];
            if ($playerId !== null) {
                $players[] = $playerId;
            }
            $newest = $this->newer($newest, $row['updated_at'] ?? null);
        }

        foreach (array_chunk(array_values($values), 250) as $chunk) {
            // The ecom* columns are this app's own and are never overwritten here.
            GameOrder::upsert($chunk, ['gameKey', 'remoteOrderId'], [
                'remotePlayerId', 'store', 'orderKind', 'productId', 'itemSlug', 'itemName', 'itemCategory',
                'priceCents', 'currency', 'orderStatus', 'isTestPurchase', 'storeOrderId', 'orderedAt',
                'completedAt', 'remoteUpdatedAt', 'delete_status',
            ]);
        }

        return [$newest, array_values(array_unique($players)), count($values)];
    }

    /** Each player's completed, non-test purchases: how many, and the total in the game's currency. */
    public function recomputeTotals(array $remotePlayerIds): void
    {
        $currency = (string) config('games.salpakan.currency', 'USD');

        foreach (array_chunk($remotePlayerIds, 500) as $chunk) {
            $sums = GameOrder::forGame(self::GAME)->active()->counted()
                ->whereIn('remotePlayerId', $chunk)
                ->selectRaw('remotePlayerId, COUNT(*) as purchases, SUM(CASE WHEN currency = ? THEN priceCents ELSE 0 END) as cents', [$currency])
                ->groupBy('remotePlayerId')
                ->get()
                ->keyBy('remotePlayerId');

            foreach (GamePlayer::forGame(self::GAME)->whereIn('remotePlayerId', $chunk)->get() as $player) {
                $sum = $sums->get($player->remotePlayerId);
                $player->purchasesCount = (int) ($sum->purchases ?? 0);
                $player->totalSpentCents = (int) ($sum->cents ?? 0);
                if ($player->isDirty()) {
                    $player->save();
                }
            }
        }
    }

    // ------------------------------------------------------------------
    // /ecom-orders
    // ------------------------------------------------------------------

    // ------------------------------------------------------------------
    // Deleted accounts
    // ------------------------------------------------------------------

    /**
     * Accounts players deleted in the game (its /deletions list): each one is forgotten here too
     * (forgetPlayer), and the next Acumbamail step takes them off the mailing list.
     */
    private function syncDeletions(GameSyncState $state, float $deadline): array
    {
        $since = $this->startingPoint($state);
        $count = 0;
        $forgotten = 0;

        $done = $this->walkUpdated($state, $since, $deadline,
            fn (?string $since, int $page) => $this->api->deletions($since, $page, $this->perPage()),
            function (array $rows) use (&$count, &$forgotten) {
                $newest = null;
                foreach ($rows as $row) {
                    if (! is_array($row) || ! is_numeric($row['player_id'] ?? null)) {
                        continue;
                    }
                    $count++;
                    $newest = $this->newer($newest, $row['deleted_at'] ?? null);
                    $forgotten += $this->forgetPlayer((int) $row['player_id']);
                }

                return $newest;
            }
        );

        $message = number_format($count) . ' deleted ' . ($count === 1 ? 'account' : 'accounts') . ' fetched, ' . number_format($forgotten) . ' forgotten here.';

        return $this->outcome($done, $count, $message);
    }

    /**
     * A player who deleted their account in the game: their name, email and town are wiped and
     * they leave the players list and the usage reports. Their orders stay as sales, under no one's
     * name. On the mailing list they wait for the Acumbamail step to take them off. 1 when the player
     * was here.
     */
    private function forgetPlayer(int $remoteId): int
    {
        $player = GamePlayer::forGame(self::GAME)->where('remotePlayerId', $remoteId)->first();
        if (! $player || $player->delete_status === 'deleted') {
            return 0;  // Never here, or already forgotten (the walk overlaps the last run).
        }
        $onList = $player->acumbamailSubscribedAt && ! $player->acumbamailUnsubscribedAt && $player->acumbamailEmail;
        $player->forceFill([
            'username' => null,
            'email' => null,
            'region' => null,
            'city' => null,
            'delete_status' => 'deleted',
            'acumbamailStatus' => $onList ? 'pending' : 'skipped',
            'acumbamailMessage' => $onList ? 'Account deleted in the game: to be taken off the list.' : 'Account deleted in the game.',
        ])->save();
        GamePlayerActivity::query()->where('gameKey', self::GAME)->where('remotePlayerId', $remoteId)->update(['delete_status' => 'deleted']);

        return 1;
    }

    private function syncEcom(float $deadline): array
    {
        $rate = $this->usdToPhpRate();
        $storeName = self::gameName();
        $prefix = (string) config('games.salpakan.ecom_order_prefix', 'SAL');

        $orders = GameOrder::forGame(self::GAME)->active()
            ->where(function ($q) {
                $q->where(function ($w) {
                    $w->whereNull('ecomOrderId')
                        ->where('isTestPurchase', false)
                        ->whereIn('orderStatus', ['completed', 'refunded']);
                })->orWhere(function ($w) {
                    $w->whereNotNull('ecomOrderId')
                        ->where(function ($x) {
                            $x->whereNull('ecomStatus')->orWhereColumn('ecomStatus', '!=', 'orderStatus');
                        });
                });
            })
            // Ones that failed before go last, so they cannot hold up new sales.
            ->orderByRaw('CASE WHEN ecomError IS NULL THEN 0 ELSE 1 END')
            ->orderBy('id')
            ->limit(500)
            ->get();

        $counts = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0, 'failed' => 0];
        $firstError = null;
        $stoppedEarly = false;

        foreach ($orders as $order) {
            if (microtime(true) > $deadline) {
                $stoppedEarly = true;
                break;
            }
            try {
                $player = $order->remotePlayerId
                    ? GamePlayer::forGame(self::GAME)->where('remotePlayerId', $order->remotePlayerId)->first()
                    : null;
                $counts[$this->ecomLogger->log($order, $player, $storeName, $prefix, $rate)]++;
            } catch (Throwable $e) {
                $counts['failed']++;
                $firstError ??= $e->getMessage();
                $order->forceFill(['ecomError' => mb_substr($e->getMessage(), 0, 500)])->save();
                Log::warning('[Games] Could not log ' . self::gameName() . ' order ' . $order->remoteOrderId . ' to /ecom-orders: ' . $e->getMessage());
            }
        }

        $message = $counts['created'] . ' logged to /ecom-orders, ' . $counts['updated'] . ' updated there.';
        if ($counts['failed']) {
            return ['status' => 'failed', 'count' => $counts['created'] + $counts['updated'], 'message' => $message . ' ' . $counts['failed'] . ' could not be: ' . $firstError];
        }

        return $this->outcome(! $stoppedEarly, $counts['created'] + $counts['updated'], $message);
    }

    /** The peso rate game orders are logged at: Settings, else SALPAKAN_USD_TO_PHP_RATE. */
    public function usdToPhpRate(): float
    {
        $set = GameSetting::getValue(self::GAME, 'usdToPhpRate');

        return is_numeric($set) && (float) $set > 0 ? (float) $set : (float) config('games.salpakan.usd_to_php_rate', 58);
    }

    // ------------------------------------------------------------------
    // Acumbamail
    // ------------------------------------------------------------------

    private function syncAcumbamail(float $deadline): array
    {
        if (! $this->acumbamail->isConfigured()) {
            return ['status' => 'skipped', 'count' => 0, 'message' => 'ACUMBAMAIL_API_KEY is not set, so nobody is being added to Acumbamail. Players waiting will be added once it is.'];
        }

        $listId = (int) config('games.salpakan.acumbamail_list_id', 1571430);
        $perRun = max(1, (int) config('services.acumbamail.per_run', 100));
        $pauseMs = max(0, (int) config('services.acumbamail.pause_ms', 250));
        $maxAttempts = max(1, (int) config('services.acumbamail.max_attempts', 5));

        $players = GamePlayer::forGame(self::GAME)
            ->where(function ($q) {
                // Deleted accounts still on the list wait here to be taken off it.
                $q->where('delete_status', 'active')
                    ->orWhere(fn ($d) => $d->where('delete_status', 'deleted')->whereNotNull('acumbamailEmail'));
            })
            ->where(function ($q) use ($maxAttempts) {
                $q->where('acumbamailStatus', 'pending')
                    ->orWhere(function ($w) use ($maxAttempts) {
                        $w->where('acumbamailStatus', 'failed')->where('acumbamailAttempts', '<', $maxAttempts);
                    });
            })
            ->orderByRaw("CASE WHEN acumbamailStatus = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->limit($perRun)
            ->get();

        $added = 0;
        $removed = 0;
        $skipped = 0;
        $failed = 0;
        $calls = 0;
        $stop = null;

        foreach ($players as $player) {
            if (microtime(true) > $deadline) {
                $stop = 'Stopped for time; the next sync carries on.';
                break;
            }

            [$action, $detail] = $this->acumbamailDecision($player);

            if ($action === 'skip' || $action === 'done') {
                $player->forceFill([
                    'acumbamailStatus' => $action === 'done' ? 'subscribed' : 'skipped',
                    'acumbamailMessage' => $detail,
                ])->save();
                $action === 'skip' ? $skipped++ : null;

                continue;
            }

            if ($calls > 0 && $pauseMs > 0) {
                usleep($pauseMs * 1000);
            }
            $calls++;

            try {
                if ($action === 'subscribe') {
                    $subscriberId = $this->acumbamail->addSubscriber($listId, $detail);
                    $player->forceFill([
                        'acumbamailStatus' => 'subscribed',
                        'acumbamailEmail' => $detail,
                        'acumbamailSubscriberId' => $subscriberId,
                        'acumbamailSubscribedAt' => now(),
                        'acumbamailUnsubscribedAt' => null,
                        'acumbamailTriedAt' => now(),
                        'acumbamailAttempts' => $player->acumbamailAttempts + 1,
                        'acumbamailMessage' => null,
                    ])->save();
                    $added++;
                } else {
                    $this->acumbamail->unsubscribeSubscriber($listId, (string) $player->acumbamailEmail);
                    $deleted = $player->delete_status === 'deleted';
                    $player->forceFill([
                        'acumbamailStatus' => 'unsubscribed',
                        'acumbamailUnsubscribedAt' => now(),
                        'acumbamailTriedAt' => now(),
                        'acumbamailMessage' => $deleted ? 'Account deleted in the game, so taken off the list.' : 'Opted out of marketing in the game, so taken off the list.',
                    ] + ($deleted ? ['acumbamailEmail' => null, 'acumbamailSubscriberId' => null] : []))->save();
                    $removed++;
                }
            } catch (AcumbamailException $e) {
                // The key, the rate limit or the connection: about every call, not
                // this player, so stop here and leave the player as they were.
                if ($e->isAuthFailure()) {
                    return ['status' => 'failed', 'count' => $added + $removed, 'message' => $e->getMessage()];
                }
                if ($e->isRateLimited() || $e->status === 0) {
                    $stop = $e->getMessage();
                    break;
                }
                $player->forceFill([
                    'acumbamailStatus' => 'failed',
                    'acumbamailTriedAt' => now(),
                    'acumbamailAttempts' => $player->acumbamailAttempts + 1,
                    'acumbamailMessage' => mb_substr($e->getMessage(), 0, 500),
                ])->save();
                $failed++;
            }
        }

        $message = $added . ' added to the list, ' . $removed . ' taken off, ' . $skipped . ' not added (no real email or opted out)'
            . ($failed ? ', ' . $failed . ' refused by Acumbamail (tried again next sync)' : '') . '.';
        if ($stop) {
            return ['status' => 'partial', 'count' => $added + $removed, 'message' => $message . ' ' . $stop];
        }

        return ['status' => 'ok', 'count' => $added + $removed, 'message' => $message];
    }

    /**
     * What to do with one player: subscribe (their address), unsubscribe,
     * done (already on the list with this address) or skip (and why).
     *
     * @return array{0: string, 1: ?string}
     */
    private function acumbamailDecision(GamePlayer $player): array
    {
        $email = trim((string) $player->email);
        $onList = $player->acumbamailSubscribedAt && ! $player->acumbamailUnsubscribedAt && $player->acumbamailEmail;

        if ($player->delete_status === 'deleted') {
            return $onList ? ['unsubscribe', null] : ['skip', 'Account deleted in the game.'];
        }
        if ($player->marketingOptOut) {
            return $onList ? ['unsubscribe', null] : ['skip', 'Opted out of marketing in the game.'];
        }
        if ($email === '') {
            return ['skip', 'No email address.'];
        }
        if (str_ends_with(strtolower($email), GamePlayer::STEAM_PLACEHOLDER_DOMAIN)) {
            return ['skip', 'Steam account without a real email address.'];
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return ['skip', 'Not a valid email address.'];
        }
        if ($onList && strcasecmp((string) $player->acumbamailEmail, $email) === 0) {
            return ['done', null];
        }

        return ['subscribe', $email];
    }

    // ------------------------------------------------------------------
    // Analytics codes
    // ------------------------------------------------------------------

    private function syncAnalytics(): array
    {
        $data = $this->cleanAnalytics($this->api->analytics());
        GameSetting::setJson(self::GAME, 'analytics', $data);
        GameSetting::touchTime(self::GAME, 'analyticsFetchedAt');

        return ['status' => 'ok', 'count' => 1, 'message' => 'Analytics codes read from the game.'];
    }

    /** Read the codes from the game now (Settings > "Read from the game"). */
    public function refreshAnalytics(): array
    {
        $result = $this->run(['analytics'], 30, 'admin');

        return $result['analytics'] ?? reset($result);
    }

    /**
     * Send analytics codes to the game (Settings > Save). Records what the game
     * said either way, so the page can show whether it took them.
     *
     * @return array{ok: bool, status: int, message: string, errors: array, data: array}
     */
    public function pushAnalytics(array $values): array
    {
        try {
            $data = $this->cleanAnalytics($this->api->updateAnalytics($values));
            GameSetting::setJson(self::GAME, 'analytics', $data);
            GameSetting::touchTime(self::GAME, 'analyticsFetchedAt');
            GameSetting::touchTime(self::GAME, 'analyticsPushedAt');
            GameSetting::setValue(self::GAME, 'analyticsPushStatus', 'accepted');
            GameSetting::setValue(self::GAME, 'analyticsPushMessage', 'The game accepted the analytics codes.');
            GameSyncState::for(self::GAME, 'analytics')->markDone(1, 'Saved to the game from Settings.');

            return ['ok' => true, 'status' => 200, 'message' => 'Saved. The game accepted the analytics codes.', 'errors' => [], 'data' => $data];
        } catch (SalpakanApiException $e) {
            $said = $e->getMessage();
            foreach ($e->errors as $field => $messages) {
                $said .= ' ' . $field . ': ' . implode(' ', (array) $messages);
            }
            GameSetting::touchTime(self::GAME, 'analyticsPushedAt');
            GameSetting::setValue(self::GAME, 'analyticsPushStatus', $e->status === 422 ? 'rejected' : 'failed');
            GameSetting::setValue(self::GAME, 'analyticsPushMessage', mb_substr(trim($said), 0, 1000));
            Log::warning('[Games] ' . self::gameName() . ' analytics push failed: ' . $said);

            return ['ok' => false, 'status' => $e->status, 'message' => $e->getMessage(), 'errors' => $e->errors, 'data' => []];
        }
    }

    public function cleanAnalytics(array $data): array
    {
        $clean = [];
        foreach (self::ANALYTICS_FIELDS as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }
            $value = $data[$field];
            $clean[$field] = in_array($field, ['enabled', 'has_ga4_api_secret', 'has_meta_access_token'], true)
                ? (bool) $value
                : (is_scalar($value) ? mb_substr((string) $value, 0, 200) : null);
        }

        return $clean;
    }

    // ------------------------------------------------------------------
    // Paging by updated_at
    // ------------------------------------------------------------------

    /**
     * Walk a list the game sorts by updated_at then id. After each page the
     * cursor moves to the newest updated_at kept and the walk starts again at
     * page 1 from there (updated_since is inclusive, so ties come again and
     * are upserted again, harmlessly). Asking from a moving point rather than
     * stepping page numbers means a row changed during the run moves to the end
     * and is met there, instead of shifting another row past the page boundary.
     * When a whole page shares one updated_at the walk steps to the next page.
     *
     * While a walk is under way resumeCursor holds the exact point reached; a
     * run that stops early or dies carries on from there (no overlap, which
     * would make it fetch the same first page again). A finished walk clears it.
     *
     * @return bool true when everything up to now was fetched
     */
    private function walkUpdated(GameSyncState $state, ?string $since, float $deadline, callable $fetch, callable $keep): bool
    {
        $page = 1;
        $requests = 0;

        while (true) {
            if (microtime(true) > $deadline || $requests >= $this->maxPages()) {
                return false;
            }
            $requests++;

            $result = $fetch($since, $page);
            $rows = $result['data'];
            if (! $rows) {
                $state->forceFill(['resumeCursor' => null])->save();

                return true;
            }

            $newest = $keep($rows, $result['meta']);
            $this->advanceCursor($state, $newest);
            $state->forceFill(['resumeCursor' => $state->cursorValue])->save();

            if ($page >= $result['meta']['last_page']) {
                $state->forceFill(['resumeCursor' => null])->save();

                return true;
            }
            if ($newest !== null && ($since === null || $this->isAfter($newest, $since))) {
                $since = $newest;
                $page = 1;
            } else {
                $page++;
            }
        }
    }

    private function advanceCursor(GameSyncState $state, ?string $newest): void
    {
        if ($newest === null) {
            return;
        }
        if ($state->cursorValue === null || $this->isAfter($newest, $state->cursorValue)) {
            $state->forceFill(['cursorValue' => $newest])->save();
        }
    }

    /** Where a players/orders walk starts: exactly where an unfinished one stopped, else the cursor less the overlap. */
    private function startingPoint(GameSyncState $state): ?string
    {
        return $state->resumeCursor ?: $this->sinceWithOverlap($state->cursorValue);
    }

    /** The cursor less the overlap, as the game expects it (ISO 8601, UTC). */
    private function sinceWithOverlap(?string $cursor): ?string
    {
        if (! $cursor) {
            return null;
        }
        try {
            return Carbon::parse($cursor)->utc()->subSeconds((int) config('games.salpakan.overlap_seconds', 120))->toIso8601ZuluString();
        } catch (Throwable) {
            return null;
        }
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function outcome(bool $complete, int $count, string $message): array
    {
        return $complete
            ? ['status' => 'ok', 'count' => $count, 'message' => $message]
            : ['status' => 'partial', 'count' => $count, 'message' => $message . ' Stopped early; the next sync carries on from here.'];
    }

    private function resultOf(GameSyncState $state): array
    {
        return ['status' => (string) $state->lastStatus, 'count' => (int) $state->lastCount, 'message' => (string) $state->lastMessage];
    }

    /** A cache lock so two runs never overlap; null when the cache cannot lock (run anyway). */
    private function lock()
    {
        try {
            $lock = Cache::lock('games-sync-' . self::GAME, 900);

            return $lock->get() ? $lock : false;
        } catch (Throwable) {
            return null;
        }
    }

    private function perPage(): int
    {
        return min(500, max(1, (int) config('games.salpakan.per_page', 500)));
    }

    private function maxPages(): int
    {
        return max(1, (int) config('games.salpakan.max_pages', 200));
    }

    /** The later of two ISO 8601 timestamps, normalised to UTC "Y-m-d\TH:i:s\Z" (fractions dropped, so it never overshoots). */
    private function newer(?string $current, $candidate): ?string
    {
        if (! is_string($candidate) || trim($candidate) === '') {
            return $current;
        }
        try {
            $normal = Carbon::parse($candidate)->utc()->toIso8601ZuluString();
        } catch (Throwable) {
            return $current;
        }

        return ($current === null || $this->isAfter($normal, $current)) ? $normal : $current;
    }

    private function isAfter(string $a, string $b): bool
    {
        return Carbon::parse($a)->gt(Carbon::parse($b));
    }

    /** An ISO 8601 time from the game, in this app's timezone (how AXIS stores times). */
    private function time($value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        try {
            return Carbon::parse($value)->timezone(config('app.timezone'));
        } catch (Throwable) {
            return null;
        }
    }

    private function dbTime($value): ?string
    {
        return $this->time($value)?->format('Y-m-d H:i:s');
    }

    private function date($value): ?string
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) !== false ? $value : null;
    }

    private function text($value, int $max): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    private function count($value): int
    {
        return is_numeric($value) ? max(0, (int) $value) : 0;
    }
}
