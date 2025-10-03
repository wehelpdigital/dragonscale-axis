<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcomProductsShippingOptions extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_products_shipping_options';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shippingId',
        'provinceTarget',
        'maxQuantity',
        'shippingPrice',
        'isActive',
        'deleteStatus',
        // Add other fields as needed
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'shippingPrice' => 'decimal:2',
        'isActive' => 'boolean',
        'deleteStatus' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope to get only active shipping options (deleteStatus = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Scope to filter by shipping ID
     */
    public function scopeByShippingId($query, $shippingId)
    {
        return $query->where('shippingId', $shippingId);
    }

    /**
     * Get formatted shipping price attribute
     */
    public function getFormattedShippingPriceAttribute()
    {
        return $this->shippingPrice ? '₱' . number_format($this->shippingPrice, 2) : '₱0.00';
    }

    /**
     * Get status text attribute
     */
    public function getStatusTextAttribute()
    {
        return $this->isActive ? 'Active' : 'Inactive';
    }

    /**
     * Get status badge class attribute
     */
    public function getStatusBadgeClassAttribute()
    {
        return $this->isActive ? 'bg-success' : 'bg-danger';
    }
}
