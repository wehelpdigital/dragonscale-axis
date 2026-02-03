<?php

namespace App\Models;

class CrmFormTriggerLog extends BaseModel
{
    protected $table = 'crm_form_trigger_logs';

    protected $fillable = [
        'triggerId',
        'submissionId',
        'executionStatus',
        'executionDetails',
        'errorMessage',
    ];

    protected $casts = [
        'triggerId' => 'integer',
        'submissionId' => 'integer',
        'executionDetails' => 'array',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Get the trigger that this log belongs to
     */
    public function trigger()
    {
        return $this->belongsTo(CrmFormTrigger::class, 'triggerId');
    }

    /**
     * Get the submission that this log belongs to
     */
    public function submission()
    {
        return $this->belongsTo(CrmFormSubmission::class, 'submissionId');
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClass()
    {
        return match($this->executionStatus) {
            'success' => 'bg-success',
            'failed' => 'bg-danger',
            'partial' => 'bg-warning',
            default => 'bg-secondary',
        };
    }
}
