<?php

namespace App\Services;

use App\Models\EcomOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AI Credits, from the mother app's side: granting a client's credits when
 * their credit-pack order is verified, and letting an admin adjust a balance
 * by hand.
 *
 * The balance is always SUM(delta) over the ledger, so it is auditable and
 * cannot drift.
 */
class AnisystemAiCreditService
{
    private const LEDGER = 'anisystem_ai_credit_ledger';
    private const PURCHASES = 'anisystem_ai_credit_purchases';

    public function balance(int $userId): float
    {
        return (float) DB::table(self::LEDGER)
            ->where('userId', $userId)
            ->where('deleteStatus', 1)
            ->sum('delta');
    }

    /** Balances for many clients at once, for the Clients list. */
    public function balances(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return DB::table(self::LEDGER)
            ->whereIn('userId', $userIds)
            ->where('deleteStatus', 1)
            ->groupBy('userId')
            ->selectRaw('userId, COALESCE(SUM(delta),0) as balance')
            ->pluck('balance', 'userId')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /**
     * Write a movement and return the new balance. Locks the client's ledger
     * so two concurrent writes cannot compute the same balanceAfter.
     */
    public function adjust(int $userId, float $delta, string $reason, string $source = 'admin', ?int $adminUserId = null): float
    {
        return DB::transaction(function () use ($userId, $delta, $reason, $source, $adminUserId) {
            $current = (float) DB::table(self::LEDGER)
                ->where('userId', $userId)
                ->where('deleteStatus', 1)
                ->lockForUpdate()
                ->sum('delta');

            $after = round($current + $delta, 2);

            DB::table(self::LEDGER)->insert([
                'userId' => $userId,
                'delta' => round($delta, 2),
                'balanceAfter' => $after,
                'reason' => $reason,
                'source' => $source,
                'adminUserId' => $adminUserId,
                'deleteStatus' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $after;
        });
    }

    /**
     * React to an admin decision on an ecom order that bought AI Credits.
     * Safe to call for any order — a no-op unless a pending credit purchase is
     * linked to it.
     */
    public function handleOrderDecision(EcomOrder $order): void
    {
        try {
            $purchase = DB::table(self::PURCHASES)
                ->where('ecomOrderId', $order->id)
                ->where('deleteStatus', 1)
                ->orderByDesc('id')
                ->first();

            if (! $purchase) {
                return;
            }

            $order->refresh();

            if (in_array($order->orderStatus, ['cancelled', 'refunded'], true)) {
                // Credits already granted are not clawed back — the client may
                // have spent them. Only a still-pending purchase is rejected.
                if ($purchase->status === 'pending') {
                    DB::table(self::PURCHASES)->where('id', $purchase->id)
                        ->update(['status' => 'rejected', 'updated_at' => now()]);
                }

                return;
            }

            if ($order->paymentVerificationStatus === 'verified') {
                // Lock + re-check so a double decision cannot grant twice.
                DB::transaction(function () use ($purchase) {
                    $fresh = DB::table(self::PURCHASES)->where('id', $purchase->id)->lockForUpdate()->first();
                    if (! $fresh || ! in_array($fresh->status, ['pending', 'rejected'], true)) {
                        return;
                    }

                    DB::table(self::PURCHASES)->where('id', $fresh->id)->update([
                        'status' => 'active',
                        'grantedAt' => Carbon::now('Asia/Manila'),
                        'updated_at' => now(),
                    ]);

                    $this->adjust(
                        (int) $fresh->userId,
                        (float) $fresh->credits,
                        $fresh->packName.' pack — order '.$fresh->orderNumber,
                        'purchase'
                    );
                });

                Log::info('AniSystem AI credits granted (payment verified)', [
                    'order_id' => $order->id,
                    'purchase_id' => $purchase->id,
                ]);

                return;
            }

            if ($order->paymentVerificationStatus === 'rejected' && $purchase->status === 'pending') {
                DB::table(self::PURCHASES)->where('id', $purchase->id)
                    ->update(['status' => 'rejected', 'updated_at' => now()]);
            }
        } catch (\Throwable $e) {
            Log::error('AniSystem AI credit order decision failed: '.$e->getMessage(), [
                'order_id' => $order->id ?? null,
            ]);
        }
    }
}
