<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class EcomAffiliateDocument extends BaseModel
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_affiliate_documents';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'affiliateId',
        'documentName',
        'documentType',
        'documentPath',
        'documentNotes',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'affiliateId' => 'integer',
        'deleteStatus' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope to get only active documents (deleteStatus = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Get the affiliate that owns this document.
     */
    public function affiliate()
    {
        return $this->belongsTo(EcomAffiliate::class, 'affiliateId', 'id');
    }
}
