<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcomProduct extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_products';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'productName',
        'productStore',
        'productDescription',
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
     * Scope to get only active products (deleteStatus = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Scope to filter by product name
     */
    public function scopeFilterByName($query, $name)
    {
        if ($name) {
            return $query->where('productName', 'LIKE', "%{$name}%");
        }
        return $query;
    }

    /**
     * Scope to filter by product store
     */
    public function scopeFilterByStore($query, $store)
    {
        if ($store) {
            return $query->where('productStore', 'LIKE', "%{$store}%");
        }
        return $query;
    }
}
