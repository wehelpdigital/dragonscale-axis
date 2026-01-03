<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcomProductStore extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_product_stores';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'storeName',
        'storeDescription',
        'storeLogo',
        'isActive',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'isActive' => 'integer',
        'deleteStatus' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope to get only active stores (deleteStatus = 1)
     * Note: E-commerce module uses integer deleteStatus (1=active, 0=deleted)
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Scope to get only enabled stores (isActive = 1)
     */
    public function scopeEnabled($query)
    {
        return $query->where('isActive', 1);
    }

    /**
     * Get the products for this store
     */
    public function products()
    {
        return $this->hasMany(EcomProduct::class, 'productStore', 'storeName');
    }

    /**
     * Get active products count for this store
     */
    public function getActiveProductsCountAttribute()
    {
        return $this->products()->where('deleteStatus', 1)->count();
    }
}
