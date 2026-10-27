<?php

namespace App\Console\Commands;

use App\Models\GameSyncState;
use App\Services\Games\SalpakanSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Brings a game's players, daily activity and orders over from its game
 * server, logs its sales into /ecom-orders, adds its players to Acumbamail
 * and reads back its analytics codes. Scheduled every fifteen minutes; each
 * data type keeps its own cursor, so a run that stops early (--seconds) or
 * fails part way carries on where it left off.
 *
 *   php artisan games:sync                       everything, Salpakan Tactics!
 *   php artisan games:sync --only=orders,ecom    just those steps
 */
class GamesSyncCommand extends Command
{
    protected $signature = 'games:sync
        {game=salpakan : The game key (config/games.php)}
        {--only= : Comma-separated steps: players, activity, orders, ecom, acumbamail, analytics}
        {--seconds=600 : Stop starting new pages or calls after this many seconds}
        {--by=console : What started it, shown on the Settings page (the schedule passes scheduler)}';

    protected $description = 'Sync a game\'s players, activity and orders from its game server into AXIS';

    public function handle(): int
    {
        $game = (string) $this->argument('game');
        if ($game !== SalpakanSyncService::GAME) {
            $this->error('Unknown game "' . $game . '". Known: ' . SalpakanSyncService::GAME . '.');

            return self::FAILURE;
        }

        $only = null;
        if ($this->option('only')) {
            $only = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('only')))));
            $unknown = array_diff($only, array_keys(GameSyncState::TYPES));
            if ($unknown) {
                $this->error('Unknown step(s): ' . implode(', ', $unknown) . '. Known: ' . implode(', ', array_keys(GameSyncState::TYPES)) . '.');

                return self::FAILURE;
            }
        }

        try {
            $results = app(SalpakanSyncService::class)->run(
                $only,
                max(10, (int) $this->option('seconds')),
                in_array($this->option('by'), ['scheduler', 'console'], true) ? $this->option('by') : 'console'
            );
        } catch (\Throwable $e) {
            // Each step already records its own failure; this is the sync not
            // being able to start at all (its tables missing, the database down).
            Log::error('[Games] games:sync could not run: ' . $e->getMessage());
            $this->error('The sync could not run: ' . $e->getMessage());

            return self::FAILURE;
        }

        foreach ($results as $type => $result) {
            $line = sprintf('%-11s %-8s %s', $type, $result['status'], $result['message']);
            $result['status'] === 'failed' ? $this->warn($line) : $this->line($line);
        }

        return self::SUCCESS;
    }
}
