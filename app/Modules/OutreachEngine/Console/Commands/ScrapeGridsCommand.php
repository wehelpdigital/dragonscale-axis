<?php

namespace App\Modules\OutreachEngine\Console\Commands;

use App\Modules\OutreachEngine\Models\OutreachSearchGrid;
use App\Modules\OutreachEngine\Services\GridScrapeService;
use App\Modules\OutreachEngine\Services\SettingsResolver;
use App\Modules\OutreachEngine\Support\OutreachException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Works pending search-grid cells - the scraping half of the Lead Finder.
 *
 * Every cell is a billable Google Places call, so the claim in claimCells() is the
 * part of this file that matters: cron fires this every two minutes while the scraper
 * screen can also run cells inline, and two runs must never pay for the same cell.
 */
class ScrapeGridsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'outreach:scrape-grids
                            {--user= : Only this usersId (default: every user with saved settings)}
                            {--batch= : Only cells belonging to this batch id}
                            {--limit=5 : How many cells to claim per user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape pending Google Places search grid cells into leads';

    /** Ceiling on --limit. A runaway value would spend the whole Places quota in one tick. */
    const MAX_LIMIT = 50;

    /** Breather between cells so a burst does not read as a scraper to Google. */
    const CELL_PAUSE_SECONDS = 2;

    /**
     * Execute the console command.
     */
    public function handle(SettingsResolver $resolver)
    {
        try {
            $limit = max(1, min((int) $this->option('limit'), self::MAX_LIMIT));

            $batchId = trim((string) $this->option('batch'));
            $batchId = $batchId === '' ? null : $batchId;

            $userIds = $this->targetUserIds($resolver);

            if (empty($userIds)) {
                $this->warn('No user has saved Lead Finder settings yet - nothing to scrape.');

                return Command::SUCCESS;
            }

            $totals = ['cells' => 0, 'results' => 0, 'new' => 0, 'failed' => 0];

            foreach ($userIds as $userId) {
                $tally = $this->scrapeForUser($resolver, $userId, $limit, $batchId);

                foreach ($totals as $key => $value) {
                    $totals[$key] = $value + $tally[$key];
                }
            }

            $this->info(
                'Scraped ' . $totals['cells'] . ' cell(s): '
                . $totals['results'] . ' place(s) seen, '
                . $totals['new'] . ' new lead(s), '
                . $totals['failed'] . ' failure(s).'
            );

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] outreach:scrape-grids failed: ' . $e->getMessage());
            $this->error('Scrape run failed: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Claim and process one account's share of the pending cells.
     *
     * @return array{cells:int,results:int,new:int,failed:int}
     */
    protected function scrapeForUser(SettingsResolver $resolver, int $userId, int $limit, ?string $batchId): array
    {
        $tally = ['cells' => 0, 'results' => 0, 'new' => 0, 'failed' => 0];

        try {
            $settings = $resolver->requireForUser($userId);
        } catch (OutreachException $e) {
            $this->warn('User #' . $userId . ': ' . $e->getMessage());

            return $tally;
        }

        // Checked BEFORE anything is claimed. processGrid() marks a keyless cell failed
        // and burns an attempt, so three unattended ticks would retire cells that never
        // got as far as a network call.
        if (!$settings->hasPlacesKey()) {
            $this->warn('User #' . $userId . ': no Google Places API key saved - skipping.');

            return $tally;
        }

        $claimedIds = $this->claimCells($userId, $limit, $batchId);

        if (empty($claimedIds)) {
            $this->line('User #' . $userId . ': no pending grid cells.');

            return $tally;
        }

        $this->info('User #' . $userId . ': claimed ' . count($claimedIds) . ' cell(s).');

        $service = new GridScrapeService($settings);
        $first = true;

        foreach ($claimedIds as $gridId) {
            $grid = OutreachSearchGrid::query()->find($gridId);

            if (!$grid) {
                // Deleted between the claim and now. Nothing to release - the row is gone.
                continue;
            }

            if (!$first) {
                sleep(self::CELL_PAUSE_SECONDS);
            }
            $first = false;

            // processGrid() owns the ~2s pause between Places pages (a next_page_token is
            // not usable the instant it is issued) and never throws - it returns the error.
            $result = $service->processGrid($grid);

            $tally['cells']++;
            $tally['results'] += (int) $result['results'];
            $tally['new'] += (int) $result['new'];

            if (!empty($result['error'])) {
                $tally['failed']++;
                $this->error('  Cell #' . $gridId . ': ' . $result['error']);

                continue;
            }

            $note = '';
            if (!empty($result['split'])) {
                $note = ' (saturated - subdivided into 4)';
            } elseif (!empty($result['sparse'])) {
                $note = ' (sparse)';
            }

            $this->line(
                '  Cell #' . $gridId . ': ' . $result['results'] . ' place(s), '
                . $result['new'] . ' new' . $note
            );
        }

        return $tally;
    }

    /**
     * Take ownership of up to $limit pending cells and return the ids actually claimed.
     *
     * The select holds a row lock for the length of the transaction, so a second run
     * arriving mid-claim blocks on the SELECT and then sees those rows as 'processing'
     * rather than 'pending' - it leaves with a different set. That is what makes the
     * conditional UPDATE exact: under the lock, every id in $ids was still pending when
     * the update ran, so all of them are ours.
     *
     * @return int[]
     */
    protected function claimCells(int $userId, int $limit, ?string $batchId): array
    {
        return DB::transaction(function () use ($userId, $limit, $batchId) {
            $ids = OutreachSearchGrid::query()
                ->active()
                ->forUser($userId)
                ->pending()
                ->where('attempts', '<', OutreachSearchGrid::MAX_ATTEMPTS)
                ->when($batchId !== null, function ($query) use ($batchId) {
                    return $query->forBatch($batchId);
                })
                // Shallow cells first: their results decide whether the deeper children
                // are ever created, so working them first keeps a batch coherent.
                ->orderBy('depth')
                ->orderBy('id')
                ->limit($limit)
                ->lockForUpdate()
                ->pluck('id')
                ->all();

            if (empty($ids)) {
                return [];
            }

            $claimed = OutreachSearchGrid::query()
                ->whereIn('id', $ids)
                ->where('status', OutreachSearchGrid::STATUS_PENDING)
                ->update(['status' => OutreachSearchGrid::STATUS_PROCESSING]);

            if ($claimed !== count($ids)) {
                // Impossible from a competing run (see the lock note above), so this means
                // something outside the module edited the rows. Worth a log line, not a
                // crash - the cells stay consistent either way.
                Log::warning(
                    '[OutreachEngine] Grid claim for user ' . $userId . ' selected ' . count($ids)
                    . ' pending cell(s) but flipped ' . $claimed . ' - the table was edited mid-claim.'
                );
            }

            return array_map('intval', $ids);
        });
    }

    /**
     * Which accounts this run covers: the one named by --user, or everyone holding an
     * active settings row.
     *
     * @return int[]
     */
    protected function targetUserIds(SettingsResolver $resolver): array
    {
        $option = trim((string) $this->option('user'));

        if ($option === '') {
            return $resolver->configuredUserIds();
        }

        $userId = (int) $option;

        if ($userId <= 0) {
            $this->error('--user expects a numeric user id.');

            return [];
        }

        return [$userId];
    }
}
