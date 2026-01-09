<?php

namespace App\Models;

use Illuminate\Support\Facades\Crypt;

class EcomStoreSmtpSetting extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_store_smtp_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'storeId',
        'smtpHost',
        'smtpPort',
        'smtpUsername',
        'smtpPassword',
        'smtpEncryption',
        'smtpFromEmail',
        'smtpFromName',
        'isActive',
        'isVerified',
        'lastTestedAt',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'storeId' => 'integer',
        'smtpPort' => 'integer',
        'isActive' => 'boolean',
        'isVerified' => 'boolean',
        'deleteStatus' => 'integer',
        'lastTestedAt' => 'datetime',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'smtpPassword',
    ];

    /**
     * Scope to get only active settings (deleteStatus = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Get the store that owns this SMTP setting.
     */
    public function store()
    {
        return $this->belongsTo(EcomProductStore::class, 'storeId');
    }

    /**
     * Set the SMTP password (encrypt it).
     */
    public function setSmtpPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['smtpPassword'] = Crypt::encryptString($value);
        } else {
            $this->attributes['smtpPassword'] = null;
        }
    }

    /**
     * Get the decrypted SMTP password.
     */
    public function getDecryptedPasswordAttribute()
    {
        if ($this->attributes['smtpPassword']) {
            try {
                return Crypt::decryptString($this->attributes['smtpPassword']);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Check if SMTP settings are configured.
     */
    public function isConfigured()
    {
        return !empty($this->smtpHost)
            && !empty($this->smtpPort)
            && !empty($this->smtpFromEmail);
    }
}
