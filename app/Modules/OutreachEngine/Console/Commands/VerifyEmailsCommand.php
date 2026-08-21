<?php

namespace App\Modules\OutreachEngine\Console\Commands;

use App\Modules\OutreachEngine\Models\OutreachLead;
use App\Modules\OutreachEngine\Services\BatchProgressService;
use App\Modules\OutreachEngine\Services\EmailVerifierService;
use App\Modules\OutreachEngine\Services\SettingsResolver;
use App\Modules\OutreachEngine\Support\OutreachException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Puts every discovered address to the verifier before anything is sent to it.
 *
 * This is the last gate in the pipeline: scrape finds the business, enrichment
 * finds an address, and this decides whether the address is worth mailing. Only
 * a confirmed-deliverable result sets isEmailValid; catch-all, role and unknown
 * are recorded and held back, because a bounce costs sender reputation that is
 * far harder to buy back than a verification credit.
 *
 * Addresses are verified one call at a time rather than in bulk. Reoon does
 * offer a bulk task API, but it is asynchronous - submit, poll, collect - and
 * that is three moving parts and a lot of state for a queue that a per-minute
 * cron drains perfectly well on its own.
 */
class VerifyEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'outreach:verify-emails
                            {--user= : Only this usersId (default: every user with saved settings)}
                            {--limit=50 : How many addresses to verify per user}
                            {--batch= : Restrict to one batchId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check discovered email addresses with the Reoon verifier before sending';

    /** Ceiling on --limit. Each address is one paid API call, so a runaway tick is real money. */
    const MAX_LIMIT = 500;

    /**
     * Execute the console command.
     */
    public function handle(SettingsResolver $resolver, BatchProgressService $batches)
    {
        try {
            $limit = max(1, min((int) $this->option('limit'), self::MAX_LIMIT));
            $userIds = $this->targetUserIds($resolver);

            if (empty($userIds)) {
                $this->warn('No user has saved Lead Finder settings yet - nothing to verify.');

                return Command::SUCCESS;
            }

            $totals = ['checked' => 0, 'valid' => 0, 'rejected' => 0, 'failed' => 0];

            foreach ($userIds as $userId) {
                $tally = $this->verifyForUser($resolver, $batches, $userId, $limit);

                foreach ($totals as $key => $value) {
                    $totals[$key] = $value + $tally[$key];
                }
            }

            $this->info(
                'Verified ' . $totals['checked'] . ' address(es): '
                . $totals['valid'] . ' good, '
                . $totals['rejected'] . ' rejected, '
                . $totals['failed'] . ' could not be checked.'
            );

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] outreach:verify-emails failed: ' . $e->getMessage());
            $this->error('Verification failed: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Work one account's verification queue.
     *
     * @return array{checked:int,valid:int,rejected:int,failed:int}
     */
    protected function verifyForUser(
        SettingsResolver $resolver,
        BatchProgressService $batches,
        int $userId,
        int $limit
    ): array {
        $tally = ['checked' => 0, 'valid' => 0, 'rejected' => 0, 'failed' => 0];

        try {
            $settings = $resolver->requireForUser($userId);
        } catch (OutreachException $e) {
            $this->warn('User ' . $userId . ': ' . $e->getMessage());

            return $tally;
        }

        $verifier = new EmailVerifierService($settings);

        if (!$verifier->verificationEnabled()) {
            // Switched off deliberately. Mark the queue skipped so batches can
            // still reach Complete instead of waiting on a stage nobody wants.
            $skipped = OutreachLead::query()
                ->active()
                ->forUser($userId)
                ->needsVerification()
                ->limit($limit)
                ->update(['verificationStatus' => OutreachLead::VERIFY_SKIPPED]);

            if ($skipped > 0) {
                $this->line('  user ' . $userId . ': verification disabled, ' . $skipped . ' address(es) marked skipped.');
            }

            $batches->refreshForUser($userId, false);

            return $tally;
        }

        if (!$verifier->isConfigured()) {
            $this->warn('User ' . $userId . ': no Reoon API key saved, skipping verification.');

            return $tally;
        }

        $query = OutreachLead::query()
            ->active()
            ->forUser($userId)
            ->needsVerification()
            ->orderBy('id')
            ->limit($limit);

        if ($this->option('batch')) {
            $query->where('batchId', (string) $this->option('batch'));
        }

        $leads = $query->get();

        if ($leads->isEmpty()) {
            return $tally;
        }

        foreach ($leads as $lead) {
            $outcome = $verifier->verifyLead($lead);
            $tally['checked']++;

            if (!$outcome['ok']) {
                $tally['failed']++;

                // A transport failure will almost certainly repeat on the next
                // address too; stop rather than burning the limit against an
                // outage or a dead key.
                $this->warn('  user ' . $userId . ': ' . $outcome['error'] . ' - stopping this pass.');
                break;
            }

            if ($outcome['valid']) {
                $tally['valid']++;
            } else {
                $tally['rejected']++;
            }
        }

        $this->line(
            '  user ' . $userId . ': ' . $tally['checked'] . ' checked, '
            . $tally['valid'] . ' good, ' . $tally['rejected'] . ' rejected.'
        );

        // Verification is the last gate, so this is where a sweep usually tips
        // over into Complete - refresh before the tick ends rather than leaving
        // the batch screen a cron cycle behind.
        $batches->refreshForUser($userId, true);

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
