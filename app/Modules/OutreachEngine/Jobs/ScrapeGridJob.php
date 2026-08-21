<?php

namespace App\Modules\OutreachEngine\Jobs;

use App\Modules\OutreachEngine\Models\OutreachSearchGrid;
use App\Modules\OutreachEngine\Services\GridScrapeService;
use App\Modules\OutreachEngine\Services\SettingsResolver;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Scrapes one search-grid cell.
 *
 * READ THIS BEFORE DISPATCHING: this app runs QUEUE_CONNECTION=sync, so dispatch()
 * executes the job INLINE and blocks whatever called it - a cell can take half a minute
 * of Google round trips, which no HTTP request should ever wait on. The supported path
 * is the cron command `outreach:scrape-grids`, which claims cells and works them from
 * the CLI. This class exists so the module is ready the day the operator switches
 * QUEUE_CONNECTION to `database` (the jobs table already ships with the module) and runs
 * `php artisan queue:work`; only then does dispatching this become the better option.
 */
class ScrapeGridJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Attempts before the job is handed to failed().
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Seconds the job may run. Three Places pages plus the mandatory ~2s waits between
     * page tokens fit comfortably inside this.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * The grid cell to work.
     *
     * The id, not the model: SerializesModels would re-query the row on unserialize
     * anyway, and a stale serialised copy could overwrite a status another run has
     * since set.
     *
     * @var int
     */
    public $gridId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $gridId)
    {
        $this->gridId = $gridId;
    }

    /**
     * Execute the job.
     */
    public function handle(SettingsResolver $resolver): void
    {
        $grid = OutreachSearchGrid::query()->active()->find($this->gridId);

        if (!$grid) {
            Log::warning('[OutreachEngine] ScrapeGridJob: grid ' . $this->gridId . ' is gone or deleted.');

            return;
        }

        // Terminal cells are done: re-running one would pay Google twice for the same
        // answer and could duplicate a subdivision.
        if (in_array($grid->status, [
            OutreachSearchGrid::STATUS_COMPLETED,
            OutreachSearchGrid::STATUS_SPLIT,
        ], true)) {
            Log::info('[OutreachEngine] ScrapeGridJob: grid ' . $this->gridId . ' is already ' . $grid->status . ' - skipping.');

            return;
        }

        // requireForUser() throws when the account has no saved settings; letting that
        // escape is deliberate, so the failure lands in failed() with a real reason.
        $settings = $resolver->requireForUser((int) $grid->usersId);

        $result = (new GridScrapeService($settings))->processGrid($grid);

        if (!empty($result['error'])) {
            Log::warning('[OutreachEngine] ScrapeGridJob: grid ' . $this->gridId . ' finished with an error - ' . $result['error']);

            return;
        }

        Log::info(
            '[OutreachEngine] ScrapeGridJob: grid ' . $this->gridId . ' produced '
            . $result['results'] . ' place(s) and ' . $result['new'] . ' new lead(s).'
        );
    }

    /**
     * The job exhausted its tries or threw outside processGrid().
     *
     * processGrid() marks its own failures, so this only matters for what happened
     * before it (missing settings, a dead database connection) - without it the row
     * would sit on 'processing' and no run would ever pick it up again.
     */
    public function failed(\Throwable $e): void
    {
        try {
            OutreachSearchGrid::query()
                ->where('id', $this->gridId)
                ->update([
                    'status' => OutreachSearchGrid::STATUS_FAILED,
                    'lastError' => Str::limit($e->getMessage(), 480),
                    'processedAt' => Carbon::now('Asia/Manila'),
                ]);
        } catch (\Throwable $inner) {
            Log::error('[OutreachEngine] ScrapeGridJob: could not mark grid ' . $this->gridId . ' failed - ' . $inner->getMessage());
        }

        Log::error('[OutreachEngine] ScrapeGridJob failed for grid ' . $this->gridId . ': ' . $e->getMessage());
    }
}
