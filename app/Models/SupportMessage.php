<?php

namespace App\Models;

/**
 * One message in a support ticket thread — authored by the client or an admin.
 * Shared with the AniSystem app — integer deleteStatus (1 = live, 0 = removed).
 */
class SupportMessage extends BaseModel
{
    protected $table = 'as_support_messages';

    protected $fillable = [
        'ticketId',
        'authorType',
        'authorId',
        'authorName',
        'body',
        'deleteStatus',
    ];

    protected $casts = [
        'ticketId' => 'integer',
        'authorId' => 'integer',
        'deleteStatus' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticketId');
    }
}
