<?php

namespace App\Models;

class EcomTriggerFlowLog extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_trigger_flow_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'enrollmentId',
        'taskId',
        'flowId',
        'action',
        'nodeType',
        'nodeLabel',
        'logData',
        'message',
        'logLevel',
        'ipAddress',
        'userAgent',
        'executedBy',
        'executionSource',
        'executionTime',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'enrollmentId' => 'integer',
        'taskId' => 'integer',
        'flowId' => 'integer',
        'executedBy' => 'integer',
        'logData' => 'array',
        'executionTime' => 'decimal:4',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Action constants
     */
    const ACTION_ENROLLMENT_CREATED = 'enrollment_created';
    const ACTION_ENROLLMENT_COMPLETED = 'enrollment_completed';
    const ACTION_ENROLLMENT_CANCELLED = 'enrollment_cancelled';
    const ACTION_ENROLLMENT_PAUSED = 'enrollment_paused';
    const ACTION_ENROLLMENT_RESUMED = 'enrollment_resumed';
    const ACTION_TASK_SCHEDULED = 'task_scheduled';
    const ACTION_TASK_STARTED = 'task_started';
    const ACTION_TASK_COMPLETED = 'task_completed';
    const ACTION_TASK_FAILED = 'task_failed';
    const ACTION_TASK_SKIPPED = 'task_skipped';
    const ACTION_TASK_CANCELLED = 'task_cancelled';
    const ACTION_TASK_RETRIED = 'task_retried';
    const ACTION_CRON_RUN = 'cron_run';
    const ACTION_EMAIL_SENT = 'email_sent';
    const ACTION_SMS_SENT = 'sms_sent';
    const ACTION_WHATSAPP_SENT = 'whatsapp_sent';

    /**
     * Execution source constants
     */
    const SOURCE_CRON = 'cron';
    const SOURCE_MANUAL = 'manual';
    const SOURCE_API = 'api';

    /**
     * Log level constants
     */
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_DEBUG = 'debug';

    /**
     * Get the enrollment this log belongs to.
     */
    public function enrollment()
    {
        return $this->belongsTo(EcomTriggerFlowEnrollment::class, 'enrollmentId');
    }

    /**
     * Get the task this log belongs to.
     */
    public function task()
    {
        return $this->belongsTo(EcomTriggerFlowTask::class, 'taskId');
    }

    /**
     * Get the flow this log belongs to.
     */
    public function flow()
    {
        return $this->belongsTo(EcomTriggerFlow::class, 'flowId');
    }

    /**
     * Get the user who executed this action.
     */
    public function executor()
    {
        return $this->belongsTo(User::class, 'executedBy');
    }

    /**
     * Get log level badge class.
     */
    public function getLogLevelBadgeClassAttribute()
    {
        return match($this->logLevel) {
            self::LEVEL_INFO => 'bg-info text-white',
            self::LEVEL_WARNING => 'bg-warning text-dark',
            self::LEVEL_ERROR => 'bg-danger',
            self::LEVEL_DEBUG => 'bg-secondary',
            default => 'bg-secondary',
        };
    }

    /**
     * Get action badge class.
     */
    public function getActionBadgeClassAttribute()
    {
        return match($this->action) {
            self::ACTION_ENROLLMENT_CREATED => 'bg-primary',
            self::ACTION_ENROLLMENT_COMPLETED => 'bg-success',
            self::ACTION_ENROLLMENT_CANCELLED => 'bg-secondary',
            self::ACTION_TASK_COMPLETED => 'bg-success',
            self::ACTION_TASK_FAILED => 'bg-danger',
            self::ACTION_TASK_SKIPPED => 'bg-light text-dark',
            self::ACTION_CRON_RUN => 'bg-info text-white',
            default => 'bg-secondary',
        };
    }

    /**
     * Create an info log.
     */
    public static function info($action, $message, $data = [])
    {
        return self::createLog(self::LEVEL_INFO, $action, $message, $data);
    }

    /**
     * Create a warning log.
     */
    public static function warning($action, $message, $data = [])
    {
        return self::createLog(self::LEVEL_WARNING, $action, $message, $data);
    }

    /**
     * Create an error log.
     */
    public static function error($action, $message, $data = [])
    {
        return self::createLog(self::LEVEL_ERROR, $action, $message, $data);
    }

    /**
     * Create a log entry.
     */
    public static function createLog($level, $action, $message, $data = [])
    {
        return self::create([
            'enrollmentId' => $data['enrollmentId'] ?? null,
            'taskId' => $data['taskId'] ?? null,
            'flowId' => $data['flowId'] ?? null,
            'action' => $action,
            'nodeType' => $data['nodeType'] ?? null,
            'nodeLabel' => $data['nodeLabel'] ?? null,
            'logData' => $data['logData'] ?? null,
            'message' => $message,
            'logLevel' => $level,
            'ipAddress' => $data['ipAddress'] ?? request()->ip(),
            'userAgent' => $data['userAgent'] ?? request()->userAgent(),
            'executedBy' => $data['executedBy'] ?? auth()->id(),
            'executionSource' => $data['executionSource'] ?? self::SOURCE_MANUAL,
            'executionTime' => $data['executionTime'] ?? null,
        ]);
    }
}
