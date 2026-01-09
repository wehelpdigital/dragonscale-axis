<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EcomProductShippingRestriction extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_products_shipping_restrictions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shippingId',
        'storeId',
        'productId',
        'variantId',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'shippingId' => 'integer',
        'storeId' => 'integer',
        'productId' => 'integer',
        'variantId' => 'integer',
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
     * Get the shipping method that this restriction belongs to.
     */
    public function shipping()
    {
        return $this->belongsTo(EcomProductsShipping::class, 'shippingId');
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

    /**
     * Get the variant for this restriction.
     */
    public function variant()
    {
        return $this->belongsTo(EcomProductVariant::class, 'variantId');
    }
}
