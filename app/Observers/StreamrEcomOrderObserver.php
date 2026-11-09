<?php

namespace App\Observers;

use App\Models\EcomOrder;
use App\Services\Streamr\StreamrOrderHookService;
use Illuminate\Support\Facades\Log;

/**
 * Streamr's order hook. Every way /ecom-orders decides an order ends in
 * $order->save() (Payments > Verify/Reject, Status, refunds, reverts,
 * cancels), so one observer hears them all. Only a change of orderStatus,
 * paymentVerificationStatus or deleteStatus goes on, and the hook service
 * returns at once for any order that is not Streamr's (STR- prefix, checked
 * before anything is read). Nothing here can throw into the save.
 */
class StreamrEcomOrderObserver
{
    public function updated(EcomOrder $order): void
    {
        try {
            if ($order->wasChanged(['orderStatus', 'paymentVerificationStatus', 'deleteStatus'])) {
                app(StreamrOrderHookService::class)->handle($order, 'observer');
            }
        } catch (\Throwable $e) {
            Log::error('[Streamr] order observer failed: ' . $e->getMessage(), ['order_id' => $order->id ?? null]);
        }
    }
}
