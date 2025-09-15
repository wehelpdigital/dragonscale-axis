<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcomProductVariant extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_products_variants';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ecomProductsId',
        'ecomVariantName',
        'ecomVariantDescription',
        'stocksAvailable',
        'ecomVariantPrice',
        'maxOrderPerTransaction',
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
        'stocksAvailable' => 'integer',
        'ecomVariantPrice' => 'decimal:2',
        'isActive' => 'boolean',
        'deleteStatus' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the product that owns the variant.
     */
    public function product()
    {
        return $this->belongsTo(EcomProduct::class, 'ecomProductsId', 'id');
    }

    /**
     * Scope to get only active variants (deleteStatus = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Scope to filter by product ID
     */
    public function scopeByProduct($query, $productId)
    {
        return $query->where('ecomProductsId', $productId);
    }
}
