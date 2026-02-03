<?php

namespace App\Models;

class AiExternalProductImage extends BaseModel
{
    protected $table = 'ai_external_product_images';

    protected $fillable = [
        'productId',
        'usersId',
        'imagePath',
        'imageUrl',
        'originalName',
        'fileSize',
        'mimeType',
        'ocrText',
        'aiAnalysis',
        'status',
        'errorMessage',
        'sortOrder',
        'isPrimary',
        'delete_status',
    ];

    protected $casts = [
        'aiAnalysis' => 'array',
        'isPrimary' => 'boolean',
        'fileSize' => 'integer',
        'sortOrder' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_ANALYZED = 'analyzed';
    const STATUS_FAILED = 'failed';

    // Image type constants (for AI analysis)
    const TYPE_FRONT_LABEL = 'front_label';
    const TYPE_BACK_LABEL = 'back_label';
    const TYPE_INGREDIENTS = 'ingredients';
    const TYPE_INSTRUCTIONS = 'instructions';
    const TYPE_WARNINGS = 'warnings';
    const TYPE_OTHER = 'other';

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

    public function scopeAnalyzed($query)
    {
        return $query->where('status', self::STATUS_ANALYZED);
    }

    public function scopePrimary($query)
    {
        return $query->where('isPrimary', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('isPrimary', 'desc')->orderBy('sortOrder', 'asc');
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
     * Get the image URL for display.
     */
    public function getImageUrlAttribute($value): string
    {
        if ($value) {
            return $value;
        }
        return $this->imagePath ? asset('storage/' . $this->imagePath) : '';
    }

    /**
     * Get thumbnail URL (same as image URL for now).
     */
    public function getThumbnailUrlAttribute(): string
    {
        return $this->image_url;
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
            'analyzed' => '<span class="badge bg-success">Analyzed</span>',
            'failed' => '<span class="badge bg-danger">Failed</span>',
        ];

        return $badges[$this->status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    /**
     * Get image type display name.
     */
    public function getImageTypeDisplayAttribute(): string
    {
        $imageType = $this->aiAnalysis['imageType'] ?? null;
        if (!$imageType) return 'Unknown';

        $types = [
            'front_label' => 'Front Label',
            'back_label' => 'Back Label',
            'ingredients' => 'Ingredients',
            'instructions' => 'Instructions',
            'warnings' => 'Warnings',
            'other' => 'Other',
        ];

        return $types[$imageType] ?? ucfirst(str_replace('_', ' ', $imageType));
    }

    /**
     * Get extracted text summary.
     */
    public function getTextSummaryAttribute(): ?string
    {
        return $this->aiAnalysis['summary'] ?? null;
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if image needs processing.
     */
    public function needsProcessing(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_FAILED]);
    }

    /**
     * Check if image is being processed.
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if image has been analyzed.
     */
    public function isAnalyzed(): bool
    {
        return $this->status === self::STATUS_ANALYZED;
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
     * Mark as analyzed with OCR text and AI analysis.
     */
    public function markAsAnalyzed(string $ocrText, array $aiAnalysis): void
    {
        $this->update([
            'status' => self::STATUS_ANALYZED,
            'ocrText' => $ocrText,
            'aiAnalysis' => $aiAnalysis,
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
     * Set as primary image.
     */
    public function setAsPrimary(): void
    {
        // Remove primary from other images of this product
        self::where('productId', $this->productId)
            ->where('id', '!=', $this->id)
            ->where('delete_status', 'active')
            ->update(['isPrimary' => false]);

        $this->update(['isPrimary' => true]);
    }
}
