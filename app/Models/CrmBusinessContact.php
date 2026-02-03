<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CrmBusinessContact extends BaseModel
{
    use HasFactory;

    protected $table = 'crm_business_contacts';

    protected $fillable = [
        'usersId',
        'contactType',
        'contactStatus',
        'firstName',
        'middleName',
        'lastName',
        'nickname',
        'email',
        'phone',
        'alternatePhone',
        'companyName',
        'jobTitle',
        'department',
        'industry',
        'companySize',
        'website',
        'province',
        'municipality',
        'barangay',
        'streetAddress',
        'zipCode',
        'country',
        'facebookUrl',
        'instagramUrl',
        'linkedinUrl',
        'twitterUrl',
        'tiktokUrl',
        'viberNumber',
        'whatsappNumber',
        'relationshipStrength',
        'firstContactDate',
        'lastContactDate',
        'howWeMet',
        'referredBy',
        'notes',
        'tags',
        'delete_status',
    ];

    protected $casts = [
        'firstContactDate' => 'date',
        'lastContactDate' => 'date',
        'tags' => 'array',
    ];

    /**
     * Contact type options with labels and colors
     */
    public const CONTACT_TYPE_OPTIONS = [
        'general' => ['label' => 'General', 'color' => 'secondary', 'icon' => 'mdi-account'],
        'supplier' => ['label' => 'Supplier', 'color' => 'info', 'icon' => 'mdi-truck-delivery'],
        'partner' => ['label' => 'Partner', 'color' => 'primary', 'icon' => 'mdi-handshake'],
        'vendor' => ['label' => 'Vendor', 'color' => 'warning', 'icon' => 'mdi-store'],
        'client' => ['label' => 'Client', 'color' => 'success', 'icon' => 'mdi-account-star'],
        'investor' => ['label' => 'Investor', 'color' => 'danger', 'icon' => 'mdi-cash-multiple'],
        'consultant' => ['label' => 'Consultant', 'color' => 'dark', 'icon' => 'mdi-account-tie'],
        'service_provider' => ['label' => 'Service Provider', 'color' => 'info', 'icon' => 'mdi-wrench'],
        'media' => ['label' => 'Media/Press', 'color' => 'info', 'icon' => 'mdi-newspaper'],
        'government' => ['label' => 'Government', 'color' => 'primary', 'icon' => 'mdi-bank'],
    ];

    /**
     * Contact status options
     */
    public const STATUS_OPTIONS = [
        'active' => ['label' => 'Active', 'color' => 'success', 'icon' => 'mdi-check-circle'],
        'inactive' => ['label' => 'Inactive', 'color' => 'secondary', 'icon' => 'mdi-pause-circle'],
        'archived' => ['label' => 'Archived', 'color' => 'dark', 'icon' => 'mdi-archive'],
    ];

    /**
     * Relationship strength options
     */
    public const RELATIONSHIP_STRENGTH_OPTIONS = [
        'strong' => ['label' => 'Strong', 'color' => 'success', 'icon' => 'mdi-star'],
        'good' => ['label' => 'Good', 'color' => 'info', 'icon' => 'mdi-star-half-full'],
        'neutral' => ['label' => 'Neutral', 'color' => 'secondary', 'icon' => 'mdi-star-outline'],
        'weak' => ['label' => 'Weak', 'color' => 'warning', 'icon' => 'mdi-star-off'],
    ];

    /**
     * Company size options
     */
    public const COMPANY_SIZE_OPTIONS = [
        '1-10' => '1-10 employees',
        '11-50' => '11-50 employees',
        '51-200' => '51-200 employees',
        '201-500' => '201-500 employees',
        '501-1000' => '501-1000 employees',
        '1000+' => '1000+ employees',
    ];

    /**
     * Scope for active (non-deleted) records
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Scope for user's contacts
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Scope for contacts by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('contactType', $type);
    }

    /**
     * Scope for contacts by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('contactStatus', $status);
    }

    /**
     * Get full name attribute
     * Returns personal name if available, otherwise company name
     */
    public function getFullNameAttribute()
    {
        $nameParts = array_filter([
            $this->firstName,
            $this->middleName,
            $this->lastName,
        ]);

        if (!empty($nameParts)) {
            return implode(' ', $nameParts);
        }

        // Fall back to company name if no personal name
        return $this->companyName ?: 'Unnamed Contact';
    }

    /**
     * Check if contact is company-only (no personal name)
     */
    public function getIsCompanyOnlyAttribute()
    {
        return empty($this->firstName) && empty($this->lastName) && !empty($this->companyName);
    }

    /**
     * Get display name (with nickname if available)
     */
    public function getDisplayNameAttribute()
    {
        if ($this->nickname) {
            return $this->fullName . ' (' . $this->nickname . ')';
        }
        return $this->fullName;
    }

    /**
     * Get contact type label
     */
    public function getTypeLabelAttribute()
    {
        return self::CONTACT_TYPE_OPTIONS[$this->contactType]['label'] ?? $this->contactType;
    }

    /**
     * Get contact type color
     */
    public function getTypeColorAttribute()
    {
        return self::CONTACT_TYPE_OPTIONS[$this->contactType]['color'] ?? 'secondary';
    }

    /**
     * Get contact type icon
     */
    public function getTypeIconAttribute()
    {
        $icon = self::CONTACT_TYPE_OPTIONS[$this->contactType]['icon'] ?? 'mdi-account';
        if ($icon && !str_starts_with($icon, 'mdi ')) {
            $icon = 'mdi ' . $icon;
        }
        return $icon;
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute()
    {
        return self::STATUS_OPTIONS[$this->contactStatus]['label'] ?? $this->contactStatus;
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute()
    {
        return self::STATUS_OPTIONS[$this->contactStatus]['color'] ?? 'secondary';
    }

    /**
     * Get relationship strength label
     */
    public function getRelationshipLabelAttribute()
    {
        return self::RELATIONSHIP_STRENGTH_OPTIONS[$this->relationshipStrength]['label'] ?? $this->relationshipStrength;
    }

    /**
     * Get relationship strength color
     */
    public function getRelationshipColorAttribute()
    {
        return self::RELATIONSHIP_STRENGTH_OPTIONS[$this->relationshipStrength]['color'] ?? 'secondary';
    }

    /**
     * Get full address
     */
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->streetAddress,
            $this->barangay,
            $this->municipality,
            $this->province,
            $this->zipCode,
            $this->country,
        ]);
        return implode(', ', $parts);
    }

    /**
     * Get owner user
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    /**
     * Get associated stores (many-to-many)
     */
    public function stores()
    {
        return $this->belongsToMany(EcomProductStore::class, 'crm_business_contact_stores', 'contactId', 'storeId')
                    ->withTimestamps();
    }

    /**
     * Check if contact has social media links
     */
    public function hasSocialMedia()
    {
        return $this->facebookUrl || $this->instagramUrl || $this->linkedinUrl || $this->twitterUrl || $this->tiktokUrl || $this->viberNumber || $this->whatsappNumber;
    }

    /**
     * Get tags as array
     */
    public function getTagsArrayAttribute()
    {
        if (is_array($this->tags)) {
            return $this->tags;
        }
        return $this->tags ? json_decode($this->tags, true) : [];
    }
}
