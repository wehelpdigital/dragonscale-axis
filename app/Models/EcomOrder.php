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
        // Payment verification fields
        'paymentMethod',
        'paymentVerificationStatus',
        'paymentPayerName',
        'paymentAmountSent',
        'paymentReferenceNumber',
        'paymentPhoneNumber',
        'paymentBankName',
        'paymentBankAccountName',
        'paymentBankAccountNumber',
        'paymentScreenshot',
        'paymentVerifiedAt',
        'paymentVerifiedBy',
        'paymentNotes',
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
        // Payment verification fields
        'paymentAmountSent' => 'decimal:2',
        'paymentVerifiedAt' => 'datetime:Y-m-d H:i:s',
        'paymentVerifiedBy' => 'integer',
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
     * Get the refund requests for this order.
     */
    public function refundRequests()
    {
        return $this->hasMany(EcomRefundRequest::class, 'orderId');
    }

    /**
     * Get the payments for this order.
     */
    public function payments()
    {
        return $this->hasMany(EcomOrderPayment::class, 'orderId');
    }

    /**
     * Get active payments for this order.
     */
    public function activePayments()
    {
        return $this->hasMany(EcomOrderPayment::class, 'orderId')
            ->where('deleteStatus', 1);
    }

    /**
     * Get verified payments for this order.
     */
    public function verifiedPayments()
    {
        return $this->hasMany(EcomOrderPayment::class, 'orderId')
            ->where('deleteStatus', 1)
            ->where('paymentStatus', 'verified');
    }

    /**
     * Get total verified payment amount.
     */
    public function getTotalVerifiedPaymentsAttribute()
    {
        return $this->verifiedPayments()->sum('amountVerified') ?:
               $this->verifiedPayments()->sum('amountSent');
    }

    /**
     * Get remaining balance to be paid.
     */
    public function getRemainingBalanceAttribute()
    {
        $totalPaid = $this->totalVerifiedPayments;
        $remaining = $this->grandTotal - $totalPaid;
        return max(0, $remaining);
    }

    /**
     * Check if order is fully paid.
     */
    public function getIsFullyPaidAttribute()
    {
        return $this->remainingBalance <= 0;
    }

    /**
     * Get the active refund request for this order.
     */
    public function activeRefund()
    {
        return $this->hasOne(EcomRefundRequest::class, 'orderId')
            ->where('deleteStatus', 1)
            ->whereIn('status', ['pending', 'approved', 'processed'])
            ->latest();
    }

    /**
     * Get total refunded amount for this order.
     */
    public function getTotalRefundedAttribute()
    {
        return $this->refundRequests()
            ->where('deleteStatus', 1)
            ->where('status', 'processed')
            ->sum('approvedAmount');
    }

    /**
     * Check if order has been fully refunded.
     */
    public function getIsFullyRefundedAttribute()
    {
        $refundableAmount = $this->subtotal - $this->discountTotal;
        return $this->totalRefunded >= $refundableAmount;
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
     * Get the user who verified the payment.
     */
    public function verifiedByUser()
    {
        return $this->belongsTo(User::class, 'paymentVerifiedBy');
    }

    /**
     * Check if order requires payment verification.
     */
    public function requiresPaymentVerification()
    {
        $manualMethods = ['manual_gcash', 'manual_bank', 'manual_maya', 'manual_instapay', 'manual_other'];
        return in_array($this->paymentMethod, $manualMethods);
    }

    /**
     * Check if payment is verified.
     */
    public function isPaymentVerified()
    {
        return $this->paymentVerificationStatus === 'verified';
    }

    /**
     * Get payment method label.
     */
    public function getPaymentMethodLabelAttribute()
    {
        $labels = [
            'manual_gcash' => 'GCash (Manual)',
            'manual_maya' => 'Maya (Manual)',
            'manual_instapay' => 'Instapay (Manual)',
            'manual_bank' => 'Bank Transfer (Manual)',
            'manual_other' => 'Other Manual Payment',
            'online_payment' => 'Online Payment',
            'cod' => 'Cash on Delivery',
            'cop' => 'Cash on Pickup',
        ];
        return $labels[$this->paymentMethod] ?? $this->paymentMethod;
    }

    /**
     * Check if payment method is Instapay (shows bank fields).
     */
    public function isInstapayPayment()
    {
        return $this->paymentMethod === 'manual_instapay';
    }

    /**
     * Get payment verification status label.
     */
    public function getPaymentVerificationStatusLabelAttribute()
    {
        $labels = [
            'not_required' => 'Not Required',
            'pending' => 'Pending Verification',
            'verified' => 'Verified',
            'rejected' => 'Rejected',
        ];
        return $labels[$this->paymentVerificationStatus] ?? $this->paymentVerificationStatus;
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
