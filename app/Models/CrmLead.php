<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CrmLead extends BaseModel
{
    use HasFactory;

    protected $table = 'crm_leads';

    protected $fillable = [
        'usersId',
        'leadStatus',
        'leadPriority',
        'leadSourceId',
        'leadSourceOther',
        'referredBy',
        'firstName',
        'middleName',
        'lastName',
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
        'assignedTo',
        'lastContactDate',
        'convertedToClientId',
        'conversionDate',
        'linkedClientAt',
        'linkedStoreLoginId',
        'linkedStoreLoginAt',
        'lossReason',
        'lossDetails',
        'notes',
        'delete_status',
    ];

    protected $casts = [
        'lastContactDate' => 'datetime',
        'conversionDate' => 'datetime',
        'linkedClientAt' => 'datetime',
        'linkedStoreLoginAt' => 'datetime',
    ];

    /**
     * Lead status options with labels and colors
     */
    public const STATUS_OPTIONS = [
        'new' => ['label' => 'New', 'color' => 'info', 'icon' => 'mdi-star-outline'],
        'contacted' => ['label' => 'Contacted', 'color' => 'primary', 'icon' => 'mdi-phone-check'],
        'qualified' => ['label' => 'Qualified', 'color' => 'success', 'icon' => 'mdi-check-circle'],
        'proposal' => ['label' => 'Proposal Sent', 'color' => 'warning', 'icon' => 'mdi-file-document-outline'],
        'negotiation' => ['label' => 'Negotiation', 'color' => 'secondary', 'icon' => 'mdi-handshake'],
        'won' => ['label' => 'Won', 'color' => 'success', 'icon' => 'mdi-trophy'],
        'lost' => ['label' => 'Lost', 'color' => 'danger', 'icon' => 'mdi-close-circle'],
        'dormant' => ['label' => 'Dormant', 'color' => 'dark', 'icon' => 'mdi-sleep'],
    ];

    /**
     * Lead priority options
     */
    public const PRIORITY_OPTIONS = [
        'low' => ['label' => 'Low', 'color' => 'secondary', 'icon' => 'mdi-chevron-down'],
        'medium' => ['label' => 'Medium', 'color' => 'info', 'icon' => 'mdi-minus'],
        'high' => ['label' => 'High', 'color' => 'warning', 'icon' => 'mdi-chevron-up'],
        'urgent' => ['label' => 'Urgent', 'color' => 'danger', 'icon' => 'mdi-chevron-double-up'],
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
     * Importable fields for CSV/Excel import mapping
     */
    public const IMPORTABLE_FIELDS = [
        'fullName' => ['label' => 'Full Name', 'required' => false, 'hint' => 'Use if name is in one column'],
        'firstName' => ['label' => 'First Name', 'required' => false],
        'middleName' => ['label' => 'Middle Name', 'required' => false],
        'lastName' => ['label' => 'Last Name', 'required' => false],
        'email' => ['label' => 'Email Address', 'required' => false],
        'phone' => ['label' => 'Phone Number', 'required' => false],
        'alternatePhone' => ['label' => 'Alternate Phone', 'required' => false],
        'companyName' => ['label' => 'Company Name', 'required' => false],
        'jobTitle' => ['label' => 'Job Title', 'required' => false],
        'department' => ['label' => 'Department', 'required' => false],
        'industry' => ['label' => 'Industry', 'required' => false],
        'companySize' => ['label' => 'Company Size', 'required' => false],
        'website' => ['label' => 'Website', 'required' => false],
        'province' => ['label' => 'Province', 'required' => false],
        'municipality' => ['label' => 'City/Municipality', 'required' => false],
        'barangay' => ['label' => 'Barangay', 'required' => false],
        'streetAddress' => ['label' => 'Street Address', 'required' => false],
        'zipCode' => ['label' => 'Zip Code', 'required' => false],
        'country' => ['label' => 'Country', 'required' => false],
        'facebookUrl' => ['label' => 'Facebook URL', 'required' => false],
        'instagramUrl' => ['label' => 'Instagram URL', 'required' => false],
        'linkedinUrl' => ['label' => 'LinkedIn URL', 'required' => false],
        'twitterUrl' => ['label' => 'Twitter/X URL', 'required' => false],
        'tiktokUrl' => ['label' => 'TikTok URL', 'required' => false],
        'viberNumber' => ['label' => 'Viber Number', 'required' => false],
        'whatsappNumber' => ['label' => 'WhatsApp Number', 'required' => false],
        'leadStatus' => ['label' => 'Lead Status', 'required' => false],
        'leadPriority' => ['label' => 'Lead Priority', 'required' => false],
        'referredBy' => ['label' => 'Referred By', 'required' => false],
        'notes' => ['label' => 'Notes', 'required' => false],
    ];

    /**
     * Scope for active (non-deleted) records
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Scope for user's leads
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Scope for leads assigned to a user
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assignedTo', $userId);
    }

    /**
     * Scope for leads by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('leadStatus', $status);
    }

    /**
     * Get full name attribute
     */
    public function getFullNameAttribute()
    {
        $name = $this->firstName;
        if ($this->middleName) {
            $name .= ' ' . $this->middleName;
        }
        if ($this->lastName) {
            $name .= ' ' . $this->lastName;
        }
        return $name;
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute()
    {
        return self::STATUS_OPTIONS[$this->leadStatus]['label'] ?? $this->leadStatus;
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute()
    {
        return self::STATUS_OPTIONS[$this->leadStatus]['color'] ?? 'secondary';
    }

    /**
     * Get status icon
     */
    public function getStatusIconAttribute()
    {
        $icon = self::STATUS_OPTIONS[$this->leadStatus]['icon'] ?? 'mdi-circle';
        // Ensure proper MDI class format (mdi mdi-iconname)
        if ($icon && !str_starts_with($icon, 'mdi ')) {
            $icon = 'mdi ' . $icon;
        }
        return $icon;
    }

    /**
     * Get priority label
     */
    public function getPriorityLabelAttribute()
    {
        return self::PRIORITY_OPTIONS[$this->leadPriority]['label'] ?? $this->leadPriority;
    }

    /**
     * Get priority color
     */
    public function getPriorityColorAttribute()
    {
        return self::PRIORITY_OPTIONS[$this->leadPriority]['color'] ?? 'secondary';
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
     * Check if lead is open (not won/lost)
     */
    public function isOpen()
    {
        return !in_array($this->leadStatus, ['won', 'lost']);
    }

    /**
     * Get lead source relationship
     */
    public function source()
    {
        return $this->belongsTo(CrmLeadSource::class, 'leadSourceId');
    }

    /**
     * Get owner user
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    /**
     * Get assigned user
     */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignedTo');
    }

    /**
     * Get converted client
     */
    public function convertedClient()
    {
        return $this->belongsTo(ClientAllDatabase::class, 'convertedToClientId');
    }

    /**
     * Get linked store login
     */
    public function linkedStoreLogin()
    {
        return $this->belongsTo(ClientAccessLogin::class, 'linkedStoreLoginId');
    }

    /**
     * Get target stores (many-to-many)
     */
    public function targetStores()
    {
        return $this->belongsToMany(EcomProductStore::class, 'crm_lead_store_targets', 'leadId', 'storeId')
                    ->withTimestamps();
    }

    /**
     * Get custom data fields
     */
    public function customData()
    {
        return $this->hasMany(CrmLeadCustomData::class, 'leadId')->where('delete_status', 'active');
    }

    /**
     * Get activities
     */
    public function activities()
    {
        return $this->hasMany(CrmLeadActivity::class, 'leadId')->orderBy('activityDate', 'desc');
    }

    /**
     * Get latest activity
     */
    public function latestActivity()
    {
        return $this->hasOne(CrmLeadActivity::class, 'leadId')->latest('activityDate');
    }

    /**
     * Log an activity
     */
    public function logActivity($type, $description, $userId, $additionalData = [])
    {
        return $this->activities()->create(array_merge([
            'usersId' => $userId,
            'activityType' => $type,
            'activityDescription' => $description,
            'activityDate' => now(),
            'delete_status' => 'active',
        ], $additionalData));
    }

    /**
     * Log status change
     */
    public function logStatusChange($oldStatus, $newStatus, $userId)
    {
        $oldLabel = self::STATUS_OPTIONS[$oldStatus]['label'] ?? $oldStatus;
        $newLabel = self::STATUS_OPTIONS[$newStatus]['label'] ?? $newStatus;

        return $this->logActivity(
            'status_change',
            "Status changed from '{$oldLabel}' to '{$newLabel}'",
            $userId,
            [
                'previousStatus' => $oldStatus,
                'newStatus' => $newStatus,
            ]
        );
    }
}
