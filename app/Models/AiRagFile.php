<?php

namespace App\Models;

class AiRagFile extends BaseModel
{
    protected $table = 'ai_rag_files';

    protected $fillable = [
        'usersId',
        'fileName',
        'originalName',
        'fileSize',
        'fileType',
        'fileHash',
        'filePath',
        'status',
        'pineconeFileId',
        'pineconeNamespace',
        'vectorCount',
        'chunkCount',
        'errorMessage',
        'indexedAt',
        'delete_status',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'indexedAt' => 'datetime:Y-m-d H:i:s',
        'fileSize' => 'integer',
        'vectorCount' => 'integer',
        'chunkCount' => 'integer',
    ];

    /**
     * Status constants.
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_INDEXED = 'indexed';
    const STATUS_FAILED = 'failed';

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
     * Get the user that owns the file.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    /**
     * Get human-readable file size.
     */
    public function getFormattedFileSizeAttribute()
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
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'bg-warning text-dark',
            self::STATUS_PROCESSING => 'bg-info text-white',
            self::STATUS_INDEXED => 'bg-success',
            self::STATUS_FAILED => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    /**
     * Get status display text.
     */
    public function getStatusDisplayAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_INDEXED => 'Indexed',
            self::STATUS_FAILED => 'Failed',
            default => ucfirst($this->status),
        };
    }

    /**
     * Check if file can be retried.
     */
    public function canRetry()
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if file is being processed.
     */
    public function isProcessing()
    {
        return $this->status === self::STATUS_PROCESSING;
    }
}
