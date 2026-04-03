<?php

namespace App\Models;

class AsChatConversation extends BaseModel
{
    protected $table = 'as_chat_conversations';

    protected $fillable = [
        'visitorName',
        'visitorEmail',
        'farmLocation',
        'visitorType',
        'leadId',
        'sessionId',
        'assignedTo',
        'status',
        'deleteStatus',
    ];

    /**
     * Visitor type labels (Tagalog)
     */
    public const VISITOR_TYPE_LABELS = [
        'farm_owner' => 'Farm Owner',
        'farm_worker' => 'Farm Worker',
        'other' => 'Iba Pa',
    ];

    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 'active');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'active');
    }

    public function messages()
    {
        return $this->hasMany(AsChatMessage::class, 'conversationId')->where('deleteStatus', 'active');
    }

    public function latestMessage()
    {
        return $this->hasOne(AsChatMessage::class, 'conversationId')->where('deleteStatus', 'active')->latest();
    }

    public function unreadCount()
    {
        return $this->messages()->where('senderType', 'visitor')->where('isRead', false)->count();
    }

    public function getStatusBadgeAttribute()
    {
        return match ($this->status) {
            'active' => 'bg-success',
            'closed' => 'bg-secondary',
            default => 'bg-secondary',
        };
    }

    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'active' => 'Active',
            'closed' => 'Closed',
            default => ucfirst($this->status),
        };
    }
}
