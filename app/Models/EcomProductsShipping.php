<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcomProductsShipping extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_products_shipping';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shippingName',
        'shippingDescription',
        'defaultPrice',
        'defaultMaxQuantity',
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
        'defaultPrice' => 'decimal:2',
        'defaultMaxQuantity' => 'integer',
        'isActive' => 'boolean',
        'deleteStatus' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope to get only active shipping methods (isActive = 1 and deleteStatus = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('isActive', 1)->where('deleteStatus', 1);
    }

    /**
     * Get formatted default price attribute
     */
    public function getFormattedDefaultPriceAttribute()
    {
        return $this->defaultPrice ? '₱' . number_format($this->defaultPrice, 2) : '₱0.00';
    }

    /**
     * Get excerpt of shipping description
     */
    public function getDescriptionExcerptAttribute()
    {
        if (!$this->shippingDescription) {
            return 'No description';
        }

        $description = strip_tags($this->shippingDescription);
        return strlen($description) > 50 ? substr($description, 0, 50) . '...' : $description;
    }
}
