<?php

namespace App\Models;

class EcomOrderDiscount extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_order_discounts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'orderId',
        'discountId',
        'discountName',
        'discountCode',
        'discountType',
        'discountValue',
        'calculatedAmount',
        'isAutoApplied',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'orderId' => 'integer',
        'discountId' => 'integer',
        'discountValue' => 'decimal:2',
        'calculatedAmount' => 'decimal:2',
        'isAutoApplied' => 'boolean',
        'deleteStatus' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Scope to get only active discounts (deleteStatus = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Get the order that this discount belongs to.
     */
    public function order()
    {
        return $this->belongsTo(EcomOrder::class, 'orderId');
    }

    /**
     * Get formatted calculated amount attribute
     */
    public function getFormattedCalculatedAmountAttribute()
    {
        return '₱' . number_format($this->calculatedAmount, 2);
    }

    /**
     * Get display value attribute (shows percentage or fixed amount)
     */
    public function getDisplayValueAttribute()
    {
        if ($this->discountType === 'percentage') {
            return $this->discountValue . '%';
        }
        return '₱' . number_format($this->discountValue, 2);
    }
}
