<?php

namespace App\Services\Streamr;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The sidebar's Streamr badges: payments waiting for a person, and tickets
 * with client messages nobody has read. Kept for a minute. Any error (the
 * tables not made yet on this host, the database busy) gives zeros, so the
 * sidebar always renders.
 */
final class StreamrBadges
{
    public const CACHE_KEY = 'streamr:badges';

    /** @return array{reviews: int, support: int, total: int} */
    public static function counts(): array
    {
        try {
            $counts = Cache::remember(self::CACHE_KEY, 60, fn () => self::fresh());
        } catch (\Throwable $e) {
            $counts = null;
        }

        if (! is_array($counts)) {
            return ['reviews' => 0, 'support' => 0, 'total' => 0];
        }
        $reviews = max(0, (int) ($counts['reviews'] ?? 0));
        $support = max(0, (int) ($counts['support'] ?? 0));

        return ['reviews' => $reviews, 'support' => $support, 'total' => $reviews + $support];
    }

    public static function forget(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (\Throwable $e) {
            // Gone within the minute anyway.
        }
    }

    /** @return array{reviews: int, support: int} */
    private static function fresh(): array
    {
        try {
            $reviews = (int) DB::table('streamr_orders as so')
                ->join('ecom_orders as o', 'o.id', '=', 'so.ecomOrderId')
                ->where('so.delete_status', 'active')
                ->where('o.deleteStatus', 1)
                ->where(fn ($q) => $q->where('o.orderStatus', 'pending')->orWhereNull('o.orderStatus'))
                ->where('o.paymentVerificationStatus', 'pending')
                ->count();
        } catch (\Throwable $e) {
            $reviews = 0;
        }

        try {
            $support = (int) DB::table('streamr_desk_tickets')
                ->where('delete_status', 'active')
                ->where('status', '!=', 'closed')
                ->where('adminUnread', '>', 0)
                ->count();
        } catch (\Throwable $e) {
            $support = 0;
        }

        return ['reviews' => $reviews, 'support' => $support];
    }
}
