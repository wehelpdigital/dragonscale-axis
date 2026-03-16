<?php

namespace App\Models;

class EcomTriggerFlowEnrollment extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_trigger_flow_enrollments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'flowId',
        'clientId',
        'orderId',
        'triggerSource',
        'contextData',
        'status',
        'totalTasks',
        'completedTasks',
        'currentTaskOrder',
        'startedAt',
        'completedAt',
        'cancelledAt',
        'cancelledBy',
        'cancellationReason',
        'createdBy',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'flowId' => 'integer',
        'clientId' => 'integer',
        'orderId' => 'integer',
        'contextData' => 'array',
        'totalTasks' => 'integer',
        'completedTasks' => 'integer',
        'currentTaskOrder' => 'integer',
        'cancelledBy' => 'integer',
        'createdBy' => 'integer',
        'startedAt' => 'datetime:Y-m-d H:i:s',
        'completedAt' => 'datetime:Y-m-d H:i:s',
        'cancelledAt' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_PAUSED = 'paused';
    const STATUS_FAILED = 'failed';

    /**
     * Trigger source constants
     */
    const SOURCE_MANUAL = 'manual';
    const SOURCE_ORDER = 'order';
    const SOURCE_API = 'api';
    const SOURCE_FLOW_ACTION = 'flow_action';

    /**
     * Scope to get only active enrollments (not deleted)
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 'active');
    }

    /**
     * Scope to get enrollments with active status
     */
    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get completed enrollments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to get cancelled enrollments
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope to filter by flow
     */
    public function scopeForFlow($query, $flowId)
    {
        return $query->where('flowId', $flowId);
    }

    /**
     * Scope to filter by client
     */
    public function scopeForClient($query, $clientId)
    {
        return $query->where('clientId', $clientId);
    }

    /**
     * Scope to filter by order
     */
    public function scopeForOrder($query, $orderId)
    {
        return $query->where('orderId', $orderId);
    }

    /**
     * Get the flow that this enrollment belongs to.
     */
    public function flow()
    {
        return $this->belongsTo(EcomTriggerFlow::class, 'flowId');
    }

    /**
     * Get the client associated with this enrollment.
     */
    public function client()
    {
        return $this->belongsTo(ClientAllDatabase::class, 'clientId');
    }

    /**
     * Get the order associated with this enrollment.
     */
    public function order()
    {
        return $this->belongsTo(EcomOrder::class, 'orderId');
    }

    /**
     * Get the user who created this enrollment.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'createdBy');
    }

    /**
     * Get the user who cancelled this enrollment.
     */
    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelledBy');
    }

    /**
     * Get all tasks for this enrollment.
     */
    public function tasks()
    {
        return $this->hasMany(EcomTriggerFlowTask::class, 'enrollmentId')->orderBy('taskOrder');
    }

    /**
     * Get pending tasks for this enrollment.
     */
    public function pendingTasks()
    {
        return $this->hasMany(EcomTriggerFlowTask::class, 'enrollmentId')
            ->whereIn('status', ['pending', 'scheduled', 'ready'])
            ->orderBy('taskOrder');
    }

    /**
     * Get completed tasks for this enrollment.
     */
    public function completedTasks()
    {
        return $this->hasMany(EcomTriggerFlowTask::class, 'enrollmentId')
            ->where('status', 'completed')
            ->orderBy('taskOrder');
    }

    /**
     * Get logs for this enrollment.
     */
    public function logs()
    {
        return $this->hasMany(EcomTriggerFlowLog::class, 'enrollmentId')->orderBy('created_at', 'desc');
    }

    /**
     * Get the current task being processed.
     */
    public function getCurrentTaskAttribute()
    {
        return $this->tasks()
            ->whereIn('status', ['pending', 'scheduled', 'ready', 'running'])
            ->orderBy('taskOrder')
            ->first();
    }

    /**
     * Get progress percentage.
     */
    public function getProgressPercentAttribute()
    {
        if ($this->totalTasks == 0) {
            return 0;
        }
        return round(($this->completedTasks / $this->totalTasks) * 100);
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'bg-primary',
            self::STATUS_COMPLETED => 'bg-success',
            self::STATUS_CANCELLED => 'bg-secondary',
            self::STATUS_PAUSED => 'bg-warning text-dark',
            self::STATUS_FAILED => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'Running',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_PAUSED => 'Paused',
            self::STATUS_FAILED => 'Failed',
            default => ucfirst($this->status),
        };
    }

    /**
     * Cancel the enrollment.
     */
    public function cancel($userId = null, $reason = null)
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelledAt' => now(),
            'cancelledBy' => $userId,
            'cancellationReason' => $reason,
        ]);

        // Cancel all pending tasks
        $this->tasks()
            ->whereIn('status', ['pending', 'scheduled', 'ready'])
            ->update(['status' => 'cancelled']);
    }

    /**
     * Pause the enrollment.
     */
    public function pause()
    {
        $this->update(['status' => self::STATUS_PAUSED]);
    }

    /**
     * Resume the enrollment.
     */
    public function resume()
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Mark as completed.
     */
    public function markCompleted()
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completedAt' => now(),
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markFailed()
    {
        $this->update(['status' => self::STATUS_FAILED]);
    }

    /**
     * Increment completed tasks count.
     */
    public function incrementCompletedTasks()
    {
        $this->increment('completedTasks');
        $this->increment('currentTaskOrder');

        // Check if all tasks are completed
        if ($this->completedTasks >= $this->totalTasks) {
            $this->markCompleted();
        }
    }
}
