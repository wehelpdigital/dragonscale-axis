<?php

namespace App\Modules\OutreachEngine\Console\Commands;

use App\Modules\OutreachEngine\Models\OutreachEmailLog;
use App\Modules\OutreachEngine\Models\OutreachEmailTemplate;
use App\Modules\OutreachEngine\Models\OutreachInboundMessage;
use App\Modules\OutreachEngine\Models\OutreachLead;
use App\Modules\OutreachEngine\Models\OutreachSearchGrid;
use App\Modules\OutreachEngine\Models\OutreachSetting;
use App\Modules\OutreachEngine\Services\OutreachDecisionService;
use App\Modules\OutreachEngine\Services\SettingsResolver;
use App\Modules\OutreachEngine\Support\OutreachException;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Read-only diagnostics: why is (or is not) the Lead Finder doing anything?
 *
 * Writes nothing and calls no external API, so it is safe to run at any time. When a
 * user reports "it stopped sending", this is the first thing to run - the window row
 * carries the same verdict string the cron acted on.
 */
class OutreachStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'outreach:status
                            {--user= : Only this usersId (default: every user with saved settings)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show Lead Finder pipeline and sending-window diagnostics';

    /**
     * Execute the console command.
     */
    public function handle(SettingsResolver $resolver)
    {
        try {
            $userIds = $this->targetUserIds($resolver);

            if (empty($userIds)) {
                $this->warn('No user has saved Lead Finder settings yet.');

                return Command::SUCCESS;
            }

            foreach ($userIds as $userId) {
                try {
                    $settings = $resolver->requireForUser($userId);
                } catch (OutreachException $e) {
                    $this->warn('User #' . $userId . ': ' . $e->getMessage());

                    continue;
                }

                $this->newLine();
                $this->info('Lead Finder status - user #' . $userId . ' (' . Carbon::now('Asia/Manila')->format('Y-m-d H:i') . ' Asia/Manila)');
                $this->table(['Check', 'Value'], $this->rowsForUser($settings, $userId));
            }

            $this->newLine();

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] outreach:status failed: ' . $e->getMessage());
            $this->error('Status report failed: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Every diagnostic line for one account, in the order an operator reads them:
     * can it send, what is queued, what came back.
     *
     * @return array<int, array{0:string,1:string}>
     */
    protected function rowsForUser(OutreachSetting $settings, int $userId): array
    {
        $decision = new OutreachDecisionService($settings);
        $window = $decision->evaluateWindow();

        $grids = $this->countByStatus(
            OutreachSearchGrid::query()->active()->forUser($userId),
            'status'
        );

        $leads = $this->countByStatus(
            OutreachLead::query()->active()->forUser($userId),
            'outreachStatus'
        );

        $enrichment = $this->countByStatus(
            OutreachLead::query()->active()->forUser($userId),
            'enrichmentStatus'
        );

        $withEmail = OutreachLead::query()->active()->forUser($userId)->hasEmail()->count();
        $leadTotal = array_sum($leads);

        $sentTotal = OutreachEmailLog::query()->active()->forUser($userId)->sent()->count();
        $failedSends = OutreachEmailLog::query()->active()->forUser($userId)
            ->where('status', OutreachEmailLog::STATUS_FAILED)->count();

        $templates = OutreachEmailTemplate::query()->active()->forUser($userId)->enabled()->count();

        $unread = OutreachInboundMessage::query()->active()->forUser($userId)->unread()->count();
        $lastReply = OutreachInboundMessage::query()->active()->forUser($userId)
            ->inbound()
            ->orderByDesc('receivedAt')
            ->orderByDesc('id')
            ->first();

        $nextEligible = $window['nextEligibleAt'] instanceof Carbon
            ? $window['nextEligibleAt']->format('Y-m-d H:i')
            : '-';

        return [
            ['Outreach switch', $settings->outreachEnabled ? 'ON' : 'OFF (master kill switch)'],
            ['Window verdict', ($window['allowed'] ? 'SENDING - ' : 'HOLDING - ') . $window['reason']],
            ['Sent today', $window['sentToday'] . ' / ' . $window['cap'] . ' (effective cap)'],
            ['Next eligible', $nextEligible],
            ['Sending days', $settings->send_days_label],
            ['Sending window', $settings->send_window_label . ' Asia/Manila'],
            ['Send delay', $settings->minDelayMinutes . '-' . $settings->maxDelayMinutes . ' minutes'],
            ['Warm-up', $settings->warmupEnabled
                ? 'on, started ' . ($settings->warmupStartedOn ? Carbon::parse($settings->warmupStartedOn)->format('Y-m-d') : 'never')
                : 'off'],
            ['Active templates', (string) $templates . ($templates === 0 ? '  <-- nothing can send without one' : '')],
            ['Credentials', $this->credentialSummary($settings)],
            ['Grid cells', $this->summarise($grids, ['pending', 'processing', 'completed', 'split', 'failed'])],
            ['Leads', $leadTotal . ' total, ' . $withEmail . ' with an email'],
            ['Lead pipeline', $this->summarise($leads, ['uncontacted', 'queued', 'contacted', 'replied', 'unsubscribed', 'bounced', 'failed'])],
            ['Enrichment', $this->summarise($enrichment, ['pending', 'processing', 'enriched', 'failed', 'skipped'])],
            ['Emails sent', $sentTotal . ' total, ' . $failedSends . ' failed attempt(s)'],
            ['Unread replies', (string) $unread],
            ['Last reply', $lastReply
                ? ($lastReply->receivedAt ? $lastReply->receivedAt->format('Y-m-d H:i') : 'unknown time') . ' from ' . $lastReply->senderEmail
                : 'none yet'],
        ];
    }

    /**
     * One grouped count instead of one query per status.
     *
     * @return array<string,int> status => count
     */
    protected function countByStatus($query, string $column): array
    {
        $rows = $query->selectRaw($column . ' as statusValue, COUNT(*) as total')
            ->groupBy($column)
            ->pluck('total', 'statusValue')
            ->all();

        $counts = [];
        foreach ($rows as $status => $total) {
            $counts[(string) $status] = (int) $total;
        }

        return $counts;
    }

    /**
     * "pending 4, completed 12" - zero-count statuses are dropped so the important
     * numbers are not buried in noise.
     *
     * @param  array<string,int>  $counts
     * @param  string[]  $order
     */
    protected function summarise(array $counts, array $order): string
    {
        $parts = [];

        foreach ($order as $status) {
            if (!empty($counts[$status])) {
                $parts[] = $status . ' ' . $counts[$status];
            }
        }

        // Anything the enum grew that this command does not know about still shows up.
        foreach ($counts as $status => $total) {
            if (!in_array($status, $order, true) && $total > 0) {
                $parts[] = $status . ' ' . $total;
            }
        }

        return empty($parts) ? 'none' : implode(', ', $parts);
    }

    /**
     * Which of the four credential sets are usable. Never prints a key or a password -
     * this only reports whether one is present.
     */
    protected function credentialSummary(OutreachSetting $settings): string
    {
        $checks = [
            'Places' => $settings->hasPlacesKey(),
            'Search' => $settings->hasSearchKey(),
            'LLM' => $settings->hasLlm(),
            'SMTP' => $settings->smtpConfigured(),
            'IMAP' => $settings->imapConfigured(),
        ];

        $parts = [];
        foreach ($checks as $label => $ok) {
            $parts[] = $label . ' ' . ($ok ? 'OK' : 'missing');
        }

        return implode(', ', $parts);
    }

    /**
     * Which accounts this report covers: the one named by --user, or everyone holding an
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
