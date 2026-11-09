<?php

namespace App\Services\Streamr;

use App\Models\EcomOrder;
use Illuminate\Support\Facades\Log;

/**
 * Rings Streamr when one of its /ecom-orders orders is decided here (verified,
 * rejected, completed, cancelled, refunded, reverted, deleted). The ring is a
 * doorbell, not the decision: Streamr reads GET /api/streamr/v1/orders/{number}
 * and applies what it finds, so a lost or repeated ring is harmless (its
 * 5-minute sync catches up).
 *
 * Guarded so it can never touch another venture's order or break an
 * /ecom-orders request:
 *   1. the order number must start with STR- (no query at all otherwise)
 *   2. Streamr must be linked (STREAMR_APP_URL + STREAMR_API_TOKEN)
 *   3. a fresh read must find a live item of the Streamr store
 *   4. the signed POST goes out after the response (8 s, no retry), one per
 *      order per request, carrying the order as it stands by then
 * Everything is inside try/catch; failures are only logged.
 */
class StreamrOrderHookService
{
    private const PENDING = 'streamr.order-hook.pending';

    public function handle(EcomOrder $order, string $source = 'observer'): void
    {
        try {
            $prefix = (string) config('streamr.order_prefix', 'STR-');
            if ($prefix === '' || ! str_starts_with((string) $order->orderNumber, $prefix)) {
                return;
            }
            // A decision changes the "to review" badge (a cache key, not a query).
            StreamrBadges::forget();
            if ((string) config('streamr.app_url', '') === '' || trim((string) config('streamr.api_token', '')) === '') {
                return;
            }

            $id = (int) $order->id;
            $pending = $this->pending();
            if ($id <= 0 || isset($pending[$id])) {
                return;
            }

            // A fresh copy: never refresh() the caller's model.
            $fresh = EcomOrder::find($id);
            if (! $fresh || ! $fresh->items()->where('deleteStatus', 1)->where('productStore', StreamrSettings::storeName())->exists()) {
                return;
            }

            $actor = ['id' => auth()->id(), 'name' => (string) (auth()->user()?->name ?? 'System')];
            $at = StreamrTime::now()->toIso8601String();
            $pending[$id] = true;

            dispatch(function () use ($id, $actor, $source, $at) {
                StreamrOrderHookService::ring($id, $actor, $source, $at);
            })->afterResponse();
        } catch (\Throwable $e) {
            Log::error('[Streamr] order hook failed: ' . $e->getMessage(), ['order_id' => $order->id ?? null]);
        }
    }

    /** After the response: the signed doorbell, with the order as it is now. Never throws. */
    public static function ring(int $id, array $actor, string $source, string $at): void
    {
        try {
            $pending = app()->bound(self::PENDING) ? app(self::PENDING) : null;
            if ($pending instanceof \ArrayObject) {
                unset($pending[$id]);
            }

            $order = EcomOrder::find($id);
            if (! $order) {
                return;
            }

            $result = app(StreamrCallbacks::class)->orderChanged(self::body($order, $actor, $source, $at));
            if (! $result['ok']) {
                Log::warning('[Streamr] order doorbell not delivered: ' . $result['message'], ['order' => $order->orderNumber, 'status' => $result['status']]);
            }
        } catch (\Throwable $e) {
            Log::warning('[Streamr] order doorbell failed: ' . $e->getMessage(), ['order_id' => $id]);
        }
    }

    /** The doorbell body (Streamr's architecture §10.3, POST /api/mother/orders/changed). */
    public static function body(EcomOrder $order, array $actor, string $source, string $at): array
    {
        return [
            'event' => 'ecom_order.changed',
            'order_number' => (string) $order->orderNumber,
            'mother_order_id' => (int) $order->id,
            'order_status' => (string) ($order->orderStatus ?? 'pending'),
            'payment_verification_status' => (string) $order->paymentVerificationStatus,
            'delete_status' => (int) $order->deleteStatus,
            'actor' => ['id' => $actor['id'] ?? null, 'name' => (string) ($actor['name'] ?? 'System')],
            'source' => $source,
            'at' => $at,
        ];
    }

    /** Orders already waiting for their doorbell in this request (one ring each). */
    private function pending(): \ArrayObject
    {
        if (! app()->bound(self::PENDING)) {
            app()->instance(self::PENDING, new \ArrayObject());
        }

        return app(self::PENDING);
    }
}
