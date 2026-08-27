<?php

namespace App\Models;

/**
 * One email AniSystem means to send.
 *
 * The book is shared with the farmer app: it writes rows for anything a
 * person is standing there waiting for and sends those itself, and this side
 * writes the scheduled work and drains whatever is due. Both read the same
 * table, so "did Nena get her schedule?" has one answer.
 */
class AsEmailTask extends BaseModel
{
    protected $table = 'as_email_tasks';

    public const QUEUED = 'queued';
    public const SENT = 'sent';
    public const FAILED = 'failed';

    /** After this many tries a row stops being picked up; the reason stays. */
    public const MAX_ATTEMPTS = 4;

    protected $fillable = [
        'groupKey', 'templateKey', 'toEmail', 'toName', 'subject', 'bodyHtml',
        'status', 'attempts', 'lastError', 'providerId', 'sendAfter', 'sentAt',
        'relatedType', 'relatedId', 'croppingScheduleId', 'createdByUserId',
        'deleteStatus',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'sendAfter' => 'datetime',
        'sentAt' => 'datetime',
        'deleteStatus' => 'integer',
    ];

    /** Due, still owed, oldest first — and not one that has given up. */
    public function scopeDue($query)
    {
        return $query->where('deleteStatus', 1)
            ->whereIn('status', [self::QUEUED, self::FAILED])
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->where(function ($w) {
                $w->whereNull('sendAfter')->orWhere('sendAfter', '<=', now());
            })
            ->orderBy('sendAfter')
            ->orderBy('id');
    }

    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /** A short word for the list. */
    public function statusLabel(): string
    {
        if ($this->status === self::SENT) {
            return 'Sent';
        }
        if ($this->status === self::FAILED) {
            return $this->attempts >= self::MAX_ATTEMPTS ? 'Given up' : 'Failed — will retry';
        }

        return $this->sendAfter && $this->sendAfter->isFuture() ? 'Scheduled' : 'Waiting';
    }

    /** What Bootstrap should colour the badge. */
    public function statusTone(): string
    {
        if ($this->status === self::SENT) {
            return 'success';
        }
        if ($this->status === self::FAILED) {
            return $this->attempts >= self::MAX_ATTEMPTS ? 'danger' : 'warning';
        }

        return 'secondary';
    }
}
