<?php

namespace App\Models;

class EcomOrderAuditLog extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_order_audit_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'orderId',
        'orderNumber',
        'userId',
        'userName',
        'actionType',
        'fieldChanged',
        'previousValue',
        'newValue',
        'description',
        'ipAddress',
        'userAgent',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'orderId' => 'integer',
        'userId' => 'integer',
        'deleteStatus' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Scope to get only active audit logs (deleteStatus = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Scope to filter by order
     */
    public function scopeForOrder($query, $orderId)
    {
        return $query->where('orderId', $orderId);
    }

    /**
     * Scope to filter by order number
     */
    public function scopeForOrderNumber($query, $orderNumber)
    {
        return $query->where('orderNumber', $orderNumber);
    }

    /**
     * Get the order that this audit log belongs to.
     */
    public function order()
    {
        return $this->belongsTo(EcomOrder::class, 'orderId');
    }

    /**
     * Get the user who made the change.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    /**
     * Get human-readable action type.
     */
    public function getActionTypeLabelAttribute()
    {
        return match($this->actionType) {
            'order_created' => 'Order Created',
            'status_change' => 'Status Changed',
            'shipping_change' => 'Shipping Status Changed',
            'order_cancelled' => 'Order Cancelled',
            'order_refunded' => 'Order Refunded',
            'tracking_updated' => 'Tracking Updated',
            'notes_updated' => 'Notes Updated',
            'payment_details_updated' => 'Payment Details Updated',
            'payment_details_submitted' => 'Payment Details Submitted (Customer)',
            'payment_verified' => 'Payment Verified',
            'payment_rejected' => 'Payment Rejected',
            default => ucfirst(str_replace('_', ' ', $this->actionType)),
        };
    }

    /**
     * Get formatted previous value.
     */
    public function getFormattedPreviousValueAttribute()
    {
        return $this->formatValue($this->previousValue);
    }

    /**
     * Get formatted new value.
     */
    public function getFormattedNewValueAttribute()
    {
        return $this->formatValue($this->newValue);
    }

    /**
     * Format value for display.
     */
    private function formatValue($value)
    {
        if ($value === null || $value === '') {
            return '-';
        }

        // Format status values nicely
        return match(strtolower($value)) {
            'pending' => 'Pending',
            'paid' => 'Paid',
            'complete' => 'Complete',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            'shipped' => 'Shipped',
            'not_applicable' => 'Not Applicable',
            // Payment methods
            'manual_gcash' => 'GCash (Manual)',
            'manual_maya' => 'Maya (Manual)',
            'manual_instapay' => 'Instapay (Manual)',
            'manual_bank' => 'Bank Transfer (Manual)',
            'manual_other' => 'Other Manual Payment',
            'online_payment' => 'Online Payment',
            'cod' => 'Cash on Delivery',
            'cop' => 'Cash on Pickup',
            // Payment verification statuses
            'not_required' => 'Not Required',
            'verified' => 'Verified',
            'rejected' => 'Rejected',
            default => ucfirst($value),
        };
    }

    /**
     * Create an audit log entry.
     *
     * @param EcomOrder $order
     * @param string $actionType
     * @param string|null $fieldChanged
     * @param string|null $previousValue
     * @param string|null $newValue
     * @param string|null $description
     * @return static
     */
    public static function logAction(
        EcomOrder $order,
        string $actionType,
        ?string $fieldChanged = null,
        ?string $previousValue = null,
        ?string $newValue = null,
        ?string $description = null
    ): self {
        $user = auth()->user();

        return self::create([
            'orderId' => $order->id,
            'orderNumber' => $order->orderNumber,
            'userId' => $user?->id,
            'userName' => $user?->name ?? 'System',
            'actionType' => $actionType,
            'fieldChanged' => $fieldChanged,
            'previousValue' => $previousValue,
            'newValue' => $newValue,
            'description' => $description,
            'ipAddress' => request()->ip(),
            'userAgent' => request()->userAgent(),
            'deleteStatus' => 1,
        ]);
    }
}
