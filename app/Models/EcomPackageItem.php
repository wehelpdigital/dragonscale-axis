<?php

namespace App\Models;

class EcomPackageItem extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_package_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'packageId',
        'productId',
        'variantId',
        'productName',
        'variantName',
        'variantSku',
        'storeName',
        'unitPrice',
        'quantity',
        'subtotal',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'packageId' => 'integer',
        'productId' => 'integer',
        'variantId' => 'integer',
        'unitPrice' => 'decimal:2',
        'quantity' => 'integer',
        'subtotal' => 'decimal:2',
        'deleteStatus' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Scope to get only active items (deleteStatus = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Scope to filter by package
     */
    public function scopeForPackage($query, $packageId)
    {
        return $query->where('packageId', $packageId);
    }

    /**
     * Get the package that owns this item.
     */
    public function package()
    {
        return $this->belongsTo(EcomPackage::class, 'packageId', 'id');
    }

    /**
     * Get the product reference.
     */
    public function product()
    {
        return $this->belongsTo(EcomProduct::class, 'productId', 'id');
    }

    /**
     * Get the variant reference.
     */
    public function variant()
    {
        return $this->belongsTo(EcomProductVariant::class, 'variantId', 'id');
    }

    /**
     * Calculate subtotal based on unit price and quantity.
     *
     * @return float
     */
    public function calculateSubtotal()
    {
        return $this->unitPrice * $this->quantity;
    }

    /**
     * Boot method for model events.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-calculate subtotal before saving
        static::saving(function ($item) {
            $item->subtotal = $item->unitPrice * $item->quantity;
        });

        // Update package calculated price after item changes
        static::saved(function ($item) {
            if ($item->package) {
                $item->package->recalculatePrice();
            }
        });

        static::deleted(function ($item) {
            if ($item->package) {
                $item->package->recalculatePrice();
            }
        });
    }
}
