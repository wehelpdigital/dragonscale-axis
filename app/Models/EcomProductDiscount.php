<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcomProductDiscount extends Model
{
    use HasFactory;

    protected $table = 'ecom_products_discount';

    protected $fillable = [
        'ecomProductsId',
        'discountName',
        'discountType',
        'discountCode',
        'timerType',
        'discountValueType',
        'countdownValueDays',
        'countdownValueMinutes',
        'scheduledEnding',
        'slotsRemainingValue',
        'discountValuePercentage',
        'discountValueAmount',
        'discountValueChange',
        'discountValueMax',
        'discountPriceMax',
        'isActive',
        'deleteStatus'
    ];

    protected $casts = [
        'scheduledEnding' => 'datetime',
        'countdownValueDays' => 'integer',
        'countdownValueMinutes' => 'integer',
        'slotsRemainingValue' => 'integer',
        'discountValuePercentage' => 'integer',
        'discountValueAmount' => 'decimal:2',
        'discountValueChange' => 'integer',
        'discountValueMax' => 'integer',
        'discountPriceMax' => 'integer',
        'isActive' => 'integer',
        'deleteStatus' => 'integer'
    ];

    /**
     * Scope to get only active discounts
     */
    public function scopeActive($query)
    {
        return $query->where('isActive', 1)->where('deleteStatus', 1);
    }

    /**
     * Get the product that owns the discount
     */
    public function product()
    {
        return $this->belongsTo(EcomProduct::class, 'ecomProductsId', 'id');
    }

    /**
     * Get formatted discount type
     */
    public function getFormattedDiscountTypeAttribute()
    {
        return $this->discountType === 'discount code' ? 'Discount Code' : 'Auto Apply';
    }
}
