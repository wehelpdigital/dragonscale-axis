<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Streamr\StreamrApiException;
use App\Services\Streamr\StreamrClientRegistry;
use App\Services\Streamr\StreamrConfigBuilder;
use App\Services\Streamr\StreamrDesk;
use App\Services\Streamr\StreamrOrderState;
use App\Services\Streamr\StreamrOrderWriter;
use App\Services\Streamr\StreamrReferenceChecker;
use App\Services\Streamr\StreamrSettings;
use App\Services\Streamr\StreamrSignature;
use App\Services\Streamr\StreamrTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * /api/streamr/v1: what Streamr (wehelpdigital/streamr) reads and writes
 * here, behind StreamrApiToken (bearer + timestamp + HMAC). Every answer is
 * the contract's envelope: {"success": true, "data": {...}} or
 * {"success": false, "error": {"code", "message", "fields"?}}.
 *
 *   GET  /ping                          link test
 *   GET  /config                        how Streamr is paid, support categories
 *   POST /orders                        a purchase into /ecom-orders (multipart payload + proof, or JSON)
 *   GET  /orders?numbers[]=             several orders' state (≤ 50)
 *   GET  /orders/{number}               one order's state
 *   POST /orders/{number}/proofs        a new receipt after a rejection
 *   POST /orders/{number}/ai-check      a later AI reading of a receipt
 *   GET  /references/check              was this reference / file used before?
 *   PUT  /clients/{streamrUserId}       the client registry
 *   POST /support/tickets               a ticket (and its first message)
 *   POST /support/tickets/{uuid}/messages
 *   PUT  /support/tickets/{uuid}/status
 *   GET  /support/updates?since=        what was written here since the cursor
 */
class StreamrApiController extends Controller
{
    public function ping(): JsonResponse
    {
        $store = StreamrSettings::storeRow();

        return $this->ok([
            'app' => 'ds-axis',
            'module_version' => (string) config('streamr.module_version', '1.0.0'),
            'time' => StreamrTime::now()->toIso8601String(),
            'store' => ['name' => StreamrSettings::storeName(), 'id' => $store ? (int) $store->id : null],
        ]);
    }

    public function config(StreamrConfigBuilder $builder): JsonResponse
    {
        return $this->run(fn () => $builder->build());
    }

    public function createOrder(Request $request, StreamrOrderWriter $writer): JsonResponse
    {
        return $this->run(function () use ($request, $writer) {
            $payload = $this->payload($request);
            $proof = $this->proofFile($request);
            $this->guardSize($payload, $proof);
            $payload['ai'] = StreamrOrderWriter::normalizeAi($payload['ai'] ?? null);
            $this->validate_($payload, StreamrOrderWriter::createRules());

            return $writer->create($payload, $proof);
        });
    }

    public function orders(Request $request, StreamrOrderState $state): JsonResponse
    {
        return $this->run(function () use ($request, $state) {
            $numbers = $request->query('numbers', []);
            if (! is_array($numbers) || $numbers === [] || count($numbers) > StreamrOrderState::MAX_NUMBERS) {
                throw StreamrApiException::validation(['numbers' => 'Send 1 to ' . StreamrOrderState::MAX_NUMBERS . ' order numbers as numbers[].']);
            }

            return $state->many(array_values($numbers));
        });
    }

    public function order(StreamrOrderState $state, string $number): JsonResponse
    {
        return $this->run(fn () => $state->for($number));
    }

    public function addProof(Request $request, StreamrOrderWriter $writer, string $number): JsonResponse
    {
        return $this->run(function () use ($request, $writer, $number) {
            $payload = $this->payload($request);
            $proof = $this->proofFile($request);
            $this->guardSize($payload, $proof);
            $payload['ai'] = StreamrOrderWriter::normalizeAi($payload['ai'] ?? null);
            $this->validate_($payload, StreamrOrderWriter::proofRules());

            return $writer->addProof($number, $payload, $proof);
        });
    }

    public function aiCheck(Request $request, StreamrOrderWriter $writer, string $number): JsonResponse
    {
        return $this->run(function () use ($request, $writer, $number) {
            $payload = $this->payload($request);
            $payload['ai'] = StreamrOrderWriter::normalizeAi($payload['ai'] ?? null);
            $this->validate_($payload, StreamrOrderWriter::aiCheckRules());

            return $writer->recordAiCheck($number, $payload);
        });
    }

    public function references(Request $request, StreamrReferenceChecker $checker): JsonResponse
    {
        return $this->run(function () use ($request, $checker) {
            $query = $this->rawQuery($request);
            $this->validate_($query, [
                'ref_key' => ['nullable', 'string', 'max:100'],
                'sha256' => ['nullable', 'string', 'regex:/^[a-fA-F0-9]{64}$/'],
                'exclude_number' => ['nullable', 'string', 'max:50'],
            ]);
            $ref = trim((string) ($query['ref_key'] ?? ''));
            $sha = trim((string) ($query['sha256'] ?? ''));
            if ($ref === '' && $sha === '') {
                throw StreamrApiException::validation(['ref_key' => 'Send ref_key, sha256 or both.']);
            }

            return $checker->check($ref, $sha, $query['exclude_number'] ?? null);
        });
    }

    public function upsertClient(Request $request, StreamrClientRegistry $registry, string $streamrUserId): JsonResponse
    {
        return $this->run(function () use ($request, $registry, $streamrUserId) {
            $snapshot = $this->payload($request);
            $this->validate_($snapshot, StreamrClientRegistry::rules());
            if ((int) $snapshot['streamr_user_id'] !== (int) $streamrUserId) {
                throw StreamrApiException::validation(['streamr_user_id' => 'streamr_user_id must be the id in the address.']);
            }

            return $registry->store($snapshot) ? ['stored' => true] : ['stored' => false, 'stale' => true];
        });
    }

    public function supportTicket(Request $request, StreamrDesk $desk): JsonResponse
    {
        return $this->run(function () use ($request, $desk) {
            $ticket = $this->payload($request);
            $rules = StreamrDesk::ticketRules() + ['message' => ['nullable', 'array']];
            if (is_array($ticket['message'] ?? null)) {
                $rules += StreamrDesk::messageRules('message.');
            }
            $this->validate_($ticket, $rules);

            return $desk->upsertTicket($ticket);
        });
    }

    public function supportMessage(Request $request, StreamrDesk $desk, string $uuid): JsonResponse
    {
        return $this->run(function () use ($request, $desk, $uuid) {
            $body = $this->payload($request);
            // The message itself, or wrapped as {message: {...}}; an optional {ticket: {...}} beside it.
            $message = is_array($body['message'] ?? null) ? $body['message'] : $body;
            $ticket = is_array($body['ticket'] ?? null) ? $body['ticket'] : null;
            $this->validate_($message, StreamrDesk::messageRules());
            if ($ticket !== null) {
                $this->validate_($ticket, StreamrDesk::ticketRules(), 'ticket.');
            }

            return $desk->addMessage($uuid, $message, $ticket);
        });
    }

    public function supportStatus(Request $request, StreamrDesk $desk, string $uuid): JsonResponse
    {
        return $this->run(function () use ($request, $desk, $uuid) {
            $body = $this->payload($request);
            $this->validate_($body, [
                'status' => ['required', 'in:open,answered,closed'],
                'changed_at' => ['required', 'date'],
                'changed_by' => ['nullable', 'string', 'max:80'],
                'ticket' => ['nullable', 'array'],
            ]);
            $ticket = is_array($body['ticket'] ?? null) ? $body['ticket'] : null;
            if ($ticket !== null) {
                $this->validate_($ticket, StreamrDesk::ticketRules(), 'ticket.');
            }

            return ['applied' => $desk->applyStatus($uuid, (string) $body['status'], StreamrTime::parse($body['changed_at']),
                (string) ($body['changed_by'] ?? 'streamr'), $ticket)];
        });
    }

    public function supportUpdates(Request $request, StreamrDesk $desk): JsonResponse
    {
        return $this->run(function () use ($request, $desk) {
            $query = $this->rawQuery($request);
            $this->validate_($query, [
                'since' => ['nullable', 'string', 'max:40'],
                'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            ]);

            return $desk->updates((string) ($query['since'] ?? '0'), (int) ($query['limit'] ?? 100));
        });
    }

    // ------------------------------------------------------------------

    /** Run a step and answer in the envelope; anything unexpected is a 500 server_error. */
    private function run(callable $step): JsonResponse
    {
        try {
            return $this->ok($step());
        } catch (StreamrApiException $e) {
            return $e->render();
        } catch (\Throwable $e) {
            Log::error('[Streamr] API ' . request()->method() . ' ' . request()->path() . ' failed: ' . $e->getMessage());

            return StreamrApiException::envelope('server_error', 'DS AXIS could not do that just now. It is safe to try again.', 500);
        }
    }

    private function ok($data): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * The JSON Streamr signed: the `payload` form field of a multipart
     * request, else the raw body (decoded here, not by the middleware that
     * trims strings, so it is exactly what was signed).
     */
    private function payload(Request $request): array
    {
        if (StreamrSignature::isMultipart($request)) {
            $raw = $request->request->get('payload');
            if (! is_string($raw) || trim($raw) === '') {
                throw new StreamrApiException('bad_request', 'A multipart request must carry the JSON in the "payload" field.', 400);
            }
        } else {
            $raw = (string) $request->getContent();
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            throw new StreamrApiException('bad_request', 'The body is not a JSON object.', 400);
        }

        return $decoded;
    }

    private function proofFile(Request $request): ?UploadedFile
    {
        $file = $request->file('proof');

        return $file instanceof UploadedFile ? $file : null;
    }

    /** A receipt over the limit is 413 (before validation would call it a 422). */
    private function guardSize(array $payload, ?UploadedFile $proof): void
    {
        $max = StreamrOrderWriter::maxBytes();
        $declared = $payload['payment']['proof']['bytes'] ?? null;
        if (($proof && $proof->isValid() && (int) $proof->getSize() > $max) || (is_numeric($declared) && (int) $declared > $max)) {
            throw new StreamrApiException('payload_too_large', 'The proof is over ' . (int) (config('streamr.proof_max_kb', 8192) / 1024) . ' MB.', 413);
        }
    }

    /** The query as it arrived (the global middleware turns blanks into nulls). */
    private function rawQuery(Request $request): array
    {
        $raw = $request->server->get('QUERY_STRING');
        if (is_string($raw) && $raw !== '') {
            parse_str($raw, $params);

            return is_array($params) ? $params : [];
        }

        return $request->query->all();
    }

    /** Validate, or answer 422 validation_failed with the first message per field. */
    private function validate_(array $data, array $rules, string $prefix = ''): void
    {
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            $fields = [];
            foreach ($validator->errors()->messages() as $field => $messages) {
                $fields[$prefix . $field] = (string) ($messages[0] ?? 'Invalid.');
            }

            throw StreamrApiException::validation($fields);
        }
    }
}
