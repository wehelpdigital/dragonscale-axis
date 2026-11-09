<?php

namespace App\Services\Streamr;

use Illuminate\Support\Carbon;

/**
 * Times between the two apps. Streamr sends ISO 8601 with an offset; this app
 * stores Manila wall-clock (Asia/Manila, like every mother table) and answers
 * in ISO 8601 with +08:00. A Carbon in another zone written straight to the
 * database would be stored hours off, so everything goes through here.
 */
final class StreamrTime
{
    public const ZONE = 'Asia/Manila';

    public static function now(): Carbon
    {
        return Carbon::now(self::ZONE);
    }

    /** An ISO string (any offset) or a stored Manila value, as Manila time; null when blank or unreadable. */
    public static function parse($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            if ($value instanceof \DateTimeInterface) {
                return Carbon::instance($value)->setTimezone(self::ZONE);
            }

            return Carbon::parse((string) $value, self::ZONE)->setTimezone(self::ZONE);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** ISO 8601 with +08:00, or null. */
    public static function iso($value): ?string
    {
        $at = self::parse($value);

        return $at ? $at->toIso8601String() : null;
    }

    /** Manila wall-clock for a query-builder write. */
    public static function stamp($value = null): string
    {
        return ($value === null ? self::now() : (self::parse($value) ?? self::now()))->format('Y-m-d H:i:s');
    }

    /** "Nov 8, 2026 3:10 PM", or the fallback. */
    public static function show($value, string $fallback = '—', string $format = 'M j, Y g:i A'): string
    {
        $at = self::parse($value);

        return $at ? $at->format($format) : $fallback;
    }
}
