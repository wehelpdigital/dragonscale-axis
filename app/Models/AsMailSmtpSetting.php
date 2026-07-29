<?php

namespace App\Models;

class AsMailSmtpSetting extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * NOTE: smtpPassword is stored PLAIN TEXT in this table (the AniSystem
     * client app reads it directly with its own APP_KEY, so Crypt cannot be
     * shared). Do NOT add an encryption mutator here.
     *
     * @var string
     */
    protected $table = 'as_mail_smtp_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'groupKey',
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
        'smtpPort' => 'integer',
        'isActive' => 'boolean',
        'isVerified' => 'boolean',
        'lastTestedAt' => 'datetime:Y-m-d H:i:s',
        'deleteStatus' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Never leak the password in JSON payloads.
     *
     * @var array
     */
    protected $hidden = [
        'smtpPassword',
    ];

    /**
     * Scope to get only active rows (deleteStatus = 1).
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Scope by group key (e.g. 'AniSystem').
     */
    public function scopeForGroup($query, string $groupKey)
    {
        return $query->where('groupKey', $groupKey);
    }

    /**
     * Check if SMTP settings are configured enough to attempt a send.
     */
    public function isConfigured()
    {
        return !empty($this->smtpHost)
            && !empty($this->smtpPort)
            && !empty($this->smtpFromEmail);
    }
}
