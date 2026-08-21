<?php

namespace App\Modules\OutreachEngine\Services;

use App\Modules\OutreachEngine\Models\OutreachEmailLog;
use App\Modules\OutreachEngine\Models\OutreachInboundMessage;
use App\Modules\OutreachEngine\Models\OutreachLead;
use App\Modules\OutreachEngine\Models\OutreachSetting;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

/**
 * Turns raw mailbox traffic into campaign decisions.
 *
 * run() is the cron path (outreach:fetch-replies, every five minutes): pull unseen
 * mail, match each message to a lead, store it, and auto-stop the campaign for that
 * lead. ingest() is the same store-and-decide half on its own, so the inbound
 * webhook can feed an already-parsed message through identical logic.
 *
 * De-duplication rides on the (usersId, messageUid) unique index rather than a
 * SELECT: the poller reads with BODY.PEEK[] and therefore sees the same UIDs on
 * every run, and two overlapping cron ticks would both pass a "does it exist?"
 * check before either inserted. Catching the unique violation cannot race.
 */
class InboundProcessor
{
    /** Longest body text/html we store per message - well under max_allowed_packet. */
    const MAX_BODY_CHARS = 500000;

    /** References headers can list dozens of ids; only the newest handful can plausibly match. */
    const MAX_THREAD_CANDIDATES = 20;

    protected OutreachSetting $settings;

    protected ImapClientService $imap;

    public function __construct(OutreachSetting $settings, ImapClientService $imap)
    {
        $this->settings = $settings;
        $this->imap = $imap;
    }

    /**
     * Poll the mailbox and process everything unseen.
     *
     * @return array ['fetched'=>int,'stored'=>int,'matched'=>int,'bounces'=>int,'error'=>?string]
     */
    public function run(int $userId, int $limit = 50): array
    {
        $result = ['fetched' => 0, 'stored' => 0, 'matched' => 0, 'bounces' => 0, 'error' => null];

        if ($userId <= 0) {
            $result['error'] = 'No user was given for the inbound run.';

            return $result;
        }

        $settingsUser = (int) $this->settings->usersId;
        if ($settingsUser > 0 && $settingsUser !== $userId) {
            // Mail would land under the wrong owner; refuse rather than cross the streams.
            Log::error('[OutreachEngine] Inbound run for user ' . $userId . ' was handed settings for user ' . $settingsUser . '.');
            $result['error'] = 'The IMAP settings do not belong to this user.';

            return $result;
        }

        if (!$this->settings->imapConfigured()) {
            $result['error'] = 'IMAP is not configured.';

            return $result;
        }

        try {
            $messages = $this->imap->fetchUnseen($limit);
            $result['fetched'] = count($messages);

            // fetchUnseen() returns [] for both "empty mailbox" and "server refused us",
            // so the client's own error is the only way to report the second.
            $imapError = $this->imap->lastError();
            if ($imapError !== null) {
                $result['error'] = $imapError;
            }

            foreach ($messages as $message) {
                $row = $this->ingest($userId, $message);

                if (!$row) {
                    continue;
                }

                $result['stored']++;

                if (!empty($row->leadId)) {
                    $result['matched']++;
                }

                if ($row->isBounce) {
                    $result['bounces']++;
                }
            }
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] Inbound run failed for user ' . $userId . ': ' . $e->getMessage());
            $result['error'] = 'Inbound processing failed: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Store one already-parsed message, match it to a lead and apply the auto-stop.
     *
     * Returns null when the message is unusable or was already stored on an earlier
     * poll - neither is an error worth failing the whole run over.
     *
     * @param  array<string,mixed>  $message  Shape produced by ImapClientService::fetchUnseen()
     */
    public function ingest(int $userId, array $message): ?OutreachInboundMessage
    {
        $senderEmail = strtolower(trim((string) ($message['from'] ?? '')));

        if ($senderEmail === '') {
            Log::warning('[OutreachEngine] Skipped an inbound message with no sender address (uid ' . (string) ($message['uid'] ?? '') . ').');

            return null;
        }

        $isBounce = !empty($message['isBounce']);
        $direction = ($message['direction'] ?? '') === OutreachInboundMessage::DIRECTION_OUTBOUND
            ? OutreachInboundMessage::DIRECTION_OUTBOUND
            : OutreachInboundMessage::DIRECTION_INBOUND;

        $threadIds = $this->threadCandidates($message);
        $matchedLog = null;
        $lead = $this->matchLead($userId, $senderEmail, $threadIds, $matchedLog);

        $uid = trim((string) ($message['uid'] ?? ''));

        $payload = [
            'usersId' => $userId,
            'leadId' => $lead ? $lead->id : null,
            'uidValidity' => $this->clip($message['uidValidity'] ?? null, 60),
            // A null UID keeps a row out of the unique index - the webhook has no UID
            // to offer and must never collide with a polled message.
            'messageUid' => $uid !== '' ? $this->clip($uid, 120) : null,
            'messageId' => $this->clip($message['messageId'] ?? null, 255),
            'inReplyTo' => $this->clip($message['inReplyTo'] ?? null, 255),
            'senderEmail' => $this->clip($senderEmail, 255),
            'senderName' => $this->clip($message['fromName'] ?? null, 255),
            'subject' => $this->clip($message['subject'] ?? null, 500),
            'bodyText' => $this->clip($message['text'] ?? null, self::MAX_BODY_CHARS),
            'bodyHtml' => $this->clip($message['html'] ?? null, self::MAX_BODY_CHARS),
            'direction' => $direction,
            'isBounce' => $isBounce,
            'isReplied' => false,
            'receivedAt' => $this->receivedAt($message['date'] ?? null),
            'delete_status' => 'active',
        ];

        try {
            $row = OutreachInboundMessage::create($payload);
        } catch (QueryException $e) {
            if ($this->isDuplicate($e)) {
                // Already stored on an earlier poll. Expected, not an error.
                return null;
            }

            Log::error('[OutreachEngine] Could not store inbound message from ' . $senderEmail . ': ' . $e->getMessage());

            return null;
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] Could not store inbound message from ' . $senderEmail . ': ' . $e->getMessage());

            return null;
        }

        if ($lead && $direction === OutreachInboundMessage::DIRECTION_INBOUND) {
            $this->applyAutoStop($lead, $isBounce, $matchedLog);
        }

        return $row;
    }

    // ==================== MATCHING ====================

    /**
     * Find the lead a message belongs to: sender address first, then the thread.
     *
     * @param  array<int,string>  $threadIds
     * @param  OutreachEmailLog|null  $matchedLog  Set to the log the thread matched, for the bounce path.
     */
    private function matchLead(int $userId, string $senderEmail, array $threadIds, ?OutreachEmailLog &$matchedLog): ?OutreachLead
    {
        $matchedLog = null;

        $lead = OutreachLead::query()
            ->active()
            ->forUser($userId)
            ->where('email', $senderEmail)
            ->orderBy('id')
            ->first();

        if ($lead) {
            return $lead;
        }

        if (empty($threadIds)) {
            return null;
        }

        // A bounce arrives from the MTA, not from the lead, so the only link back is
        // the Message-ID we set at send time, echoed in In-Reply-To or References.
        $log = OutreachEmailLog::query()
            ->active()
            ->forUser($userId)
            ->whereIn('messageId', $threadIds)
            ->orderByDesc('id')
            ->first();

        if (!$log) {
            return null;
        }

        $matchedLog = $log;

        return OutreachLead::query()
            ->active()
            ->forUser($userId)
            ->where('id', $log->leadId)
            ->first();
    }

    /**
     * Message-IDs from In-Reply-To and References, with and without angle brackets.
     *
     * Both forms are tried because the send path decides how it stores its own
     * Message-ID and this side must match either way.
     *
     * @param  array<string,mixed>  $message
     * @return array<int,string>
     */
    private function threadCandidates(array $message): array
    {
        $raw = trim((string) ($message['inReplyTo'] ?? '')) . ' ' . trim((string) ($message['references'] ?? ''));

        if (trim($raw) === '') {
            return [];
        }

        $tokens = [];

        if (preg_match_all('/<([^<>]+)>/', $raw, $matches)) {
            foreach ($matches[1] as $token) {
                $tokens[] = trim($token);
            }
        }

        // Our own parser strips the brackets from In-Reply-To before it gets here.
        foreach (preg_split('/\s+/', $raw) as $piece) {
            $piece = trim((string) $piece, " \t<>,;");
            if ($piece !== '' && strpos($piece, '@') !== false) {
                $tokens[] = $piece;
            }
        }

        $tokens = array_values(array_unique(array_filter($tokens, 'strlen')));

        // The newest ids sit at the end of a References chain; keep those.
        if (count($tokens) > self::MAX_THREAD_CANDIDATES) {
            $tokens = array_slice($tokens, -self::MAX_THREAD_CANDIDATES);
        }

        $candidates = [];
        foreach ($tokens as $token) {
            $candidates[] = $token;
            $candidates[] = '<' . $token . '>';
        }

        return array_values(array_unique($candidates));
    }

    // ==================== AUTO-STOP ====================

    /**
     * A reply or a bounce closes the lead and cancels whatever is still queued for it.
     *
     * This is the whole point of polling: nothing must go out to somebody who has
     * already answered, and nothing must keep going to an address that does not exist.
     */
    private function applyAutoStop(OutreachLead $lead, bool $isBounce, ?OutreachEmailLog $matchedLog): void
    {
        try {
            if ($isBounce) {
                // A real reply outranks a later bounce notification - do not overwrite it.
                if (!in_array($lead->outreachStatus, [OutreachLead::OUTREACH_REPLIED, OutreachLead::OUTREACH_UNSUBSCRIBED], true)) {
                    $lead->update(['outreachStatus' => OutreachLead::OUTREACH_BOUNCED]);
                }

                $this->markLogBounced($lead, $matchedLog);

                return;
            }

            if ($lead->outreachStatus !== OutreachLead::OUTREACH_UNSUBSCRIBED) {
                $lead->update([
                    'outreachStatus' => OutreachLead::OUTREACH_REPLIED,
                    'repliedAt' => Carbon::now('Asia/Manila'),
                ]);
            }

            OutreachEmailLog::query()
                ->active()
                ->forUser((int) $lead->usersId)
                ->where('leadId', $lead->id)
                ->queued()
                ->update([
                    'status' => OutreachEmailLog::STATUS_FAILED,
                    'errorMessage' => 'Cancelled: lead replied',
                ]);
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] Auto-stop failed for lead ' . $lead->id . ': ' . $e->getMessage());
        }
    }

    /**
     * Flag the send this bounce refers to - the threaded one when we know it, else
     * the most recent successful send to that lead.
     */
    private function markLogBounced(OutreachLead $lead, ?OutreachEmailLog $matchedLog): void
    {
        $log = $matchedLog;

        if (!$log) {
            $log = OutreachEmailLog::query()
                ->active()
                ->forUser((int) $lead->usersId)
                ->where('leadId', $lead->id)
                ->sent()
                ->orderByDesc('sentAt')
                ->orderByDesc('id')
                ->first();
        }

        if (!$log || $log->status === OutreachEmailLog::STATUS_BOUNCED) {
            return;
        }

        $log->update(['status' => OutreachEmailLog::STATUS_BOUNCED]);
    }

    // ==================== HELPERS ====================

    /**
     * Is this the (usersId, messageUid) unique index rejecting a message we already have?
     */
    private function isDuplicate(QueryException $e): bool
    {
        if ((string) $e->getCode() === '23000') {
            return true;
        }

        $message = $e->getMessage();

        return strpos($message, 'outreach_inbound_user_uid_unique') !== false
            || strpos($message, 'Duplicate entry') !== false
            || strpos($message, '1062') !== false;
    }

    /**
     * The message's own Date header when it parses, otherwise the moment we read it.
     *
     * @param  mixed  $raw
     */
    private function receivedAt($raw): Carbon
    {
        $raw = trim((string) $raw);

        if ($raw !== '') {
            try {
                return Carbon::parse($raw)->timezone('Asia/Manila');
            } catch (\Throwable $e) {
                // A malformed Date header is not worth losing the message over.
                Log::warning('[OutreachEngine] Unparseable inbound date "' . $raw . '"; using the current time.');
            }
        }

        return Carbon::now('Asia/Manila');
    }

    /**
     * Trim a value to its column width, or null when there is nothing to store.
     *
     * @param  mixed  $value
     */
    private function clip($value, int $length): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return mb_strlen($value) > $length ? mb_substr($value, 0, $length) : $value;
    }
}
