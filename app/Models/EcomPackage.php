<?php

namespace App\Models;

class EcomPackage extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_packages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'packageName',
        'packageDescription',
        'calculatedPrice',
        'packagePrice',
        'packageStatus',
        'usersId',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'calculatedPrice' => 'decimal:2',
        'packagePrice' => 'decimal:2',
        'usersId' => 'integer',
        'deleteStatus' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Scope to get only active packages (deleteStatus = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('packageStatus', $status);
    }

    /**
     * Scope to filter by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Get the items in this package.
     */
    public function items()
    {
        return $this->hasMany(EcomPackageItem::class, 'packageId', 'id')
            ->where('deleteStatus', 1);
    }

    /**
     * Get the user who created this package.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId', 'id');
    }

    /**
     * Calculate the total price from items.
     *
     * @return float
     */
    public function calculateTotalPrice()
    {
        return $this->items()->sum('subtotal');
    }

    /**
     * Recalculate and update the calculated price.
     *
     * @return void
     */
    public function recalculatePrice()
    {
        $this->calculatedPrice = $this->calculateTotalPrice();
        $this->save();
    }

    /**
     * Get the discount amount (difference between calculated and package price).
     *
     * @return float
     */
    public function getDiscountAmountAttribute()
    {
        return max(0, $this->calculatedPrice - $this->packagePrice);
    }

    /**
     * Get the discount percentage.
     *
     * @return float
     */
    public function getDiscountPercentageAttribute()
    {
        if ($this->calculatedPrice > 0) {
            return round(($this->discountAmount / $this->calculatedPrice) * 100, 2);
        }
        return 0;
    }

    /**
     * Get status badge class.
     *
     * @return string
     */
    public function getStatusBadgeAttribute()
    {
        return $this->packageStatus === 'active' ? 'bg-success' : 'bg-secondary';
    }
}
