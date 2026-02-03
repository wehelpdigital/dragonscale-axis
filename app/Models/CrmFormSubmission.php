<?php

namespace App\Models;

class CrmFormSubmission extends BaseModel
{
    protected $table = 'crm_form_submissions';

    protected $fillable = [
        'formId',
        'submissionData',
        'submitterIp',
        'submitterUserAgent',
        'submitterEmail',
        'submitterName',
        'submissionStatus',
        'processedAt',
        'delete_status',
    ];

    protected $casts = [
        'formId' => 'integer',
        'submissionData' => 'array',
        'processedAt' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Scope to get only active submissions (delete_status = 'active')
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Scope to get submissions for a specific form
     */
    public function scopeForForm($query, $formId)
    {
        return $query->where('formId', $formId);
    }

    /**
     * Scope to get new submissions
     */
    public function scopeNew($query)
    {
        return $query->where('submissionStatus', 'new');
    }

    /**
     * Get the form that this submission belongs to
     */
    public function form()
    {
        return $this->belongsTo(CrmForm::class, 'formId');
    }

    /**
     * Get the trigger logs for this submission
     */
    public function triggerLogs()
    {
        return $this->hasMany(CrmFormTriggerLog::class, 'submissionId');
    }

    /**
     * Get a specific field value from submission data
     */
    public function getFieldValue($fieldId)
    {
        $data = $this->submissionData ?? [];
        return $data[$fieldId] ?? null;
    }

    /**
     * Mark submission as read
     */
    public function markAsRead()
    {
        $this->update(['submissionStatus' => 'read']);
    }

    /**
     * Mark submission as processed
     */
    public function markAsProcessed()
    {
        $this->update([
            'submissionStatus' => 'processed',
            'processedAt' => now(),
        ]);
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClass()
    {
        return match($this->submissionStatus) {
            'new' => 'bg-primary',
            'read' => 'bg-info',
            'processed' => 'bg-success',
            'archived' => 'bg-secondary',
            default => 'bg-secondary',
        };
    }
}
