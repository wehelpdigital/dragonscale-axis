<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CrmLeadActivity extends BaseModel
{
    use HasFactory;

    protected $table = 'crm_lead_activities';

    protected $fillable = [
        'leadId',
        'usersId',
        'activityType',
        'activitySubject',
        'activityDescription',
        'activityDate',
        'durationMinutes',
        'previousStatus',
        'newStatus',
        'attachments',
        'delete_status',
    ];

    protected $casts = [
        'activityDate' => 'datetime',
        'durationMinutes' => 'integer',
        'attachments' => 'array',
    ];

    /**
     * Activity type options with labels and icons
     */
    public const ACTIVITY_TYPES = [
        'call_outbound' => ['label' => 'Outbound Call', 'icon' => 'mdi-phone-outgoing', 'color' => 'primary'],
        'call_inbound' => ['label' => 'Inbound Call', 'icon' => 'mdi-phone-incoming', 'color' => 'info'],
        'email_sent' => ['label' => 'Email Sent', 'icon' => 'mdi-email-send', 'color' => 'success'],
        'email_received' => ['label' => 'Email Received', 'icon' => 'mdi-email-receive', 'color' => 'info'],
        'meeting' => ['label' => 'Meeting', 'icon' => 'mdi-account-group', 'color' => 'warning'],
        'note' => ['label' => 'Note', 'icon' => 'mdi-note-text', 'color' => 'secondary'],
        'status_change' => ['label' => 'Status Change', 'icon' => 'mdi-swap-horizontal', 'color' => 'dark'],
        'follow_up' => ['label' => 'Follow-up', 'icon' => 'mdi-calendar-check', 'color' => 'primary'],
        'proposal_sent' => ['label' => 'Proposal Sent', 'icon' => 'mdi-file-document-outline', 'color' => 'success'],
        'document_sent' => ['label' => 'Document Sent', 'icon' => 'mdi-file-send', 'color' => 'info'],
        'social_media' => ['label' => 'Social Media', 'icon' => 'mdi-share-variant', 'color' => 'primary'],
        'other' => ['label' => 'Other', 'icon' => 'mdi-dots-horizontal', 'color' => 'secondary'],
    ];

    /**
     * Scope for active (non-deleted) records
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Get activity type label
     */
    public function getTypeLabelAttribute()
    {
        return self::ACTIVITY_TYPES[$this->activityType]['label'] ?? $this->activityType;
    }

    /**
     * Get activity type icon
     */
    public function getTypeIconAttribute()
    {
        $icon = self::ACTIVITY_TYPES[$this->activityType]['icon'] ?? 'mdi-circle';
        // Ensure proper MDI class format (mdi mdi-iconname)
        if ($icon && !str_starts_with($icon, 'mdi ')) {
            $icon = 'mdi ' . $icon;
        }
        return $icon;
    }

    /**
     * Get activity type color
     */
    public function getTypeColorAttribute()
    {
        return self::ACTIVITY_TYPES[$this->activityType]['color'] ?? 'secondary';
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute()
    {
        if (!$this->durationMinutes) {
            return null;
        }

        $hours = floor($this->durationMinutes / 60);
        $minutes = $this->durationMinutes % 60;

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }

        return $minutes . ' min';
    }

    /**
     * Get lead relationship
     */
    public function lead()
    {
        return $this->belongsTo(CrmLead::class, 'leadId');
    }

    /**
     * Get user who performed the activity
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }
}
