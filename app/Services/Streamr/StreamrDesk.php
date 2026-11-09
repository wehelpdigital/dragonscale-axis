<?php

namespace App\Services\Streamr;

use App\Models\StreamrDeskMessage;
use App\Models\StreamrDeskTicket;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The Streamr support desk (streamr_desk_tickets / streamr_desk_messages),
 * kept in step with Streamr's own tickets both ways:
 *
 *   Streamr -> here   tickets, client and Streamr-admin messages and status
 *                     changes, pushed to /api/streamr/v1/support/*; idempotent
 *                     by uuid, status by changed_at (the newest wins)
 *   here -> Streamr   a reply or a close/reopen made in Streamr > Support is a
 *                     row with origin "mother"; Streamr is rung after the
 *                     response (StreamrCallbacks) and also reads them through
 *                     GET /support/updates (cursor = message id), so a lost
 *                     doorbell only delays it
 *
 * Streamr numbers the tickets and emails its clients; this app never does.
 */
final class StreamrDesk
{
    public const STATUSES = ['open', 'answered', 'closed'];

    public static function messageRules(string $prefix = ''): array
    {
        return [
            $prefix . 'uuid' => ['required', 'uuid'],
            $prefix . 'author_type' => ['required', 'in:client,admin,system'],
            $prefix . 'author_id' => ['nullable', 'integer'],
            $prefix . 'author_name' => ['nullable', 'string', 'max:120'],
            $prefix . 'origin' => ['nullable', 'string', 'max:10'],
            $prefix . 'body' => ['required', 'string', 'max:16000'],
            $prefix . 'body_format' => ['nullable', 'in:text,html'],
            $prefix . 'attachments' => ['nullable', 'array', 'max:10'],
            $prefix . 'attachments.*.url' => ['required', 'string', 'max:500', 'regex:#^https?://#i'],
            $prefix . 'attachments.*.name' => ['nullable', 'string', 'max:191'],
            $prefix . 'attachments.*.mime' => ['nullable', 'string', 'max:100'],
            $prefix . 'attachments.*.size' => ['nullable', 'integer', 'min:0'],
            $prefix . 'is_internal' => ['nullable', 'boolean'],
            $prefix . 'created_at' => ['nullable', 'date'],
        ];
    }

    public static function ticketRules(string $prefix = ''): array
    {
        return [
            $prefix . 'uuid' => ['required', 'uuid'],
            $prefix . 'number' => ['required', 'string', 'max:20'],
            $prefix . 'streamr_user_id' => ['required', 'integer', 'min:1'],
            $prefix . 'client' => ['nullable', 'array'],
            $prefix . 'client.name' => ['nullable', 'string', 'max:191'],
            $prefix . 'client.email' => ['nullable', 'string', 'max:191'],
            $prefix . 'client.store' => ['nullable', 'string', 'max:150'],
            $prefix . 'subject' => ['required', 'string', 'max:200'],
            $prefix . 'category' => ['nullable', 'string', 'max:40'],
            $prefix . 'priority' => ['nullable', 'in:low,normal,high,urgent'],
            $prefix . 'status' => ['nullable', 'in:open,answered,closed'],
            $prefix . 'created_at' => ['nullable', 'date'],
        ];
    }

    // ------------------------------------------------------------------
    // From Streamr
    // ------------------------------------------------------------------

    /** @return array{stored: bool, duplicate: bool} */
    public function upsertTicket(array $t): array
    {
        $uuid = strtolower((string) $t['uuid']);
        $ticket = StreamrDeskTicket::where('uuid', $uuid)->first();
        $existed = (bool) $ticket;

        $values = [
            'subject' => mb_substr(trim((string) $t['subject']), 0, 200),
            'category' => mb_substr(trim((string) ($t['category'] ?? '')) ?: 'general', 0, 40),
            'priority' => in_array($t['priority'] ?? null, ['low', 'normal', 'high', 'urgent'], true) ? $t['priority'] : 'normal',
            'clientName' => $this->text($t['client']['name'] ?? null, 191),
            'clientEmail' => $this->text($t['client']['email'] ?? null, 191),
            'storeName' => $this->text($t['client']['store'] ?? null, 150),
        ];

        $changed = false;
        if (! $ticket) {
            $created = StreamrTime::parse($t['created_at'] ?? null) ?? StreamrTime::now();
            try {
                $ticket = StreamrDeskTicket::create($values + [
                    'uuid' => $uuid,
                    'ticketNumber' => mb_substr(trim((string) $t['number']), 0, 20),
                    'streamrUserId' => (int) $t['streamr_user_id'],
                    'status' => in_array($t['status'] ?? null, self::STATUSES, true) ? $t['status'] : 'open',
                    'statusChangedAt' => $created,
                    'statusChangedBy' => 'streamr',
                    'lastMessageAt' => $created,
                    'origin' => 'streamr',
                    'delete_status' => 'active',
                ]);
                $changed = true;
            } catch (QueryException $e) {
                $ticket = StreamrDeskTicket::where('uuid', $uuid)->first();
                if (! $ticket) {
                    throw $e;
                }
                $existed = true;
            }
        }
        if ($existed) {
            // Never the status from here: it moves only by messages and status changes.
            $ticket->fill(array_filter($values, fn ($v) => $v !== null));
            if ($ticket->isDirty()) {
                $ticket->save();
                $changed = true;
            }
        }

        $messageStored = false;
        if (is_array($t['message'] ?? null)) {
            $messageStored = $this->addMessage($uuid, $t['message'], null)['stored'];
        }

        return ['stored' => $changed || $messageStored, 'duplicate' => $existed && ! $messageStored];
    }

    /** @return array{stored: bool, duplicate: bool} */
    public function addMessage(string $ticketUuid, array $m, ?array $ticket): array
    {
        $ticketUuid = strtolower($ticketUuid);
        $row = StreamrDeskTicket::where('uuid', $ticketUuid)->first();
        if (! $row && is_array($ticket) && strtolower((string) ($ticket['uuid'] ?? '')) === $ticketUuid) {
            $this->upsertTicket(array_diff_key($ticket, ['message' => true]));
            $row = StreamrDeskTicket::where('uuid', $ticketUuid)->first();
        }
        if (! $row) {
            throw new StreamrApiException('unknown_ticket', 'No Streamr ticket ' . $ticketUuid . ' in DS AXIS.', 404);
        }

        $uuid = strtolower((string) $m['uuid']);
        if (StreamrDeskMessage::where('uuid', $uuid)->exists()) {
            return ['stored' => false, 'duplicate' => true];
        }

        $author = in_array($m['author_type'] ?? null, ['client', 'admin', 'system'], true) ? $m['author_type'] : 'client';
        $internal = (bool) filter_var($m['is_internal'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $at = StreamrTime::parse($m['created_at'] ?? null) ?? StreamrTime::now();

        try {
            DB::transaction(function () use ($row, $uuid, $m, $author, $internal, $at) {
                StreamrDeskMessage::create([
                    'uuid' => $uuid,
                    'ticketId' => $row->id,
                    'ticketUuid' => $row->uuid,
                    'kind' => 'message',
                    'authorType' => $author,
                    'authorId' => isset($m['author_id']) && is_numeric($m['author_id']) ? (int) $m['author_id'] : null,
                    'authorName' => $this->text($m['author_name'] ?? null, 120),
                    'origin' => 'streamr',
                    'body' => (string) $m['body'],
                    'bodyFormat' => ($m['body_format'] ?? 'text') === 'html' ? 'html' : 'text',
                    'attachments' => $this->attachments($m['attachments'] ?? []),
                    'isInternal' => $internal,
                    'pulledAt' => null,
                    'delete_status' => 'active',
                ]);

                if ($internal) {
                    return;
                }
                $ticket = StreamrDeskTicket::where('id', $row->id)->lockForUpdate()->first();
                if ($author === 'client') {
                    $ticket->status = 'open';
                    $ticket->adminUnread = (int) $ticket->adminUnread + 1;
                    $ticket->lastMessageBy = 'client';
                } else {
                    if ($author === 'admin') {
                        $ticket->status = 'answered';
                    }
                    $ticket->clientUnread = (int) $ticket->clientUnread + 1;
                    $ticket->lastMessageBy = $author;
                }
                if (! $ticket->lastMessageAt || $at->gte($ticket->lastMessageAt)) {
                    $ticket->lastMessageAt = $at;
                }
                $ticket->save();
            });
        } catch (QueryException $e) {
            if (StreamrDeskMessage::where('uuid', $uuid)->exists()) {
                return ['stored' => false, 'duplicate' => true];
            }
            throw $e;
        }
        StreamrBadges::forget();

        return ['stored' => true, 'duplicate' => false];
    }

    /** A status change made in Streamr: applied when newer than the last one. */
    public function applyStatus(string $uuid, string $status, Carbon $changedAt, string $changedBy, ?array $ticket): bool
    {
        $uuid = strtolower($uuid);
        $row = StreamrDeskTicket::where('uuid', $uuid)->first();
        if (! $row && is_array($ticket) && strtolower((string) ($ticket['uuid'] ?? '')) === $uuid) {
            $this->upsertTicket(array_diff_key($ticket, ['message' => true]));
            $row = StreamrDeskTicket::where('uuid', $uuid)->first();
        }
        if (! $row) {
            throw new StreamrApiException('unknown_ticket', 'No Streamr ticket ' . $uuid . ' in DS AXIS.', 404);
        }
        if (! in_array($status, self::STATUSES, true)) {
            return false;
        }
        if ($row->statusChangedAt && $changedAt->lte($row->statusChangedAt)) {
            return false;
        }

        DB::transaction(function () use ($row, $status, $changedAt, $changedBy) {
            $row->forceFill([
                'status' => $status,
                'statusChangedAt' => $changedAt,
                'statusChangedBy' => mb_substr($changedBy, 0, 80),
            ])->save();

            $this->statusRow($row, $status, $changedAt, $changedBy, 'streamr', null, null);
        });
        StreamrBadges::forget();

        return true;
    }

    // ------------------------------------------------------------------
    // From this app's desk
    // ------------------------------------------------------------------

    public function reply(StreamrDeskTicket $ticket, string $body, bool $internal, User $admin): StreamrDeskMessage
    {
        $now = StreamrTime::now();

        $message = DB::transaction(function () use ($ticket, $body, $internal, $admin, $now) {
            $message = StreamrDeskMessage::create([
                'uuid' => (string) Str::uuid(),
                'ticketId' => $ticket->id,
                'ticketUuid' => $ticket->uuid,
                'kind' => 'message',
                'authorType' => 'admin',
                'authorId' => $admin->id,
                'authorName' => mb_substr(trim((string) $admin->name) ?: 'Support team', 0, 120),
                'origin' => 'mother',
                'body' => $body,
                'bodyFormat' => 'text',
                'attachments' => [],
                'isInternal' => $internal,
                'pulledAt' => null,
                'delete_status' => 'active',
            ]);

            if (! $internal) {
                $locked = StreamrDeskTicket::where('id', $ticket->id)->lockForUpdate()->first();
                $locked->forceFill([
                    'status' => 'answered',
                    'clientUnread' => (int) $locked->clientUnread + 1,
                    'lastMessageAt' => $now,
                    'lastMessageBy' => 'admin',
                ])->save();
            }

            return $message;
        });

        $ticketId = $ticket->id;
        $messageId = $message->id;
        dispatch(function () use ($ticketId, $messageId) {
            StreamrDesk::ring('message', $ticketId, $messageId);
        })->afterResponse();

        return $message;
    }

    public function setStatus(StreamrDeskTicket $ticket, string $status, User $admin): StreamrDeskMessage
    {
        if (! in_array($status, ['open', 'closed'], true)) {
            throw new \InvalidArgumentException('Only open or closed can be chosen; answered comes from replying.');
        }
        $now = StreamrTime::now();
        $by = 'mother:' . mb_substr(trim((string) $admin->name) ?: 'admin', 0, 72);

        $row = DB::transaction(function () use ($ticket, $status, $now, $by, $admin) {
            $ticket->forceFill(['status' => $status, 'statusChangedAt' => $now, 'statusChangedBy' => $by])->save();

            return $this->statusRow($ticket, $status, $now, $by, 'mother', $admin->id, (string) $admin->name);
        });

        $ticketId = $ticket->id;
        $rowId = $row->id;
        dispatch(function () use ($ticketId, $rowId) {
            StreamrDesk::ring('status', $ticketId, $rowId);
        })->afterResponse();

        return $row;
    }

    public function markRead(StreamrDeskTicket $ticket): void
    {
        if ((int) $ticket->adminUnread === 0 && $ticket->adminReadAt) {
            return;
        }
        $ticket->forceFill(['adminUnread' => 0, 'adminReadAt' => StreamrTime::now()])->save();
    }

    /**
     * What Streamr has not heard yet: rows written here (origin mother) after
     * the cursor, oldest first, marked as received.
     *
     * @return array{items: list<array>, next_cursor: string}
     */
    public function updates(string $since, int $limit = 100): array
    {
        $cursor = (int) (preg_replace('/\D+/', '', $since) ?: 0);
        $limit = max(1, min(100, $limit));

        $rows = StreamrDeskMessage::where('origin', 'mother')->where('id', '>', $cursor)->where('delete_status', 'active')
            ->orderBy('id')->limit($limit)->get();

        $items = $rows->map(fn (StreamrDeskMessage $m) => $m->kind === 'status'
            ? [
                'type' => 'status',
                'ticket_uuid' => $m->ticketUuid,
                'status' => (string) ($m->meta['status'] ?? ''),
                'changed_at' => StreamrTime::iso($m->meta['changed_at'] ?? $m->created_at),
                'changed_by' => (string) ($m->meta['changed_by'] ?? ''),
            ]
            : [
                'type' => 'message',
                'ticket_uuid' => $m->ticketUuid,
                'message' => self::messagePayload($m),
            ])->values()->all();

        $unpulled = $rows->whereNull('pulledAt')->pluck('id')->all();
        if ($unpulled !== []) {
            try {
                StreamrDeskMessage::whereIn('id', $unpulled)->whereNull('pulledAt')->update(['pulledAt' => StreamrTime::now()]);
            } catch (\Throwable $e) {
                // Best effort: Streamr keeps the cursor either way.
            }
        }

        return ['items' => $items, 'next_cursor' => (string) ($rows->isEmpty() ? $cursor : $rows->last()->id)];
    }

    /** A mother message as Streamr reads it (doorbell and feed). */
    public static function messagePayload(StreamrDeskMessage $m): array
    {
        return [
            'uuid' => $m->uuid,
            'author_type' => $m->authorType,
            'author_id' => null,
            'author_name' => (string) ($m->authorName ?: 'Support team'),
            'origin' => 'mother',
            'body' => (string) $m->body,
            'body_format' => $m->bodyFormat === 'html' ? 'html' : 'text',
            'attachments' => array_values((array) ($m->attachments ?? [])),
            'is_internal' => (bool) $m->isInternal,
            'created_at' => StreamrTime::iso($m->created_at),
        ];
    }

    /** After the response: tell Streamr about a reply or a status change made here. Never throws. */
    public static function ring(string $what, int $ticketId, int $messageId): void
    {
        try {
            $ticket = StreamrDeskTicket::find($ticketId);
            $message = StreamrDeskMessage::find($messageId);
            if (! $ticket || ! $message) {
                return;
            }
            $callbacks = app(StreamrCallbacks::class);
            $what === 'status' ? $callbacks->supportStatus($ticket, $message) : $callbacks->supportMessage($ticket, $message);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[Streamr] support doorbell failed: ' . $e->getMessage());
        }
    }

    private function statusRow(StreamrDeskTicket $ticket, string $status, Carbon $changedAt, string $changedBy, string $origin, ?int $authorId, ?string $authorName): StreamrDeskMessage
    {
        return StreamrDeskMessage::create([
            'uuid' => (string) Str::uuid(),
            'ticketId' => $ticket->id,
            'ticketUuid' => $ticket->uuid,
            'kind' => 'status',
            'authorType' => $origin === 'mother' ? 'admin' : 'system',
            'authorId' => $authorId,
            'authorName' => $authorName !== null ? mb_substr($authorName, 0, 120) : null,
            'origin' => $origin,
            'body' => 'Status: ' . $status . ' (' . $changedBy . ')',
            'bodyFormat' => 'text',
            'attachments' => [],
            'isInternal' => false,
            'meta' => ['status' => $status, 'changed_at' => $changedAt->toIso8601String(), 'changed_by' => $changedBy],
            'pulledAt' => null,
            'delete_status' => 'active',
        ]);
    }

    private function attachments($list): array
    {
        $out = [];
        foreach (array_slice(is_array($list) ? $list : [], 0, 10) as $a) {
            if (! is_array($a) || ! is_string($a['url'] ?? null) || ! preg_match('#^https?://#i', $a['url'])) {
                continue;
            }
            $out[] = [
                'url' => mb_substr($a['url'], 0, 500),
                'name' => mb_substr((string) ($a['name'] ?? 'attachment'), 0, 191),
                'mime' => mb_substr((string) ($a['mime'] ?? ''), 0, 100),
                'size' => isset($a['size']) && is_numeric($a['size']) ? (int) $a['size'] : null,
            ];
        }

        return $out;
    }

    private function text($value, int $max): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : mb_substr($value, 0, $max);
    }
}
