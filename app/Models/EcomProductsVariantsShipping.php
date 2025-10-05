<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcomProductsVariantsShipping extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_products_variants_shipping';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ecomVariantId',
        'ecomShippingId',
        // Add other fields as needed
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope to get records by variant ID (no soft delete needed)
     */
    public function scopeActive($query)
    {
        return $query; // No filtering needed since table doesn't have soft delete columns
    }

    /**
     * Scope to filter by variant ID
     */
    public function scopeByVariant($query, $variantId)
    {
        return $query->where('ecomVariantId', $variantId);
    }

    /**
     * Scope to filter by shipping ID
     */
    public function scopeByShipping($query, $shippingId)
    {
        return $query->where('ecomShippingId', $shippingId);
    }

    /**
     * Get the variant that owns this shipping assignment.
     */
    public function variant()
    {
        return $this->belongsTo(EcomProductVariant::class, 'ecomVariantId', 'id');
    }

    /**
     * Get the shipping method for this assignment.
     */
    public function shipping()
    {
        return $this->belongsTo(EcomProductsShipping::class, 'ecomShippingId', 'id');
    }
}
