<?php

namespace App\Models;

/**
 * A support ticket raised by an AniSystem client. Admins answer it here in the
 * mother app; the thread of client + admin messages lives in as_support_messages.
 * Shared with the AniSystem app — integer deleteStatus (1 = live, 0 = removed).
 */
class SupportTicket extends BaseModel
{
    protected $table = 'as_support_tickets';

    protected $fillable = [
        'userId',
        'subject',
        'category',
        'status',
        'lastReplyAt',
        'deleteStatus',
    ];

    protected $casts = [
        'userId' => 'integer',
        'deleteStatus' => 'integer',
        'lastReplyAt' => 'datetime',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /** Category key => label, kept in sync with the AniSystem client app. */
    public const CATEGORIES = [
        'general' => 'General',
        'billing' => 'Billing',
        'technical' => 'Technical',
        'schedule' => 'Schedules',
        'community' => 'Community',
    ];

    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    public function messages()
    {
        return $this->hasMany(SupportMessage::class, 'ticketId');
    }

    public function user()
    {
        return $this->belongsTo(AnisystemUser::class, 'userId');
    }
}
