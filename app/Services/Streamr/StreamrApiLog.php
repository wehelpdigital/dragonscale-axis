<?php

namespace App\Services\Streamr;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The request log Streamr › Link status reads (streamr_api_requests): one row
 * per call either way, path and status only (never a body, never a secret).
 * Rows older than 30 days are removed now and then (1 in 100 writes). It
 * never throws: a log that cannot be written is simply not written.
 */
final class StreamrApiLog
{
    public const KEEP_DAYS = 30;

    /** Refused calls (bad token, not set up) logged per address per minute, so a flood cannot fill the table. */
    public const REFUSALS_PER_MINUTE = 20;

    public static function record(string $direction, string $method, string $path, ?int $status, ?string $errorCode, ?int $durationMs, array $extra = []): void
    {
        try {
            if (($extra['refusal'] ?? false) && ! self::refusalAllowed((string) ($extra['ip'] ?? ''))) {
                return;
            }

            DB::table('streamr_api_requests')->insert([
                'direction' => $direction === 'out' ? 'out' : 'in',
                'method' => mb_substr(strtoupper($method), 0, 6),
                'path' => mb_substr($path, 0, 191),
                'status' => $status,
                'errorCode' => $errorCode !== null ? mb_substr($errorCode, 0, 40) : null,
                'durationMs' => $durationMs !== null ? max(0, $durationMs) : null,
                'requestId' => self::shortOrNull($extra['request_id'] ?? null, 36),
                'idempotencyKey' => self::shortOrNull($extra['idempotency_key'] ?? null, 36),
                'ip' => self::shortOrNull($extra['ip'] ?? null, 45),
                'note' => self::shortOrNull($extra['note'] ?? null, 255),
                'created_at' => StreamrTime::stamp(),
            ]);

            if (random_int(1, 100) === 1) {
                self::prune();
            }
        } catch (\Throwable $e) {
            // The log is a convenience; the call it describes already happened.
        }
    }

    public static function prune(): int
    {
        try {
            return DB::table('streamr_api_requests')
                ->where('created_at', '<', StreamrTime::now()->subDays(self::KEEP_DAYS)->format('Y-m-d H:i:s'))
                ->delete();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private static function refusalAllowed(string $ip): bool
    {
        try {
            $key = 'streamr:api:refusals:' . sha1($ip) . ':' . date('YmdHi');
            Cache::add($key, 0, 120);

            return Cache::increment($key) <= self::REFUSALS_PER_MINUTE;
        } catch (\Throwable $e) {
            return true;
        }
    }

    private static function shortOrNull($value, int $max): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : mb_substr($value, 0, $max);
    }
}
