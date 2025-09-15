<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcomOrder extends Model
{
    use HasFactory;

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
        'orderNumber',
        'paymentStatus',
        'shippingStatus',
        'customerFullName',
        'paymentAmount',
        'paymentDiscount',
        'shippingAmount',
        'totalToPay',
        'handledBy',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'paymentAmount' => 'decimal:2',
        'paymentDiscount' => 'decimal:2',
        'shippingAmount' => 'decimal:2',
        'totalToPay' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope to get all orders (no filtering needed since no deleteStatus column)
     */
    public function scopeActive($query)
    {
        return $query; // Return all orders since there's no deleteStatus column
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
     * Get formatted payment amount attribute
     */
    public function getFormattedPaymentAmountAttribute()
    {
        return $this->paymentAmount ? '₱' . number_format($this->paymentAmount, 2) : '₱0.00';
    }

    /**
     * Get formatted payment discount attribute
     */
    public function getFormattedPaymentDiscountAttribute()
    {
        return $this->paymentDiscount ? '₱' . number_format($this->paymentDiscount, 2) : '₱0.00';
    }

    /**
     * Get formatted shipping amount attribute
     */
    public function getFormattedShippingAmountAttribute()
    {
        return $this->shippingAmount ? '₱' . number_format($this->shippingAmount, 2) : '₱0.00';
    }

    /**
     * Get formatted total to pay attribute
     */
    public function getFormattedTotalToPayAttribute()
    {
        return $this->totalToPay ? '₱' . number_format($this->totalToPay, 2) : '₱0.00';
    }
}
