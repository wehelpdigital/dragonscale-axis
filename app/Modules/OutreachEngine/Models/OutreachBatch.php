<?php

namespace App\Modules\OutreachEngine\Models;

use App\Models\BaseModel;

/**
 * A named sweep: one business type across one province, and how far it has got.
 *
 * The counters are a cache of what the grid and lead tables already know, kept
 * here so the batch list can be drawn in one query instead of half a dozen
 * aggregates per row. BatchProgressService owns them; nothing else should write
 * them, or the two will disagree and the screen will start lying about progress.
 */
class OutreachBatch extends BaseModel
{
    protected $table = 'outreach_batches';

    protected $fillable = [
        'usersId',
        'batchId',
        'name',
        'businessType',
        'regionLabel',
        'radiusKm',
        'status',
        'totalCells',
        'pendingCells',
        'totalLeads',
        'leadsWithEmail',
        'leadsVerified',
        'leadsValid',
        'startedAt',
        'completedAt',
        'countsRefreshedAt',
        'delete_status',
    ];

    protected $casts = [
        'usersId' => 'integer',
        'radiusKm' => 'decimal:3',
        'totalCells' => 'integer',
        'pendingCells' => 'integer',
        'totalLeads' => 'integer',
        'leadsWithEmail' => 'integer',
        'leadsVerified' => 'integer',
        'leadsValid' => 'integer',
        'startedAt' => 'datetime:Y-m-d H:i:s',
        'completedAt' => 'datetime:Y-m-d H:i:s',
        'countsRefreshedAt' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    // Pipeline stages, in the order a sweep passes through them.
    const STATUS_SCRAPING = 'scraping';
    const STATUS_ENRICHING = 'enriching';
    const STATUS_VERIFYING = 'verifying';
    const STATUS_COMPLETE = 'complete';
    const STATUS_CANCELLED = 'cancelled';

    const STATUSES = [
        self::STATUS_SCRAPING,
        self::STATUS_ENRICHING,
        self::STATUS_VERIFYING,
        self::STATUS_COMPLETE,
        self::STATUS_CANCELLED,
    ];

    /**
     * Human labels for the pipeline stages.
     *
     * @return array<string, string>
     */
    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_SCRAPING => 'Scraping',
            self::STATUS_ENRICHING => 'Finding emails',
            self::STATUS_VERIFYING => 'Verifying emails',
            self::STATUS_COMPLETE => 'Complete',
            self::STATUS_CANCELLED => 'Cancelled',
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
     * Scope: sweeps that have run the whole pipeline.
     */
    public function scopeComplete($query)
    {
        return $query->where('status', self::STATUS_COMPLETE);
    }

    /**
     * The leads this sweep found.
     */
    public function leads()
    {
        return $this->hasMany(OutreachLead::class, 'batchId', 'batchId');
    }

    /**
     * The grid cells this sweep laid down.
     */
    public function grids()
    {
        return $this->hasMany(OutreachSearchGrid::class, 'batchId', 'batchId');
    }

    /**
     * What to call this sweep on screen.
     *
     * A renamed batch keeps its own title; everything else falls back to what
     * was actually searched, which is more use than a uuid.
     */
    public function getDisplayNameAttribute(): string
    {
        $name = trim((string) $this->name);

        if ($name !== '') {
            return $name;
        }

        $type = trim((string) $this->businessType);
        $region = trim((string) $this->regionLabel);

        if ($type === '' && $region === '') {
            return 'Untitled search';
        }

        return trim($type . ($region !== '' ? ' in ' . $region : ''));
    }

    /**
     * Badge for the pipeline stage, following CLAUDE.md section 12.2 contrast rules.
     */
    public function getStatusBadgeAttribute(): string
    {
        switch ($this->status) {
            case self::STATUS_COMPLETE:
                return '<span class="badge bg-success">Complete</span>';
            case self::STATUS_VERIFYING:
                return '<span class="badge bg-info text-white">Verifying</span>';
            case self::STATUS_ENRICHING:
                return '<span class="badge bg-primary">Finding emails</span>';
            case self::STATUS_CANCELLED:
                return '<span class="badge bg-light text-dark">Cancelled</span>';
            default:
                return '<span class="badge bg-warning text-dark">Scraping</span>';
        }
    }

    /**
     * How far through the whole pipeline this sweep is, 0-100.
     *
     * Weighted by stage rather than by row count: scraping is half the journey
     * because it is where the wall-clock goes, and the two follow-up passes
     * split the rest. A sweep that found no leads at all reads 100 once its
     * cells are done - there is genuinely nothing left for it to do.
     */
    public function getProgressPercentAttribute(): int
    {
        if ($this->status === self::STATUS_COMPLETE) {
            return 100;
        }

        if ($this->status === self::STATUS_CANCELLED) {
            return 0;
        }

        $cells = max(1, (int) $this->totalCells);
        $scraped = ($cells - (int) $this->pendingCells) / $cells;

        $leads = (int) $this->totalLeads;

        if ($leads === 0) {
            return (int) round($scraped * 100);
        }

        $enriched = min(1, (int) $this->leadsWithEmail / $leads);
        $withEmail = max(1, (int) $this->leadsWithEmail);
        $verified = min(1, (int) $this->leadsVerified / $withEmail);

        return (int) round(($scraped * 0.5 + $enriched * 0.3 + $verified * 0.2) * 100);
    }
}
