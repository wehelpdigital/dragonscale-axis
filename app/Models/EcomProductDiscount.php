<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EcomProductDiscount extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_products_discount';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'discountName',
        'discountDescription',
        'discountType',
        'discountTrigger',
        'discountCode',
        'amountType',
        'valuePercent',
        'valueAmount',
        'valueReplacement',
        'discountCapType',
        'discountCapValue',
        'usageLimit',
        'expirationType',
        'dateTimeExpiration',
        'timerCountdown',
        'isActive',
        'restrictionType',
        'deleteStatus',
    ];

    /**
     * Scope a query to only include active discounts (not deleted).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Scope a query to only include enabled discounts (isActive = 1).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('isActive', 1);
    }

    /**
     * Scope a query to only include auto-apply discounts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAutoApply($query)
    {
        return $query->where('discountTrigger', 'Auto Apply');
    }

    /**
     * Scope a query to only include code-based discounts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCodeBased($query)
    {
        return $query->where('discountTrigger', 'Discount Code');
    }

    /**
     * Check if the discount is expired.
     *
     * @return bool
     */
    public function isExpired()
    {
        if ($this->expirationType === 'Time and Date' && $this->dateTimeExpiration) {
            return \Carbon\Carbon::parse($this->dateTimeExpiration)->isPast();
        }
        return false;
    }

    /**
     * Get the discount value for display.
     *
     * @return string
     */
    public function getDisplayValue()
    {
        if ($this->amountType === 'Percentage' && $this->valuePercent !== null) {
            return $this->valuePercent . '%';
        } elseif ($this->amountType === 'Specific Amount' && $this->valueAmount !== null) {
            return '₱' . number_format($this->valueAmount, 2);
        } elseif ($this->amountType === 'Price Replacement' && $this->valueReplacement !== null) {
            return '₱' . number_format($this->valueReplacement, 2) . ' (replacement)';
        }
        return 'N/A';
    }

    /**
     * Get the restrictions for this discount.
     */
    public function restrictions()
    {
        return $this->hasMany(EcomProductDiscountRestriction::class, 'discountId')->where('deleteStatus', 1);
    }

    /**
     * Get the restricted stores for this discount.
     */
    public function restrictedStores()
    {
        return $this->restrictions()->whereNotNull('storeId');
    }

    /**
     * Get the restricted products for this discount.
     */
    public function restrictedProducts()
    {
        return $this->restrictions()->whereNotNull('productId');
    }
}

