<?php

namespace App\Services\Games;

use App\Models\EcomOrder;
use App\Models\EcomOrderAuditLog;
use App\Models\EcomOrderItem;
use App\Models\EcomProductStore;
use App\Models\EcomRefundItem;
use App\Models\EcomRefundRequest;
use App\Models\GameOrder;
use App\Models\GamePlayer;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Logs a game's real-money orders into the e-commerce orders (/ecom-orders),
 * so the game's sales sit beside every other store's.
 *
 * What gets logged: an order that is completed or refunded and is not a store
 * test purchase. A pending order waits (it is logged once it completes; one
 * that is cancelled before completing never was a sale and is never logged).
 * Once logged, the order follows the game: completed -> complete, refunded ->
 * refunded (with a processed full refund, so the Sales Report nets it out the
 * way it does a refund made in /ecom-refunds), cancelled -> cancelled.
 *
 * The /ecom-orders order is found again by its order number,
 * {prefix}-{the game's order id}, which ecom_orders keeps unique: logging the
 * same order twice, or from two runs at once, cannot make a second one.
 *
 * Shape of the logged order: store = the game's name (a store row is made for
 * it the first time, like Stores > Add would), one line item named after the
 * item bought, no shipping (shippingStatus not_applicable), paymentMethod
 * online_payment, no payment verification, handled by "System" (usersId 0),
 * customer = the player (username as first name, their email unless it is a
 * Steam placeholder). /ecom-orders is in pesos: the price is converted at the
 * rate given and the original price and rate are kept in the order notes.
 */
class GameEcomOrderLogger
{
    /** Game order status => ecom_orders.orderStatus. */
    public const STATUS_MAP = [
        'pending' => 'pending',
        'completed' => 'complete',
        'cancelled' => 'cancelled',
        'refunded' => 'refunded',
    ];

    /** Does this order belong in /ecom-orders yet? */
    public static function shouldLog(GameOrder $order): bool
    {
        if ($order->ecomOrderId) {
            return true;
        }

        return ! $order->isTestPurchase && in_array($order->orderStatus, ['completed', 'refunded'], true);
    }

    public static function orderNumber(GameOrder $order, string $prefix): string
    {
        return mb_substr($prefix . '-' . $order->remoteOrderId, 0, 50);
    }

    /**
     * Create or bring up to date the /ecom-orders order for one game order.
     *
     * @return string created | updated | unchanged | skipped
     */
    public function log(GameOrder $order, ?GamePlayer $player, string $storeName, string $prefix, float $usdToPhp): string
    {
        if (! self::shouldLog($order)) {
            return 'skipped';
        }

        $number = self::orderNumber($order, $prefix);

        $ecom = $order->ecomOrderId ? EcomOrder::find($order->ecomOrderId) : null;
        $ecom ??= EcomOrder::where('orderNumber', $number)->first();

        $created = false;
        if (! $ecom) {
            try {
                $ecom = $this->create($order, $player, $storeName, $number, $usdToPhp);
                $created = true;
            } catch (QueryException $e) {
                // Another run logged it a moment ago: the order number is unique.
                $ecom = EcomOrder::where('orderNumber', $number)->first();
                if (! $ecom) {
                    throw $e;
                }
            }
        }

        $changed = $created ? false : $this->follow($order, $ecom, $storeName);
        $this->link($order, $ecom, $order->orderStatus);

        return $created ? 'created' : ($changed ? 'updated' : 'unchanged');
    }

    /** The peso amount for a game price, or null when there is no rate for its currency. */
    public static function pesoAmount(int $priceCents, string $currency, float $usdToPhp): ?array
    {
        $currency = strtoupper($currency);
        if ($currency === 'PHP') {
            return ['amount' => round($priceCents / 100, 2), 'rate' => 1.0];
        }
        if ($currency === 'USD' && $usdToPhp > 0) {
            return ['amount' => round($priceCents / 100 * $usdToPhp, 2), 'rate' => $usdToPhp];
        }

        return null;
    }

    private function create(GameOrder $order, ?GamePlayer $player, string $storeName, string $number, float $usdToPhp): EcomOrder
    {
        $peso = self::pesoAmount($order->priceCents, (string) $order->currency, $usdToPhp);
        if (! $peso) {
            throw new RuntimeException('No peso rate for ' . $order->currency . ' prices, so it was not logged to /ecom-orders.');
        }

        return DB::transaction(function () use ($order, $player, $storeName, $number, $peso) {
            $store = $this->store($storeName);
            $at = $order->completedAt ?? $order->orderedAt ?? now();
            $email = $player && $player->hasRealEmail() ? $player->email : null;
            $name = $player?->username ?: 'Player #' . $order->remotePlayerId;
            $itemName = $order->itemName ?: ($order->orderKind === 'remove_ads' ? 'No Ads' : ($order->productId ?: 'In-game purchase'));

            $ecom = new EcomOrder();
            $ecom->fill([
                'usersId' => 0,
                'orderNumber' => $number,
                'orderStatus' => self::STATUS_MAP[$order->orderStatus] ?? 'pending',
                'shippingStatus' => 'not_applicable',
                'clientId' => null,
                'clientFirstName' => mb_substr($name, 0, 100),
                'clientEmail' => $email,
                'subtotal' => $peso['amount'],
                'shippingTotal' => 0,
                'discountTotal' => 0,
                'grandTotal' => $peso['amount'],
                'affiliateCommissionTotal' => 0,
                'netRevenue' => $peso['amount'],
                'orderNotes' => $this->notes($order, $storeName, $peso['rate']),
                'isPackage' => false,
                'paymentMethod' => $order->store === 'web' ? 'manual_gcash' : 'online_payment',
                'paymentVerificationStatus' => 'not_required',
                'paymentReferenceNumber' => $order->storeOrderId ? mb_substr($order->storeOrderId, 0, 100) : null,
                'deleteStatus' => 1,
            ]);
            // Dated when the sale happened, so the Sales Report puts it on the right day.
            $ecom->created_at = Carbon::parse($at)->timezone(config('app.timezone'));
            $ecom->save();

            $item = EcomOrderItem::create([
                'orderId' => $ecom->id,
                'productId' => null,
                'productName' => mb_substr($itemName, 0, 255),
                'productStore' => $store->storeName,
                'productType' => 'access',
                'variantId' => null,
                'variantName' => GameOrder::KINDS[$order->orderKind] ?? $order->orderKind,
                'variantSku' => $order->productId ? mb_substr($order->productId, 0, 100) : null,
                'unitPrice' => $peso['amount'],
                'quantity' => 1,
                'subtotal' => $peso['amount'],
                'shippingCost' => 0,
                'accessClientName' => mb_substr($name, 0, 255),
                'accessClientEmail' => $email,
                'deleteStatus' => 1,
            ]);

            EcomOrderAuditLog::logAction(
                $ecom,
                'order_created',
                null,
                null,
                null,
                "Order #{$number} logged from {$storeName} ({$order->storeLabel()}): {$itemName}, "
                    . $order->formattedPrice() . ' = ₱' . number_format($peso['amount'], 2)
            );

            if ($order->orderStatus === 'refunded') {
                $this->refund($ecom, $item, $order, $store, $storeName);
            }

            $order->forceFill(['ecomPhpAmount' => $peso['amount'], 'ecomRate' => $peso['rate']]);

            return $ecom;
        });
    }

    /** Make the /ecom-orders order say what the game says now. */
    private function follow(GameOrder $order, EcomOrder $ecom, string $storeName): bool
    {
        $wanted = self::STATUS_MAP[$order->orderStatus] ?? null;
        if (! $wanted || $ecom->orderStatus === $wanted) {
            return false;
        }

        DB::transaction(function () use ($order, $ecom, $wanted, $storeName) {
            $previous = $ecom->orderStatus;
            $ecom->orderStatus = $wanted;
            $ecom->save();

            EcomOrderAuditLog::logAction(
                $ecom,
                $wanted === 'refunded' ? 'refund_processed' : 'status_change',
                'orderStatus',
                $previous,
                $wanted,
                "Status changed in {$storeName} ({$order->storeLabel()}): the game reports this order as {$order->orderStatus}."
            );

            if ($wanted === 'refunded') {
                $item = EcomOrderItem::where('orderId', $ecom->id)->where('deleteStatus', 1)->first();
                $store = $this->store($storeName);
                if ($item) {
                    $this->refund($ecom, $item, $order, $store, $storeName);
                }
            }
        });

        return true;
    }

    /** A processed full refund, the way /ecom-refunds records one, unless the order has one. */
    private function refund(EcomOrder $ecom, EcomOrderItem $item, GameOrder $order, EcomProductStore $store, string $storeName): void
    {
        $exists = EcomRefundRequest::where('orderId', $ecom->id)
            ->where('deleteStatus', 1)
            ->where('status', 'processed')
            ->exists();
        if ($exists) {
            return;
        }

        $when = Carbon::parse($order->remoteUpdatedAt ?? now())->timezone(config('app.timezone'));
        $amount = (float) $ecom->grandTotal;

        $refund = EcomRefundRequest::create([
            'orderId' => $ecom->id,
            'storeId' => $store->id,
            'storeName' => $storeName,
            'clientName' => $ecom->clientFirstName,
            'clientEmail' => $ecom->clientEmail,
            'refundNumber' => EcomRefundRequest::generateRefundNumber(),
            'requestReason' => 'Refunded through ' . $order->storeLabel() . '.',
            'requestedAt' => $when,
            'status' => 'processed',
            'refundType' => 'full',
            'orderSubtotal' => $amount,
            'requestedAmount' => $amount,
            'approvedAmount' => $amount,
            'processedBy' => null,
            'processedAt' => $when,
            'adminNotes' => 'Logged by the ' . $storeName . ' sync from the game server.',
            'deleteStatus' => 1,
        ]);

        EcomRefundItem::create([
            'refundRequestId' => $refund->id,
            'orderItemId' => $item->id,
            'productId' => null,
            'variantId' => null,
            'productName' => $item->productName,
            'variantName' => $item->variantName,
            'productStore' => $item->productStore,
            'originalQuantity' => 1,
            'refundQuantity' => 1,
            'unitPrice' => $item->unitPrice,
            'refundAmount' => $amount,
            'deleteStatus' => 1,
        ]);
    }

    /** The game's store in E-commerce > Stores, made the first time an order needs it. */
    private function store(string $storeName): EcomProductStore
    {
        $store = EcomProductStore::where('storeName', $storeName)->orderByDesc('deleteStatus')->first();

        return $store ?? EcomProductStore::create([
            'storeName' => $storeName,
            'storeDescription' => 'In-game purchases, logged automatically from the game server.',
            'storeLogo' => null,
            'isActive' => 1,
            'deleteStatus' => 1,
        ]);
    }

    private function link(GameOrder $order, EcomOrder $ecom, string $status): void
    {
        $order->forceFill([
            'ecomOrderId' => $ecom->id,
            'ecomStatus' => $status,
            'ecomLoggedAt' => $order->ecomLoggedAt ?? now(),
            'ecomError' => null,
        ])->save();
    }

    private function notes(GameOrder $order, string $storeName, float $rate): string
    {
        $parts = [
            $storeName . ' in-game purchase via ' . $order->storeLabel() . '.',
            'Game order ' . $order->remoteOrderId . '.',
        ];
        if ($order->storeOrderId) {
            $parts[] = $order->storeLabel() . ' order ' . $order->storeOrderId . '.';
        }
        $parts[] = 'Price ' . $order->formattedPrice() . ($rate != 1.0 ? ', logged at ₱' . number_format($rate, 2) . ' per ' . strtoupper((string) $order->currency) . '.' : '.');

        return implode(' ', $parts);
    }
}
