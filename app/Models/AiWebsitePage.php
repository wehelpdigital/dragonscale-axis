<?php

namespace App\Models;

class AiWebsitePage extends BaseModel
{
    protected $table = 'ai_website_pages';

    protected $fillable = [
        'websiteId',
        'usersId',
        'url',
        'urlHash',
        'title',
        'metaDescription',
        'metaKeywords',
        'rawHtml',
        'cleanContent',
        'structuredData',
        'headings',
        'links',
        'images',
        'wordCount',
        'contentLength',
        'contentHash',
        'language',
        'httpStatus',
        'contentType',
        'responseTime',
        'pageSize',
        'depth',
        'parentUrl',
        'isIndexable',
        'hasChanges',
        'firstScrapedAt',
        'lastScrapedAt',
        'contentChangedAt',
        'scrapeStatus',
        'scrapeError',
        'pineconeFileId',
        'pineconeStatus',
        'pineconeError',
        'pineconeIndexedAt',
        'delete_status',
    ];

    /**
     * Boot method to auto-generate URL hash.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->urlHash) && !empty($model->url)) {
                $model->urlHash = hash('sha256', $model->url);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('url')) {
                $model->urlHash = hash('sha256', $model->url);
            }
        });
    }

    protected $casts = [
        'structuredData' => 'array',
        'headings' => 'array',
        'links' => 'array',
        'images' => 'array',
        'wordCount' => 'integer',
        'contentLength' => 'integer',
        'httpStatus' => 'integer',
        'responseTime' => 'integer',
        'pageSize' => 'integer',
        'depth' => 'integer',
        'isIndexable' => 'boolean',
        'hasChanges' => 'boolean',
        'firstScrapedAt' => 'datetime',
        'lastScrapedAt' => 'datetime',
        'contentChangedAt' => 'datetime',
        'pineconeIndexedAt' => 'datetime',
    ];

    // Pinecone status constants
    const PINECONE_PENDING = 'pending';
    const PINECONE_PROCESSING = 'processing';
    const PINECONE_INDEXED = 'indexed';
    const PINECONE_FAILED = 'failed';

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_SKIPPED = 'skipped';

    /**
     * Scope: Active records only.
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
     * Scope: Filter by website.
     */
    public function scopeForWebsite($query, $websiteId)
    {
        return $query->where('websiteId', $websiteId);
    }

    /**
     * Scope: Successfully scraped pages.
     */
    public function scopeCompleted($query)
    {
        return $query->where('scrapeStatus', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Pages with content changes.
     */
    public function scopeWithChanges($query)
    {
        return $query->where('hasChanges', true);
    }

    /**
     * Get the parent website.
     */
    public function website()
    {
        return $this->belongsTo(AiWebsite::class, 'websiteId');
    }

    /**
     * Get status badge HTML.
     */
    public function getStatusBadgeAttribute(): string
    {
        switch ($this->scrapeStatus) {
            case self::STATUS_COMPLETED:
                return '<span class="badge bg-success">Completed</span>';
            case self::STATUS_IN_PROGRESS:
                return '<span class="badge bg-info">In Progress</span>';
            case self::STATUS_FAILED:
                return '<span class="badge bg-danger">Failed</span>';
            case self::STATUS_SKIPPED:
                return '<span class="badge bg-secondary">Skipped</span>';
            default:
                return '<span class="badge bg-warning text-dark">Pending</span>';
        }
    }

    /**
     * Get truncated content for preview.
     */
    public function getContentPreviewAttribute(): string
    {
        if (empty($this->cleanContent)) {
            return 'No content extracted';
        }

        return \Str::limit($this->cleanContent, 200);
    }

    /**
     * Get formatted page size.
     */
    public function getFormattedPageSizeAttribute(): string
    {
        if (!$this->pageSize) {
            return 'N/A';
        }

        $bytes = $this->pageSize;
        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
    }

    /**
     * Generate content hash for change detection.
     */
    public static function generateContentHash(?string $content): ?string
    {
        if (empty($content)) {
            return null;
        }

        // Normalize whitespace before hashing
        $normalized = preg_replace('/\s+/', ' ', trim($content));
        return hash('sha256', $normalized);
    }

    /**
     * Check if content has changed from previous scrape.
     */
    public function detectChanges(string $newContent): bool
    {
        $newHash = self::generateContentHash($newContent);
        return $this->contentHash !== $newHash;
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
                return '<span class="badge bg-secondary">Not Indexed</span>';
        }
    }

    /**
     * Check if page needs to be uploaded to Pinecone.
     */
    public function needsPineconeUpload(): bool
    {
        // Needs upload if:
        // 1. Never uploaded (no pineconeFileId)
        // 2. Content has changed since last index
        // 3. Previous upload failed
        if (!$this->pineconeFileId) {
            return true;
        }

        if ($this->pineconeStatus === self::PINECONE_FAILED) {
            return true;
        }

        if ($this->hasChanges) {
            return true;
        }

        return false;
    }

    /**
     * Check if page is indexed in Pinecone.
     */
    public function isIndexedInPinecone(): bool
    {
        return $this->pineconeStatus === self::PINECONE_INDEXED && !empty($this->pineconeFileId);
    }

    /**
     * Scope: Pages indexed in Pinecone.
     */
    public function scopeIndexedInPinecone($query)
    {
        return $query->where('pineconeStatus', self::PINECONE_INDEXED);
    }

    /**
     * Scope: Pages needing Pinecone upload.
     */
    public function scopeNeedsPineconeUpload($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('pineconeFileId')
              ->orWhere('pineconeStatus', self::PINECONE_FAILED)
              ->orWhere('hasChanges', true);
        });
    }
}
