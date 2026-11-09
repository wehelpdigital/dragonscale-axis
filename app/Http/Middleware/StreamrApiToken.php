<?php

namespace App\Http\Middleware;

use App\Services\Streamr\StreamrApiException;
use App\Services\Streamr\StreamrApiLog;
use App\Services\Streamr\StreamrSignature;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Streamr's door into this app (/api/streamr/v1/*), modelled on GamesSiteToken:
 *
 *   1. no STREAMR_API_TOKEN set           -> 503 not_configured
 *   2. bearer missing or wrong            -> 401 unauthorized
 *   3. X-Streamr-Timestamp off by > 300 s -> 401 stale_timestamp
 *   4. X-Streamr-Signature does not match -> 401 bad_signature
 *
 * The signature is the HMAC of "{timestamp}.{canonical}" with the same token
 * (App\Services\Streamr\StreamrSignature). Every call, refused or not, is
 * written to streamr_api_requests once the response has gone (terminate).
 */
class StreamrApiToken
{
    private const LOG = 'streamr.api.log';

    public function handle(Request $request, Closure $next): Response
    {
        $started = microtime(true);
        $refusal = $this->refusal($request);

        $response = $refusal ?? $next($request);

        $request->attributes->set(self::LOG, [
            'started' => $started,
            'refused' => $refusal !== null,
        ]);

        return $response;
    }

    /** Written after the response: method, path, status, error code, time taken. */
    public function terminate(Request $request, Response $response): void
    {
        $meta = $request->attributes->get(self::LOG);
        if (! is_array($meta)) {
            return;
        }

        $status = $response->getStatusCode();
        $code = null;
        if ($status >= 400) {
            $json = json_decode((string) $response->getContent(), true);
            $code = is_array($json) ? ($json['error']['code'] ?? null) : null;
            $code = is_string($code) ? $code : ($status === 429 ? 'rate_limited' : null);
        }

        StreamrApiLog::record('in', $request->getMethod(), $request->getPathInfo(), $status, $code,
            (int) round((microtime(true) - (float) $meta['started']) * 1000), [
                'request_id' => $request->header('X-Streamr-Request-Id'),
                'idempotency_key' => $request->header('Idempotency-Key'),
                'ip' => $request->ip(),
                'refusal' => (bool) $meta['refused'],
            ]);
    }

    private function refusal(Request $request): ?Response
    {
        $token = trim((string) config('streamr.api_token'));
        if ($token === '') {
            return StreamrApiException::envelope('not_configured', 'The Streamr link is not set up in DS AXIS (STREAMR_API_TOKEN).', 503);
        }

        $given = (string) $request->bearerToken();
        if ($given === '' || ! hash_equals($token, $given)) {
            return StreamrApiException::envelope('unauthorized', 'Unauthenticated.', 401);
        }

        $timestamp = trim((string) $request->header(StreamrSignature::HEADER_TIMESTAMP, ''));
        $window = max(1, (int) config('streamr.signature_window', 300));
        if (! preg_match('/^\d{1,12}$/', $timestamp) || abs(time() - (int) $timestamp) > $window) {
            return StreamrApiException::envelope('stale_timestamp', 'The request time is more than ' . $window . ' seconds off. Check both servers\' clocks.', 401);
        }

        $given = strtolower(trim((string) $request->header(StreamrSignature::HEADER_SIGNATURE, '')));
        $expected = StreamrSignature::sign($token, (int) $timestamp, StreamrSignature::canonical($request));
        if ($given === '' || ! hash_equals($expected, $given)) {
            return StreamrApiException::envelope('bad_signature', 'The request signature does not match.', 401);
        }

        return null;
    }
}
