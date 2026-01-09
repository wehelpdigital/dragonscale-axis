<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class EcomAffiliateStore extends BaseModel
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_affiliate_stores';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'affiliateId',
        'storeId',
        'totalEarnings',
        'totalPending',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'affiliateId' => 'integer',
        'storeId' => 'integer',
        'totalEarnings' => 'decimal:2',
        'totalPending' => 'decimal:2',
        'deleteStatus' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope to get only active affiliate-store relationships (deleteStatus = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Get the affiliate that owns this store relationship.
     */
    public function affiliate()
    {
        return $this->belongsTo(EcomAffiliate::class, 'affiliateId', 'id');
    }

    /**
     * Get the store for this relationship.
     */
    public function store()
    {
        return $this->belongsTo(EcomProductStore::class, 'storeId', 'id');
    }
}
