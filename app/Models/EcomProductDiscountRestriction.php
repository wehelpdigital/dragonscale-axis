<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EcomProductDiscountRestriction extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_products_discounts_restrictions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'discountId',
        'storeId',
        'productId',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'discountId' => 'integer',
        'storeId' => 'integer',
        'productId' => 'integer',
        'deleteStatus' => 'integer',
    ];

    /**
     * Scope to get only active restrictions (deleteStatus = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Get the discount that this restriction belongs to.
     */
    public function discount()
    {
        return $this->belongsTo(EcomProductDiscount::class, 'discountId');
    }

    /**
     * Get the store for this restriction.
     */
    public function store()
    {
        return $this->belongsTo(EcomProductStore::class, 'storeId');
    }

    /**
     * Get the product for this restriction.
     */
    public function product()
    {
        return $this->belongsTo(EcomProduct::class, 'productId');
    }
}
