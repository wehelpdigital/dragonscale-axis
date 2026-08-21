<?php

namespace App\Modules\OutreachEngine\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\OutreachEngine\Models\OutreachEmailLog;
use App\Modules\OutreachEngine\Models\OutreachInboundMessage;
use App\Modules\OutreachEngine\Models\OutreachLead;
use App\Modules\OutreachEngine\Models\OutreachSetting;
use App\Modules\OutreachEngine\Services\ImapClientService;
use App\Modules\OutreachEngine\Services\InboundProcessor;
use App\Modules\OutreachEngine\Services\SettingsResolver;
use App\Modules\OutreachEngine\Services\SmtpMailerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * The reply inbox: one thread per lead, merged from what we sent
 * (outreach_email_logs) and everything that came back or went out afterwards
 * (outreach_inbound_messages, which stores both directions).
 *
 * Inbound HTML is never handed to the browser as markup. A reply arrives from a
 * stranger, and rendering their HTML inside an authenticated admin page is a
 * stored-XSS invitation, so every inbound body is flattened to text on the way out and
 * the view prints it as text. Our own sent copy is ours, and stays HTML.
 *
 * webhook() is the one unauthenticated action in the module. It carries no session and
 * no CSRF token, so it proves itself with a shared secret derived from the settings row
 * id and the app key - no new column, nothing extra to leak.
 */
class InboxController extends Controller
{
    /** Threads listed in one page of the sidebar. */
    const MAX_THREADS = 200;

    /** Messages pulled per side when building one conversation. */
    const MAX_THREAD_ENTRIES = 200;

    /** Longest body returned to the browser per entry - a mail loop can produce enormous text. */
    const MAX_BODY_CHARS = 40000;

    /** Largest webhook payload accepted, in bytes. Anything larger is refused unread. */
    const MAX_WEBHOOK_BYTES = 262144;

    /** Namespace for the webhook HMAC, so the digest cannot be reused for anything else. */
    const WEBHOOK_SECRET_PREFIX = 'outreach-webhook:';

    /** Subject lines that mean "this never reached a human". */
    const BOUNCE_SUBJECT_PATTERN = '/undeliverable|delivery status notification|returned mail|delivery has failed|mail delivery failed|failure notice/i';

    /** @var \App\Modules\OutreachEngine\Services\SettingsResolver */
    protected $resolver;

    public function __construct(SettingsResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * The shared secret for one settings row, or null when the row was never saved.
     *
     * Derived rather than stored: the app key already is a secret, the settings id is
     * already unique per account, and an HMAC of the two needs no extra column and
     * cannot be read out of the database on its own.
     */
    public static function webhookToken(?OutreachSetting $settings): ?string
    {
        if (!$settings || empty($settings->id)) {
            return null;
        }

        return hash_hmac('sha256', self::WEBHOOK_SECRET_PREFIX . $settings->id, (string) config('app.key'));
    }

    /**
     * The full webhook URL for the settings screen's copy box, or null before the first
     * save. The settings id travels in the query string so the endpoint can look up one
     * row instead of hashing its way through every account.
     */
    public static function webhookUrl(?OutreachSetting $settings): ?string
    {
        $token = self::webhookToken($settings);

        if ($token === null) {
            return null;
        }

        return route('outreach.inbox.webhook') . '?s=' . $settings->id . '&token=' . $token;
    }

    /**
     * Render the inbox screen. The thread list and conversations load over AJAX.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $userId = (int) Auth::id();
        $settings = $this->resolver->forUser($userId);

        $unreadCount = OutreachInboundMessage::query()
            ->active()
            ->forUser($userId)
            ->unread()
            ->count();

        return view('outreach::inbox', [
            'settings' => $settings,
            'unreadCount' => $unreadCount,
            'imapConfigured' => $settings->imapConfigured(),
            'smtpConfigured' => $settings->smtpConfigured(),
            'openLeadId' => $request->filled('lead') ? (int) $request->query('lead') : null,
        ]);
    }

    /**
     * Thread list: every lead that has messages, newest conversation first.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function threads(Request $request)
    {
        try {
            $userId = (int) Auth::id();

            $aggregates = OutreachInboundMessage::query()
                ->active()
                ->forUser($userId)
                ->whereNotNull('leadId')
                ->selectRaw(
                    'leadId,'
                    . ' COUNT(*) as messageCount,'
                    . ' SUM(CASE WHEN direction = ? AND readAt IS NULL THEN 1 ELSE 0 END) as unreadCount,'
                    . ' MAX(CASE WHEN isBounce = 1 THEN 1 ELSE 0 END) as hasBounce,'
                    . ' MAX(COALESCE(receivedAt, created_at)) as lastMessageAt',
                    [OutreachInboundMessage::DIRECTION_INBOUND]
                )
                ->groupBy('leadId')
                ->orderByDesc('lastMessageAt')
                ->limit(self::MAX_THREADS)
                ->get();

            if ($aggregates->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No conversations yet.',
                    'data' => [
                        'threads' => [],
                        'unreadTotal' => 0,
                        'unmatchedCount' => $this->unmatchedCount($userId),
                    ],
                ]);
            }

            $leadIds = $aggregates->pluck('leadId')->map(function ($id) {
                return (int) $id;
            })->all();

            $leads = OutreachLead::query()
                ->active()
                ->forUser($userId)
                ->whereIn('id', $leadIds)
                ->get()
                ->keyBy('id');

            $snippets = $this->latestSnippets($userId, $leadIds);

            $onlyUnread = $request->boolean('unreadOnly');
            $search = trim((string) $request->query('search', ''));

            $threads = [];
            $unreadTotal = 0;

            foreach ($aggregates as $row) {
                $leadId = (int) $row->leadId;
                $lead = $leads->get($leadId);

                // The lead was soft deleted after its messages arrived - the conversation has
                // nothing left to attach to, so it drops out of the list.
                if (!$lead) {
                    continue;
                }

                $unread = (int) $row->unreadCount;
                $unreadTotal += $unread;

                if ($onlyUnread && $unread === 0) {
                    continue;
                }

                if ($search !== '' && !$this->leadMatchesSearch($lead, $search)) {
                    continue;
                }

                $lastMessageAt = $this->toCarbon($row->lastMessageAt);
                $snippet = $snippets[$leadId] ?? null;

                $threads[] = [
                    'leadId' => $leadId,
                    'businessName' => (string) $lead->businessName,
                    'email' => (string) $lead->email,
                    'location' => $lead->display_location,
                    'outreachStatus' => (string) $lead->outreachStatus,
                    'statusBadge' => $lead->outreach_status_badge,
                    'unreadCount' => $unread,
                    'messageCount' => (int) $row->messageCount,
                    'hasBounce' => (bool) $row->hasBounce,
                    'lastMessageAt' => $lastMessageAt ? $lastMessageAt->format('Y-m-d H:i:s') : null,
                    'lastMessageLabel' => $lastMessageAt ? $this->humanTime($lastMessageAt) : '',
                    'lastDirection' => $snippet['direction'] ?? OutreachInboundMessage::DIRECTION_INBOUND,
                    'subject' => $snippet['subject'] ?? '',
                    'snippet' => $snippet['snippet'] ?? '',
                ];
            }

            return response()->json([
                'success' => true,
                'message' => count($threads) . ' ' . (count($threads) === 1 ? 'conversation' : 'conversations') . '.',
                'data' => [
                    'threads' => $threads,
                    'unreadTotal' => $unreadTotal,
                    'unmatchedCount' => $this->unmatchedCount($userId),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] Loading inbox threads failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading your conversations.',
            ], 500);
        }
    }

    /**
     * One conversation, oldest first: our sends interleaved with everything that came
     * back and every quick reply we typed here.
     *
     * Read-only on purpose. Opening a thread does not mark it read - that is what the
     * markRead endpoint is for, and a GET should not change state.
     *
     * @param  int  $leadId
     * @return \Illuminate\Http\JsonResponse
     */
    public function thread($leadId)
    {
        try {
            $userId = (int) Auth::id();
            $lead = $this->findOwnedLead((int) $leadId, $userId);

            if (!$lead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lead not found.',
                ], 404);
            }

            $logs = OutreachEmailLog::query()
                ->active()
                ->forUser($userId)
                ->where('leadId', $lead->id)
                ->orderByDesc('id')
                ->limit(self::MAX_THREAD_ENTRIES)
                ->get();

            $messages = OutreachInboundMessage::query()
                ->active()
                ->forUser($userId)
                ->where('leadId', $lead->id)
                ->orderByDesc('id')
                ->limit(self::MAX_THREAD_ENTRIES)
                ->get();

            // Resolved once: the From identity is the same for every entry, and this is a
            // database read that has no business being inside the loop.
            $settings = $this->resolver->forUser($userId);
            $fromEmail = (string) $settings->smtpFromEmail;
            $fromName = (string) $settings->smtpFromName;

            $entries = [];

            foreach ($logs as $log) {
                // A queued log has not left yet; sentAt is null, so created_at is the only
                // honest position for it on the timeline.
                $when = $log->sentAt ?: $log->created_at;

                $entries[] = [
                    'type' => 'sent',
                    'direction' => OutreachInboundMessage::DIRECTION_OUTBOUND,
                    'id' => 'log-' . $log->id,
                    'logId' => (int) $log->id,
                    'subject' => (string) $log->subjectUsed,
                    'isHtml' => true,
                    'body' => $this->clip((string) $log->bodyUsed, self::MAX_BODY_CHARS),
                    'bodyPlain' => $this->plainBody((string) $log->bodyUsed),
                    'from' => $fromEmail,
                    'fromName' => $fromName,
                    'status' => (string) $log->status,
                    'statusBadge' => $log->status_badge,
                    'isBounce' => false,
                    'aiRephrased' => (bool) $log->aiRephrased,
                    'errorMessage' => $log->errorMessage ? mb_substr((string) $log->errorMessage, 0, 500) : null,
                    'at' => $when ? $when->format('Y-m-d H:i:s') : null,
                    'atLabel' => $when ? $this->humanTime($when) : '',
                    'sortKey' => $when ? $when->getTimestamp() : 0,
                ];
            }

            foreach ($messages as $message) {
                $when = $message->receivedAt ?: $message->created_at;
                $isOutbound = $message->direction === OutreachInboundMessage::DIRECTION_OUTBOUND;

                // Plenty of senders ship HTML only, so the text part is preferred and the
                // HTML one flattened when it is all there is.
                $source = trim((string) $message->bodyText) !== ''
                    ? (string) $message->bodyText
                    : (string) $message->bodyHtml;
                $plain = $this->plainBody($source);

                // Our own quick replies are HTML we wrote; anything inbound is flattened.
                $body = $isOutbound
                    ? $this->clip((string) $message->bodyHtml, self::MAX_BODY_CHARS)
                    : $plain;

                $entries[] = [
                    'type' => $isOutbound ? 'reply' : 'received',
                    'direction' => $isOutbound
                        ? OutreachInboundMessage::DIRECTION_OUTBOUND
                        : OutreachInboundMessage::DIRECTION_INBOUND,
                    'id' => 'msg-' . $message->id,
                    'messageRowId' => (int) $message->id,
                    'subject' => (string) $message->subject,
                    'isHtml' => $isOutbound,
                    'body' => $body,
                    'bodyPlain' => $plain,
                    'from' => (string) $message->senderEmail,
                    'fromName' => (string) $message->senderName,
                    'status' => $isOutbound ? 'sent' : 'received',
                    'statusBadge' => $message->direction_badge,
                    'isBounce' => (bool) $message->isBounce,
                    'aiRephrased' => false,
                    'errorMessage' => null,
                    'at' => $when ? $when->format('Y-m-d H:i:s') : null,
                    'atLabel' => $when ? $this->humanTime($when) : '',
                    'sortKey' => $when ? $when->getTimestamp() : 0,
                ];
            }

            // Chronological. Equal timestamps fall back to the entry id so a send and the
            // reply it triggered in the same second keep a stable order.
            usort($entries, function ($a, $b) {
                if ($a['sortKey'] === $b['sortKey']) {
                    return strcmp($a['id'], $b['id']);
                }

                return $a['sortKey'] < $b['sortKey'] ? -1 : 1;
            });

            return response()->json([
                'success' => true,
                'message' => count($entries) . ' ' . (count($entries) === 1 ? 'message' : 'messages') . '.',
                'data' => [
                    'lead' => [
                        'id' => (int) $lead->id,
                        'businessName' => (string) $lead->businessName,
                        'email' => (string) $lead->email,
                        'phone' => (string) $lead->phone,
                        'website' => (string) $lead->website,
                        'location' => $lead->display_location,
                        'outreachStatus' => (string) $lead->outreachStatus,
                        'statusBadge' => $lead->outreach_status_badge,
                    ],
                    'entries' => $entries,
                    'canReply' => $settings->smtpConfigured() && $lead->hasValidEmail(),
                    'replySubject' => $this->suggestedReplySubject($messages, $logs),
                    'unreadCount' => $messages->filter(function ($message) {
                        return $message->isUnread();
                    })->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] Loading thread ' . $leadId . ' failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the conversation.',
            ], 500);
        }
    }

    /**
     * Send a quick reply into a thread.
     *
     * The reply is recorded as an OUTBOUND outreach_inbound_messages row, not as an
     * email log: the daily cap counts sent logs, and a human answering a prospect must
     * not eat into the campaign's automated quota.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reply(Request $request)
    {
        try {
            $userId = (int) Auth::id();

            $validator = Validator::make($request->all(), [
                'leadId' => 'required|integer|min:1',
                'subject' => 'required|string|max:500',
                'body' => 'required|string|max:200000',
            ], [
                'leadId.required' => 'No conversation was selected.',
                'subject.required' => 'A subject line is required.',
                'body.required' => 'The reply cannot be empty.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            $lead = $this->findOwnedLead((int) $request->input('leadId'), $userId);

            if (!$lead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lead not found.',
                ], 404);
            }

            if (!$lead->hasValidEmail()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This lead has no valid email address to reply to.',
                ], 422);
            }

            $settings = $this->resolver->forUser($userId);

            if (!$settings->smtpConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'SMTP is not configured. Add the host, username, password and From address in Settings first.',
                ], 422);
            }

            $subject = trim((string) $request->input('subject'));
            $bodyHtml = trim((string) $request->input('body'));

            // Threading: answer the newest thing they actually sent us, so the reply lands
            // inside the existing conversation in their mail client.
            $inReplyTo = $this->latestThreadReference($userId, (int) $lead->id);

            $mailer = new SmtpMailerService($settings);
            $sent = $mailer->send(
                (string) $lead->email,
                (string) $lead->businessName,
                $subject,
                $bodyHtml,
                $inReplyTo
            );

            if (empty($sent['success'])) {
                Log::warning('[OutreachEngine] Reply to lead ' . $lead->id . ' failed: ' . (string) ($sent['error'] ?? ''));

                return response()->json([
                    'success' => false,
                    'message' => (string) ($sent['error'] ?: 'The reply could not be sent.'),
                ], 422);
            }

            $now = Carbon::now('Asia/Manila');

            $row = OutreachInboundMessage::create([
                'usersId' => $userId,
                'leadId' => $lead->id,
                'uidValidity' => null,
                // No UID: a null keeps our own replies out of unique(usersId, messageUid),
                // which belongs to the IMAP poller.
                'messageUid' => null,
                'messageId' => $sent['messageId'] ?? null,
                'inReplyTo' => $inReplyTo,
                'senderEmail' => (string) $settings->smtpFromEmail,
                'senderName' => (string) $settings->smtpFromName,
                'subject' => $subject,
                'bodyText' => $this->plainBody($bodyHtml),
                'bodyHtml' => $bodyHtml,
                'direction' => OutreachInboundMessage::DIRECTION_OUTBOUND,
                'isBounce' => false,
                // Our own message is read by definition; leaving readAt null would inflate
                // the unread badge with our own words.
                'readAt' => $now,
                'isReplied' => false,
                'receivedAt' => $now,
                'delete_status' => 'active',
            ]);

            // Everything they sent has now been answered, and seen.
            OutreachInboundMessage::query()
                ->active()
                ->forUser($userId)
                ->where('leadId', $lead->id)
                ->inbound()
                ->update(['isReplied' => true]);

            OutreachInboundMessage::query()
                ->active()
                ->forUser($userId)
                ->where('leadId', $lead->id)
                ->inbound()
                ->whereNull('readAt')
                ->update(['readAt' => $now]);

            return response()->json([
                'success' => true,
                'message' => 'Reply sent to ' . $lead->email . '.',
                'data' => [
                    'messageRowId' => (int) $row->id,
                    'messageId' => $sent['messageId'] ?? null,
                    'leadId' => (int) $lead->id,
                    'at' => $now->format('Y-m-d H:i:s'),
                    'atLabel' => $this->humanTime($now),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] Sending a reply failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending the reply.',
            ], 500);
        }
    }

    /**
     * Poll the mailbox now instead of waiting for the five-minute cron.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchNow(Request $request)
    {
        try {
            $userId = (int) Auth::id();
            $settings = $this->resolver->forUser($userId);

            if (!$settings->imapConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'IMAP is not configured. Add the host, username and password in Settings first.',
                ], 422);
            }

            $limit = (int) $request->input('limit', 50);
            $limit = max(1, min($limit, 200));

            $processor = new InboundProcessor($settings, new ImapClientService($settings));
            $result = $processor->run($userId, $limit);

            if (!empty($result['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => (string) $result['error'],
                    'data' => $result,
                ], 422);
            }

            $stored = (int) ($result['stored'] ?? 0);

            return response()->json([
                'success' => true,
                'message' => $stored === 0
                    ? 'Mailbox checked - nothing new.'
                    : $stored . ' new ' . ($stored === 1 ? 'message' : 'messages') . ' stored.',
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] Manual inbox fetch failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking the mailbox.',
            ], 500);
        }
    }

    /**
     * Mark one lead's inbound messages as read.
     *
     * @param  int  $leadId
     * @return \Illuminate\Http\JsonResponse
     */
    public function markRead($leadId)
    {
        try {
            $userId = (int) Auth::id();
            $lead = $this->findOwnedLead((int) $leadId, $userId);

            if (!$lead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lead not found.',
                ], 404);
            }

            $marked = OutreachInboundMessage::query()
                ->active()
                ->forUser($userId)
                ->where('leadId', $lead->id)
                ->unread()
                ->update(['readAt' => Carbon::now('Asia/Manila')]);

            $unreadTotal = OutreachInboundMessage::query()
                ->active()
                ->forUser($userId)
                ->unread()
                ->count();

            return response()->json([
                'success' => true,
                'message' => $marked === 0 ? 'Nothing left to mark.' : $marked . ' marked as read.',
                'data' => [
                    'leadId' => (int) $lead->id,
                    'marked' => $marked,
                    'unreadTotal' => $unreadTotal,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] Marking thread ' . $leadId . ' read failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the conversation.',
            ], 500);
        }
    }

    /**
     * Accept a pushed inbound message from a mail provider. UNAUTHENTICATED.
     *
     * Nothing here trusts the payload until the shared secret matches, and the body is
     * refused outright above a fixed size so a hostile caller cannot make the app
     * allocate its way out of memory before the check even runs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function webhook(Request $request)
    {
        try {
            $declared = (int) $request->header('Content-Length', 0);

            if ($declared > self::MAX_WEBHOOK_BYTES) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payload too large.',
                ], 413);
            }

            $settings = $this->authenticateWebhook($request);

            if (!$settings) {
                // Deliberately vague: a caller with the wrong token learns nothing about
                // whether the account exists.
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden.',
                ], 403);
            }

            $raw = (string) $request->getContent();

            if (strlen($raw) > self::MAX_WEBHOOK_BYTES) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payload too large.',
                ], 413);
            }

            $payload = $request->all();

            // Laravel only decodes the body when the provider labelled it as JSON, and
            // plenty of them post JSON under text/plain. Decoding again costs nothing when
            // the body was already parsed and rescues the payload when it was not.
            if ($raw !== '') {
                $decoded = json_decode($raw, true);

                if (is_array($decoded)) {
                    $payload = array_merge($payload, $decoded);
                }
            }

            $message = $this->normalizeWebhookPayload($payload);

            if ($message['from'] === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'The payload has no sender address.',
                ], 422);
            }

            $processor = new InboundProcessor($settings, new ImapClientService($settings));
            $stored = $processor->ingest((int) $settings->usersId, $message);

            return response()->json([
                'success' => true,
                'message' => $stored ? 'Message stored.' : 'Message ignored (duplicate or unusable).',
                'data' => [
                    'stored' => (bool) $stored,
                    'messageRowId' => $stored ? (int) $stored->id : null,
                    'matchedLead' => $stored ? $stored->leadId : null,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] Inbound webhook failed: ' . $e->getMessage());

            // A 500 tells the provider to retry, which is what we want when the fault is
            // ours; a 200 here would drop a real reply on the floor.
            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed.',
            ], 500);
        }
    }

    // ==================== INTERNALS ====================

    /**
     * Resolve the settings row a webhook call is speaking for, or null.
     *
     * The copyable URL carries ?s=<settings id>, which makes this one indexed lookup.
     * Without it every active row is tried, because a provider that strips query strings
     * down to a single token should still work.
     *
     * @return \App\Modules\OutreachEngine\Models\OutreachSetting|null
     */
    protected function authenticateWebhook(Request $request)
    {
        $token = (string) $request->query('token', $request->header('X-Outreach-Token', ''));
        $token = trim($token);

        if ($token === '') {
            return null;
        }

        $settingsId = (int) $request->query('s', 0);

        if ($settingsId > 0) {
            $settings = OutreachSetting::query()->active()->where('id', $settingsId)->first();

            if (!$settings) {
                return null;
            }

            $expected = self::webhookToken($settings);

            return ($expected !== null && hash_equals($expected, $token)) ? $settings : null;
        }

        // No id supplied: walk the (small) set of configured accounts. hash_equals still
        // does the comparing, so no single account leaks through response timing.
        $candidates = OutreachSetting::query()->active()->orderBy('id')->get();

        foreach ($candidates as $candidate) {
            $expected = self::webhookToken($candidate);

            if ($expected !== null && hash_equals($expected, $token)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Map a provider payload onto the shape InboundProcessor::ingest() expects.
     *
     * Field names differ per provider, so each slot accepts the handful of spellings the
     * common ones use. Nothing is trusted beyond being cast to a string.
     *
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    protected function normalizeWebhookPayload(array $payload): array
    {
        $from = $this->firstString($payload, ['from', 'sender', 'senderEmail', 'from_email', 'From']);
        $fromName = $this->firstString($payload, ['fromName', 'senderName', 'from_name', 'name']);

        // "Display Name <a@b.com>" - keep the address, and salvage the name when the
        // payload did not send one separately.
        if (preg_match('/^(.*)<([^>]+)>\s*$/', $from, $matches)) {
            $candidateName = trim(trim($matches[1]), '"\' ');
            $from = trim($matches[2]);

            if ($fromName === '' && $candidateName !== '') {
                $fromName = $candidateName;
            }
        }

        // {"sender": {"email": ..., "name": ...}} - firstString took the address out of
        // the nested object, so the name it sat next to is fetched separately.
        if ($fromName === '') {
            foreach (['from', 'sender'] as $key) {
                if (is_array($payload[$key] ?? null) && !empty($payload[$key]['name'])) {
                    $fromName = trim((string) $payload[$key]['name']);
                    break;
                }
            }
        }

        $from = strtolower(trim($from));
        $subject = $this->firstString($payload, ['subject', 'Subject', 'title']);
        $text = $this->firstString($payload, ['text', 'bodyText', 'plain', 'body-plain', 'TextBody']);
        $html = $this->firstString($payload, ['html', 'bodyHtml', 'body-html', 'HtmlBody']);

        if ($text === '' && $html !== '') {
            $text = $this->plainBody($html);
        }

        $isBounce = (bool) ($payload['isBounce'] ?? $payload['is_bounce'] ?? false);

        if (!$isBounce) {
            $isBounce = (bool) preg_match('/(mailer-daemon|postmaster)/i', $from)
                || (bool) preg_match(self::BOUNCE_SUBJECT_PATTERN, $subject);
        }

        return [
            // No IMAP UID exists for a pushed message; a null keeps it clear of the
            // (usersId, messageUid) unique index the poller owns.
            'uid' => '',
            'uidValidity' => null,
            'messageId' => $this->firstString($payload, ['messageId', 'message_id', 'Message-Id', 'MessageID']),
            'inReplyTo' => $this->firstString($payload, ['inReplyTo', 'in_reply_to', 'In-Reply-To']),
            'references' => $this->firstString($payload, ['references', 'References']),
            'from' => $from,
            'fromName' => $fromName,
            'subject' => $subject,
            'text' => $this->clip($text, InboundProcessor::MAX_BODY_CHARS),
            'html' => $this->clip($html, InboundProcessor::MAX_BODY_CHARS),
            'date' => $this->firstString($payload, ['date', 'Date', 'receivedAt', 'timestamp']),
            'isBounce' => $isBounce,
            'direction' => OutreachInboundMessage::DIRECTION_INBOUND,
        ];
    }

    /**
     * First non-empty scalar among a list of possible keys.
     *
     * @param  array<string,mixed>  $payload
     * @param  array<int,string>  $keys
     */
    protected function firstString(array $payload, array $keys): string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }

            $value = $payload[$key];

            if (is_array($value)) {
                // Some providers send {"from": {"email": "...", "name": "..."}}.
                $value = $value['email'] ?? $value['address'] ?? $value['value'] ?? null;
            }

            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return '';
    }

    /**
     * The user's own lead, or null. Never trust an id on its own.
     *
     * @return \App\Modules\OutreachEngine\Models\OutreachLead|null
     */
    protected function findOwnedLead(int $leadId, int $userId)
    {
        return OutreachLead::query()
            ->active()
            ->forUser($userId)
            ->where('id', $leadId)
            ->first();
    }

    /**
     * Newest message per lead, for the thread list preview.
     *
     * One query for all the leads on the page, then grouped in PHP - a per-lead
     * subquery would be N round trips to a remote database for what fits in one.
     *
     * @param  array<int,int>  $leadIds
     * @return array<int,array<string,string>>
     */
    protected function latestSnippets(int $userId, array $leadIds): array
    {
        if (empty($leadIds)) {
            return [];
        }

        $messages = OutreachInboundMessage::query()
            ->active()
            ->forUser($userId)
            ->whereIn('leadId', $leadIds)
            ->orderByDesc('id')
            ->limit(self::MAX_THREADS * 5)
            ->get(['id', 'leadId', 'subject', 'bodyText', 'bodyHtml', 'direction']);

        $snippets = [];

        foreach ($messages as $message) {
            $leadId = (int) $message->leadId;

            // Ordered by id descending, so the first one seen for a lead is its newest.
            if (isset($snippets[$leadId])) {
                continue;
            }

            $snippets[$leadId] = [
                'subject' => (string) $message->subject,
                'snippet' => $message->snippet,
                'direction' => (string) $message->direction,
            ];
        }

        return $snippets;
    }

    /**
     * How many stored messages could not be tied to a lead - mail from an address that
     * matches nothing we scraped. Surfaced so it is visibly parked, not lost.
     */
    protected function unmatchedCount(int $userId): int
    {
        return OutreachInboundMessage::query()
            ->active()
            ->forUser($userId)
            ->whereNull('leadId')
            ->count();
    }

    /**
     * The Message-ID a quick reply should thread onto: the newest inbound message we
     * have, falling back to the last thing we sent.
     */
    protected function latestThreadReference(int $userId, int $leadId): ?string
    {
        $inbound = OutreachInboundMessage::query()
            ->active()
            ->forUser($userId)
            ->where('leadId', $leadId)
            ->inbound()
            ->whereNotNull('messageId')
            ->orderByDesc('id')
            ->first();

        if ($inbound && !empty($inbound->messageId)) {
            return (string) $inbound->messageId;
        }

        $log = OutreachEmailLog::query()
            ->active()
            ->forUser($userId)
            ->where('leadId', $leadId)
            ->whereNotNull('messageId')
            ->orderByDesc('id')
            ->first();

        return $log && !empty($log->messageId) ? (string) $log->messageId : null;
    }

    /**
     * Prefilled reply subject: the newest subject on the thread, with one "Re: " on it.
     *
     * @param  \Illuminate\Support\Collection  $messages
     * @param  \Illuminate\Support\Collection  $logs
     */
    protected function suggestedReplySubject($messages, $logs): string
    {
        $subject = '';

        $newestInbound = $messages->first(function ($message) {
            return $message->direction === OutreachInboundMessage::DIRECTION_INBOUND;
        });

        if ($newestInbound) {
            $subject = (string) $newestInbound->subject;
        } elseif ($logs->isNotEmpty()) {
            $subject = (string) $logs->first()->subjectUsed;
        }

        $subject = trim($subject);

        if ($subject === '') {
            return 'Re: our message';
        }

        return preg_match('/^re:/i', $subject) ? $subject : 'Re: ' . $subject;
    }

    /**
     * HTML flattened to readable plain text.
     *
     * Script and style blocks go first: strip_tags drops the tags but keeps whatever was
     * between them, which would otherwise dump a stylesheet into the middle of a reply.
     */
    protected function plainBody(string $body): string
    {
        if (trim($body) === '') {
            return '';
        }

        $text = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $body);
        $text = preg_replace('#<br\s*/?>#i', "\n", (string) $text);
        $text = preg_replace('#</(p|div|tr|h[1-6]|li)>#i', "\n", (string) $text);
        $text = strip_tags((string) $text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text);
        // Trailing spaces on a line are what a stripped <style> block leaves behind; clear
        // them before collapsing blank lines, or they keep the blank lines alive.
        $text = preg_replace("/ *\n */", "\n", (string) $text);
        $text = preg_replace("/\n{3,}/", "\n\n", (string) $text);

        return $this->clip(trim((string) $text), self::MAX_BODY_CHARS);
    }

    /**
     * Hard length limit with a visible marker, so a truncated body never reads as a
     * complete one.
     */
    protected function clip(string $value, int $limit): string
    {
        if ($limit <= 0 || mb_strlen($value) <= $limit) {
            return $value;
        }

        return mb_substr($value, 0, $limit) . "\n\n[truncated]";
    }

    /**
     * "Aug 21, 2026 3:04 PM" in Manila time, whatever timezone the value carries.
     *
     * @param  \Carbon\Carbon  $when
     */
    protected function humanTime($when): string
    {
        return Carbon::parse($when)->timezone('Asia/Manila')->format('M j, Y g:i A');
    }

    /**
     * A raw aggregate value from the database as a Carbon, or null.
     *
     * @param  mixed  $value
     * @return \Carbon\Carbon|null
     */
    protected function toCarbon($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Does this lead match the sidebar's search box?
     */
    protected function leadMatchesSearch(OutreachLead $lead, string $search): bool
    {
        $needle = mb_strtolower($search);

        $haystack = mb_strtolower(
            (string) $lead->businessName . ' '
            . (string) $lead->email . ' '
            . $lead->display_location
        );

        return mb_strpos($haystack, $needle) !== false;
    }
}
