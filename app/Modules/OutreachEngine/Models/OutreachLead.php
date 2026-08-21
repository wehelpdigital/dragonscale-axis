<?php

namespace App\Modules\OutreachEngine\Models;

use App\Models\BaseModel;
use App\Models\User;

/**
 * A business discovered by the grid scraper - the module's central record.
 *
 * placeId is UNIQUE ACROSS THE WHOLE TABLE, not per user: overlapping circles and
 * child cells re-cover their parent's area, so the same business comes back many
 * times. Any existence pre-check must therefore be written WITHOUT forUser(), e.g.
 * OutreachLead::where('placeId', $id)->exists(), or a cross-user collision slips
 * past the check and blows up on the unique index.
 *
 * A lead carries two independent state machines: enrichmentStatus answers "do we
 * have an email yet?" and outreachStatus answers "where is it in the campaign?".
 * They move separately - a lead can be enriched but never contacted, or contacted
 * and later bounced.
 */
class OutreachLead extends BaseModel
{
    protected $table = 'outreach_leads';

    protected $fillable = [
        'usersId',
        'batchId',
        'gridId',
        'placeId',
        'businessName',
        'category',
        'aiCategory',
        'categoryStatus',
        'categoryAttempts',
        'categorizedAt',
        'address',
        'city',
        'province',
        'latitude',
        'longitude',
        'phone',
        'website',
        'facebookUrl',
        'email',
        'emailSource',
        'rating',
        'userRatingsTotal',
        'enrichmentStatus',
        'enrichmentAttempts',
        'enrichmentError',
        'enrichedAt',
        'outreachStatus',
        'lastContactedAt',
        'repliedAt',
        'contactAttempts',
        'notes',
        'delete_status',
    ];

    /**
     * NOTE: BaseModel declares the created_at/updated_at casts; redeclaring $casts here
     * replaces that array, so both timestamps are repeated to keep this additive.
     *
     * The decimal casts mirror the column precision exactly (lat/lng 10,7 - rating 3,2)
     * so a value read back from MySQL and one just assigned compare the same way.
     *
     * @var array
     */
    protected $casts = [
        'usersId' => 'integer',
        'gridId' => 'integer',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'rating' => 'decimal:2',
        'userRatingsTotal' => 'integer',
        'enrichmentAttempts' => 'integer',
        'enrichedAt' => 'datetime:Y-m-d H:i:s',
        'categoryAttempts' => 'integer',
        'categorizedAt' => 'datetime:Y-m-d H:i:s',
        'lastContactedAt' => 'datetime:Y-m-d H:i:s',
        'repliedAt' => 'datetime:Y-m-d H:i:s',
        'contactAttempts' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    // Campaign state constants.
    const OUTREACH_UNCONTACTED = 'uncontacted';
    const OUTREACH_QUEUED = 'queued';
    const OUTREACH_CONTACTED = 'contacted';
    const OUTREACH_REPLIED = 'replied';
    const OUTREACH_UNSUBSCRIBED = 'unsubscribed';
    const OUTREACH_BOUNCED = 'bounced';
    const OUTREACH_FAILED = 'failed';

    // Email-discovery state constants.
    // Categorisation queue states. Mirrors the enrichment ladder deliberately:
    // 'skipped' means "no AI configured yet, retry once there is", while
    // 'failed' means the model was asked and could not answer.
    const CATEGORY_PENDING = 'pending';
    const CATEGORY_PROCESSING = 'processing';
    const CATEGORY_CATEGORIZED = 'categorized';
    const CATEGORY_FAILED = 'failed';
    const CATEGORY_SKIPPED = 'skipped';

    const MAX_CATEGORY_ATTEMPTS = 3;

    /** Every valid categoryStatus, for validating a filter value off the wire. */
    const CATEGORY_STATUSES = [
        self::CATEGORY_PENDING,
        self::CATEGORY_PROCESSING,
        self::CATEGORY_CATEGORIZED,
        self::CATEGORY_FAILED,
        self::CATEGORY_SKIPPED,
    ];

    const ENRICHMENT_PENDING = 'pending';
    const ENRICHMENT_PROCESSING = 'processing';
    const ENRICHMENT_ENRICHED = 'enriched';
    const ENRICHMENT_FAILED = 'failed';
    const ENRICHMENT_SKIPPED = 'skipped';

    // Known emailSource values (the column itself is free text).
    const SOURCE_PLACES = 'places';
    const SOURCE_WEBSITE = 'website';
    const SOURCE_FACEBOOK = 'facebook';
    const SOURCE_LLM = 'llm';
    const SOURCE_MANUAL = 'manual';

    /** Give up on email discovery after this many tries so the cron stops burning quota. */
    const MAX_ENRICHMENT_ATTEMPTS = 3;

    /**
     * Statuses that make a lead unreachable for good - a reply, an unsubscribe or a
     * bounce all mean "never send to this address again".
     */
    const CLOSED_OUTREACH_STATUSES = [
        self::OUTREACH_REPLIED,
        self::OUTREACH_UNSUBSCRIBED,
        self::OUTREACH_BOUNCED,
    ];

    /**
     * Campaign status => label, for filter dropdowns.
     */
    public static function getOutreachStatusLabels(): array
    {
        return [
            self::OUTREACH_UNCONTACTED => 'Uncontacted',
            self::OUTREACH_QUEUED => 'Queued',
            self::OUTREACH_CONTACTED => 'Contacted',
            self::OUTREACH_REPLIED => 'Replied',
            self::OUTREACH_UNSUBSCRIBED => 'Unsubscribed',
            self::OUTREACH_BOUNCED => 'Bounced',
            self::OUTREACH_FAILED => 'Failed',
        ];
    }

    /**
     * Enrichment status => label, for filter dropdowns.
     */
    public static function getEnrichmentStatusLabels(): array
    {
        return [
            self::ENRICHMENT_PENDING => 'Pending',
            self::ENRICHMENT_PROCESSING => 'Processing',
            self::ENRICHMENT_ENRICHED => 'Enriched',
            self::ENRICHMENT_FAILED => 'Failed',
            self::ENRICHMENT_SKIPPED => 'Skipped',
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
     * Scope: Leads that actually have somewhere to send to.
     */
    public function scopeHasEmail($query)
    {
        return $query->whereNotNull('email')->where('email', '!=', '');
    }

    /**
     * Scope: Leads still waiting on email discovery and worth another try.
     */
    public function scopeNeedsEnrichment($query)
    {
        return $query->where('enrichmentStatus', self::ENRICHMENT_PENDING)
            ->whereNull('email')
            ->where('enrichmentAttempts', '<', self::MAX_ENRICHMENT_ATTEMPTS);
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the admin user this lead belongs to.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    /**
     * Get every outbound email attempt aimed at this lead.
     */
    public function emailLogs()
    {
        return $this->hasMany(OutreachEmailLog::class, 'leadId');
    }

    /**
     * Get the conversation - both inbound replies and our own quick replies.
     */
    public function inboundMessages()
    {
        return $this->hasMany(OutreachInboundMessage::class, 'leadId');
    }

    /**
     * Get the search cell this lead was found in.
     */
    public function grid()
    {
        return $this->belongsTo(OutreachSearchGrid::class, 'gridId');
    }

    // ==================== HELPERS ====================

    /**
     * "City, Province" with the blanks dropped - Places often supplies one and not
     * the other. Returns an empty string when neither is known, so a CSV export gets
     * a blank cell instead of a placeholder that reads like real data.
     */
    public function getDisplayLocationAttribute(): string
    {
        $parts = [];

        foreach ([$this->city, $this->province] as $piece) {
            $piece = trim((string) $piece);
            if ($piece !== '') {
                $parts[] = $piece;
            }
        }

        return implode(', ', $parts);
    }

    /**
     * Campaign status badge for the leads table.
     * Colours follow the contrast rules in CLAUDE.md section 12.2.
     */
    public function getOutreachStatusBadgeAttribute(): string
    {
        switch ($this->outreachStatus) {
            case self::OUTREACH_QUEUED:
                return '<span class="badge bg-info text-white">Queued</span>';
            case self::OUTREACH_CONTACTED:
                return '<span class="badge bg-primary">Contacted</span>';
            case self::OUTREACH_REPLIED:
                return '<span class="badge bg-success">Replied</span>';
            case self::OUTREACH_UNSUBSCRIBED:
                return '<span class="badge bg-dark text-white">Unsubscribed</span>';
            case self::OUTREACH_BOUNCED:
                return '<span class="badge bg-warning text-dark">Bounced</span>';
            case self::OUTREACH_FAILED:
                return '<span class="badge bg-danger">Failed</span>';
            case self::OUTREACH_UNCONTACTED:
            default:
                return '<span class="badge bg-secondary">Uncontacted</span>';
        }
    }

    /**
     * Email-discovery badge for the leads table.
     */
    public function getEnrichmentStatusBadgeAttribute(): string
    {
        switch ($this->enrichmentStatus) {
            case self::ENRICHMENT_PROCESSING:
                return '<span class="badge bg-info text-white">Processing</span>';
            case self::ENRICHMENT_ENRICHED:
                return '<span class="badge bg-success">Enriched</span>';
            case self::ENRICHMENT_FAILED:
                return '<span class="badge bg-danger">Failed</span>';
            case self::ENRICHMENT_SKIPPED:
                return '<span class="badge bg-light text-dark">Skipped</span>';
            case self::ENRICHMENT_PENDING:
            default:
                return '<span class="badge bg-secondary">Pending</span>';
        }
    }

    /**
     * Rating rendered for a table cell, e.g. "4.60 (128)". Blank when unrated.
     */
    public function getRatingLabelAttribute(): string
    {
        if ($this->rating === null) {
            return '';
        }

        $label = number_format((float) $this->rating, 2);

        if (!empty($this->userRatingsTotal)) {
            $label .= ' (' . number_format((int) $this->userRatingsTotal) . ')';
        }

        return $label;
    }

    /**
     * Does this lead hold a syntactically valid address we could mail?
     */
    public function hasValidEmail(): bool
    {
        return !empty($this->email) && filter_var($this->email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Has this lead left the funnel for good (replied, unsubscribed or bounced)?
     */
    public function isClosed(): bool
    {
        return in_array($this->outreachStatus, self::CLOSED_OUTREACH_STATUSES, true);
    }

    /**
     * What to show in the category column: the model's answer when it has one,
     * otherwise Google's raw type so the cell is never simply empty.
     */
    public function getDisplayCategoryAttribute(): string
    {
        $ai = trim((string) $this->aiCategory);

        if ($ai !== '') {
            return $ai;
        }

        $raw = trim((string) $this->category);

        // Google types arrive snake_cased ("meal_takeaway"); make them readable.
        return $raw === '' ? '' : ucwords(str_replace('_', ' ', $raw));
    }

    /**
     * Badge for the categorisation state, following the contrast rules in
     * CLAUDE.md section 12.2.
     */
    public function getCategoryStatusBadgeAttribute(): string
    {
        switch ($this->categoryStatus) {
            case self::CATEGORY_CATEGORIZED:
                return '<span class="badge bg-success">Categorised</span>';
            case self::CATEGORY_PROCESSING:
                return '<span class="badge bg-info text-white">Working</span>';
            case self::CATEGORY_FAILED:
                return '<span class="badge bg-danger">Failed</span>';
            case self::CATEGORY_SKIPPED:
                return '<span class="badge bg-light text-dark">Skipped</span>';
            default:
                return '<span class="badge bg-warning text-dark">Pending</span>';
        }
    }
}
