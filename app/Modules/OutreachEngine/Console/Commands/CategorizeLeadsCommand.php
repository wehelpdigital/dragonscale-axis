<?php

namespace App\Modules\OutreachEngine\Console\Commands;

use App\Modules\OutreachEngine\Models\OutreachLead;
use App\Modules\OutreachEngine\Services\LeadCategorizationService;
use App\Modules\OutreachEngine\Services\SettingsResolver;
use App\Modules\OutreachEngine\Support\OutreachException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Names the business behind every scraped lead.
 *
 * Google's types[] is ordered by its own taxonomy, so a beach resort commonly
 * arrives as "point_of_interest" and a clinic as "health". This walks the
 * pending queue and asks the model what each business actually is, writing the
 * answer to aiCategory and leaving Google's original in category untouched.
 *
 * Unlike enrichment, this is cheap: one model call handles a whole batch of
 * leads, so the per-tick limit can be far higher than the enrichment command's.
 */
class CategorizeLeadsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'outreach:categorize-leads
                            {--user= : Only this usersId (default: every user with saved settings)}
                            {--limit=100 : How many leads to categorise per user}
                            {--retry-failed : Also re-try leads previously marked failed or skipped}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign a business category to leads using the configured AI provider';

    /** Ceiling on --limit, so one tick cannot run away with the whole table. */
    const MAX_LIMIT = 500;

    /**
     * Execute the console command.
     */
    public function handle(SettingsResolver $resolver)
    {
        try {
            $limit = max(1, min((int) $this->option('limit'), self::MAX_LIMIT));
            $userIds = $this->targetUserIds($resolver);

            if (empty($userIds)) {
                $this->warn('No user has saved Lead Finder settings yet - nothing to categorise.');

                return Command::SUCCESS;
            }

            $totals = ['categorized' => 0, 'failed' => 0, 'skipped' => 0];

            foreach ($userIds as $userId) {
                $tally = $this->categorizeForUser($resolver, $userId, $limit);

                foreach ($totals as $key => $value) {
                    $totals[$key] = $value + $tally[$key];
                }
            }

            $this->info(
                'Categorised ' . $totals['categorized'] . ' lead(s); '
                . $totals['failed'] . ' failed, '
                . $totals['skipped'] . ' skipped.'
            );

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] outreach:categorize-leads failed: ' . $e->getMessage());
            $this->error('Categorisation failed: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Work one account's pending queue.
     *
     * @return array{categorized:int,failed:int,skipped:int}
     */
    protected function categorizeForUser(SettingsResolver $resolver, int $userId, int $limit): array
    {
        $tally = ['categorized' => 0, 'failed' => 0, 'skipped' => 0];

        try {
            $settings = $resolver->requireForUser($userId);
        } catch (OutreachException $e) {
            $this->warn('User ' . $userId . ': ' . $e->getMessage());

            return $tally;
        }

        $service = new LeadCategorizationService($settings);

        if (!$service->isConfigured()) {
            // A config state, not a crash - say so once and move on rather than
            // burning the queue by marking every row skipped on every tick.
            $this->warn('User ' . $userId . ': no AI provider configured, skipping categorisation.');

            return $tally;
        }

        $statuses = [OutreachLead::CATEGORY_PENDING];

        if ($this->option('retry-failed')) {
            $statuses[] = OutreachLead::CATEGORY_FAILED;
            $statuses[] = OutreachLead::CATEGORY_SKIPPED;
        }

        $remaining = $limit;

        while ($remaining > 0) {
            $take = min(LeadCategorizationService::BATCH_SIZE, $remaining);

            $leads = OutreachLead::active()
                ->forUser($userId)
                ->whereIn('categoryStatus', $statuses)
                ->where('categoryAttempts', '<', OutreachLead::MAX_CATEGORY_ATTEMPTS)
                ->orderBy('id')
                ->limit($take)
                ->get();

            if ($leads->isEmpty()) {
                break;
            }

            // Claim the batch before spending a model call on it, so an
            // overlapping tick cannot pay to categorise the same rows twice.
            OutreachLead::whereIn('id', $leads->pluck('id')->all())
                ->update(['categoryStatus' => OutreachLead::CATEGORY_PROCESSING]);

            $result = $service->categorizeBatch($leads);

            $tally['categorized'] += $result['categorized'];
            $tally['failed'] += $result['failed'];
            $tally['skipped'] += $result['skipped'];

            $this->line(
                '  user ' . $userId . ': batch of ' . $leads->count()
                . ' -> ' . $result['categorized'] . ' categorised'
                . ($result['error'] ? ' (' . $result['error'] . ')' : '')
            );

            // A provider-level failure will repeat on the next batch too; stop
            // rather than grinding the whole limit against a dead key.
            if ($result['error'] !== null && $result['categorized'] === 0) {
                break;
            }

            $remaining -= $leads->count();
        }

        return $tally;
    }

    /**
     * Which accounts this run covers.
     *
     * @return array<int, int>
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
