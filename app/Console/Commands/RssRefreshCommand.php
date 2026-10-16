<?php

namespace App\Console\Commands;

use App\Models\RssSetting;
use App\Models\RssSource;
use App\Services\RssFeeds\RssRefresher;
use Illuminate\Console\Command;

/**
 * Reads the RSS Feeds module's sources that are due (each has its own
 * refreshMinutes), or one source with --source. Scheduled every five
 * minutes; a run stops starting new reads at --seconds so two never pile up.
 */
class RssRefreshCommand extends Command
{
    protected $signature = 'rss:refresh
        {--source= : Read only this source id, due or not}
        {--all : Read every active source, due or not}
        {--seconds=240 : Stop starting new reads after this many seconds}';

    protected $description = 'Read the RSS Feeds sources that are due and keep their new stories';

    public function handle(RssRefresher $refresher): int
    {
        $deadline = microtime(true) + max(30, (int) $this->option('seconds'));
        $report = function (RssSource $source, array $r) {
            $line = sprintf('#%d %s: %s', $source->id, $source->sourceName, $r['message']);
            $r['ok'] ? $this->line($line) : $this->warn($line);
        };

        if ($id = $this->option('source')) {
            $source = RssSource::active()->find($id);
            if (! $source) {
                $this->error('No source ' . $id . '.');

                return self::FAILURE;
            }
            $report($source, $refresher->refresh($source, $deadline));

            return self::SUCCESS;
        }

        if (! $refresher->refreshDue($deadline, (bool) $this->option('all'), $report)) {
            $this->line('No source is due.');
        }
        RssSetting::ranBy('scheduler');

        return self::SUCCESS;
    }
}
