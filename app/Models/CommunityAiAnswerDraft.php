<?php

namespace App\Models;

/**
 * A generated (or edited) AI answer awaiting the admin's review before it is
 * posted as a community reply by the AI Technician persona.
 */
class CommunityAiAnswerDraft extends BaseModel
{
    protected $table = 'as_community_ai_answer_drafts';

    protected $fillable = [
        'postId',
        'groupId',
        'questionTitle',
        'questionBody',
        'answerBody',
        'status',
        'model',
        'generatedByUserId',
        'postedReplyId',
        'postedAt',
        'deleteStatus',
    ];

    protected $casts = [
        'postedAt' => 'datetime',
        'deleteStatus' => 'integer',
    ];

    public function scopeActive($q)
    {
        return $q->where('as_community_ai_answer_drafts.deleteStatus', 1);
    }

    public function post()
    {
        return $this->belongsTo(CommunityGroupPost::class, 'postId');
    }
}
