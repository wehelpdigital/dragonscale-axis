<?php

namespace App\Modules\OutreachEngine\Models;

use App\Models\BaseModel;

/**
 * A campaign: one finalised list, one flow, one pool of contacts to work through.
 *
 * flowConfig holds the per-node settings the Outreach Flow screen edits. The
 * node SET is fixed - four triggers, wired the way the flow describes - so what
 * varies is only each node's configuration, which is why this is JSON rather
 * than a table of nodes and edges. There is no graph to store; there is a
 * pipeline with knobs.
 */
class OutreachTask extends BaseModel
{
    protected $table = 'outreach_tasks';

    protected $fillable = [
        'usersId',
        'listId',
        'name',
        'description',
        'status',
        'flowConfig',
        'totalLeads',
        'sentCount',
        'openedCount',
        'repliedCount',
        'interestedCount',
        'notInterestedCount',
        'noReplyCount',
        'bouncedCount',
        'spamCount',
        'startedAt',
        'completedAt',
        'lastProcessedAt',
        'countsRefreshedAt',
        'delete_status',
    ];

    protected $casts = [
        'usersId' => 'integer',
        'listId' => 'integer',
        'flowConfig' => 'array',
        'totalLeads' => 'integer',
        'sentCount' => 'integer',
        'openedCount' => 'integer',
        'repliedCount' => 'integer',
        'interestedCount' => 'integer',
        'notInterestedCount' => 'integer',
        'noReplyCount' => 'integer',
        'bouncedCount' => 'integer',
        'spamCount' => 'integer',
        'startedAt' => 'datetime:Y-m-d H:i:s',
        'completedAt' => 'datetime:Y-m-d H:i:s',
        'lastProcessedAt' => 'datetime:Y-m-d H:i:s',
        'countsRefreshedAt' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_RUNNING = 'running';
    const STATUS_PAUSED = 'paused';
    const STATUS_COMPLETE = 'complete';

    const STATUSES = [self::STATUS_DRAFT, self::STATUS_RUNNING, self::STATUS_PAUSED, self::STATUS_COMPLETE];

    // The four fixed flow branches. These strings key into flowConfig.
    const NODE_START = 'start';
    const NODE_REPLIED = 'replied';
    const NODE_INTERESTED = 'interested';
    const NODE_NO_REPLY = 'no_reply';

    /**
     * @return array<string, string>
     */
    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_RUNNING => 'Running',
            self::STATUS_PAUSED => 'Paused',
            self::STATUS_COMPLETE => 'Complete',
        ];
    }

    /**
     * The shape of flowConfig when nothing has been configured yet.
     *
     * Every value here is a node setting, not a guard rail: the timing walls
     * (minimum gap, daily ceiling, when silence counts as no reply) live in
     * outreach_settings, because they protect the sending domain across every
     * campaign and must not be raisable per task.
     *
     * @return array<string, mixed>
     */
    public static function defaultFlowConfig(): array
    {
        return [
            self::NODE_START => [
                'enabled' => true,
                'templateId' => null,
                'aiTiming' => true,
                'aiRephrase' => true,
            ],
            self::NODE_REPLIED => [
                'enabled' => true,
                'templateId' => null,
                'aiTiming' => true,
                'aiRephrase' => true,
                // Off by default: answering a human automatically is the single
                // most damaging thing this flow can get wrong, so it is opt-in.
                'autoReply' => false,
            ],
            self::NODE_INTERESTED => [
                'enabled' => true,
                'autoTag' => true,
                // Once a contact is interested the automation stands down and a
                // person takes over in the inbox.
                'stopAutomation' => true,
            ],
            self::NODE_NO_REPLY => [
                'enabled' => true,
                // Null means "use the account default" (noReplyAfterDays).
                'afterDays' => null,
            ],
        ];
    }

    /**
     * flowConfig with defaults filled in for anything missing.
     *
     * Always read through this rather than the raw attribute: a task saved
     * before a node existed would otherwise hand back a config with a hole in it.
     *
     * @return array<string, mixed>
     */
    public function resolvedFlowConfig(): array
    {
        $defaults = self::defaultFlowConfig();
        $stored = is_array($this->flowConfig) ? $this->flowConfig : [];

        foreach ($defaults as $node => $settings) {
            $stored[$node] = array_merge($settings, is_array($stored[$node] ?? null) ? $stored[$node] : []);
        }

        return $stored;
    }

    /**
     * One node's settings.
     *
     * @return array<string, mixed>
     */
    public function nodeConfig(string $node): array
    {
        $config = $this->resolvedFlowConfig();

        return is_array($config[$node] ?? null) ? $config[$node] : [];
    }

    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    public function list()
    {
        return $this->belongsTo(OutreachList::class, 'listId');
    }

    public function taskLeads()
    {
        return $this->hasMany(OutreachTaskLead::class, 'taskId');
    }

    public function emailLogs()
    {
        return $this->hasMany(OutreachEmailLog::class, 'taskId');
    }

    /**
     * Badge for the campaign status, per CLAUDE.md section 12.2 contrast rules.
     */
    public function getStatusBadgeAttribute(): string
    {
        switch ($this->status) {
            case self::STATUS_RUNNING:
                return '<span class="badge bg-success">Running</span>';
            case self::STATUS_PAUSED:
                return '<span class="badge bg-warning text-dark">Paused</span>';
            case self::STATUS_COMPLETE:
                return '<span class="badge bg-info text-white">Complete</span>';
            default:
                return '<span class="badge bg-light text-dark">Draft</span>';
        }
    }

    /**
     * How far through the pool this campaign is, 0-100.
     *
     * Counts every contact the flow has finished with - not just the ones that
     * replied - because a bounce or a fortnight of silence is a resolved
     * outcome, and a bar that only moved on success would sit near zero forever.
     */
    public function getProgressPercentAttribute(): int
    {
        $total = (int) $this->totalLeads;

        if ($total <= 0) {
            return $this->status === self::STATUS_COMPLETE ? 100 : 0;
        }

        $settled = (int) $this->repliedCount
            + (int) $this->interestedCount
            + (int) $this->notInterestedCount
            + (int) $this->noReplyCount
            + (int) $this->bouncedCount
            + (int) $this->spamCount;

        return (int) round(min(1, $settled / $total) * 100);
    }

    /**
     * Open rate as a percentage of messages actually sent.
     *
     * Inflated by image pre-fetching (Apple Mail Privacy Protection in
     * particular). Useful for comparing templates against each other, not as a
     * count of human beings - the reports screen says so beside the number.
     */
    public function getOpenRateAttribute(): float
    {
        $sent = (int) $this->sentCount;

        return $sent > 0 ? round(((int) $this->openedCount / $sent) * 100, 1) : 0.0;
    }

    /**
     * Reply rate against messages sent.
     */
    public function getReplyRateAttribute(): float
    {
        $sent = (int) $this->sentCount;

        return $sent > 0 ? round(((int) $this->repliedCount / $sent) * 100, 1) : 0.0;
    }
}
