<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CrmLeadCustomData extends BaseModel
{
    use HasFactory;

    protected $table = 'crm_lead_custom_data';

    protected $fillable = [
        'leadId',
        'fieldName',
        'fieldValue',
        'usersId',
        'delete_status',
    ];

    /**
     * Scope for active (non-deleted) records
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Get the lead that owns this custom data
     */
    public function lead()
    {
        return $this->belongsTo(CrmLead::class, 'leadId');
    }

    /**
     * Get the user who created this custom data
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }
}
