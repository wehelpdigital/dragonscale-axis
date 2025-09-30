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
        'isActive' => 'boolean',
        'deleteStatus' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope to get only active stores (isActive = 1 and deleteStatus = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('isActive', 1)->where('deleteStatus', 1);
    }

    /**
     * Get the products for this store
     */
    public function products()
    {
        return $this->hasMany(EcomProduct::class, 'productStore', 'storeName');
    }
}

