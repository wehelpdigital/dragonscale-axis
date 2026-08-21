<?php

namespace App\Modules\OutreachEngine\Console\Commands;

use App\Modules\OutreachEngine\Services\ImapClientService;
use App\Modules\OutreachEngine\Services\InboundProcessor;
use App\Modules\OutreachEngine\Services\SettingsResolver;
use App\Modules\OutreachEngine\Support\OutreachException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Polls the mailbox for replies and bounces.
 *
 * This is also the campaign's brake: a matched reply flips the lead to 'replied' and a
 * bounce flips it to 'bounced', so anything still queued for that lead is cancelled.
 * Running it every five minutes keeps the window between a prospect answering and us
 * going quiet short enough that nobody gets a follow-up after saying yes.
 */
class FetchRepliesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'outreach:fetch-replies
                            {--user= : Only this usersId (default: every user with saved settings)}
                            {--limit=50 : Maximum unseen messages to pull per user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch inbound replies and bounces over IMAP and match them to leads';

    /** Ceiling on --limit. Each message is a separate IMAP FETCH round trip. */
    const MAX_LIMIT = 200;

    /**
     * Execute the console command.
     */
    public function handle(SettingsResolver $resolver)
    {
        try {
            $limit = max(1, min((int) $this->option('limit'), self::MAX_LIMIT));

            $userIds = $this->targetUserIds($resolver);

            if (empty($userIds)) {
                $this->warn('No user has saved Lead Finder settings yet - no mailbox to poll.');

                return Command::SUCCESS;
            }

            $totals = ['fetched' => 0, 'stored' => 0, 'matched' => 0, 'bounces' => 0];

            foreach ($userIds as $userId) {
                $tally = $this->fetchForUser($resolver, $userId, $limit);

                foreach ($totals as $key => $value) {
                    $totals[$key] = $value + $tally[$key];
                }
            }

            $this->info(
                'Fetched ' . $totals['fetched'] . ' message(s): '
                . $totals['stored'] . ' stored, '
                . $totals['matched'] . ' matched to a lead, '
                . $totals['bounces'] . ' bounce(s).'
            );

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] outreach:fetch-replies failed: ' . $e->getMessage());
            $this->error('Reply fetch failed: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Poll one account's mailbox.
     *
     * @return array{fetched:int,stored:int,matched:int,bounces:int}
     */
    protected function fetchForUser(SettingsResolver $resolver, int $userId, int $limit): array
    {
        $tally = ['fetched' => 0, 'stored' => 0, 'matched' => 0, 'bounces' => 0];

        try {
            $settings = $resolver->requireForUser($userId);
        } catch (OutreachException $e) {
            $this->warn('User #' . $userId . ': ' . $e->getMessage());

            return $tally;
        }

        if (!$settings->imapConfigured()) {
            $this->line('User #' . $userId . ': IMAP is not configured - skipping.');

            return $tally;
        }

        $processor = new InboundProcessor($settings, new ImapClientService($settings));
        $result = $processor->run($userId, $limit);

        foreach ($tally as $key => $value) {
            $tally[$key] = (int) ($result[$key] ?? 0);
        }

        if (!empty($result['error'])) {
            // A refused login or a dropped connection is a soft failure: whatever was read
            // before the error is already stored, and the next tick tries again. Logged and
            // shown, but it does not fail the run - cron would otherwise alert every five
            // minutes for the length of a mail-host outage.
            Log::warning('[OutreachEngine] Reply fetch for user ' . $userId . ': ' . $result['error']);
            $this->warn('User #' . $userId . ': ' . $result['error']);
        }

        $this->line(
            'User #' . $userId . ': ' . $tally['fetched'] . ' fetched, '
            . $tally['stored'] . ' stored, ' . $tally['matched'] . ' matched, '
            . $tally['bounces'] . ' bounce(s).'
        );

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
