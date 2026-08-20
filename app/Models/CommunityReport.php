<?php

namespace App\Models;

/**
 * A member of AniSenso saying "this does not belong here".
 *
 * Written by the AniSystem app, read and acted on here — this is the house's
 * side of the same shared table. Nothing about the reported content changes
 * when a report arrives; that is a decision staff make.
 */
class CommunityReport extends BaseModel
{
    protected $table = 'as_community_reports';

    protected $fillable = [
        'reporterUserId', 'targetType', 'targetId', 'targetUserId',
        'reason', 'details', 'snapshot',
        'status', 'note', 'reviewedByUserId', 'reviewedAt', 'deleteStatus',
    ];

    protected $casts = ['reviewedAt' => 'datetime'];

    /** The words the app offers a farmer, keyed as they are stored. */
    public const REASONS = [
        'spam' => 'Spam or advertising',
        'scam' => 'Scam or fake selling',
        'false' => 'False or misleading advice',
        'harassment' => 'Bullying or harassment',
        'hate' => 'Hateful or abusive language',
        'sexual' => 'Nudity or sexual content',
        'violence' => 'Violence or something dangerous',
        'other' => 'Something else',
    ];

    /** What each kind of report points at, in words staff use. */
    public const TARGETS = [
        'post' => 'Wall post',
        'story' => 'Story',
        'comment' => 'Wall comment',
        'topic' => 'Discussion topic',
        'reply' => 'Discussion reply',
        'group' => 'Discussion',
    ];

    public function scopeActive($q)
    {
        return $q->where('as_community_reports.deleteStatus', 1);
    }

    public function reporter()
    {
        return $this->belongsTo(AnisystemUser::class, 'reporterUserId');
    }

    public function reportedUser()
    {
        return $this->belongsTo(AnisystemUser::class, 'targetUserId');
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? ucfirst((string) $this->reason);
    }

    public function targetLabel(): string
    {
        return self::TARGETS[$this->targetType] ?? ucfirst((string) $this->targetType);
    }
}
