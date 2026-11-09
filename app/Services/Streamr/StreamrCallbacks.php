<?php

namespace App\Services\Streamr;

use App\Models\StreamrClientAction;
use App\Models\StreamrDeskMessage;
use App\Models\StreamrDeskTicket;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * This app calling Streamr: signed requests to {STREAMR_APP_URL}/api/mother/*
 * (Streamr's architecture §10.3), each logged in streamr_api_requests.
 *
 *   ping()             GET  /ping                         Link status > Test link
 *   orderChanged()     POST /orders/changed               the order doorbell (after the response)
 *   client()           GET  /clients/{id}                 Refresh from Streamr
 *   clientAction()     POST /clients/{id}/actions         an action from Streamr > Clients (the admin waits)
 *   supportMessage()   POST /support/messages             a reply written here
 *   supportStatus()    POST /support/tickets/{uuid}/status  a close/reopen made here
 *
 * Never throws: every method answers {ok, status, data, code, message}. When
 * STREAMR_APP_URL or STREAMR_API_TOKEN is not set nothing is sent and the
 * answer is code "not_configured".
 */
class StreamrCallbacks
{
    public function configured(): bool
    {
        return (string) config('streamr.app_url', '') !== '' && trim((string) config('streamr.api_token', '')) !== '';
    }

    public function ping(): array
    {
        return $this->send('GET', '/ping', null, $this->doorbellTimeout(), 'ping');
    }

    public function orderChanged(array $body): array
    {
        return $this->send('POST', '/orders/changed', $body, $this->doorbellTimeout(), 'order_changed');
    }

    /** GET the client's snapshot and keep it in the registry. */
    public function client(int $id): array
    {
        $result = $this->send('GET', '/clients/' . $id, null, $this->actionTimeout(), 'client');
        if ($result['ok'] && is_array($result['data']) && (int) ($result['data']['streamr_user_id'] ?? 0) === $id) {
            $result['stored'] = app(StreamrClientRegistry::class)->store($result['data']);
        }

        return $result;
    }

    /**
     * Ask Streamr to apply an action to one of its clients; logged in
     * streamr_client_actions, Streamr's fresh snapshot kept in the registry.
     *
     * @return array{ok: bool, status: ?int, data: array, code: ?string, message: string, action: ?StreamrClientAction}
     */
    public function clientAction(int $id, string $action, array $payload, User $admin, ?string $retryOf = null): array
    {
        $uuid = (string) Str::uuid();
        $send = $payload;
        unset($send['retry_of']);

        $log = null;
        try {
            $log = StreamrClientAction::create([
                'uuid' => $uuid,
                'streamrUserId' => $id,
                'action' => $action,
                'payload' => $retryOf ? $send + ['retry_of' => $retryOf] : $send,
                'requestedBy' => $admin->id,
                'requestedByName' => mb_substr((string) $admin->name, 0, 120),
                'status' => StreamrClientAction::SENT,
                'attempts' => 1,
                'delete_status' => 'active',
            ]);
        } catch (\Throwable $e) {
            // The action still goes out; only its log line is missing.
        }

        $started = microtime(true);
        $result = $this->send('POST', '/clients/' . $id . '/actions', [
            'uuid' => $uuid,
            'action' => $action,
            'payload' => (object) $send,
            'admin' => ['id' => $admin->id, 'name' => mb_substr((string) ($admin->name ?: 'DS AXIS admin'), 0, 120)],
            'requested_at' => StreamrTime::now()->toIso8601String(),
        ], $this->actionTimeout(), 'client_action', $uuid);

        $snapshotAt = null;
        if ($result['ok'] && is_array($result['data']['client'] ?? null)) {
            try {
                app(StreamrClientRegistry::class)->store($result['data']['client']);
                $snapshotAt = $result['data']['client']['snapshot_at'] ?? null;
            } catch (\Throwable $e) {
                // Streamr applied it; the registry catches up with the next push.
            }
        }

        if ($log) {
            try {
                $log->forceFill([
                    'status' => $result['ok'] ? StreamrClientAction::APPLIED : StreamrClientAction::FAILED,
                    'httpStatus' => $result['status'],
                    'result' => $result['ok'] ? ['message' => (string) ($result['data']['message'] ?? ''), 'client_snapshot_at' => $snapshotAt] : null,
                    'error' => $result['ok'] ? null : mb_substr((string) $result['message'], 0, 1000),
                    'durationMs' => (int) round((microtime(true) - $started) * 1000),
                ])->save();
            } catch (\Throwable $e) {
                // As above.
            }
        }

        $result['action'] = $log;
        if ($result['ok']) {
            $result['message'] = (string) ($result['data']['message'] ?? '') ?: 'Streamr applied it.';
        }

        return $result;
    }

    /** Send a failed action again, as a new action (new uuid) linked to the old one. */
    public function retry(StreamrClientAction $failed, User $admin): array
    {
        return $this->clientAction((int) $failed->streamrUserId, (string) $failed->action, (array) ($failed->payload ?? []), $admin, (string) $failed->uuid);
    }

    public function supportMessage(StreamrDeskTicket $ticket, StreamrDeskMessage $message): array
    {
        $result = $this->send('POST', '/support/messages', [
            'ticket_uuid' => $ticket->uuid,
            'message' => StreamrDesk::messagePayload($message),
        ], $this->doorbellTimeout(), 'support_message', $message->uuid);

        $this->markPulled($result, $message);

        return $result;
    }

    public function supportStatus(StreamrDeskTicket $ticket, StreamrDeskMessage $statusRow): array
    {
        $meta = (array) ($statusRow->meta ?? []);
        $result = $this->send('POST', '/support/tickets/' . rawurlencode((string) $ticket->uuid) . '/status', [
            'status' => (string) ($meta['status'] ?? $ticket->status),
            'changed_at' => StreamrTime::iso($meta['changed_at'] ?? $statusRow->created_at),
            'changed_by' => (string) ($meta['changed_by'] ?? $ticket->statusChangedBy),
        ], $this->doorbellTimeout(), 'support_status', $statusRow->uuid);

        $this->markPulled($result, $statusRow);

        return $result;
    }

    // ------------------------------------------------------------------

    /**
     * One signed call. $path is under /api/mother. Answers
     * {ok, status, data, code, message}; network trouble is code "unreachable".
     */
    private function send(string $method, string $path, ?array $body, int $timeout, string $kind, ?string $idempotencyKey = null): array
    {
        $base = rtrim((string) config('streamr.app_url', ''), '/');
        $secret = trim((string) config('streamr.api_token', ''));
        $path = '/api/mother' . $path;

        if ($base === '' || $secret === '') {
            return $this->result(false, null, [], 'not_configured', 'Streamr is not linked yet: set STREAMR_APP_URL and STREAMR_API_TOKEN.');
        }

        $method = strtoupper($method);
        $json = $body === null ? null : (string) json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $headers = StreamrSignature::headers($secret, $method, $path, $json);
        if ($json !== null) {
            $headers['Idempotency-Key'] = $idempotencyKey ?: (string) Str::uuid();
        }

        $started = microtime(true);
        try {
            $request = Http::withHeaders($headers)
                ->connectTimeout(min(5, $timeout))
                ->timeout($timeout)
                ->withOptions(['allow_redirects' => false]);
            $response = $json === null
                ? $request->send($method, $base . $path)
                : $request->withBody($json, 'application/json')->send($method, $base . $path);
        } catch (\Throwable $e) {
            if (class_exists(\Illuminate\Http\Client\StrayRequestException::class) && $e instanceof \Illuminate\Http\Client\StrayRequestException) {
                throw $e; // Tests only: a call nobody faked must fail the test.
            }
            StreamrApiLog::record('out', $method, $path, null, 'unreachable', $this->ms($started), ['note' => $kind, 'request_id' => $headers['X-Streamr-Request-Id']]);

            return $this->result(false, null, [], 'unreachable', 'Streamr is not reachable — try again.');
        }

        $status = $response->status();
        $decoded = $response->json();
        $decoded = is_array($decoded) ? $decoded : [];
        $ok = $response->successful() && ($decoded['success'] ?? true) !== false;
        $data = $ok ? (is_array($decoded['data'] ?? null) ? $decoded['data'] : $decoded) : [];
        $code = $ok ? null : (string) ($decoded['error']['code'] ?? $this->codeFor($status));
        $message = $ok ? '' : $this->messageFor($status, $decoded);

        StreamrApiLog::record('out', $method, $path, $status, $code, $this->ms($started), ['note' => $kind, 'request_id' => $headers['X-Streamr-Request-Id']]);

        return $this->result($ok, $status, $data, $code, $message);
    }

    private function messageFor(int $status, array $decoded): string
    {
        $said = trim((string) ($decoded['error']['message'] ?? $decoded['message'] ?? ''));
        if ($status === 401) {
            return 'Streamr refused the signature: check STREAMR_API_TOKEN and both servers\' clocks.' . ($said !== '' ? ' (' . $said . ')' : '');
        }
        if ($said !== '') {
            return $said;
        }

        return match (true) {
            $status === 404 => 'Streamr does not know that client or ticket.',
            $status === 429 => 'Streamr asked us to slow down — try again in a minute.',
            $status >= 500 => 'Streamr had a problem (' . $status . ') — try again.',
            default => 'Streamr answered ' . $status . '.',
        };
    }

    private function codeFor(int $status): string
    {
        return match (true) {
            $status === 401 => 'unauthorized',
            $status === 404 => 'not_found',
            $status === 409 => 'conflict',
            $status === 422 => 'validation_failed',
            $status === 429 => 'rate_limited',
            $status >= 500 => 'server_error',
            default => 'bad_request',
        };
    }

    private function markPulled(array $result, StreamrDeskMessage $message): void
    {
        if (! $result['ok']) {
            return;
        }
        try {
            StreamrDeskMessage::where('id', $message->id)->whereNull('pulledAt')->update(['pulledAt' => StreamrTime::now()]);
        } catch (\Throwable $e) {
            // The feed marks it next time.
        }
    }

    private function result(bool $ok, ?int $status, array $data, ?string $code, string $message): array
    {
        return ['ok' => $ok, 'status' => $status, 'data' => $data, 'code' => $code, 'message' => $message];
    }

    private function ms(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }

    private function doorbellTimeout(): int
    {
        return max(1, (int) config('streamr.doorbell_timeout', 8));
    }

    private function actionTimeout(): int
    {
        return max(1, (int) config('streamr.action_timeout', 15));
    }
}
