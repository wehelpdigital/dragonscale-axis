<?php

namespace App\Models;

class EcomOrder extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_orders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'usersId',
        'orderNumber',
        'orderStatus',
        'shippingStatus',
        'trackingNumber',
        'clientId',
        'clientFirstName',
        'clientMiddleName',
        'clientLastName',
        'clientPhone',
        'clientEmail',
        'shippingType',
        'shippingName',
        'shippingFirstName',
        'shippingMiddleName',
        'shippingLastName',
        'shippingPhone',
        'shippingEmail',
        'shippingHouseNumber',
        'shippingStreet',
        'shippingZone',
        'shippingMunicipality',
        'shippingProvince',
        'shippingZipCode',
        'subtotal',
        'shippingTotal',
        'discountTotal',
        'grandTotal',
        'affiliateCommissionTotal',
        'netRevenue',
        'orderNotes',
        // Package purchase fields
        'isPackage',
        'packageId',
        'packageName',
        'packageDescription',
        'packageCalculatedPrice',
        'packagePrice',
        'packageSavings',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'usersId' => 'integer',
        'clientId' => 'integer',
        'subtotal' => 'decimal:2',
        'shippingTotal' => 'decimal:2',
        'discountTotal' => 'decimal:2',
        'grandTotal' => 'decimal:2',
        'affiliateCommissionTotal' => 'decimal:2',
        'netRevenue' => 'decimal:2',
        // Package fields
        'isPackage' => 'boolean',
        'packageId' => 'integer',
        'packageCalculatedPrice' => 'decimal:2',
        'packagePrice' => 'decimal:2',
        'packageSavings' => 'decimal:2',
        'deleteStatus' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Scope to get only active orders (deleteStatus = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Scope to filter by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Get the order items for this order.
     */
    public function items()
    {
        return $this->hasMany(EcomOrderItem::class, 'orderId');
    }

    /**
     * Get the discounts for this order.
     */
    public function discounts()
    {
        return $this->hasMany(EcomOrderDiscount::class, 'orderId');
    }

    /**
     * Get the affiliate commissions for this order.
     */
    public function affiliateCommissions()
    {
        return $this->hasMany(EcomOrderAffiliateCommission::class, 'orderId');
    }

    /**
     * Get the user who created this order.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    /**
     * Get the package for this order (if it's a package purchase).
     */
    public function package()
    {
        return $this->belongsTo(EcomPackage::class, 'packageId');
    }

    /**
     * Generate a unique order number.
     */
    public static function generateOrderNumber()
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));

        do {
            $orderNumber = $prefix . '-' . $date . '-' . $random;
            $exists = self::where('orderNumber', $orderNumber)->exists();
            if ($exists) {
                $random = strtoupper(substr(uniqid(), -4));
            }
        } while ($exists);

        return $orderNumber;
    }

    /**
     * Get formatted date attribute
     */
    public function getFormattedDateAttribute()
    {
        return $this->created_at ? $this->created_at->format('F j, Y') : '';
    }

    /**
     * Get formatted time attribute
     */
    public function getFormattedTimeAttribute()
    {
        return $this->created_at ? $this->created_at->format('g:i A') : '';
    }

    /**
     * Get formatted grand total attribute
     */
    public function getFormattedGrandTotalAttribute()
    {
        return $this->grandTotal ? '₱' . number_format($this->grandTotal, 2) : '₱0.00';
    }

    /**
     * Get client full name attribute
     */
    public function getClientFullNameAttribute()
    {
        $parts = array_filter([
            $this->clientFirstName,
            $this->clientMiddleName,
            $this->clientLastName
        ]);
        return implode(' ', $parts) ?: 'N/A';
    }

    /**
     * Get shipping full name attribute
     */
    public function getShippingFullNameAttribute()
    {
        $parts = array_filter([
            $this->shippingFirstName,
            $this->shippingMiddleName,
            $this->shippingLastName
        ]);
        return implode(' ', $parts) ?: 'N/A';
    }

    /**
     * Get full shipping address attribute
     */
    public function getFullShippingAddressAttribute()
    {
        $parts = array_filter([
            $this->shippingHouseNumber,
            $this->shippingStreet,
            $this->shippingZone,
            $this->shippingMunicipality,
            $this->shippingProvince,
            $this->shippingZipCode
        ]);
        return implode(', ', $parts) ?: 'N/A';
    }
}
