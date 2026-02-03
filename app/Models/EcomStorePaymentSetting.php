<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EcomStorePaymentSetting extends Model
{
    protected $table = 'ecom_store_payment_settings';

    protected $fillable = [
        'storeId',
        'bankName',
        'bankAccountName',
        'bankAccountNumber',
        'isBankActive',
        'gcashNumber',
        'gcashAccountName',
        'paymentScreenshot',
        'qrCodeImage',
        'isGcashActive',
        'paymentInstructions',
        'isActive',
        'deleteStatus'
    ];

    protected $casts = [
        'storeId' => 'integer',
        'isBankActive' => 'boolean',
        'isGcashActive' => 'boolean',
        'isActive' => 'boolean',
        'deleteStatus' => 'integer'
    ];

    /**
     * Relationships
     */
    public function store()
    {
        return $this->belongsTo(EcomProductStore::class, 'storeId', 'id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where('storeId', $storeId);
    }

    public function scopeEnabled($query)
    {
        return $query->where('isActive', true);
    }

    /**
     * Check if bank details are complete (all required fields filled)
     */
    public function isBankComplete(): bool
    {
        return !empty($this->bankName) && !empty($this->bankAccountName) && !empty($this->bankAccountNumber);
    }

    /**
     * Check if GCash details are complete (number and name filled)
     */
    public function isGcashComplete(): bool
    {
        return !empty($this->gcashNumber) && !empty($this->gcashAccountName);
    }

    /**
     * Check if any payment method is configured and active
     */
    public function isConfigured(): bool
    {
        return ($this->isBankComplete() && $this->isBankActive)
            || ($this->isGcashComplete() && $this->isGcashActive);
    }

    /**
     * Check if any payment detail has been entered
     */
    public function hasAnyDetails(): bool
    {
        return !empty($this->bankName)
            || !empty($this->bankAccountName)
            || !empty($this->bankAccountNumber)
            || !empty($this->gcashNumber)
            || !empty($this->gcashAccountName)
            || !empty($this->paymentScreenshot)
            || !empty($this->qrCodeImage);
    }

    /**
     * Get payment screenshot URL
     */
    public function getPaymentScreenshotUrlAttribute()
    {
        return $this->paymentScreenshot ? asset($this->paymentScreenshot) : null;
    }

    /**
     * Get QR code URL
     */
    public function getQrCodeUrlAttribute()
    {
        return $this->qrCodeImage ? asset($this->qrCodeImage) : null;
    }

    /**
     * Get or create payment settings for a store
     */
    public static function getOrCreate($storeId)
    {
        $settings = self::active()->forStore($storeId)->first();

        if (!$settings) {
            $settings = self::create([
                'storeId' => $storeId,
                'isActive' => false,
                'deleteStatus' => 1
            ]);
        }

        return $settings;
    }
}
