<?php

namespace App\Models;

class EcomOrderItem extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_order_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'orderId',
        'productId',
        'productName',
        'productStore',
        'productType',
        'variantId',
        'variantName',
        'variantSku',
        'variantImage',
        'unitPrice',
        'quantity',
        'subtotal',
        'shippingMethodId',
        'shippingMethodName',
        'shippingCost',
        'accessClientId',
        'accessClientName',
        'accessClientPhone',
        'accessClientEmail',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'orderId' => 'integer',
        'productId' => 'integer',
        'variantId' => 'integer',
        'unitPrice' => 'decimal:2',
        'quantity' => 'integer',
        'subtotal' => 'decimal:2',
        'shippingMethodId' => 'integer',
        'shippingCost' => 'decimal:2',
        'accessClientId' => 'integer',
        'deleteStatus' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Scope to get only active items (deleteStatus = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Get the order that this item belongs to.
     */
    public function order()
    {
        return $this->belongsTo(EcomOrder::class, 'orderId');
    }

    /**
     * Get formatted unit price attribute
     */
    public function getFormattedUnitPriceAttribute()
    {
        return '₱' . number_format($this->unitPrice, 2);
    }

    /**
     * Get formatted subtotal attribute
     */
    public function getFormattedSubtotalAttribute()
    {
        return '₱' . number_format($this->subtotal, 2);
    }

    /**
     * Get formatted shipping cost attribute
     */
    public function getFormattedShippingCostAttribute()
    {
        return '₱' . number_format($this->shippingCost, 2);
    }
}
