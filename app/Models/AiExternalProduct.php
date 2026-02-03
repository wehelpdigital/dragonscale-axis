<?php

namespace App\Models;

class AiExternalProduct extends BaseModel
{
    protected $table = 'ai_external_products';

    protected $fillable = [
        'usersId',
        'productName',
        'brandName',
        'manufacturer',
        'productType',
        'manualText',
        'imagePath',
        'imageUrl',
        'ocrText',
        'aiAnalysis',
        'ragContent',
        'pineconeFileId',
        'ragStatus',
        'ragError',
        'isVerified',
        'isActive',
        'delete_status',
    ];

    protected $casts = [
        'aiAnalysis' => 'array',
        'isVerified' => 'boolean',
        'isActive' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Product type constants
    const TYPE_PESTICIDE = 'pesticide';
    const TYPE_INSECTICIDE = 'insecticide';
    const TYPE_FUNGICIDE = 'fungicide';
    const TYPE_HERBICIDE = 'herbicide';
    const TYPE_BACTERICIDE = 'bactericide';
    const TYPE_NEMATICIDE = 'nematicide';
    const TYPE_MOLLUSCICIDE = 'molluscicide';
    const TYPE_RODENTICIDE = 'rodenticide';
    const TYPE_FERTILIZER_GRANULAR = 'fertilizer_granular';
    const TYPE_FERTILIZER_FOLIAR = 'fertilizer_foliar';
    const TYPE_FERTILIZER_LIQUID = 'fertilizer_liquid';
    const TYPE_FERTILIZER_ORGANIC = 'fertilizer_organic';
    const TYPE_PLANT_GROWTH_REGULATOR = 'plant_growth_regulator';
    const TYPE_SOIL_CONDITIONER = 'soil_conditioner';
    const TYPE_SEED_TREATMENT = 'seed_treatment';
    const TYPE_ADJUVANT = 'adjuvant';
    const TYPE_OTHER = 'other';

    // RAG status constants
    const RAG_PENDING = 'pending';
    const RAG_PROCESSING = 'processing';
    const RAG_ANALYZING = 'analyzing';
    const RAG_UPLOADING = 'uploading';
    const RAG_INDEXED = 'indexed';
    const RAG_FAILED = 'failed';

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    public function scopeIndexed($query)
    {
        return $query->where('ragStatus', self::RAG_INDEXED);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('productType', $type);
    }

    public function scopePending($query)
    {
        return $query->whereIn('ragStatus', [self::RAG_PENDING, self::RAG_FAILED]);
    }

    // ==================== RELATIONSHIPS ====================

    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    public function images()
    {
        return $this->hasMany(AiExternalProductImage::class, 'productId')
            ->where('delete_status', 'active')
            ->orderBy('isPrimary', 'desc')
            ->orderBy('sortOrder', 'asc');
    }

    public function primaryImage()
    {
        return $this->hasOne(AiExternalProductImage::class, 'productId')
            ->where('delete_status', 'active')
            ->where('isPrimary', true);
    }

    public function analyzedImages()
    {
        return $this->hasMany(AiExternalProductImage::class, 'productId')
            ->where('delete_status', 'active')
            ->where('status', AiExternalProductImage::STATUS_ANALYZED);
    }

    public function documents()
    {
        return $this->hasMany(AiExternalProductDocument::class, 'productId')
            ->where('delete_status', 'active')
            ->orderBy('sortOrder', 'asc');
    }

    public function extractedDocuments()
    {
        return $this->hasMany(AiExternalProductDocument::class, 'productId')
            ->where('delete_status', 'active')
            ->where('status', AiExternalProductDocument::STATUS_EXTRACTED);
    }

    // ==================== ACCESSORS ====================

    /**
     * Get product type display name.
     */
    public function getTypeDisplayAttribute(): string
    {
        return self::getProductTypes()[$this->productType] ?? ucfirst($this->productType);
    }

    /**
     * Get RAG status badge HTML.
     */
    public function getRagStatusBadgeAttribute(): string
    {
        $badges = [
            'pending' => '<span class="badge bg-secondary">Pending</span>',
            'processing' => '<span class="badge bg-info">Processing</span>',
            'analyzing' => '<span class="badge bg-primary">Analyzing</span>',
            'uploading' => '<span class="badge bg-warning text-dark">Uploading</span>',
            'indexed' => '<span class="badge bg-success">Indexed</span>',
            'failed' => '<span class="badge bg-danger">Failed</span>',
        ];

        return $badges[$this->ragStatus] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    /**
     * Get product summary from AI analysis.
     */
    public function getSummaryAttribute(): ?string
    {
        return $this->aiAnalysis['summary'] ?? null;
    }

    /**
     * Get purpose from AI analysis.
     */
    public function getPurposeAttribute(): ?string
    {
        return $this->aiAnalysis['purpose'] ?? null;
    }

    /**
     * Get search tags from AI analysis.
     */
    public function getSearchTagsAttribute(): array
    {
        return $this->aiAnalysis['searchTags'] ?? [];
    }

    /**
     * Get target pests from AI analysis.
     */
    public function getTargetPestsAttribute(): array
    {
        return $this->aiAnalysis['targetPests'] ?? [];
    }

    /**
     * Get target diseases from AI analysis.
     */
    public function getTargetDiseasesAttribute(): array
    {
        return $this->aiAnalysis['targetDiseases'] ?? [];
    }

    /**
     * Get active ingredients from AI analysis.
     */
    public function getActiveIngredientsAttribute(): array
    {
        return $this->aiAnalysis['activeIngredients'] ?? [];
    }

    /**
     * Get the primary image thumbnail URL.
     */
    public function getPrimaryImageUrlAttribute(): ?string
    {
        // First check for related images
        $primaryImage = $this->primaryImage;
        if ($primaryImage) {
            return $primaryImage->image_url;
        }

        // Fall back to first image
        $firstImage = $this->images()->first();
        if ($firstImage) {
            return $firstImage->image_url;
        }

        // Legacy: check imagePath field
        if ($this->imagePath) {
            return asset('storage/' . $this->imagePath);
        }

        return null;
    }

    /**
     * Get image count.
     */
    public function getImageCountAttribute(): int
    {
        return $this->images()->count();
    }

    /**
     * Get document count.
     */
    public function getDocumentCountAttribute(): int
    {
        return $this->documents()->count();
    }

    /**
     * Get combined text from all extracted documents.
     */
    public function getCombinedDocumentTextAttribute(): string
    {
        $docTexts = [];

        $documents = $this->extractedDocuments;
        foreach ($documents as $document) {
            if ($document->extractedText) {
                $docTexts[] = "=== Document: {$document->originalName} ===\n" . $document->extractedText;
            }
        }

        return implode("\n\n", $docTexts);
    }

    /**
     * Get combined OCR text from all analyzed images.
     */
    public function getCombinedOcrTextAttribute(): string
    {
        $ocrTexts = [];

        // Get OCR from all analyzed images
        $images = $this->analyzedImages;
        foreach ($images as $image) {
            if ($image->ocrText) {
                $ocrTexts[] = $image->ocrText;
            }
        }

        // Also include legacy ocrText field if present
        if ($this->ocrText && !in_array($this->ocrText, $ocrTexts)) {
            $ocrTexts[] = $this->ocrText;
        }

        return implode("\n\n---\n\n", $ocrTexts);
    }

    // ==================== STATIC METHODS ====================

    /**
     * Get all product types with display names.
     */
    public static function getProductTypes(): array
    {
        return [
            self::TYPE_PESTICIDE => 'Pesticide (General)',
            self::TYPE_INSECTICIDE => 'Insecticide',
            self::TYPE_FUNGICIDE => 'Fungicide',
            self::TYPE_HERBICIDE => 'Herbicide',
            self::TYPE_BACTERICIDE => 'Bactericide',
            self::TYPE_NEMATICIDE => 'Nematicide',
            self::TYPE_MOLLUSCICIDE => 'Molluscicide (Snail/Slug)',
            self::TYPE_RODENTICIDE => 'Rodenticide',
            self::TYPE_FERTILIZER_GRANULAR => 'Fertilizer - Granular',
            self::TYPE_FERTILIZER_FOLIAR => 'Fertilizer - Foliar',
            self::TYPE_FERTILIZER_LIQUID => 'Fertilizer - Liquid',
            self::TYPE_FERTILIZER_ORGANIC => 'Fertilizer - Organic',
            self::TYPE_PLANT_GROWTH_REGULATOR => 'Plant Growth Regulator',
            self::TYPE_SOIL_CONDITIONER => 'Soil Conditioner',
            self::TYPE_SEED_TREATMENT => 'Seed Treatment',
            self::TYPE_ADJUVANT => 'Adjuvant/Spreader-Sticker',
            self::TYPE_OTHER => 'Other',
        ];
    }

    /**
     * Get product type categories for grouping.
     */
    public static function getProductTypeCategories(): array
    {
        return [
            'Pest Control' => [
                self::TYPE_PESTICIDE,
                self::TYPE_INSECTICIDE,
                self::TYPE_FUNGICIDE,
                self::TYPE_HERBICIDE,
                self::TYPE_BACTERICIDE,
                self::TYPE_NEMATICIDE,
                self::TYPE_MOLLUSCICIDE,
                self::TYPE_RODENTICIDE,
            ],
            'Fertilizers' => [
                self::TYPE_FERTILIZER_GRANULAR,
                self::TYPE_FERTILIZER_FOLIAR,
                self::TYPE_FERTILIZER_LIQUID,
                self::TYPE_FERTILIZER_ORGANIC,
            ],
            'Others' => [
                self::TYPE_PLANT_GROWTH_REGULATOR,
                self::TYPE_SOIL_CONDITIONER,
                self::TYPE_SEED_TREATMENT,
                self::TYPE_ADJUVANT,
                self::TYPE_OTHER,
            ],
        ];
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if product is indexed in RAG.
     */
    public function isIndexed(): bool
    {
        return $this->ragStatus === self::RAG_INDEXED && !empty($this->pineconeFileId);
    }

    /**
     * Check if product needs processing.
     */
    public function needsProcessing(): bool
    {
        return in_array($this->ragStatus, [self::RAG_PENDING, self::RAG_FAILED]);
    }

    /**
     * Update RAG status.
     */
    public function updateRagStatus(string $status, ?string $error = null): void
    {
        $this->update([
            'ragStatus' => $status,
            'ragError' => $error,
        ]);
    }

    /**
     * Mark as indexed with Pinecone file ID.
     */
    public function markAsIndexed(string $pineconeFileId): void
    {
        $this->update([
            'ragStatus' => self::RAG_INDEXED,
            'pineconeFileId' => $pineconeFileId,
            'ragError' => null,
        ]);
    }

    /**
     * Mark as failed with error message.
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'ragStatus' => self::RAG_FAILED,
            'ragError' => $error,
        ]);
    }

    /**
     * Build RAG content from all product data.
     */
    public function buildRagContent(): string
    {
        $content = "=== AGRICULTURAL PRODUCT INFORMATION ===\n\n";

        // Basic info
        $content .= "PRODUCT NAME: {$this->productName}\n";
        if ($this->brandName) {
            $content .= "BRAND: {$this->brandName}\n";
        }
        if ($this->manufacturer) {
            $content .= "MANUFACTURER: {$this->manufacturer}\n";
        }
        $content .= "TYPE: {$this->type_display}\n\n";

        // AI Analysis
        if ($this->aiAnalysis) {
            $analysis = $this->aiAnalysis;

            if (!empty($analysis['summary'])) {
                $content .= "SUMMARY:\n{$analysis['summary']}\n\n";
            }

            if (!empty($analysis['purpose'])) {
                $content .= "PURPOSE:\n{$analysis['purpose']}\n\n";
            }

            if (!empty($analysis['activeIngredients'])) {
                $content .= "ACTIVE INGREDIENTS:\n";
                foreach ($analysis['activeIngredients'] as $ing) {
                    $content .= "- {$ing['name']}";
                    if (!empty($ing['concentration'])) {
                        $content .= " ({$ing['concentration']})";
                    }
                    if (!empty($ing['purpose'])) {
                        $content .= " - {$ing['purpose']}";
                    }
                    $content .= "\n";
                }
                $content .= "\n";
            }

            if (!empty($analysis['targetPests'])) {
                $content .= "TARGET PESTS: " . implode(', ', $analysis['targetPests']) . "\n\n";
            }

            if (!empty($analysis['targetDiseases'])) {
                $content .= "TARGET DISEASES: " . implode(', ', $analysis['targetDiseases']) . "\n\n";
            }

            if (!empty($analysis['targetCrops'])) {
                $content .= "TARGET CROPS: " . implode(', ', $analysis['targetCrops']) . "\n\n";
            }

            if (!empty($analysis['applicationMethod'])) {
                $content .= "APPLICATION METHOD: {$analysis['applicationMethod']}\n\n";
            }

            if (!empty($analysis['dosage'])) {
                $content .= "DOSAGE: {$analysis['dosage']}\n\n";
            }

            if (!empty($analysis['applicationTiming'])) {
                $content .= "WHEN TO APPLY: {$analysis['applicationTiming']}\n\n";
            }

            if (!empty($analysis['safetyPrecautions'])) {
                $content .= "SAFETY PRECAUTIONS:\n";
                foreach ($analysis['safetyPrecautions'] as $precaution) {
                    $content .= "- {$precaution}\n";
                }
                $content .= "\n";
            }

            if (!empty($analysis['preHarvestInterval'])) {
                $content .= "PRE-HARVEST INTERVAL: {$analysis['preHarvestInterval']}\n\n";
            }

            if (!empty($analysis['searchTags'])) {
                $content .= "RELATED KEYWORDS: " . implode(', ', $analysis['searchTags']) . "\n\n";
            }
        }

        // Manual text
        if ($this->manualText) {
            $content .= "ADDITIONAL INFORMATION:\n{$this->manualText}\n\n";
        }

        // OCR text from all images
        $combinedOcr = $this->combined_ocr_text;
        if ($combinedOcr) {
            $content .= "PRODUCT LABEL TEXT (FROM IMAGES):\n{$combinedOcr}\n\n";
        }

        // Document text from all extracted documents
        $combinedDocText = $this->combined_document_text;
        if ($combinedDocText) {
            $content .= "DOCUMENT CONTENT:\n{$combinedDocText}\n\n";
        }

        // Document references
        $documents = $this->documents;
        if ($documents->count() > 0) {
            $content .= "ATTACHED DOCUMENTS:\n";
            foreach ($documents as $index => $document) {
                $docNum = $index + 1;
                $content .= "- Document {$docNum}: {$document->originalName}";
                if ($document->status === 'extracted') {
                    $content .= " (Extracted - {$document->word_count} words)";
                }
                $content .= "\n";
            }
            $content .= "\n";
        }

        // Image references
        $images = $this->images;
        if ($images->count() > 0) {
            $content .= "PRODUCT IMAGES:\n";
            foreach ($images as $index => $image) {
                $imageNum = $index + 1;
                $content .= "- Image {$imageNum}: {$image->image_url}";
                if ($image->aiAnalysis && isset($image->aiAnalysis['imageType'])) {
                    $content .= " ({$image->image_type_display})";
                }
                $content .= "\n";
            }
            $content .= "\n";
        } elseif ($this->imageUrl) {
            // Legacy single image
            $content .= "PRODUCT IMAGE: {$this->imageUrl}\n\n";
        }

        $content .= "=== END OF PRODUCT INFORMATION ===\n";

        return $content;
    }
}
