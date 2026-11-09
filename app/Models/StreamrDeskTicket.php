<?php

namespace App\Models;

/**
 * A Streamr support ticket (streamr_desk_tickets), opened in Streamr and
 * answered from either side. Ticket numbers come from Streamr only.
 */
class StreamrDeskTicket extends BaseModel
{
    protected $table = 'streamr_desk_tickets';

    public const STATUSES = [
        'open' => 'Open',
        'answered' => 'Answered',
        'closed' => 'Closed',
    ];

    public const STATUS_BADGES = [
        'open' => 'bg-warning text-dark',
        'answered' => 'bg-success',
        'closed' => 'bg-secondary',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    public const PRIORITY_BADGES = [
        'low' => 'bg-light text-dark',
        'normal' => 'bg-info text-white',
        'high' => 'bg-warning text-dark',
        'urgent' => 'bg-danger',
    ];

    protected $fillable = [
        'uuid', 'ticketNumber', 'streamrUserId', 'clientName', 'clientEmail', 'storeName', 'subject', 'category',
        'priority', 'status', 'lastMessageAt', 'lastMessageBy', 'adminUnread', 'clientUnread', 'adminReadAt',
        'statusChangedAt', 'statusChangedBy', 'origin', 'delete_status',
    ];

    protected $casts = [
        'streamrUserId' => 'integer',
        'adminUnread' => 'integer',
        'clientUnread' => 'integer',
        'lastMessageAt' => 'datetime',
        'adminReadAt' => 'datetime',
        'statusChangedAt' => 'datetime',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    public function messages()
    {
        return $this->hasMany(StreamrDeskMessage::class, 'ticketId');
    }

    public function categoryLabel(): string
    {
        foreach ((array) config('streamr.support_categories', []) as $category) {
            if (($category['key'] ?? null) === $this->category) {
                return (string) $category['label'];
            }
        }

        return ucfirst(str_replace('_', ' ', (string) ($this->category ?: 'general')));
    }
}
