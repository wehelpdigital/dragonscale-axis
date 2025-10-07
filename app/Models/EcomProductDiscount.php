<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EcomProductDiscount extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_products_discount';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'discountName',
        'discountDescription',
        'discountType',
        'discountTrigger',
        'discountCode',
        'amountType',
        'valuePercent',
        'valueAmount',
        'valueReplacement',
        'discountCapType',
        'discountCapValue',
        'usageLimit',
        'expirationType',
        'dateTimeExpiration',
        'timerCountdown',
        'isActive',
        'deleteStatus',
    ];

    /**
     * Scope a query to only include active discounts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }
}

