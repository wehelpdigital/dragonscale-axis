<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class EcomRefundAuditLog extends BaseModel
{
    protected $table = 'ecom_refund_audit_logs';

    protected $fillable = [
        'refundRequestId',
        'orderId',
        'refundNumber',
        'orderNumber',
        'action',
        'actionLabel',
        'actionBy',
        'actionByName',
        'actionByEmail',
        'fieldChanged',
        'previousValue',
        'newValue',
        'notes',
        'metadata',
        'ipAddress',
        'userAgent',
        'actionAt',
        'deleteStatus',
    ];

    protected $casts = [
        'metadata' => 'array',
        'actionAt' => 'datetime',
    ];

    /**
     * Action type constants
     */
    const ACTION_CREATED = 'created';
    const ACTION_VIEWED = 'viewed';
    const ACTION_APPROVED = 'approved';
    const ACTION_REJECTED = 'rejected';
    const ACTION_PROCESSED = 'processed';
    const ACTION_DELETED = 'deleted';
    const ACTION_RESTORED = 'restored';
    const ACTION_STATUS_CHANGED = 'status_changed';
    const ACTION_NOTES_UPDATED = 'notes_updated';
    const ACTION_AMOUNT_ADJUSTED = 'amount_adjusted';
    const ACTION_ATTACHMENT_ADDED = 'attachment_added';
    const ACTION_ATTACHMENT_REMOVED = 'attachment_removed';

    /**
     * Action labels mapping
     */
    public static $actionLabels = [
        self::ACTION_CREATED => 'Refund Request Created',
        self::ACTION_VIEWED => 'Refund Request Viewed',
        self::ACTION_APPROVED => 'Refund Request Approved',
        self::ACTION_REJECTED => 'Refund Request Rejected',
        self::ACTION_PROCESSED => 'Refund Processed',
        self::ACTION_DELETED => 'Refund Request Deleted',
        self::ACTION_RESTORED => 'Refund Request Restored',
        self::ACTION_STATUS_CHANGED => 'Status Changed',
        self::ACTION_NOTES_UPDATED => 'Admin Notes Updated',
        self::ACTION_AMOUNT_ADJUSTED => 'Amount Adjusted',
        self::ACTION_ATTACHMENT_ADDED => 'Attachment Added',
        self::ACTION_ATTACHMENT_REMOVED => 'Attachment Removed',
    ];

    /**
     * Action badge classes for UI
     */
    public static $actionBadgeClasses = [
        self::ACTION_CREATED => 'bg-primary',
        self::ACTION_VIEWED => 'bg-secondary',
        self::ACTION_APPROVED => 'bg-info text-white',
        self::ACTION_REJECTED => 'bg-warning text-dark',
        self::ACTION_PROCESSED => 'bg-success',
        self::ACTION_DELETED => 'bg-danger',
        self::ACTION_RESTORED => 'bg-success',
        self::ACTION_STATUS_CHANGED => 'bg-info text-white',
        self::ACTION_NOTES_UPDATED => 'bg-secondary',
        self::ACTION_AMOUNT_ADJUSTED => 'bg-warning text-dark',
        self::ACTION_ATTACHMENT_ADDED => 'bg-light text-dark',
        self::ACTION_ATTACHMENT_REMOVED => 'bg-light text-dark',
    ];

    /**
     * Action icons for UI
     */
    public static $actionIcons = [
        self::ACTION_CREATED => 'bx bx-plus-circle',
        self::ACTION_VIEWED => 'bx bx-show',
        self::ACTION_APPROVED => 'bx bx-check',
        self::ACTION_REJECTED => 'bx bx-x',
        self::ACTION_PROCESSED => 'bx bx-check-double',
        self::ACTION_DELETED => 'bx bx-trash',
        self::ACTION_RESTORED => 'bx bx-revision',
        self::ACTION_STATUS_CHANGED => 'bx bx-transfer',
        self::ACTION_NOTES_UPDATED => 'bx bx-edit',
        self::ACTION_AMOUNT_ADJUSTED => 'bx bx-dollar',
        self::ACTION_ATTACHMENT_ADDED => 'bx bx-paperclip',
        self::ACTION_ATTACHMENT_REMOVED => 'bx bx-unlink',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the refund request this log belongs to
     */
    public function refundRequest()
    {
        return $this->belongsTo(EcomRefundRequest::class, 'refundRequestId');
    }

    /**
     * Get the order this log is associated with
     */
    public function order()
    {
        return $this->belongsTo(EcomOrder::class, 'orderId');
    }

    /**
     * Get the user who performed the action
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'actionBy');
    }

    // ==================== SCOPES ====================

    /**
     * Scope for active (non-deleted) logs
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Scope for a specific refund request
     */
    public function scopeForRefund($query, $refundRequestId)
    {
        return $query->where('refundRequestId', $refundRequestId);
    }

    /**
     * Scope for a specific order
     */
    public function scopeForOrder($query, $orderId)
    {
        return $query->where('orderId', $orderId);
    }

    /**
     * Scope for a specific action type
     */
    public function scopeOfAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        if ($from) {
            $query->whereDate('actionAt', '>=', $from);
        }
        if ($to) {
            $query->whereDate('actionAt', '<=', $to);
        }
        return $query;
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the action label
     */
    public function getActionLabelAttribute()
    {
        return $this->attributes['actionLabel'] ?? self::$actionLabels[$this->action] ?? ucfirst($this->action);
    }

    /**
     * Get the action badge class
     */
    public function getActionBadgeClassAttribute()
    {
        return self::$actionBadgeClasses[$this->action] ?? 'bg-secondary';
    }

    /**
     * Get the action icon
     */
    public function getActionIconAttribute()
    {
        return self::$actionIcons[$this->action] ?? 'bx bx-history';
    }

    /**
     * Get formatted action timestamp
     */
    public function getFormattedActionAtAttribute()
    {
        return $this->actionAt ? $this->actionAt->format('M j, Y g:i A') : null;
    }

    /**
     * Get relative time (e.g., "2 hours ago")
     */
    public function getRelativeTimeAttribute()
    {
        return $this->actionAt ? $this->actionAt->diffForHumans() : null;
    }

    // ==================== STATIC LOGGING METHODS ====================

    /**
     * Log a refund action
     *
     * @param EcomRefundRequest $refund The refund request
     * @param string $action The action type (use class constants)
     * @param array $options Additional options:
     *   - fieldChanged: Which field was changed
     *   - previousValue: Previous value
     *   - newValue: New value
     *   - notes: Additional notes
     *   - metadata: Additional metadata array
     * @return EcomRefundAuditLog
     */
    public static function logAction(EcomRefundRequest $refund, string $action, array $options = [])
    {
        $user = Auth::user();

        return self::create([
            'refundRequestId' => $refund->id,
            'orderId' => $refund->orderId,
            'refundNumber' => $refund->refundNumber,
            'orderNumber' => $refund->order->orderNumber ?? null,
            'action' => $action,
            'actionLabel' => self::$actionLabels[$action] ?? ucfirst($action),
            'actionBy' => $user?->id,
            'actionByName' => $user?->name ?? 'System',
            'actionByEmail' => $user?->email,
            'fieldChanged' => $options['fieldChanged'] ?? null,
            'previousValue' => isset($options['previousValue']) ? (is_array($options['previousValue']) ? json_encode($options['previousValue']) : $options['previousValue']) : null,
            'newValue' => isset($options['newValue']) ? (is_array($options['newValue']) ? json_encode($options['newValue']) : $options['newValue']) : null,
            'notes' => $options['notes'] ?? null,
            'metadata' => $options['metadata'] ?? null,
            'ipAddress' => Request::ip(),
            'userAgent' => Request::userAgent(),
            'actionAt' => now(),
            'deleteStatus' => 1,
        ]);
    }

    /**
     * Log refund creation
     */
    public static function logCreation(EcomRefundRequest $refund, array $items = [], int $attachmentCount = 0)
    {
        $metadata = [
            'requestedAmount' => $refund->requestedAmount,
            'itemCount' => count($items),
            'attachmentCount' => $attachmentCount,
            'items' => array_map(function ($item) {
                return [
                    'productName' => $item['productName'] ?? '',
                    'quantity' => $item['refundQuantity'] ?? 0,
                    'amount' => $item['refundAmount'] ?? 0,
                ];
            }, $items),
        ];

        return self::logAction($refund, self::ACTION_CREATED, [
            'notes' => "Refund request created for {$refund->formattedRequestedAmount}",
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log refund approval
     */
    public static function logApproval(EcomRefundRequest $refund, ?string $adminNotes = null)
    {
        return self::logAction($refund, self::ACTION_APPROVED, [
            'fieldChanged' => 'status',
            'previousValue' => 'pending',
            'newValue' => 'approved',
            'notes' => $adminNotes ?? 'Refund request approved',
            'metadata' => [
                'requestedAmount' => $refund->requestedAmount,
            ],
        ]);
    }

    /**
     * Log refund rejection
     */
    public static function logRejection(EcomRefundRequest $refund, string $reason, ?string $previousStatus = 'pending')
    {
        return self::logAction($refund, self::ACTION_REJECTED, [
            'fieldChanged' => 'status',
            'previousValue' => $previousStatus,
            'newValue' => 'rejected',
            'notes' => $reason,
            'metadata' => [
                'rejectionReason' => $reason,
                'requestedAmount' => $refund->requestedAmount,
            ],
        ]);
    }

    /**
     * Log refund processing (finalization)
     */
    public static function logProcessing(EcomRefundRequest $refund, float $approvedAmount, ?string $adminNotes = null, ?string $previousStatus = 'pending')
    {
        return self::logAction($refund, self::ACTION_PROCESSED, [
            'fieldChanged' => 'status',
            'previousValue' => $previousStatus,
            'newValue' => 'processed',
            'notes' => $adminNotes ?? "Refund processed with approved amount of ₱" . number_format($approvedAmount, 2),
            'metadata' => [
                'requestedAmount' => $refund->requestedAmount,
                'approvedAmount' => $approvedAmount,
                'refundType' => $refund->refundType,
            ],
        ]);
    }

    /**
     * Log refund deletion
     */
    public static function logDeletion(EcomRefundRequest $refund)
    {
        return self::logAction($refund, self::ACTION_DELETED, [
            'notes' => "Refund request deleted",
            'metadata' => [
                'requestedAmount' => $refund->requestedAmount,
                'status' => $refund->status,
            ],
        ]);
    }

    /**
     * Log attachment addition
     */
    public static function logAttachmentAdded(EcomRefundRequest $refund, int $count, array $fileNames = [])
    {
        return self::logAction($refund, self::ACTION_ATTACHMENT_ADDED, [
            'notes' => "{$count} attachment(s) added",
            'metadata' => [
                'attachmentCount' => $count,
                'fileNames' => $fileNames,
            ],
        ]);
    }
}
