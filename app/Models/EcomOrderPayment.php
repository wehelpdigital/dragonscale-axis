<?php

namespace App\Models;

use Carbon\Carbon;

class EcomOrderPayment extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_order_payments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'orderId',
        'paymentNumber',
        'paymentMethod',
        'paymentStatus',
        'amountSent',
        'amountVerified',
        'payerName',
        'referenceNumber',
        'phoneNumber',
        'bankName',
        'bankAccountName',
        'bankAccountNumber',
        'screenshot',
        'verifiedAt',
        'verifiedBy',
        'verificationNotes',
        'invoiceNumber',
        'invoiceToken',
        'invoiceGeneratedAt',
        'invoicePath',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'orderId' => 'integer',
        'amountSent' => 'decimal:2',
        'amountVerified' => 'decimal:2',
        'verifiedAt' => 'datetime',
        'invoiceGeneratedAt' => 'datetime',
        'verifiedBy' => 'integer',
        'deleteStatus' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Payment method labels
     */
    public static $paymentMethodLabels = [
        'manual_gcash' => 'GCash',
        'manual_maya' => 'Maya',
        'manual_instapay' => 'InstaPay',
        'manual_bank' => 'Bank Transfer',
        'manual_other' => 'Other',
        'online_payment' => 'Online Payment',
        'cod' => 'Cash on Delivery',
        'cop' => 'Cash on Pickup',
    ];

    /**
     * Payment status labels
     */
    public static $paymentStatusLabels = [
        'pending' => 'Pending',
        'verified' => 'Verified',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ];

    /**
     * Payment status badge classes
     */
    public static $paymentStatusBadgeClasses = [
        'pending' => 'bg-warning text-dark',
        'verified' => 'bg-success',
        'rejected' => 'bg-danger',
        'cancelled' => 'bg-secondary',
    ];

    /**
     * Scope to get only active payments (deleteStatus = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Scope to get payments for a specific order
     */
    public function scopeForOrder($query, $orderId)
    {
        return $query->where('orderId', $orderId);
    }

    /**
     * Scope to get pending payments
     */
    public function scopePending($query)
    {
        return $query->where('paymentStatus', 'pending');
    }

    /**
     * Scope to get verified payments
     */
    public function scopeVerified($query)
    {
        return $query->where('paymentStatus', 'verified');
    }

    /**
     * Get the order that owns this payment.
     */
    public function order()
    {
        return $this->belongsTo(EcomOrder::class, 'orderId');
    }

    /**
     * Get the user who verified this payment.
     */
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verifiedBy');
    }

    /**
     * Get payment method label
     */
    public function getPaymentMethodLabelAttribute()
    {
        return self::$paymentMethodLabels[$this->paymentMethod] ?? ucfirst($this->paymentMethod);
    }

    /**
     * Get payment status label
     */
    public function getPaymentStatusLabelAttribute()
    {
        return self::$paymentStatusLabels[$this->paymentStatus] ?? ucfirst($this->paymentStatus);
    }

    /**
     * Get payment status badge class
     */
    public function getPaymentStatusBadgeClassAttribute()
    {
        return self::$paymentStatusBadgeClasses[$this->paymentStatus] ?? 'bg-secondary';
    }

    /**
     * Get formatted amount sent
     */
    public function getFormattedAmountSentAttribute()
    {
        return '₱' . number_format($this->amountSent, 2);
    }

    /**
     * Get formatted amount verified
     */
    public function getFormattedAmountVerifiedAttribute()
    {
        return $this->amountVerified !== null ? '₱' . number_format($this->amountVerified, 2) : null;
    }

    /**
     * Get verified at formatted
     */
    public function getVerifiedAtFormattedAttribute()
    {
        return $this->verifiedAt ? $this->verifiedAt->format('M j, Y g:i A') : null;
    }

    /**
     * Get verifier name
     */
    public function getVerifierNameAttribute()
    {
        return $this->verifier ? $this->verifier->name : null;
    }

    /**
     * Check if payment is pending
     */
    public function isPending()
    {
        return $this->paymentStatus === 'pending';
    }

    /**
     * Check if payment is verified
     */
    public function isVerified()
    {
        return $this->paymentStatus === 'verified';
    }

    /**
     * Check if payment requires manual verification
     */
    public function requiresVerification()
    {
        $manualMethods = ['manual_gcash', 'manual_maya', 'manual_instapay', 'manual_bank', 'manual_other'];
        return in_array($this->paymentMethod, $manualMethods);
    }

    /**
     * Generate a unique payment number
     */
    public static function generatePaymentNumber($orderId)
    {
        $prefix = 'PAY';
        $date = Carbon::now('Asia/Manila')->format('ymd');
        $orderPart = str_pad($orderId, 5, '0', STR_PAD_LEFT);

        // Get count of payments for this order today
        $count = self::where('orderId', $orderId)
            ->whereDate('created_at', Carbon::today('Asia/Manila'))
            ->count();

        $sequence = str_pad($count + 1, 2, '0', STR_PAD_LEFT);

        return "{$prefix}-{$date}-{$orderPart}-{$sequence}";
    }

    /**
     * Generate a unique invoice number
     */
    public static function generateInvoiceNumber($orderId)
    {
        $prefix = 'INV';
        $date = Carbon::now('Asia/Manila')->format('ymd');
        $orderPart = str_pad($orderId, 5, '0', STR_PAD_LEFT);

        // Get count of invoices for this order
        $count = self::where('orderId', $orderId)
            ->whereNotNull('invoiceNumber')
            ->count();

        $sequence = str_pad($count + 1, 2, '0', STR_PAD_LEFT);

        return "{$prefix}-{$date}-{$orderPart}-{$sequence}";
    }

    /**
     * Get effective amount (verified if available, otherwise sent)
     */
    public function getEffectiveAmountAttribute()
    {
        return $this->amountVerified ?? $this->amountSent;
    }

    /**
     * Generate a unique invoice token for public access
     */
    public static function generateInvoiceToken()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Get the public invoice URL
     */
    public function getInvoiceUrlAttribute()
    {
        if ($this->invoiceToken) {
            return url('/invoice/' . $this->invoiceToken);
        }
        return null;
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate payment number on creation
        static::creating(function ($payment) {
            if (empty($payment->paymentNumber)) {
                $payment->paymentNumber = self::generatePaymentNumber($payment->orderId);
            }
        });
    }
}
