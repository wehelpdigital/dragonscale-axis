<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class EcomAffiliate extends BaseModel
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_affiliates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'clientId',
        'firstName',
        'middleName',
        'lastName',
        'phoneNumber',
        'emailAddress',
        'bankDetails',
        'gcashNumber',
        'userPhoto',
        'expirationDate',
        'accountStatus',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'clientId' => 'integer',
        'bankDetails' => 'array',
        'expirationDate' => 'date',
        'deleteStatus' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope to get only active affiliates (deleteStatus = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Scope to get only enabled affiliates (accountStatus = 'active')
     */
    public function scopeEnabled($query)
    {
        return $query->where('accountStatus', 'active');
    }

    /**
     * Scope to get affiliates that are not expired
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expirationDate')
              ->orWhere('expirationDate', '>=', now()->toDateString());
        });
    }

    /**
     * Get the client associated with this affiliate.
     */
    public function client()
    {
        return $this->belongsTo(ClientAllDatabase::class, 'clientId', 'id');
    }

    /**
     * Get the affiliate stores (pivot records) for this affiliate.
     */
    public function affiliateStores()
    {
        return $this->hasMany(EcomAffiliateStore::class, 'affiliateId', 'id')
            ->where('deleteStatus', 1);
    }

    /**
     * Get the stores for this affiliate through the pivot table.
     */
    public function stores()
    {
        return $this->belongsToMany(
            EcomProductStore::class,
            'ecom_affiliate_stores',
            'affiliateId',
            'storeId'
        )->wherePivot('deleteStatus', 1);
    }

    /**
     * Get the documents for this affiliate.
     */
    public function documents()
    {
        return $this->hasMany(EcomAffiliateDocument::class, 'affiliateId', 'id')
            ->where('deleteStatus', 1);
    }

    /**
     * Get the referrals made by this affiliate.
     */
    public function referrals()
    {
        return $this->hasMany(EcomAffiliateReferral::class, 'affiliateId', 'id')
            ->where('deleteStatus', 1);
    }

    /**
     * Get referrals grouped by store
     */
    public function getReferralsByStore()
    {
        return $this->referrals()
            ->with(['store', 'client'])
            ->get()
            ->groupBy('storeId');
    }

    /**
     * Get the full name of the affiliate
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
     * Check if the affiliate is expired
     */
    public function getIsExpiredAttribute()
    {
        if (!$this->expirationDate) {
            return false;
        }
        return $this->expirationDate->isPast();
    }

    /**
     * Get formatted bank details
     */
    public function getFormattedBankDetailsAttribute()
    {
        if (!$this->bankDetails) {
            return null;
        }
        $details = $this->bankDetails;
        return sprintf(
            '%s - %s (%s)',
            $details['bankName'] ?? 'N/A',
            $details['accountNumber'] ?? 'N/A',
            $details['accountName'] ?? 'N/A'
        );
    }

    /**
     * Get total earnings across all stores
     */
    public function getTotalEarningsAttribute()
    {
        return $this->affiliateStores()->sum('totalEarnings');
    }

    /**
     * Get total pending earnings across all stores
     */
    public function getTotalPendingAttribute()
    {
        return $this->affiliateStores()->sum('totalPending');
    }

    /**
     * Get earnings breakdown by store
     */
    public function getEarningsByStore()
    {
        return $this->affiliateStores()
            ->with('store')
            ->get()
            ->map(function ($affiliateStore) {
                return [
                    'storeId' => $affiliateStore->storeId,
                    'storeName' => $affiliateStore->store ? $affiliateStore->store->storeName : 'Unknown Store',
                    'totalEarnings' => (float) $affiliateStore->totalEarnings,
                    'totalPending' => (float) $affiliateStore->totalPending,
                ];
            });
    }
}
