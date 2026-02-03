<?php

namespace App\Models;

class AiKbImage extends BaseModel
{
    protected $table = 'ai_kb_images';

    protected $fillable = [
        'usersId',
        'fileName',
        'originalName',
        'filePath',
        'description',
        'aiAnalysis',
        'aiProvider',
        'aiModel',
        'fileSize',
        'mimeType',
        'fileHash',
        'pineconeFileId',
        'pineconeStatus',
        'errorMessage',
        'indexedAt',
        'delete_status',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'indexedAt' => 'datetime:Y-m-d H:i:s',
        'fileSize' => 'integer',
    ];

    /**
     * Pinecone status constants.
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_INDEXED = 'indexed';
    const STATUS_FAILED = 'failed';

    /**
     * Allowed MIME types for image uploads.
     */
    const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /**
     * Allowed file extensions.
     */
    const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * Maximum file size in bytes (10MB).
     */
    const MAX_FILE_SIZE = 10 * 1024 * 1024;

    // ==================== SCOPES ====================

    /**
     * Scope for active records.
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Scope for user's records.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Scope for indexed images only.
     */
    public function scopeIndexed($query)
    {
        return $query->where('pineconeStatus', self::STATUS_INDEXED);
    }

    /**
     * Scope for pending images.
     */
    public function scopePending($query)
    {
        return $query->where('pineconeStatus', self::STATUS_PENDING);
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user that owns the image.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    // ==================== COMPUTED ATTRIBUTES ====================

    /**
     * Get human-readable file size.
     */
    public function getFileSizeHumanAttribute()
    {
        $bytes = $this->fileSize;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Get Pinecone status badge HTML class.
     */
    public function getPineconeStatusBadgeAttribute()
    {
        return match($this->pineconeStatus) {
            self::STATUS_PENDING => 'bg-warning text-dark',
            self::STATUS_PROCESSING => 'bg-info text-white',
            self::STATUS_INDEXED => 'bg-success',
            self::STATUS_FAILED => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    /**
     * Get Pinecone status display text.
     */
    public function getPineconeStatusDisplayAttribute()
    {
        return match($this->pineconeStatus) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_INDEXED => 'Indexed',
            self::STATUS_FAILED => 'Failed',
            default => ucfirst($this->pineconeStatus),
        };
    }

    /**
     * Get the full URL for the image thumbnail.
     */
    public function getThumbnailUrlAttribute()
    {
        if ($this->filePath) {
            return asset('storage/' . $this->filePath);
        }
        return null;
    }

    /**
     * Get truncated description for display.
     */
    public function getDescriptionShortAttribute()
    {
        if (strlen($this->description) > 100) {
            return substr($this->description, 0, 100) . '...';
        }
        return $this->description;
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if image can be retried for Pinecone upload.
     */
    public function canRetry(): bool
    {
        return $this->pineconeStatus === self::STATUS_FAILED;
    }

    /**
     * Check if image is currently being processed.
     */
    public function isProcessing(): bool
    {
        return $this->pineconeStatus === self::STATUS_PROCESSING;
    }

    /**
     * Check if image is indexed in Pinecone.
     */
    public function isIndexed(): bool
    {
        return $this->pineconeStatus === self::STATUS_INDEXED;
    }

    /**
     * Check if image is pending upload.
     */
    public function isPending(): bool
    {
        return $this->pineconeStatus === self::STATUS_PENDING;
    }
}
