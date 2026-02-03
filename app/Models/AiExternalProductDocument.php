<?php

namespace App\Models;

class AiExternalProductDocument extends BaseModel
{
    protected $table = 'ai_external_product_documents';

    protected $fillable = [
        'productId',
        'usersId',
        'documentPath',
        'documentUrl',
        'originalName',
        'fileSize',
        'mimeType',
        'fileExtension',
        'extractedText',
        'metadata',
        'status',
        'errorMessage',
        'sortOrder',
        'delete_status',
    ];

    protected $casts = [
        'metadata' => 'array',
        'fileSize' => 'integer',
        'sortOrder' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_EXTRACTED = 'extracted';
    const STATUS_FAILED = 'failed';

    // Supported document types
    const SUPPORTED_EXTENSIONS = ['pdf', 'txt', 'doc', 'docx'];
    const SUPPORTED_MIMES = [
        'application/pdf',
        'text/plain',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    public function scopeForProduct($query, $productId)
    {
        return $query->where('productId', $productId);
    }

    public function scopeExtracted($query)
    {
        return $query->where('status', self::STATUS_EXTRACTED);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sortOrder', 'asc')->orderBy('created_at', 'desc');
    }

    // ==================== RELATIONSHIPS ====================

    public function product()
    {
        return $this->belongsTo(AiExternalProduct::class, 'productId');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the document URL for display/download.
     */
    public function getDocumentUrlAttribute($value): string
    {
        if ($value) {
            return $value;
        }
        return $this->documentPath ? asset('storage/' . $this->documentPath) : '';
    }

    /**
     * Get human-readable file size.
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->fileSize;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    /**
     * Get status badge HTML.
     */
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'pending' => '<span class="badge bg-secondary">Pending</span>',
            'processing' => '<span class="badge bg-info">Processing</span>',
            'extracted' => '<span class="badge bg-success">Extracted</span>',
            'failed' => '<span class="badge bg-danger">Failed</span>',
        ];

        return $badges[$this->status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    /**
     * Get document icon based on file extension.
     */
    public function getDocumentIconAttribute(): string
    {
        $icons = [
            'pdf' => 'bx bxs-file-pdf text-danger',
            'txt' => 'bx bxs-file-txt text-secondary',
            'doc' => 'bx bxs-file-doc text-primary',
            'docx' => 'bx bxs-file-doc text-primary',
        ];

        return $icons[$this->fileExtension] ?? 'bx bxs-file text-secondary';
    }

    /**
     * Get short display name (truncated if too long).
     */
    public function getShortNameAttribute(): string
    {
        $name = $this->originalName;
        if (strlen($name) > 30) {
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $basename = pathinfo($name, PATHINFO_FILENAME);
            return substr($basename, 0, 25) . '...' . ($ext ? '.' . $ext : '');
        }
        return $name;
    }

    /**
     * Get extracted text preview (first 200 characters).
     */
    public function getTextPreviewAttribute(): ?string
    {
        if (!$this->extractedText) return null;
        $text = strip_tags($this->extractedText);
        if (strlen($text) > 200) {
            return substr($text, 0, 200) . '...';
        }
        return $text;
    }

    /**
     * Get word count of extracted text.
     */
    public function getWordCountAttribute(): int
    {
        if (!$this->extractedText) return 0;
        return str_word_count($this->extractedText);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if document needs processing.
     */
    public function needsProcessing(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_FAILED]);
    }

    /**
     * Check if document is being processed.
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if document text has been extracted.
     */
    public function isExtracted(): bool
    {
        return $this->status === self::STATUS_EXTRACTED;
    }

    /**
     * Update status.
     */
    public function updateStatus(string $status, ?string $error = null): void
    {
        $this->update([
            'status' => $status,
            'errorMessage' => $error,
        ]);
    }

    /**
     * Mark as extracted with text content.
     */
    public function markAsExtracted(string $extractedText, array $metadata = []): void
    {
        $this->update([
            'status' => self::STATUS_EXTRACTED,
            'extractedText' => $extractedText,
            'metadata' => array_merge($this->metadata ?? [], $metadata),
            'errorMessage' => null,
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'errorMessage' => $error,
        ]);
    }

    /**
     * Check if file extension is supported.
     */
    public static function isSupportedExtension(string $extension): bool
    {
        return in_array(strtolower($extension), self::SUPPORTED_EXTENSIONS);
    }

    /**
     * Check if mime type is supported.
     */
    public static function isSupportedMime(string $mime): bool
    {
        return in_array($mime, self::SUPPORTED_MIMES);
    }

    /**
     * Get validation rules for document upload.
     */
    public static function getValidationRules(): array
    {
        return [
            'documents.*' => 'file|mimes:pdf,txt,doc,docx|max:51200', // 50MB max
        ];
    }
}
