<?php

namespace App\Modules\OutreachEngine\Console\Commands;

use App\Modules\OutreachEngine\Models\OutreachLead;
use App\Modules\OutreachEngine\Services\LeadEnrichmentService;
use App\Modules\OutreachEngine\Services\SettingsResolver;
use App\Modules\OutreachEngine\Support\OutreachException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Hunts for the missing email address on scraped leads.
 *
 * A lead without an email is dead weight, so this walks the pending queue in small
 * batches: search, fetch, regex, and only then the LLM. Each lead can cost a few HTTP
 * round trips, which is why --limit stays low and cron runs it every three minutes
 * instead of trying to drain the whole table at once.
 */
class EnrichLeadsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'outreach:enrich-leads
                            {--user= : Only this usersId (default: every user with saved settings)}
                            {--limit=10 : How many leads to enrich per user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Discover contact emails for leads still waiting on enrichment';

    /** Ceiling on --limit. Enrichment is network-bound, so a huge batch just stalls the tick. */
    const MAX_LIMIT = 100;

    /**
     * Execute the console command.
     */
    public function handle(SettingsResolver $resolver)
    {
        try {
            $limit = max(1, min((int) $this->option('limit'), self::MAX_LIMIT));

            $userIds = $this->targetUserIds($resolver);

            if (empty($userIds)) {
                $this->warn('No user has saved Lead Finder settings yet - nothing to enrich.');

                return Command::SUCCESS;
            }

            $totals = ['processed' => 0, 'found' => 0, 'missed' => 0];

            foreach ($userIds as $userId) {
                $tally = $this->enrichForUser($resolver, $userId, $limit);

                foreach ($totals as $key => $value) {
                    $totals[$key] = $value + $tally[$key];
                }
            }

            $this->info(
                'Enriched ' . $totals['processed'] . ' lead(s): '
                . $totals['found'] . ' email(s) found, '
                . $totals['missed'] . ' without one.'
            );

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] outreach:enrich-leads failed: ' . $e->getMessage());
            $this->error('Enrichment run failed: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Enrich one account's next batch of leads.
     *
     * @return array{processed:int,found:int,missed:int}
     */
    protected function enrichForUser(SettingsResolver $resolver, int $userId, int $limit): array
    {
        $tally = ['processed' => 0, 'found' => 0, 'missed' => 0];

        try {
            $settings = $resolver->requireForUser($userId);
        } catch (OutreachException $e) {
            $this->warn('User #' . $userId . ': ' . $e->getMessage());

            return $tally;
        }

        // needsEnrichment() is the whole selection rule: pending, no email yet, and still
        // under MAX_ENRICHMENT_ATTEMPTS. enrich() bumps that counter before it touches the
        // network, so a lead whose site keeps timing out retires itself after three tries.
        $leads = OutreachLead::query()
            ->active()
            ->forUser($userId)
            ->needsEnrichment()
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($leads->isEmpty()) {
            $this->line('User #' . $userId . ': no leads waiting on enrichment.');

            return $tally;
        }

        $this->info('User #' . $userId . ': enriching ' . $leads->count() . ' lead(s).');

        $service = new LeadEnrichmentService($settings);

        foreach ($leads as $lead) {
            try {
                $result = $service->enrich($lead);
            } catch (\Throwable $e) {
                // enrich() already swallows its own failures; anything reaching here is a
                // surprise (a fatal in a dependency, say). One bad lead must not end the run.
                Log::error('[OutreachEngine] Enrichment of lead ' . $lead->id . ' crashed: ' . $e->getMessage());
                $this->error('  Lead #' . $lead->id . ': ' . $e->getMessage());
                $tally['processed']++;
                $tally['missed']++;

                continue;
            }

            $tally['processed']++;

            if (!empty($result['email'])) {
                $tally['found']++;
                $this->line(
                    '  Lead #' . $lead->id . ' ' . $lead->businessName . ': '
                    . $result['email'] . ' (via ' . ($result['source'] ?: 'unknown') . ')'
                );

                continue;
            }

            $tally['missed']++;
            $this->line(
                '  Lead #' . $lead->id . ' ' . $lead->businessName . ': no email - '
                . ($result['error'] ?: 'nothing found')
            );
        }

        return $tally;
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
