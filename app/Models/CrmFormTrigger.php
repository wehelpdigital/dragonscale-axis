<?php

namespace App\Models;

class CrmFormTrigger extends BaseModel
{
    protected $table = 'crm_form_triggers';

    protected $fillable = [
        'formId',
        'triggerName',
        'triggerDescription',
        'triggerEvent',
        'triggerStatus',
        'triggerFlow',
        'executionCount',
        'lastExecutedAt',
        'delete_status',
    ];

    protected $casts = [
        'formId' => 'integer',
        'triggerFlow' => 'array',
        'executionCount' => 'integer',
        'lastExecutedAt' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Scope to get only active triggers (delete_status = 'active')
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Scope to get triggers for a specific form
     */
    public function scopeForForm($query, $formId)
    {
        return $query->where('formId', $formId);
    }

    /**
     * Scope to get only enabled triggers
     */
    public function scopeEnabled($query)
    {
        return $query->where('triggerStatus', 'active');
    }

    /**
     * Get the form that this trigger belongs to
     */
    public function form()
    {
        return $this->belongsTo(CrmForm::class, 'formId');
    }

    /**
     * Get the execution logs for this trigger
     */
    public function logs()
    {
        return $this->hasMany(CrmFormTriggerLog::class, 'triggerId');
    }

    /**
     * Check if trigger is enabled
     */
    public function isEnabled()
    {
        return $this->triggerStatus === 'active';
    }

    /**
     * Increment execution count
     */
    public function recordExecution()
    {
        $this->increment('executionCount');
        $this->update(['lastExecutedAt' => now()]);
    }

    /**
     * Get available trigger actions
     */
    public static function getAvailableActions()
    {
        return [
            'send_email' => [
                'name' => 'Send Email',
                'icon' => 'bx-envelope',
                'color' => '#556ee6',
                'description' => 'Send an email notification',
                'fields' => ['to', 'subject', 'body'],
            ],
            'create_lead' => [
                'name' => 'Create Lead',
                'icon' => 'bx-user-plus',
                'color' => '#34c38f',
                'description' => 'Create a new CRM lead',
                'fields' => ['source', 'status'],
            ],
            'add_tag' => [
                'name' => 'Add Tag',
                'icon' => 'bx-purchase-tag',
                'color' => '#f46a6a',
                'description' => 'Add a tag to the contact',
                'fields' => ['tagId'],
            ],
            'webhook' => [
                'name' => 'Webhook',
                'icon' => 'bx-link-external',
                'color' => '#50a5f1',
                'description' => 'Send data to an external URL',
                'fields' => ['url', 'method', 'headers'],
            ],
            'delay' => [
                'name' => 'Delay',
                'icon' => 'bx-time',
                'color' => '#f1b44c',
                'description' => 'Wait for a specified time',
                'fields' => ['duration', 'unit'],
            ],
            'condition' => [
                'name' => 'Condition',
                'icon' => 'bx-git-branch',
                'color' => '#74788d',
                'description' => 'Branch based on condition',
                'fields' => ['field', 'operator', 'value'],
            ],
            'notify_admin' => [
                'name' => 'Notify Admin',
                'icon' => 'bx-bell',
                'color' => '#e83e8c',
                'description' => 'Send notification to admin',
                'fields' => ['message'],
            ],
        ];
    }
}
