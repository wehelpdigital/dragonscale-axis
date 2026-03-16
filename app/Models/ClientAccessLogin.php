<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientAccessLogin extends BaseModel
{
    use HasFactory;

    protected $table = 'clients_access_login';

    protected $fillable = [
        'productStore',
        'clientFirstName',
        'clientMiddleName',
        'clientLastName',
        'clientPhoneNumber',
        'clientEmailAddress',
        'clientPassword',
        'isActive',
        'deleteStatus',
    ];

    protected $casts = [
        'isActive' => 'boolean',
    ];

    /**
     * Scope for active (non-deleted) records
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Get full name attribute
     */
    public function getFullNameAttribute()
    {
        $name = $this->clientFirstName;
        if ($this->clientMiddleName) {
            $name .= ' ' . $this->clientMiddleName;
        }
        if ($this->clientLastName) {
            $name .= ' ' . $this->clientLastName;
        }
        return $name;
    }

    /**
     * Get the store this login belongs to
     */
    public function store()
    {
        return $this->belongsTo(EcomProductStore::class, 'productStore');
    }

    /**
     * Get linked leads
     */
    public function linkedLeads()
    {
        return $this->hasMany(CrmLead::class, 'linkedStoreLoginId');
    }
}
