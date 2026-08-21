<?php

namespace App\Modules\OutreachEngine\Models;

use App\Models\BaseModel;
use App\Models\User;

/**
 * One circular search cell in a region sweep.
 *
 * A batch (batchId, a plain uuid) tiles a region's bounding box into overlapping
 * circles - one row each. Google Places caps a Nearby Search at 60 results, so a
 * saturated cell is marked 'split' and spawns four half-radius children through
 * parentId; that self-reference is what children()/parent() walk.
 *
 * There are no foreign keys on this table (repo convention) - the relations below
 * are Eloquent-only and a deleted parent simply leaves orphan children behind.
 */
class OutreachSearchGrid extends BaseModel
{
    protected $table = 'outreach_search_grids';

    protected $fillable = [
        'usersId',
        'batchId',
        'businessType',
        'regionLabel',
        'latitude',
        'longitude',
        'radiusKm',
        'depth',
        'parentId',
        'status',
        'resultsCount',
        'newLeadsCount',
        'pageToken',
        'attempts',
        'lastError',
        'processedAt',
        'delete_status',
    ];

    /**
     * NOTE: BaseModel declares the created_at/updated_at casts; redeclaring $casts here
     * replaces that array, so both timestamps are repeated to keep this additive.
     *
     * The decimal casts mirror the column precision exactly (lat/lng 10,7 - radius 8,3)
     * so a value read back from MySQL and one just assigned compare the same way.
     *
     * @var array
     */
    protected $casts = [
        'usersId' => 'integer',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'radiusKm' => 'decimal:3',
        'depth' => 'integer',
        'parentId' => 'integer',
        'resultsCount' => 'integer',
        'newLeadsCount' => 'integer',
        'attempts' => 'integer',
        'processedAt' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    // Grid lifecycle constants - services and commands must use these, not bare strings.
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_SPLIT = 'split';
    const STATUS_FAILED = 'failed';

    /** How many attempts a cell gets before the scraper stops picking it up. */
    const MAX_ATTEMPTS = 3;

    /**
     * Google Places returns at most 60 results across 3 pages. Hitting that ceiling
     * means the cell is hiding businesses, which is the trigger to subdivide.
     */
    const SATURATION_THRESHOLD = 60;

    /**
     * Status => label, for filter dropdowns and progress readouts.
     */
    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_SPLIT => 'Split',
            self::STATUS_FAILED => 'Failed',
        ];
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Active records only (not deleted).
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Scope: Filter by owning user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Scope: One scrape run.
     */
    public function scopeForBatch($query, $batchId)
    {
        return $query->where('batchId', $batchId);
    }

    /**
     * Scope: Cells the scraper may still claim.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the admin user this grid belongs to.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    /**
     * Get the four half-radius cells this one split into.
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parentId');
    }

    /**
     * Get the saturated cell this one was subdivided from.
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parentId');
    }

    /**
     * Get the leads discovered inside this cell.
     */
    public function leads()
    {
        return $this->hasMany(OutreachLead::class, 'gridId');
    }

    // ==================== HELPERS ====================

    /**
     * Status badge for the scraper progress table.
     * Colours follow the contrast rules in CLAUDE.md section 12.2.
     */
    public function getStatusBadgeAttribute(): string
    {
        switch ($this->status) {
            case self::STATUS_COMPLETED:
                return '<span class="badge bg-success">Completed</span>';
            case self::STATUS_PROCESSING:
                return '<span class="badge bg-info text-white">Processing</span>';
            case self::STATUS_SPLIT:
                return '<span class="badge bg-primary">Split</span>';
            case self::STATUS_FAILED:
                return '<span class="badge bg-danger">Failed</span>';
            case self::STATUS_PENDING:
            default:
                return '<span class="badge bg-secondary">Pending</span>';
        }
    }

    /**
     * Human centre point, e.g. "16.6159, 120.3209".
     */
    public function getCoordinatesLabelAttribute(): string
    {
        if ($this->latitude === null || $this->longitude === null) {
            return '';
        }

        return round((float) $this->latitude, 5) . ', ' . round((float) $this->longitude, 5);
    }

    /**
     * Is this cell finished as far as the scraper is concerned?
     * 'split' counts as finished - its work moved to the children.
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_SPLIT, self::STATUS_FAILED], true);
    }

    /**
     * Radius the children of this cell would get.
     */
    public function childRadiusKm(): float
    {
        return round(((float) $this->radiusKm) / 2, 3);
    }
}
