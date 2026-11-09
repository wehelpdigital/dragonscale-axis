<?php

namespace App\Services\Streamr;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * The signature both apps put on every call between them (Streamr's
 * architecture §10.1; Streamr has the twin, App\Support\Mother\StreamrSignature).
 *
 *   Authorization: Bearer {secret}
 *   X-Streamr-Timestamp: {unix seconds}
 *   X-Streamr-Signature: lowercase hex HMAC-SHA256(secret, "{timestamp}.{canonical}")
 *
 * The canonical string is the raw JSON body; for a multipart request, the
 * `payload` form field (the JSON); for GET/DELETE, "{METHOD} {path}" plus
 * "?{q}" when there is a query, where q is http_build_query (RFC 3986) after
 * sorting the TOP level keys only (a list keeps its order and gets numeric
 * indices: numbers%5B0%5D=...&numbers%5B1%5D=...).
 */
final class StreamrSignature
{
    public const HEADER_TIMESTAMP = 'X-Streamr-Timestamp';

    public const HEADER_SIGNATURE = 'X-Streamr-Signature';

    public const USER_AGENT = 'DS-AXIS-Streamr/1.0';

    public static function sign(string $secret, int $timestamp, string $canonical): string
    {
        return hash_hmac('sha256', $timestamp . '.' . $canonical, $secret);
    }

    /** The canonical string of a request this app received. */
    public static function canonical(Request $request): string
    {
        $method = strtoupper($request->getMethod());

        if (in_array($method, ['GET', 'DELETE', 'HEAD'], true)) {
            return self::requestLine($method, $request->getPathInfo(), self::receivedQuery($request));
        }

        if (self::isMultipart($request)) {
            $payload = $request->request->get('payload');

            return is_string($payload) ? $payload : '';
        }

        return (string) $request->getContent();
    }

    /** "{METHOD} {path}", plus "?{q}" when there are query parameters. */
    public static function requestLine(string $method, string $path, array $query = []): string
    {
        $line = strtoupper($method) . ' ' . $path;

        return $query === [] ? $line : $line . '?' . self::query($query);
    }

    /** q: the top level sorted by key, then http_build_query in RFC 3986. */
    public static function query(array $params): string
    {
        ksort($params, SORT_STRING);

        return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Headers for a call this app makes (to Streamr's /api/mother/*). $path is
     * the path the receiver sees (e.g. /api/mother/orders/changed); $body the
     * exact JSON sent, null for GET/DELETE.
     */
    public static function headers(string $secret, string $method, string $path, ?string $body, array $query = [], ?int $timestamp = null): array
    {
        $timestamp ??= time();
        $method = strtoupper($method);
        $canonical = in_array($method, ['GET', 'DELETE', 'HEAD'], true)
            ? self::requestLine($method, $path, $query)
            : (string) $body;

        return [
            'Authorization' => 'Bearer ' . $secret,
            self::HEADER_TIMESTAMP => (string) $timestamp,
            self::HEADER_SIGNATURE => self::sign($secret, $timestamp, $canonical),
            'Accept' => 'application/json',
            'User-Agent' => self::USER_AGENT,
            'X-Streamr-Request-Id' => (string) Str::uuid(),
        ];
    }

    public static function isMultipart(Request $request): bool
    {
        return str_contains(strtolower((string) $request->headers->get('Content-Type', '')), 'multipart/form-data');
    }

    /**
     * The query exactly as it arrived. The global TrimStrings and
     * ConvertEmptyStringsToNull middleware rewrite $request->query (a blank
     * value becomes null and drops out of http_build_query), so the raw
     * QUERY_STRING is parsed again here; the cleaned bag is the fallback.
     */
    private static function receivedQuery(Request $request): array
    {
        $raw = $request->server->get('QUERY_STRING');
        if (is_string($raw) && $raw !== '') {
            parse_str($raw, $params);

            return is_array($params) ? $params : [];
        }

        return $request->query->all();
    }
}
