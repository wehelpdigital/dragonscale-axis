<?php

namespace App\Models;

/**
 * One real-money purchase in a game (games_orders): Google Play or Steam,
 * an item or Remove Ads. ecomOrderId is the /ecom-orders order it was logged
 * as; ecomStatus is the game status that order last reflected, so an order
 * whose status moved on since (refunded, cancelled) is found by comparing the
 * two columns.
 */
class GameOrder extends BaseModel
{
    protected $table = 'games_orders';

    public const STORES = [
        'google_play' => 'Google Play',
        'steam' => 'Steam',
    ];

    public const KINDS = [
        'item' => 'Item',
        'remove_ads' => 'Remove Ads',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
    ];

    public const STATUS_BADGES = [
        'pending' => 'bg-warning text-dark',
        'completed' => 'bg-success',
        'cancelled' => 'bg-secondary',
        'refunded' => 'bg-danger',
    ];

    protected $fillable = [
        'gameKey',
        'remoteOrderId',
        'remotePlayerId',
        'store',
        'orderKind',
        'productId',
        'itemSlug',
        'itemName',
        'itemCategory',
        'priceCents',
        'currency',
        'orderStatus',
        'isTestPurchase',
        'storeOrderId',
        'orderedAt',
        'completedAt',
        'remoteUpdatedAt',
        'ecomOrderId',
        'ecomStatus',
        'ecomLoggedAt',
        'ecomPhpAmount',
        'ecomRate',
        'ecomError',
        'delete_status',
    ];

    protected $casts = [
        'remotePlayerId' => 'integer',
        'priceCents' => 'integer',
        'isTestPurchase' => 'boolean',
        'orderedAt' => 'datetime',
        'completedAt' => 'datetime',
        'remoteUpdatedAt' => 'datetime',
        'ecomOrderId' => 'integer',
        'ecomLoggedAt' => 'datetime',
        'ecomPhpAmount' => 'decimal:2',
        'ecomRate' => 'decimal:4',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    public function scopeForGame($query, string $gameKey)
    {
        return $query->where('gameKey', $gameKey);
    }

    /** Money actually taken: completed, and not a store test purchase. */
    public function scopeCounted($query)
    {
        return $query->where('orderStatus', 'completed')->where('isTestPurchase', false);
    }

    public function player()
    {
        return GamePlayer::forGame($this->gameKey)->where('remotePlayerId', $this->remotePlayerId)->first();
    }

    public function amount(): float
    {
        return round($this->priceCents / 100, 2);
    }

    public function formattedPrice(): string
    {
        return self::money($this->priceCents, $this->currency);
    }

    public static function money(int|float|null $cents, ?string $currency = 'USD'): string
    {
        $currency = strtoupper((string) ($currency ?: 'USD'));
        $amount = number_format(((float) $cents) / 100, 2);

        return $currency === 'USD' ? '$' . $amount : $currency . ' ' . $amount;
    }

    public function storeLabel(): string
    {
        return self::STORES[$this->store] ?? ucfirst(str_replace('_', ' ', (string) $this->store));
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->orderKind] ?? ucfirst(str_replace('_', ' ', (string) $this->orderKind));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->orderStatus] ?? ucfirst((string) $this->orderStatus);
    }
}
