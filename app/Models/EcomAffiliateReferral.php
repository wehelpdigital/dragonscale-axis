<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class EcomAffiliateReferral extends BaseModel
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_affiliate_referrals';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'affiliateId',
        'storeId',
        'clientId',
        'referralDate',
        'referralNotes',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'affiliateId' => 'integer',
        'storeId' => 'integer',
        'clientId' => 'integer',
        'referralDate' => 'date',
        'deleteStatus' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope to get only active referrals (deleteStatus = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Scope to filter by affiliate
     */
    public function scopeForAffiliate($query, $affiliateId)
    {
        return $query->where('affiliateId', $affiliateId);
    }

    /**
     * Scope to filter by store
     */
    public function scopeForStore($query, $storeId)
    {
        return $query->where('storeId', $storeId);
    }

    /**
     * Get the affiliate that made this referral.
     */
    public function affiliate()
    {
        return $this->belongsTo(EcomAffiliate::class, 'affiliateId', 'id');
    }

    /**
     * Get the store for this referral.
     */
    public function store()
    {
        return $this->belongsTo(EcomProductStore::class, 'storeId', 'id');
    }

    /**
     * Get the referred client.
     */
    public function client()
    {
        return $this->belongsTo(ClientAllDatabase::class, 'clientId', 'id');
    }

    /**
     * Check if a client is already referred in a specific store (by any affiliate)
     *
     * @param int $storeId
     * @param int $clientId
     * @param int|null $excludeReferralId - Exclude this referral ID (for updates)
     * @return bool
     */
    public static function isClientReferredInStore($storeId, $clientId, $excludeReferralId = null)
    {
        $query = self::active()
            ->where('storeId', $storeId)
            ->where('clientId', $clientId);

        if ($excludeReferralId) {
            $query->where('id', '!=', $excludeReferralId);
        }

        return $query->exists();
    }

    /**
     * Get IDs of clients already referred in a specific store
     *
     * @param int $storeId
     * @return array
     */
    public static function getReferredClientIdsInStore($storeId)
    {
        return self::active()
            ->where('storeId', $storeId)
            ->pluck('clientId')
            ->toArray();
    }
}
