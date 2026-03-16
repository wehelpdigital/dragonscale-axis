<?php

namespace App\Models;

class EcomStoreInvoiceSetting extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_store_invoice_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'storeId',
        'logoPath',
        'businessName',
        'businessAddress',
        'businessPhone',
        'businessEmail',
        'taxId',
        'primaryColor',
        'secondaryColor',
        'headerBgColor',
        'headerTextColor',
        'termsAndConditions',
        'thankYouMessage',
        'footerNote',
        'bankName',
        'bankAccountName',
        'bankAccountNumber',
        'gcashNumber',
        'mayaNumber',
        'showLogo',
        'showTaxId',
        'showBankDetails',
        'showTerms',
        'showThankYou',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'storeId' => 'integer',
        'showLogo' => 'boolean',
        'showTaxId' => 'boolean',
        'showBankDetails' => 'boolean',
        'showTerms' => 'boolean',
        'showThankYou' => 'boolean',
        'deleteStatus' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Default values
     */
    protected $attributes = [
        'primaryColor' => '#556ee6',
        'secondaryColor' => '#34c38f',
        'headerBgColor' => '#556ee6',
        'headerTextColor' => '#ffffff',
        'showLogo' => true,
        'showTaxId' => false,
        'showBankDetails' => true,
        'showTerms' => true,
        'showThankYou' => true,
        'deleteStatus' => 1,
    ];

    /**
     * Scope to get only active records
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Scope to get by store
     */
    public function scopeForStore($query, $storeId)
    {
        return $query->where('storeId', $storeId);
    }

    /**
     * Get the store that owns this setting.
     */
    public function store()
    {
        return $this->belongsTo(EcomProductStore::class, 'storeId');
    }

    /**
     * Get the logo URL
     */
    public function getLogoUrlAttribute()
    {
        if ($this->logoPath) {
            return asset($this->logoPath);
        }
        return null;
    }

    /**
     * Get or create settings for a store
     */
    public static function getOrCreateForStore($storeId)
    {
        $settings = self::active()->forStore($storeId)->first();

        if (!$settings) {
            $store = EcomProductStore::find($storeId);
            $settings = self::create([
                'storeId' => $storeId,
                'businessName' => $store ? $store->storeName : 'My Store',
                'deleteStatus' => 1,
            ]);
        }

        return $settings;
    }
}
