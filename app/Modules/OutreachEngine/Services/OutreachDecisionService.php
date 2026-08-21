<?php

namespace App\Modules\OutreachEngine\Services;

use App\Modules\OutreachEngine\Models\OutreachEmailLog;
use App\Modules\OutreachEngine\Models\OutreachLead;
use App\Modules\OutreachEngine\Models\OutreachSetting;
use Carbon\Carbon;

/**
 * The cron brain: may we send right now, and to whom?
 *
 * outreach:process-queue runs every minute and asks this class before it touches
 * SMTP. Nothing here sends, writes or throws - it only reads settings and counts
 * rows, which keeps the guard rails cheap to run and easy to unit test.
 *
 * evaluateWindow() returns the FIRST failing check in a fixed order and the reason
 * string is shown to the admin verbatim on the dashboard, so neither the order nor
 * the wording may drift.
 */
class OutreachDecisionService
{
    /**
     * Candidates inspected per status before giving up on finding an eligible lead.
     * Bounds the per-lead queries isLeadEligible() runs inside the loop.
     */
    const CANDIDATE_SCAN_LIMIT = 50;

    // Verbatim dashboard reasons - see the class docblock before editing.
    const REASON_READY = 'Ready to send';
    const REASON_DISABLED = 'Outreach is disabled';
    const REASON_NOT_SENDING_DAY = 'Not a sending day';
    const REASON_OUTSIDE_HOURS = 'Outside business hours';
    const REASON_CAP_REACHED = 'Daily cap reached';
    const REASON_THROTTLED = 'Throttled';

    protected OutreachSetting $settings;

    public function __construct(OutreachSetting $settings)
    {
        $this->settings = $settings;
    }

    /**
     * May the campaign send at this moment?
     *
     * Checks in order, first failure wins: master switch, sending day, business
     * hours, daily cap, per-send throttle.
     *
     * @return array ['allowed'=>bool,'reason'=>string,'sentToday'=>int,'cap'=>int,'nextEligibleAt'=>?Carbon]
     */
    public function evaluateWindow(?Carbon $now = null): array
    {
        $now = $now ? $now->copy()->timezone('Asia/Manila') : Carbon::now('Asia/Manila');

        $cap = $this->settings->effectiveDailyCap();
        $sentToday = $this->sentToday($now);

        if (!$this->settings->outreachEnabled) {
            // No nextEligibleAt: only a human flipping the switch changes this.
            return $this->windowResult(false, self::REASON_DISABLED, $sentToday, $cap, null);
        }

        $sendDays = $this->settings->sendDaysArray();
        if (!in_array((int) $now->isoWeekday(), $sendDays, true)) {
            return $this->windowResult(false, self::REASON_NOT_SENDING_DAY, $sentToday, $cap, $this->nextWindowOpening($now));
        }

        // sendWindowStart / sendWindowEnd are MySQL TIME columns and reach us as raw
        // 'HH:MM:SS' strings, never Carbon. Compare seconds-of-day so only the clock
        // time matters and the calendar date never enters into it.
        $nowSeconds = $now->hour * 3600 + $now->minute * 60 + $now->second;
        $startSeconds = $this->timeOfDaySeconds($this->settings->sendWindowStart, 8 * 3600 + 30 * 60);
        $endSeconds = $this->timeOfDaySeconds($this->settings->sendWindowEnd, 17 * 3600);

        if ($nowSeconds < $startSeconds || $nowSeconds > $endSeconds) {
            return $this->windowResult(false, self::REASON_OUTSIDE_HOURS, $sentToday, $cap, $this->nextWindowOpening($now));
        }

        if ($sentToday >= $cap) {
            // Today is spent; the next chance is the next sending day's opening.
            return $this->windowResult(false, self::REASON_CAP_REACHED, $sentToday, $cap, $this->nextWindowOpening($now));
        }

        $throttledUntil = $this->throttledUntil();
        if ($throttledUntil !== null && $now->lessThan($throttledUntil)) {
            return $this->windowResult(false, self::REASON_THROTTLED, $sentToday, $cap, $throttledUntil);
        }

        return $this->windowResult(true, self::REASON_READY, $sentToday, $cap, null);
    }

    /**
     * The next lead worth a first touch, or null when the list is exhausted.
     *
     * Leads already marked 'queued' go first - something has already decided they
     * are next - then the oldest uncontacted ones.
     */
    public function nextLead(): ?OutreachLead
    {
        $userId = (int) $this->settings->usersId;

        if ($userId <= 0) {
            return null;
        }

        foreach ([OutreachLead::OUTREACH_QUEUED, OutreachLead::OUTREACH_UNCONTACTED] as $status) {
            $candidates = OutreachLead::query()
                ->active()
                ->forUser($userId)
                ->hasEmail()
                ->where('outreachStatus', $status)
                ->orderBy('id')
                ->limit(self::CANDIDATE_SCAN_LIMIT)
                ->get();

            foreach ($candidates as $lead) {
                if ($this->isLeadEligible($lead)['eligible']) {
                    return $lead;
                }
            }
        }

        return null;
    }

    /**
     * Per-lead guard rails - the last thing checked before a send.
     *
     * @return array ['eligible'=>bool,'reason'=>string]
     */
    public function isLeadEligible(OutreachLead $lead): array
    {
        if ($lead->delete_status !== 'active') {
            return ['eligible' => false, 'reason' => 'Lead has been deleted'];
        }

        if (empty($lead->email)) {
            return ['eligible' => false, 'reason' => 'Lead has no email address'];
        }

        if (!$lead->hasValidEmail()) {
            return ['eligible' => false, 'reason' => 'Lead email is not a valid address'];
        }

        switch ($lead->outreachStatus) {
            case OutreachLead::OUTREACH_REPLIED:
                return ['eligible' => false, 'reason' => 'Lead already replied'];
            case OutreachLead::OUTREACH_UNSUBSCRIBED:
                return ['eligible' => false, 'reason' => 'Lead unsubscribed'];
            case OutreachLead::OUTREACH_BOUNCED:
                return ['eligible' => false, 'reason' => 'Lead address bounced'];
        }

        if (!in_array($lead->outreachStatus, [OutreachLead::OUTREACH_UNCONTACTED, OutreachLead::OUTREACH_QUEUED], true)) {
            return ['eligible' => false, 'reason' => 'Lead is not awaiting a first touch'];
        }

        // The status can lag behind reality if a previous run died between the send
        // and the status update, so the log is the authority on "already contacted".
        $alreadySent = OutreachEmailLog::query()
            ->active()
            ->forUser((int) $lead->usersId)
            ->where('leadId', $lead->id)
            ->sent()
            ->exists();

        if ($alreadySent) {
            return ['eligible' => false, 'reason' => 'Lead already received an email'];
        }

        return ['eligible' => true, 'reason' => 'Eligible'];
    }

    /**
     * A random gap in minutes between sends, so the pattern never looks automated.
     */
    public function randomDelayMinutes(): int
    {
        $min = max(0, (int) $this->settings->minDelayMinutes);
        $max = max($min, (int) $this->settings->maxDelayMinutes);

        try {
            return random_int($min, $max);
        } catch (\Throwable $e) {
            // random_int only fails when the platform has no CSPRNG; any delay beats none.
            return $min;
        }
    }

    /**
     * Emails this user actually sent today, Asia/Manila. The daily cap counts these.
     */
    public function sentToday(?Carbon $now = null): int
    {
        $userId = (int) $this->settings->usersId;

        if ($userId <= 0) {
            return 0;
        }

        $now = $now ? $now->copy()->timezone('Asia/Manila') : Carbon::now('Asia/Manila');

        return OutreachEmailLog::query()
            ->active()
            ->forUser($userId)
            ->sent()
            ->whereDate('sentAt', $now->toDateString())
            ->count();
    }

    // ==================== INTERNALS ====================

    /**
     * Shape one evaluateWindow() answer.
     */
    private function windowResult(bool $allowed, string $reason, int $sentToday, int $cap, ?Carbon $nextEligibleAt): array
    {
        return [
            'allowed' => $allowed,
            'reason' => $reason,
            'sentToday' => $sentToday,
            'cap' => $cap,
            'nextEligibleAt' => $nextEligibleAt,
        ];
    }

    /**
     * When the throttle after the last send expires, or null when nothing was sent yet.
     *
     * The gap is derived from the last log id rather than stored anywhere: the same
     * id always yields the same delay, so two cron ticks a second apart agree, yet
     * the interval still varies from send to send.
     */
    private function throttledUntil(): ?Carbon
    {
        $userId = (int) $this->settings->usersId;

        if ($userId <= 0) {
            return null;
        }

        $lastSent = OutreachEmailLog::query()
            ->active()
            ->forUser($userId)
            ->sent()
            ->whereNotNull('sentAt')
            ->orderByDesc('sentAt')
            ->orderByDesc('id')
            ->first();

        if (!$lastSent || empty($lastSent->sentAt)) {
            return null;
        }

        $min = max(0, (int) $this->settings->minDelayMinutes);
        $max = max($min, (int) $this->settings->maxDelayMinutes);
        $spread = max(1, $max - $min + 1);
        $delay = $min + ((int) $lastSent->id % $spread);

        return Carbon::parse($lastSent->sentAt)->timezone('Asia/Manila')->addMinutes($delay);
    }

    /**
     * The next moment the window opens, searching today plus the coming week.
     *
     * Covers every "come back later" case: before today's start, after today's end,
     * a non-sending day, and a cap that is already spent.
     */
    private function nextWindowOpening(Carbon $now): ?Carbon
    {
        $sendDays = $this->settings->sendDaysArray();

        if (empty($sendDays)) {
            return null;
        }

        $startSeconds = $this->timeOfDaySeconds($this->settings->sendWindowStart, 8 * 3600 + 30 * 60);

        for ($offset = 0; $offset <= 7; $offset++) {
            $candidate = $now->copy()->addDays($offset)->startOfDay()->addSeconds($startSeconds);

            if (!in_array((int) $candidate->isoWeekday(), $sendDays, true)) {
                continue;
            }

            if ($candidate->greaterThan($now)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * A raw 'HH:MM:SS' TIME column as seconds past midnight.
     *
     * @param  mixed  $raw
     */
    private function timeOfDaySeconds($raw, int $fallback): int
    {
        $raw = trim((string) $raw);

        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $raw, $matches)) {
            $hours = min(23, (int) $matches[1]);
            $minutes = min(59, (int) $matches[2]);
            $seconds = isset($matches[3]) ? min(59, (int) $matches[3]) : 0;

            return $hours * 3600 + $minutes * 60 + $seconds;
        }

        return $fallback;
    }
}
