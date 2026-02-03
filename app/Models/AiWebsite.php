<?php

namespace App\Models;

class AiWebsite extends BaseModel
{
    protected $table = 'ai_websites';

    protected $fillable = [
        'usersId',
        'websiteName',
        'websiteUrl',
        'description',
        'scrapeType',
        'cssSelector',
        'allowedPaths',
        'excludedPaths',
        'scrapeFrequency',
        'maxPages',
        'maxDepth',
        'pagesScraped',
        'crawlQueue',
        'lastScrapedAt',
        'lastRagSyncAt',
        'pineconeFileId',
        'pineconeStatus',
        'pineconeError',
        'lastScrapeStatus',
        'lastScrapeError',
        'scrapeCount',
        'priority',
        'isActive',
        'delete_status',
    ];

    protected $casts = [
        'allowedPaths' => 'array',
        'excludedPaths' => 'array',
        'crawlQueue' => 'array',
        'priority' => 'integer',
        'scrapeCount' => 'integer',
        'maxPages' => 'integer',
        'maxDepth' => 'integer',
        'pagesScraped' => 'integer',
        'isActive' => 'boolean',
        'lastScrapedAt' => 'datetime',
        'lastRagSyncAt' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scrape type constants.
     */
    const SCRAPE_FULL_PAGE = 'full_page';
    const SCRAPE_SPECIFIC_SELECTOR = 'specific_selector';
    const SCRAPE_SITEMAP = 'sitemap';
    const SCRAPE_API_ENDPOINT = 'api_endpoint';
    const SCRAPE_WHOLE_SITE = 'whole_site';

    /**
     * Scrape frequency constants.
     */
    const FREQ_MANUAL = 'manual';
    const FREQ_HOURLY = 'hourly';
    const FREQ_DAILY = 'daily';
    const FREQ_WEEKLY = 'weekly';
    const FREQ_MONTHLY = 'monthly';

    /**
     * Scrape status constants.
     */
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_IN_PROGRESS = 'in_progress';

    /**
     * Pinecone status constants.
     */
    const PINECONE_PENDING = 'pending';
    const PINECONE_PROCESSING = 'processing';
    const PINECONE_INDEXED = 'indexed';
    const PINECONE_FAILED = 'failed';

    /**
     * Get scrape type labels for display.
     */
    public static function getScrapeTypeLabels(): array
    {
        return [
            self::SCRAPE_FULL_PAGE => 'Single Page',
            self::SCRAPE_SPECIFIC_SELECTOR => 'CSS Selector',
            self::SCRAPE_SITEMAP => 'Sitemap Crawl',
            self::SCRAPE_API_ENDPOINT => 'API Endpoint',
            self::SCRAPE_WHOLE_SITE => 'Whole Site Crawl',
        ];
    }

    /**
     * Get scrape type descriptions.
     */
    public static function getScrapeTypeDescriptions(): array
    {
        return [
            self::SCRAPE_FULL_PAGE => 'Extracts all text content from a single webpage.',
            self::SCRAPE_SPECIFIC_SELECTOR => 'Extracts content only from elements matching a CSS selector.',
            self::SCRAPE_SITEMAP => 'Discovers and scrapes pages listed in the site\'s sitemap.xml.',
            self::SCRAPE_API_ENDPOINT => 'Fetches JSON data from an API endpoint.',
            self::SCRAPE_WHOLE_SITE => 'Crawls the entire website by following internal links recursively.',
        ];
    }

    /**
     * Get frequency labels for display.
     */
    public static function getFrequencyLabels(): array
    {
        return [
            self::FREQ_MANUAL => 'Manual Only',
            self::FREQ_HOURLY => 'Every Hour',
            self::FREQ_DAILY => 'Daily',
            self::FREQ_WEEKLY => 'Weekly',
            self::FREQ_MONTHLY => 'Monthly',
        ];
    }

    /**
     * Scope: Active websites only.
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Scope: Filter by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Scope: Enabled websites only.
     */
    public function scopeEnabled($query)
    {
        return $query->where('isActive', true);
    }

    /**
     * Scope: Order by priority (highest first).
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Get the scrape type label.
     */
    public function getScrapeTypeLabelAttribute(): string
    {
        return self::getScrapeTypeLabels()[$this->scrapeType] ?? $this->scrapeType;
    }

    /**
     * Get the frequency label.
     */
    public function getFrequencyLabelAttribute(): string
    {
        return self::getFrequencyLabels()[$this->scrapeFrequency] ?? $this->scrapeFrequency;
    }

    /**
     * Get the domain from the URL.
     */
    public function getDomainAttribute(): string
    {
        $parsed = parse_url($this->websiteUrl);
        return $parsed['host'] ?? $this->websiteUrl;
    }

    /**
     * Get status badge HTML.
     */
    public function getStatusBadgeAttribute(): string
    {
        if ($this->isActive) {
            return '<span class="badge bg-success">Active</span>';
        }
        return '<span class="badge bg-secondary">Inactive</span>';
    }

    /**
     * Get scrape status badge HTML.
     */
    public function getScrapeStatusBadgeAttribute(): string
    {
        $badges = [
            self::STATUS_PENDING => '<span class="badge bg-warning text-dark">Pending</span>',
            self::STATUS_SUCCESS => '<span class="badge bg-success">Success</span>',
            self::STATUS_FAILED => '<span class="badge bg-danger">Failed</span>',
            self::STATUS_IN_PROGRESS => '<span class="badge bg-info text-dark">In Progress</span>',
        ];

        return $badges[$this->lastScrapeStatus] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    /**
     * Get scrape type badge HTML.
     */
    public function getScrapeTypeBadgeAttribute(): string
    {
        $colors = [
            self::SCRAPE_FULL_PAGE => 'primary',
            self::SCRAPE_SPECIFIC_SELECTOR => 'info',
            self::SCRAPE_SITEMAP => 'warning',
            self::SCRAPE_API_ENDPOINT => 'success',
            self::SCRAPE_WHOLE_SITE => 'danger',
        ];

        $color = $colors[$this->scrapeType] ?? 'secondary';
        $textClass = in_array($color, ['warning', 'info']) ? 'text-dark' : 'text-white';

        return '<span class="badge bg-' . $color . ' ' . $textClass . '">' . $this->scrape_type_label . '</span>';
    }

    /**
     * Get frequency badge HTML.
     */
    public function getFrequencyBadgeAttribute(): string
    {
        $colors = [
            self::FREQ_MANUAL => 'secondary',
            self::FREQ_HOURLY => 'danger',
            self::FREQ_DAILY => 'warning',
            self::FREQ_WEEKLY => 'info',
            self::FREQ_MONTHLY => 'primary',
        ];

        $color = $colors[$this->scrapeFrequency] ?? 'secondary';
        $textClass = in_array($color, ['warning', 'info']) ? 'text-dark' : 'text-white';

        return '<span class="badge bg-' . $color . ' ' . $textClass . '">' . $this->frequency_label . '</span>';
    }

    /**
     * Get last scraped time in human readable format.
     */
    public function getLastScrapedHumanAttribute(): string
    {
        if (!$this->lastScrapedAt) {
            return 'Never';
        }
        return $this->lastScrapedAt->diffForHumans();
    }

    /**
     * Get last RAG sync time in human readable format.
     */
    public function getLastRagSyncHumanAttribute(): string
    {
        if (!$this->lastRagSyncAt) {
            return 'Never';
        }
        return $this->lastRagSyncAt->diffForHumans();
    }

    /**
     * Get status display text (for unified KB view).
     */
    public function getStatusDisplayAttribute(): string
    {
        $statuses = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_SUCCESS => 'Scraped',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_IN_PROGRESS => 'Scraping...',
        ];

        return $statuses[$this->lastScrapeStatus] ?? 'Unknown';
    }

    /**
     * Get status badge class (for unified KB view).
     */
    public function getStatusBadgeClassAttribute(): string
    {
        $classes = [
            self::STATUS_PENDING => 'bg-warning text-dark',
            self::STATUS_SUCCESS => 'bg-success',
            self::STATUS_FAILED => 'bg-danger',
            self::STATUS_IN_PROGRESS => 'bg-info text-dark',
        ];

        return $classes[$this->lastScrapeStatus] ?? 'bg-secondary';
    }

    /**
     * Get pages count (for unified KB view).
     */
    public function getPagesCountAttribute(): int
    {
        return $this->pages()->active()->count();
    }

    /**
     * Get Pinecone status badge HTML.
     */
    public function getPineconeStatusBadgeAttribute(): string
    {
        switch ($this->pineconeStatus) {
            case self::PINECONE_INDEXED:
                return '<span class="badge bg-success">Indexed</span>';
            case self::PINECONE_PROCESSING:
                return '<span class="badge bg-info text-dark">Processing</span>';
            case self::PINECONE_FAILED:
                return '<span class="badge bg-danger">Failed</span>';
            case self::PINECONE_PENDING:
                return '<span class="badge bg-warning text-dark">Pending</span>';
            default:
                return '<span class="badge bg-secondary">Not Synced</span>';
        }
    }

    /**
     * Check if website is indexed in Pinecone.
     */
    public function isIndexedInPinecone(): bool
    {
        return $this->pineconeStatus === self::PINECONE_INDEXED && !empty($this->pineconeFileId);
    }

    /**
     * Check if website needs Pinecone upload.
     */
    public function needsPineconeUpload(): bool
    {
        // Needs upload if:
        // 1. Never uploaded (no pineconeFileId)
        // 2. Previous upload failed
        // 3. Has been scraped since last RAG sync
        if (!$this->pineconeFileId) {
            return true;
        }

        if ($this->pineconeStatus === self::PINECONE_FAILED) {
            return true;
        }

        // Check if scraped after last RAG sync
        if ($this->lastScrapedAt && $this->lastRagSyncAt && $this->lastScrapedAt > $this->lastRagSyncAt) {
            return true;
        }

        return false;
    }

    /**
     * Relationship: User who owns this website.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    /**
     * Relationship: Scraped pages for this website.
     */
    public function pages()
    {
        return $this->hasMany(AiWebsitePage::class, 'websiteId');
    }

    /**
     * Get active pages count.
     */
    public function getActivePagesCountAttribute(): int
    {
        return $this->pages()->active()->completed()->count();
    }

    /**
     * Get total content size from all pages.
     */
    public function getTotalContentSizeAttribute(): int
    {
        return $this->pages()->active()->completed()->sum('pageSize') ?? 0;
    }

    /**
     * Get formatted total content size.
     */
    public function getFormattedTotalSizeAttribute(): string
    {
        $bytes = $this->total_content_size;
        if ($bytes === 0) {
            return 'No data';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
    }

    /**
     * Check if this scrape type supports multiple pages.
     */
    public function supportsMultiplePages(): bool
    {
        return in_array($this->scrapeType, [
            self::SCRAPE_SITEMAP,
            self::SCRAPE_WHOLE_SITE,
        ]);
    }
}
