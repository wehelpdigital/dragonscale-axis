<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcomStoreSpecialTag extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_store_special_tags';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'storeId',
        'tagName',
        'tagValue',
        'tagDescription',
        'isActive',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'storeId' => 'integer',
        'isActive' => 'boolean',
        'deleteStatus' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope to get only active tags (deleteStatus = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Scope to get only enabled tags (isActive = true)
     */
    public function scopeEnabled($query)
    {
        return $query->where('isActive', true);
    }

    /**
     * Scope to filter by store
     */
    public function scopeForStore($query, $storeId)
    {
        return $query->where('storeId', $storeId);
    }

    /**
     * Get the store that owns this tag
     */
    public function store()
    {
        return $this->belongsTo(EcomProductStore::class, 'storeId');
    }
}
