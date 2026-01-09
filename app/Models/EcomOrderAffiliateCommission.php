<?php

namespace App\Models;

class EcomOrderAffiliateCommission extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_order_affiliate_commissions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'orderId',
        'affiliateId',
        'affiliateName',
        'affiliateEmail',
        'affiliatePhone',
        'storeId',
        'storeName',
        'commissionPercentage',
        'baseAmount',
        'commissionAmount',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'orderId' => 'integer',
        'affiliateId' => 'integer',
        'storeId' => 'integer',
        'commissionPercentage' => 'decimal:2',
        'baseAmount' => 'decimal:2',
        'commissionAmount' => 'decimal:2',
        'deleteStatus' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Scope to get only active commissions (deleteStatus = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Get the order that this commission belongs to.
     */
    public function order()
    {
        return $this->belongsTo(EcomOrder::class, 'orderId');
    }

    /**
     * Get formatted commission amount attribute
     */
    public function getFormattedCommissionAmountAttribute()
    {
        return '₱' . number_format($this->commissionAmount, 2);
    }

    /**
     * Get formatted base amount attribute
     */
    public function getFormattedBaseAmountAttribute()
    {
        return '₱' . number_format($this->baseAmount, 2);
    }
}
