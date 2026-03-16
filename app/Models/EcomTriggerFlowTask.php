<?php

namespace App\Models;

use Carbon\Carbon;

class EcomTriggerFlowTask extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_trigger_flow_tasks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'enrollmentId',
        'flowId',
        'nodeId',
        'nodeType',
        'nodeLabel',
        'nodeData',
        'taskOrder',
        'parentNodeId',
        'branchType',
        'status',
        'scheduledAt',
        'startedAt',
        'completedAt',
        'resultData',
        'errorMessage',
        'retryCount',
        'maxRetries',
        'lastRetryAt',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'enrollmentId' => 'integer',
        'flowId' => 'integer',
        'taskOrder' => 'integer',
        'nodeData' => 'array',
        'resultData' => 'array',
        'retryCount' => 'integer',
        'maxRetries' => 'integer',
        'scheduledAt' => 'datetime:Y-m-d H:i:s',
        'startedAt' => 'datetime:Y-m-d H:i:s',
        'completedAt' => 'datetime:Y-m-d H:i:s',
        'lastRetryAt' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_READY = 'ready';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_SKIPPED = 'skipped';

    /**
     * Node type labels
     */
    public static $nodeTypeLabels = [
        'trigger_tag' => 'Trigger Tag',
        'course_access_start' => 'Course Access Start',
        'course_tag_start' => 'Trigger Tag',
        'product_variant_start' => 'Product & Variant',
        'special_tag_start' => 'Special Tag',
        'order_status_start' => 'Order Status Change',
        'delay' => 'Delay / Wait',
        'schedule' => 'Schedule',
        'email' => 'Send Email',
        'send_sms' => 'Send SMS',
        'send_whatsapp' => 'Send WhatsApp',
        'if_else' => 'If / Else Condition',
        'y_flow' => 'Y Flow Split',
        'course_access' => 'Grant Course Access',
        'remove_access' => 'Remove Access',
        'add_as_affiliate' => 'Add as Affiliate',
        'add_login_access' => 'Grant Login Access',
        'course_subscription' => 'Course Subscription',
        'flow_action' => 'Flow Action',
        'ai_add_referral' => 'AI Add Referral',
    ];

    /**
     * Node type icons
     */
    public static $nodeTypeIcons = [
        'trigger_tag' => 'bx-tag',
        'course_access_start' => 'bx-key',
        'course_tag_start' => 'bx-key',
        'product_variant_start' => 'bx-package',
        'special_tag_start' => 'bx-purchase-tag-alt',
        'order_status_start' => 'bx-transfer-alt',
        'delay' => 'bx-time-five',
        'schedule' => 'bx-calendar',
        'email' => 'bx-envelope',
        'send_sms' => 'bx-message-rounded-dots',
        'send_whatsapp' => 'bxl-whatsapp',
        'if_else' => 'bx-git-branch',
        'y_flow' => 'bx-git-merge',
        'course_access' => 'bx-key',
        'remove_access' => 'bx-block',
        'add_as_affiliate' => 'bx-user-plus',
        'add_login_access' => 'bx-log-in-circle',
        'course_subscription' => 'bx-book-open',
        'flow_action' => 'bx-git-branch',
        'ai_add_referral' => 'bx-bot',
    ];

    /**
     * Scope to get only active tasks (not deleted)
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 'active');
    }

    /**
     * Scope to get tasks that are ready to execute.
     */
    public function scopeReadyToExecute($query)
    {
        return $query->where(function($q) {
            // Ready status (no delay, previous done)
            $q->where('status', self::STATUS_READY)
              // Or scheduled and time has come
              ->orWhere(function($sq) {
                  $sq->where('status', self::STATUS_SCHEDULED)
                     ->where('scheduledAt', '<=', Carbon::now('Asia/Manila'));
              });
        });
    }

    /**
     * Scope to get pending tasks.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_SCHEDULED, self::STATUS_READY]);
    }

    /**
     * Scope to filter by enrollment.
     */
    public function scopeForEnrollment($query, $enrollmentId)
    {
        return $query->where('enrollmentId', $enrollmentId);
    }

    /**
     * Scope to filter by flow.
     */
    public function scopeForFlow($query, $flowId)
    {
        return $query->where('flowId', $flowId);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get the enrollment this task belongs to.
     */
    public function enrollment()
    {
        return $this->belongsTo(EcomTriggerFlowEnrollment::class, 'enrollmentId');
    }

    /**
     * Get the flow this task belongs to.
     */
    public function flow()
    {
        return $this->belongsTo(EcomTriggerFlow::class, 'flowId');
    }

    /**
     * Get logs for this task.
     */
    public function logs()
    {
        return $this->hasMany(EcomTriggerFlowLog::class, 'taskId')->orderBy('created_at', 'desc');
    }

    /**
     * Get the node type label.
     */
    public function getNodeTypeLabelAttribute()
    {
        return self::$nodeTypeLabels[$this->nodeType] ?? ucfirst(str_replace('_', ' ', $this->nodeType));
    }

    /**
     * Get the node type icon.
     */
    public function getNodeTypeIconAttribute()
    {
        return self::$nodeTypeIcons[$this->nodeType] ?? 'bx-cube';
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'bg-secondary',
            self::STATUS_SCHEDULED => 'bg-info text-white',
            self::STATUS_READY => 'bg-primary',
            self::STATUS_RUNNING => 'bg-warning text-dark',
            self::STATUS_COMPLETED => 'bg-success',
            self::STATUS_FAILED => 'bg-danger',
            self::STATUS_CANCELLED => 'bg-dark',
            self::STATUS_SKIPPED => 'bg-light text-dark',
            default => 'bg-secondary',
        };
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_READY => 'Ready',
            self::STATUS_RUNNING => 'Running',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_SKIPPED => 'Skipped',
            default => ucfirst($this->status),
        };
    }

    /**
     * Check if task is executable.
     */
    public function isExecutable()
    {
        if ($this->status === self::STATUS_READY) {
            return true;
        }

        if ($this->status === self::STATUS_SCHEDULED && $this->scheduledAt <= Carbon::now('Asia/Manila')) {
            return true;
        }

        return false;
    }

    /**
     * Check if task can be retried.
     */
    public function canRetry()
    {
        return $this->status === self::STATUS_FAILED && $this->retryCount < $this->maxRetries;
    }

    /**
     * Mark task as running.
     */
    public function markRunning()
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'startedAt' => now(),
        ]);
    }

    /**
     * Mark task as completed.
     */
    public function markCompleted($resultData = null)
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completedAt' => now(),
            'resultData' => $resultData,
        ]);

        // Update enrollment progress
        $this->enrollment->incrementCompletedTasks();
    }

    /**
     * Mark task as failed.
     */
    public function markFailed($errorMessage = null)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completedAt' => now(),
            'errorMessage' => $errorMessage,
        ]);
    }

    /**
     * Mark task as skipped.
     */
    public function markSkipped($reason = null)
    {
        $this->update([
            'status' => self::STATUS_SKIPPED,
            'completedAt' => now(),
            'resultData' => ['skip_reason' => $reason],
        ]);

        // Still count as completed for progress
        $this->enrollment->incrementCompletedTasks();
    }

    /**
     * Mark task as cancelled.
     */
    public function markCancelled()
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'completedAt' => now(),
        ]);
    }

    /**
     * Schedule task for a specific time.
     */
    public function scheduleFor(Carbon $dateTime)
    {
        $this->update([
            'status' => self::STATUS_SCHEDULED,
            'scheduledAt' => $dateTime,
        ]);
    }

    /**
     * Mark task as ready to execute.
     */
    public function markReady()
    {
        $this->update([
            'status' => self::STATUS_READY,
            'scheduledAt' => now(),
        ]);
    }

    /**
     * Increment retry count.
     */
    public function incrementRetry()
    {
        $this->update([
            'retryCount' => $this->retryCount + 1,
            'lastRetryAt' => now(),
            'status' => self::STATUS_READY,
        ]);
    }

    /**
     * Get a summary of what this task will do.
     */
    public function getTaskSummaryAttribute()
    {
        $nodeData = $this->nodeData ?? [];

        return match($this->nodeType) {
            'delay' => 'Wait ' . ($nodeData['delayValue'] ?? 1) . ' ' . ($nodeData['delayType'] ?? 'days'),
            'email' => 'Send email: ' . ($nodeData['subject'] ?? 'No subject'),
            'send_sms' => 'Send SMS',
            'send_whatsapp' => 'Send WhatsApp',
            'course_access' => 'Grant access: ' . ($nodeData['tagName'] ?? 'Unknown'),
            'remove_access' => 'Remove access: ' . ($nodeData['tagName'] ?? 'Unknown'),
            'add_as_affiliate' => 'Add as affiliate to: ' . ($nodeData['storeName'] ?? 'Unknown'),
            'add_login_access' => 'Grant login to: ' . ($nodeData['storeName'] ?? 'Unknown'),
            'course_subscription' => ($nodeData['action'] === 'add' ? 'Subscribe to: ' : 'Unsubscribe from: ') . ($nodeData['courseName'] ?? 'Unknown'),
            'if_else' => 'Check condition: ' . ($nodeData['conditionType'] ?? 'Unknown'),
            'y_flow' => 'Split into parallel paths',
            'flow_action' => ($nodeData['action'] === 'add' ? 'Add to flow: ' : 'Remove from flow: ') . ($nodeData['flowName'] ?? 'Unknown'),
            default => $this->nodeTypeLabel,
        };
    }
}
