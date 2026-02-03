<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CrmLeadSource extends BaseModel
{
    use HasFactory;

    protected $table = 'crm_lead_sources';

    protected $fillable = [
        'usersId',
        'sourceName',
        'sourceDescription',
        'sourceIcon',
        'sourceColor',
        'sourceOrder',
        'isActive',
        'isSystemDefault',
        'delete_status',
    ];

    protected $casts = [
        'isActive' => 'boolean',
        'isSystemDefault' => 'boolean',
        'sourceOrder' => 'integer',
    ];

    /**
     * Scope for active (non-deleted) records
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Scope for enabled sources
     */
    public function scopeEnabled($query)
    {
        return $query->where('isActive', true);
    }

    /**
     * Scope for user's sources (including system defaults)
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->whereNull('usersId')
              ->orWhere('usersId', $userId)
              ->orWhere('isSystemDefault', true);
        });
    }

    /**
     * Get leads using this source
     */
    public function leads()
    {
        return $this->hasMany(CrmLead::class, 'leadSourceId');
    }

    /**
     * Get owner user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    /**
     * Get properly formatted icon class
     */
    public function getFormattedIconAttribute()
    {
        $icon = $this->sourceIcon ?? 'mdi-tag';
        // Ensure proper MDI class format (mdi mdi-iconname)
        if ($icon && !str_starts_with($icon, 'mdi ')) {
            $icon = 'mdi ' . $icon;
        }
        return $icon;
    }
}
