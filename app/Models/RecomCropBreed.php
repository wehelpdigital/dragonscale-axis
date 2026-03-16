<?php

namespace App\Models;

class RecomCropBreed extends BaseModel
{
    protected $table = 'recom_crop_breeds';

    protected $fillable = [
        'usersId',
        'name',
        'cropType',
        'breedType',
        'cornType',
        'manufacturer',
        'potentialYield',
        'maturityDays',
        'geneProtection',
        'characteristics',
        'relatedInformation',
        'imagePath',
        'brochurePath',
        'additionalDocuments',
        'sourceUrl',
        'isActive',
        'delete_status',
    ];

    protected $casts = [
        'isActive' => 'boolean',
        'geneProtection' => 'array',
        'additionalDocuments' => 'array',
    ];

    // Crop type constants
    const CROP_CORN = 'corn';
    const CROP_RICE = 'rice';

    // Breed type constants
    const BREED_HYBRID = 'hybrid';
    const BREED_INBRED = 'inbred';
    const BREED_OPV = 'opv';

    // Corn type constants
    const CORN_YELLOW = 'yellow';
    const CORN_WHITE = 'white';
    const CORN_SPECIAL = 'special';

    /**
     * Scope for active records (soft delete)
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Scope for user-specific records
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Scope for crop type
     */
    public function scopeForCrop($query, $cropType)
    {
        return $query->where('cropType', $cropType);
    }

    /**
     * Scope for breed type
     */
    public function scopeForBreedType($query, $breedType)
    {
        return $query->where('breedType', $breedType);
    }

    /**
     * Scope for corn type
     */
    public function scopeForCornType($query, $cornType)
    {
        return $query->where('cornType', $cornType);
    }

    /**
     * Scope for enabled/active breeds
     */
    public function scopeEnabled($query)
    {
        return $query->where('isActive', true);
    }

    /**
     * Get user relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    /**
     * Get crop type labels
     */
    public static function getCropTypeLabels(): array
    {
        return [
            self::CROP_CORN => 'Corn (Mais)',
            self::CROP_RICE => 'Rice (Palay)',
        ];
    }

    /**
     * Get breed type labels
     */
    public static function getBreedTypeLabels(): array
    {
        return [
            self::BREED_HYBRID => 'Hybrid',
            self::BREED_INBRED => 'Inbred',
            self::BREED_OPV => 'OPV (Open Pollinated)',
        ];
    }

    /**
     * Get corn type labels
     */
    public static function getCornTypeLabels(): array
    {
        return [
            self::CORN_YELLOW => 'Yellow Corn',
            self::CORN_WHITE => 'White Corn',
            self::CORN_SPECIAL => 'Special (Glutinous/Sweet)',
        ];
    }

    /**
     * Get breed type labels for rice
     */
    public static function getRiceBreedTypeLabels(): array
    {
        return [
            self::BREED_HYBRID => 'Hybrid',
            self::BREED_INBRED => 'Inbred',
        ];
    }

    /**
     * Get crop type label
     */
    public function getCropTypeLabelAttribute(): string
    {
        return self::getCropTypeLabels()[$this->cropType] ?? $this->cropType;
    }

    /**
     * Get breed type label
     */
    public function getBreedTypeLabelAttribute(): string
    {
        return self::getBreedTypeLabels()[$this->breedType] ?? $this->breedType;
    }

    /**
     * Get corn type label
     */
    public function getCornTypeLabelAttribute(): string
    {
        return self::getCornTypeLabels()[$this->cornType] ?? $this->cornType;
    }
}
