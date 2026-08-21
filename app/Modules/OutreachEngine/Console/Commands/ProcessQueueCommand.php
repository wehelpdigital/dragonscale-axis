<?php

namespace App\Modules\OutreachEngine\Console\Commands;

use App\Modules\OutreachEngine\Models\OutreachEmailLog;
use App\Modules\OutreachEngine\Models\OutreachEmailTemplate;
use App\Modules\OutreachEngine\Models\OutreachLead;
use App\Modules\OutreachEngine\Services\OutreachDecisionService;
use App\Modules\OutreachEngine\Services\SettingsResolver;
use App\Modules\OutreachEngine\Services\SmtpMailerService;
use App\Modules\OutreachEngine\Services\TemplateRenderService;
use App\Modules\OutreachEngine\Support\OutreachException;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * The send loop: at most ONE email per account, per invocation.
 *
 * Cron runs this every minute and the decision service decides whether this particular
 * minute may send at all - kill switch, sending day, business hours, daily cap, then a
 * randomised gap since the last send. Sending one at a time is the point: a batch of
 * twenty identical messages leaving in the same second is exactly the pattern that gets
 * a young domain filed as spam.
 */
class ProcessQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'outreach:process-queue
                            {--user= : Only this usersId (default: every user with saved settings)}
                            {--dry-run : Decide and render, but send nothing and write nothing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send the next scheduled outreach email (one per account per run)';

    /** Failed sends a lead absorbs before it stops being offered to the queue. */
    const MAX_CONTACT_ATTEMPTS = 3;

    /** subjectUsed is varchar(500); a rephrased subject is clipped rather than rejected. */
    const SUBJECT_LIMIT = 500;

    /**
     * Execute the console command.
     */
    public function handle(SettingsResolver $resolver)
    {
        try {
            $dryRun = (bool) $this->option('dry-run');

            if ($dryRun) {
                $this->warn('Dry run: no email will be sent and nothing will be written.');
            }

            $userIds = $this->targetUserIds($resolver);

            if (empty($userIds)) {
                $this->warn('No user has saved Lead Finder settings yet - nothing to send.');

                return Command::SUCCESS;
            }

            $sent = 0;

            foreach ($userIds as $userId) {
                if ($this->processUser($resolver, $userId, $dryRun)) {
                    $sent++;
                }
            }

            $this->info($dryRun
                ? 'Dry run finished: ' . $sent . ' account(s) would have sent an email.'
                : 'Send run finished: ' . $sent . ' email(s) sent.');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] outreach:process-queue failed: ' . $e->getMessage());
            $this->error('Send run failed: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Run the decision flow for one account and, if everything says yes, send one email.
     *
     * @return bool True when an email went out (or would have, on a dry run).
     */
    protected function processUser(SettingsResolver $resolver, int $userId, bool $dryRun): bool
    {
        try {
            $settings = $resolver->requireForUser($userId);
        } catch (OutreachException $e) {
            $this->warn('User #' . $userId . ': ' . $e->getMessage());

            return false;
        }

        $decision = new OutreachDecisionService($settings);

        // 1. May this account send right now?
        $window = $decision->evaluateWindow();

        if (!$window['allowed']) {
            $next = $window['nextEligibleAt'] instanceof Carbon
                ? ' Next chance: ' . $window['nextEligibleAt']->format('Y-m-d H:i') . '.'
                : '';

            $this->line(
                'User #' . $userId . ': ' . $window['reason']
                . ' (' . $window['sentToday'] . '/' . $window['cap'] . ' sent today).' . $next
            );

            return false;
        }

        // 2. Who is next in line?
        $lead = $decision->nextLead();

        if (!$lead) {
            $this->line('User #' . $userId . ': window is open but no lead is eligible for a first touch.');

            return false;
        }

        // 3. Re-check the lead itself. nextLead() already asked, but this is the last gate
        //    before money and reputation are spent, and it costs one indexed query.
        $eligibility = $decision->isLeadEligible($lead);

        if (!$eligibility['eligible']) {
            $this->warn('User #' . $userId . ': lead #' . $lead->id . ' is not eligible - ' . $eligibility['reason']);

            return false;
        }

        // 4. Which template? Lowest sendOrder among the ones switched on.
        $template = OutreachEmailTemplate::query()
            ->active()
            ->forUser($userId)
            ->enabled()
            ->ordered()
            ->first();

        if (!$template) {
            // A configuration state, not a crash: the operator simply has not written or
            // switched on a template yet. Say so plainly and let the run finish clean.
            $this->warn(
                'User #' . $userId . ': no active email template. '
                . 'Open Lead Finder > Templates, create one and switch it on.'
            );

            return false;
        }

        // 5. Render, and let the LLM vary the subject and opening if that is enabled.
        $rendered = (new TemplateRenderService($settings))->personalize($template, $lead);
        $subject = mb_substr($rendered['subject'], 0, self::SUBJECT_LIMIT);
        $body = (string) $rendered['body'];

        if ($dryRun) {
            $this->info('User #' . $userId . ': WOULD send now.');
            $this->line('  Lead     : #' . $lead->id . ' ' . $lead->businessName . ' <' . $lead->email . '>');
            $this->line('  Template : #' . $template->id . ' ' . $template->name . ' (sendOrder ' . $template->sendOrder . ')');
            $this->line('  Subject  : ' . $subject);
            $this->line('  Rephrased: ' . ($rendered['rephrased'] ? 'yes' : 'no'));
            $this->line('  Body     : ' . mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($body))), 0, 160));
            $this->line('  Nothing was sent and no log row was written.');

            return true;
        }

        // 6. Send. A missing SMTP configuration is a config state like the missing template
        //    above, so it is caught here rather than becoming a failed log row for a message
        //    that was never even composed.
        $mailer = new SmtpMailerService($settings);

        if (!$mailer->isConfigured()) {
            $this->warn(
                'User #' . $userId . ': SMTP is not configured. '
                . 'Open Lead Finder > Settings and save a host, username, password and From address.'
            );

            return false;
        }

        $result = $mailer->send((string) $lead->email, (string) $lead->businessName, $subject, $body);
        $success = !empty($result['success']);
        $now = Carbon::now('Asia/Manila');

        // 7. Log the attempt either way - the log, not the lead row, is what the daily cap
        //    and the reply threading both read from.
        OutreachEmailLog::create([
            'usersId' => $userId,
            'leadId' => $lead->id,
            'templateId' => $template->id,
            'messageId' => $result['messageId'] ?? null,
            'subjectUsed' => $subject,
            'bodyUsed' => $body,
            'status' => $success ? OutreachEmailLog::STATUS_SENT : OutreachEmailLog::STATUS_FAILED,
            'smtpResponse' => $result['response'] ?? null,
            'errorMessage' => $result['error'] ?? null,
            'aiRephrased' => (bool) $rendered['rephrased'],
            'sentAt' => $success ? $now : null,
            'delete_status' => 'active',
        ]);

        // 8. Move the lead on.
        $attempts = min(255, (int) $lead->contactAttempts + 1);

        if ($success) {
            $lead->update([
                'outreachStatus' => OutreachLead::OUTREACH_CONTACTED,
                'lastContactedAt' => $now,
                'contactAttempts' => $attempts,
            ]);

            $template->markUsed();

            $this->info(
                'User #' . $userId . ': sent to ' . $lead->email . ' (lead #' . $lead->id . ') '
                . 'using template "' . $template->name . '"'
                . ($rendered['rephrased'] ? ' [AI rephrased]' : '') . '.'
            );

            // Report the real gap, which the decision service derives from the log row just
            // written - randomDelayMinutes() would print a different, unrelated number.
            $after = $decision->evaluateWindow();
            if ($after['nextEligibleAt'] instanceof Carbon) {
                $this->line('  Next send no earlier than ' . $after['nextEligibleAt']->format('Y-m-d H:i') . '.');
            }

            return true;
        }

        // A failed send leaves the lead uncontacted so the next tick retries it, but the
        // attempt counter still moves: without that, one permanently unreachable address
        // at the head of the queue would block every lead behind it forever.
        $lead->update([
            'outreachStatus' => $attempts >= self::MAX_CONTACT_ATTEMPTS
                ? OutreachLead::OUTREACH_FAILED
                : $lead->outreachStatus,
            'contactAttempts' => $attempts,
        ]);

        Log::error('[OutreachEngine] Send to lead ' . $lead->id . ' failed: ' . ($result['error'] ?? 'unknown error'));
        $this->error(
            'User #' . $userId . ': send to ' . $lead->email . ' failed (attempt ' . $attempts . ') - '
            . ($result['error'] ?? 'unknown error')
        );

        return false;
    }

    /**
     * Which accounts this run covers: the one named by --user, or everyone holding an
     * active settings row. Each account gets its own single send - the caps, windows and
     * throttles they are paced by are per account.
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
