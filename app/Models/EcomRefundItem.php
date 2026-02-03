<?php

namespace App\Models;

class EcomRefundItem extends BaseModel
{
    protected $table = 'ecom_refund_items';

    protected $fillable = [
        'refundRequestId',
        'orderItemId',
        'productId',
        'variantId',
        'productName',
        'variantName',
        'productStore',
        'originalQuantity',
        'refundQuantity',
        'unitPrice',
        'refundAmount',
        'deleteStatus',
    ];

    protected $casts = [
        'refundRequestId' => 'integer',
        'orderItemId' => 'integer',
        'productId' => 'integer',
        'variantId' => 'integer',
        'originalQuantity' => 'integer',
        'refundQuantity' => 'integer',
        'unitPrice' => 'decimal:2',
        'refundAmount' => 'decimal:2',
        'deleteStatus' => 'integer',
    ];

    /**
     * Scope to get only active records
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Relationships
     */
    public function refundRequest()
    {
        return $this->belongsTo(EcomRefundRequest::class, 'refundRequestId');
    }

    public function orderItem()
    {
        return $this->belongsTo(EcomOrderItem::class, 'orderItemId');
    }

    /**
     * Accessors
     */
    public function getFormattedUnitPriceAttribute()
    {
        return '₱' . number_format($this->unitPrice, 2);
    }

    public function getFormattedRefundAmountAttribute()
    {
        return '₱' . number_format($this->refundAmount, 2);
    }

    public function getProductDisplayNameAttribute()
    {
        if ($this->variantName) {
            return $this->productName . ' - ' . $this->variantName;
        }
        return $this->productName;
    }
}
