<?php

namespace App\Modules\OutreachEngine\Models;

use App\Models\BaseModel;

/**
 * One contact's position in one campaign.
 *
 * This is the flow's state machine. The state here belongs to the CAMPAIGN, not
 * to the contact: the same business can be finished in one campaign and waiting
 * in another, which is exactly why outreach_leads.outreachStatus could not carry
 * this on its own.
 *
 * nextActionAt is the clock. Every state that is waiting for something stamps
 * when it should next be looked at, so the cron selects due rows instead of
 * walking the whole pool and re-deriving each lead's next step every minute.
 * A null means the row is not waiting on time - it is either finished, or due
 * right now.
 */
class OutreachTaskLead extends BaseModel
{
    protected $table = 'outreach_task_leads';

    protected $fillable = [
        'usersId',
        'taskId',
        'leadId',
        'state',
        'emailsSent',
        'followUpCount',
        'firstSentAt',
        'lastSentAt',
        'lastRepliedAt',
        'stateChangedAt',
        'nextActionAt',
        'lastEmailLogId',
        'lastNote',
        'delete_status',
    ];

    protected $casts = [
        'usersId' => 'integer',
        'taskId' => 'integer',
        'leadId' => 'integer',
        'emailsSent' => 'integer',
        'followUpCount' => 'integer',
        'lastEmailLogId' => 'integer',
        'firstSentAt' => 'datetime:Y-m-d H:i:s',
        'lastSentAt' => 'datetime:Y-m-d H:i:s',
        'lastRepliedAt' => 'datetime:Y-m-d H:i:s',
        'stateChangedAt' => 'datetime:Y-m-d H:i:s',
        'nextActionAt' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    const STATE_PENDING = 'pending';
    const STATE_QUEUED = 'queued';
    const STATE_SENT = 'sent';
    const STATE_REPLIED = 'replied';
    const STATE_INTERESTED = 'interested';
    const STATE_NOT_INTERESTED = 'not_interested';
    const STATE_NO_REPLY = 'no_reply';
    const STATE_BOUNCED = 'bounced';
    const STATE_SPAM = 'spam';
    const STATE_STOPPED = 'stopped';

    /**
     * States the flow will never touch again.
     *
     * 'interested' is in here deliberately: once a contact shows interest the
     * automation stands down and a person answers from the inbox. Continuing to
     * send at someone who just engaged is the fastest way to lose them.
     */
    const TERMINAL_STATES = [
        self::STATE_INTERESTED,
        self::STATE_NOT_INTERESTED,
        self::STATE_NO_REPLY,
        self::STATE_BOUNCED,
        self::STATE_SPAM,
        self::STATE_STOPPED,
    ];

    /**
     * @return array<string, string>
     */
    public static function getStateLabels(): array
    {
        return [
            self::STATE_PENDING => 'Waiting to start',
            self::STATE_QUEUED => 'Queued',
            self::STATE_SENT => 'Sent, awaiting reply',
            self::STATE_REPLIED => 'Replied',
            self::STATE_INTERESTED => 'Interested',
            self::STATE_NOT_INTERESTED => 'Not interested',
            self::STATE_NO_REPLY => 'No reply',
            self::STATE_BOUNCED => 'Bounced',
            self::STATE_SPAM => 'Spam complaint',
            self::STATE_STOPPED => 'Stopped',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Scope: rows the flow may still act on.
     */
    public function scopeOpen($query)
    {
        return $query->whereNotIn('state', self::TERMINAL_STATES);
    }

    /**
     * Scope: rows whose clock has run out (or that were never waiting).
     */
    public function scopeDue($query, $now = null)
    {
        $now = $now ?: now('Asia/Manila');

        return $query->where(function ($inner) use ($now) {
            $inner->whereNull('nextActionAt')->orWhere('nextActionAt', '<=', $now);
        });
    }

    public function task()
    {
        return $this->belongsTo(OutreachTask::class, 'taskId');
    }

    public function lead()
    {
        return $this->belongsTo(OutreachLead::class, 'leadId');
    }

    /**
     * Is this row finished as far as the flow is concerned?
     */
    public function isTerminal(): bool
    {
        return in_array($this->state, self::TERMINAL_STATES, true);
    }

    /**
     * Move to a new state, stamping when and optionally when to look again.
     *
     * Terminal states always clear nextActionAt - a finished row that still
     * carries a due time would be picked up by the cron forever.
     */
    public function moveTo(string $state, ?\DateTimeInterface $nextActionAt = null, ?string $note = null): self
    {
        $this->state = $state;
        $this->stateChangedAt = now('Asia/Manila');
        $this->nextActionAt = in_array($state, self::TERMINAL_STATES, true) ? null : $nextActionAt;

        if ($note !== null) {
            $this->lastNote = mb_substr($note, 0, 1000);
        }

        $this->save();

        return $this;
    }

    /**
     * Badge for the pool state, per CLAUDE.md section 12.2 contrast rules.
     */
    public function getStateBadgeAttribute(): string
    {
        switch ($this->state) {
            case self::STATE_INTERESTED:
                return '<span class="badge bg-success">Interested</span>';
            case self::STATE_REPLIED:
                return '<span class="badge bg-info text-white">Replied</span>';
            case self::STATE_SENT:
                return '<span class="badge bg-primary">Sent</span>';
            case self::STATE_QUEUED:
                return '<span class="badge bg-info text-white">Queued</span>';
            case self::STATE_NOT_INTERESTED:
                return '<span class="badge bg-secondary">Not interested</span>';
            case self::STATE_NO_REPLY:
                return '<span class="badge bg-light text-dark">No reply</span>';
            case self::STATE_BOUNCED:
                return '<span class="badge bg-danger">Bounced</span>';
            case self::STATE_SPAM:
                return '<span class="badge bg-danger">Spam</span>';
            case self::STATE_STOPPED:
                return '<span class="badge bg-light text-dark">Stopped</span>';
            default:
                return '<span class="badge bg-warning text-dark">Waiting</span>';
        }
    }
}
