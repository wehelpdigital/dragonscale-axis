<?php

namespace App\Models;

class EcomRefundRequest extends BaseModel
{
    protected $table = 'ecom_refund_requests';

    protected $fillable = [
        'orderId',
        'storeId',
        'storeName',
        'clientName',
        'clientEmail',
        'clientPhone',
        'refundNumber',
        'requestReason',
        'requestedAt',
        'status',
        'refundType',
        'orderSubtotal',
        'requestedAmount',
        'approvedAmount',
        'processedBy',
        'processedAt',
        'adminNotes',
        'rejectionReason',
        'deleteStatus',
    ];

    protected $casts = [
        'orderId' => 'integer',
        'storeId' => 'integer',
        'orderSubtotal' => 'decimal:2',
        'requestedAmount' => 'decimal:2',
        'approvedAmount' => 'decimal:2',
        'processedBy' => 'integer',
        'requestedAt' => 'datetime',
        'processedAt' => 'datetime',
        'deleteStatus' => 'integer',
    ];

    /**
     * Status labels for display
     */
    const STATUS_LABELS = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'processed' => 'Processed',
    ];

    /**
     * Status badge classes
     */
    const STATUS_BADGES = [
        'pending' => 'bg-warning text-dark',
        'approved' => 'bg-info text-white',
        'rejected' => 'bg-danger',
        'processed' => 'bg-success',
    ];

    /**
     * Scope to get only active records
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Scope to filter by status
     */
    public function scopeOfStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by store
     */
    public function scopeForStore($query, $storeId)
    {
        return $query->where('storeId', $storeId);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        if ($from) {
            $query->whereDate('requestedAt', '>=', $from);
        }
        if ($to) {
            $query->whereDate('requestedAt', '<=', $to);
        }
        return $query;
    }

    /**
     * Relationships
     */
    public function order()
    {
        return $this->belongsTo(EcomOrder::class, 'orderId');
    }

    public function items()
    {
        return $this->hasMany(EcomRefundItem::class, 'refundRequestId');
    }

    public function processedByUser()
    {
        return $this->belongsTo(User::class, 'processedBy');
    }

    public function store()
    {
        return $this->belongsTo(EcomProductStore::class, 'storeName', 'storeName');
    }

    public function attachments()
    {
        return $this->hasMany(EcomRefundAttachment::class, 'refundRequestId');
    }

    /**
     * Get active attachments
     */
    public function activeAttachments()
    {
        return $this->hasMany(EcomRefundAttachment::class, 'refundRequestId')
            ->where('deleteStatus', 1);
    }

    /**
     * Accessors
     */
    public function getStatusLabelAttribute()
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusBadgeClassAttribute()
    {
        return self::STATUS_BADGES[$this->status] ?? 'bg-secondary';
    }

    public function getFormattedRequestedAtAttribute()
    {
        return $this->requestedAt ? $this->requestedAt->format('M j, Y g:i A') : '';
    }

    public function getFormattedProcessedAtAttribute()
    {
        return $this->processedAt ? $this->processedAt->format('M j, Y g:i A') : '';
    }

    public function getFormattedRequestedAmountAttribute()
    {
        return '₱' . number_format($this->requestedAmount, 2);
    }

    public function getFormattedApprovedAmountAttribute()
    {
        return '₱' . number_format($this->approvedAmount, 2);
    }

    public function getFormattedOrderSubtotalAttribute()
    {
        return '₱' . number_format($this->orderSubtotal, 2);
    }

    /**
     * Generate unique refund number
     */
    public static function generateRefundNumber()
    {
        $prefix = 'REF';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));

        do {
            $refundNumber = $prefix . '-' . $date . '-' . $random;
            $exists = self::where('refundNumber', $refundNumber)->exists();
            if ($exists) {
                $random = strtoupper(substr(uniqid(), -4));
            }
        } while ($exists);

        return $refundNumber;
    }
}
