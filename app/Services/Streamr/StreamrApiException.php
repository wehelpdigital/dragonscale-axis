<?php

namespace App\Services\Streamr;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * An answer the module's API gives Streamr instead of data, in the contract's
 * envelope: {"success": false, "error": {"code", "message", "fields"?}}.
 *
 *   400 bad_request · 401 unauthorized | bad_signature | stale_timestamp
 *   403 forbidden · 404 not_found | unknown_order | unknown_client | unknown_ticket
 *   409 duplicate_number | invalid_state | not_applicable | conflict
 *   413 payload_too_large · 422 validation_failed · 429 rate_limited
 *   500 server_error · 503 not_configured | maintenance
 */
class StreamrApiException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status,
        public readonly array $fields = [],
    ) {
        parent::__construct($message, $status);
    }

    public static function validation(array $fields, string $message = 'Some fields are not valid.'): self
    {
        return new self('validation_failed', $message, 422, $fields);
    }

    public function render($request = null): JsonResponse
    {
        return self::envelope($this->errorCode, $this->getMessage(), $this->status, $this->fields);
    }

    public static function envelope(string $code, string $message, int $status, array $fields = []): JsonResponse
    {
        $error = ['code' => $code, 'message' => $message];
        if ($fields !== []) {
            $error['fields'] = $fields;
        }

        return response()->json(['success' => false, 'error' => $error], $status);
    }
}
