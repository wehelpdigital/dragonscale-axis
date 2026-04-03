<?php

namespace App\Models;

class AsChatMessage extends BaseModel
{
    protected $table = 'as_chat_messages';

    protected $fillable = [
        'conversationId',
        'senderType',
        'senderId',
        'message',
        'isRead',
        'deleteStatus',
    ];

    protected $casts = [
        'isRead' => 'boolean',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 'active');
    }

    public function conversation()
    {
        return $this->belongsTo(AsChatConversation::class, 'conversationId');
    }
}
