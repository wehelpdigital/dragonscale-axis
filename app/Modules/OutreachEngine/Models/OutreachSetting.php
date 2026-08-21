<?php

namespace App\Modules\OutreachEngine\Models;

use App\Models\BaseModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;

/**
 * One settings row per admin user (usersId) - every credential the module needs.
 *
 * All secrets are encrypted at rest with the app key (Crypt::encryptString) and are
 * listed in $hidden so they can never leak through a toArray()/toJson() payload.
 * Controllers must send maskedKey() to the browser, never the decrypted value.
 */
class OutreachSetting extends BaseModel
{
    protected $table = 'outreach_settings';

    protected $fillable = [
        'usersId',
        'googlePlacesApiKey',
        'googleSearchApiKey',
        'googleSearchEngineId',
        'llmProvider',
        'llmApiKey',
        'llmModel',
        'smtpHost',
        'smtpPort',
        'smtpUsername',
        'smtpPassword',
        'smtpEncryption',
        'smtpFromName',
        'smtpFromEmail',
        'imapHost',
        'imapPort',
        'imapUsername',
        'imapPassword',
        'imapEncryption',
        'imapFolder',
        'dailySendCap',
        'sendWindowStart',
        'sendWindowEnd',
        'sendDays',
        'minDelayMinutes',
        'maxDelayMinutes',
        'defaultGridRadiusKm',
        'minGridRadiusKm',
        'maxSubdivisionDepth',
        'warmupEnabled',
        'warmupStartCap',
        'warmupIncrementPerDay',
        'warmupStartedOn',
        'aiRephraseEnabled',
        'outreachEnabled',
        'lastTestedAt',
        'lastTestStatus',
        'lastTestError',
        'delete_status',
    ];

    /**
     * NOTE: BaseModel declares the created_at/updated_at casts; redeclaring $casts here
     * replaces that array, so both timestamps are repeated to keep this additive.
     *
     * sendWindowStart / sendWindowEnd are MySQL TIME columns and are deliberately NOT
     * cast - they stay plain 'HH:MM:SS' strings so the decision service can compare
     * them directly against a formatted Carbon time.
     *
     * @var array
     */
    protected $casts = [
        'usersId' => 'integer',
        'smtpPort' => 'integer',
        'imapPort' => 'integer',
        'dailySendCap' => 'integer',
        'minDelayMinutes' => 'integer',
        'maxDelayMinutes' => 'integer',
        'defaultGridRadiusKm' => 'decimal:2',
        'minGridRadiusKm' => 'decimal:2',
        'maxSubdivisionDepth' => 'integer',
        'warmupEnabled' => 'boolean',
        'warmupStartCap' => 'integer',
        'warmupIncrementPerDay' => 'integer',
        'warmupStartedOn' => 'date',
        'aiRephraseEnabled' => 'boolean',
        'outreachEnabled' => 'boolean',
        'lastTestedAt' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Never serialise a secret, not even by accident.
     *
     * @var array
     */
    protected $hidden = [
        'googlePlacesApiKey',
        'googleSearchApiKey',
        'llmApiKey',
        'smtpPassword',
        'imapPassword',
    ];

    /** Attributes carrying an encrypt/decrypt pair - the only ones maskedKey() will touch. */
    const ENCRYPTED_ATTRIBUTES = [
        'googlePlacesApiKey',
        'googleSearchApiKey',
        'llmApiKey',
        'smtpPassword',
        'imapPassword',
    ];

    /** Hard ceiling on daily sends. Deliverability guard rail - no setting may exceed it. */
    const MAX_DAILY_SEND_CAP = 50;

    // LLM provider constants
    const PROVIDER_CLAUDE = 'claude';
    const PROVIDER_OPENAI = 'openai';
    const PROVIDER_GEMINI = 'gemini';

    // Connection test status constants
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';

    /**
     * Provider labels for the settings dropdown.
     */
    public static function getProviderLabels(): array
    {
        return [
            self::PROVIDER_CLAUDE => 'Claude (Anthropic)',
            self::PROVIDER_OPENAI => 'GPT (OpenAI)',
            self::PROVIDER_GEMINI => 'Gemini (Google)',
        ];
    }

    /**
     * ISO day number => label, for the sending-days checkboxes.
     */
    public static function getDayLabels(): array
    {
        return [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];
    }

    /**
     * Column defaults mirrored from the migration, so an unsaved instance handed to a
     * Blade form or a service behaves exactly like a freshly inserted row would.
     */
    protected static function defaults(): array
    {
        return [
            'llmProvider' => self::PROVIDER_GEMINI,
            'smtpPort' => 587,
            'smtpEncryption' => 'tls',
            'imapPort' => 993,
            'imapEncryption' => 'ssl',
            'imapFolder' => 'INBOX',
            'dailySendCap' => 30,
            'sendWindowStart' => '08:30:00',
            'sendWindowEnd' => '17:00:00',
            'sendDays' => '1,2,3,4,5',
            'minDelayMinutes' => 3,
            'maxDelayMinutes' => 17,
            'defaultGridRadiusKm' => 10.00,
            'minGridRadiusKm' => 0.50,
            'maxSubdivisionDepth' => 6,
            'warmupEnabled' => true,
            'warmupStartCap' => 5,
            'warmupIncrementPerDay' => 2,
            'aiRephraseEnabled' => true,
            'outreachEnabled' => false,
            'lastTestStatus' => self::STATUS_PENDING,
            'delete_status' => 'active',
        ];
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Active records only (not deleted).
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Scope: Filter by owning user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Scope: Only rows with the master kill switch ON.
     */
    public function scopeEnabled($query)
    {
        return $query->where('outreachEnabled', true);
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the admin user these settings belong to.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    // ==================== ENCRYPTED ATTRIBUTES ====================

    /**
     * Encrypt the Google Places API key before saving.
     */
    public function setGooglePlacesApiKeyAttribute($value)
    {
        $this->attributes['googlePlacesApiKey'] = !empty($value) ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt the Google Places API key when retrieving.
     */
    public function getGooglePlacesApiKeyAttribute($value)
    {
        return $this->decryptOrNull($value);
    }

    /**
     * Encrypt the Google Custom Search API key before saving.
     */
    public function setGoogleSearchApiKeyAttribute($value)
    {
        $this->attributes['googleSearchApiKey'] = !empty($value) ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt the Google Custom Search API key when retrieving.
     */
    public function getGoogleSearchApiKeyAttribute($value)
    {
        return $this->decryptOrNull($value);
    }

    /**
     * Encrypt the LLM API key before saving.
     */
    public function setLlmApiKeyAttribute($value)
    {
        $this->attributes['llmApiKey'] = !empty($value) ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt the LLM API key when retrieving.
     */
    public function getLlmApiKeyAttribute($value)
    {
        return $this->decryptOrNull($value);
    }

    /**
     * Encrypt the SMTP password before saving.
     */
    public function setSmtpPasswordAttribute($value)
    {
        $this->attributes['smtpPassword'] = !empty($value) ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt the SMTP password when retrieving.
     */
    public function getSmtpPasswordAttribute($value)
    {
        return $this->decryptOrNull($value);
    }

    /**
     * Encrypt the IMAP password before saving.
     */
    public function setImapPasswordAttribute($value)
    {
        $this->attributes['imapPassword'] = !empty($value) ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt the IMAP password when retrieving.
     */
    public function getImapPasswordAttribute($value)
    {
        return $this->decryptOrNull($value);
    }

    /**
     * Shared decrypt guard. A rotated APP_KEY (or a value written before encryption
     * existed) must degrade to "not configured" instead of throwing mid-request.
     */
    protected function decryptOrNull($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    // ==================== HELPERS ====================

    /**
     * The active settings row for a user, or an unsaved instance carrying the column
     * defaults so the settings screen always has something to render.
     */
    public static function forUserOrNew(int $userId): self
    {
        $existing = static::query()->active()->forUser($userId)->first();

        if ($existing) {
            return $existing;
        }

        $setting = new static(static::defaults());
        $setting->usersId = $userId;

        return $setting;
    }

    /**
     * Display-safe form of one encrypted attribute: abcd****wxyz.
     * Anything that is not an encrypted attribute reports as unconfigured, so a typo
     * in a Blade template can never surface a real column value.
     */
    public function maskedKey(string $attr): string
    {
        if (!in_array($attr, self::ENCRYPTED_ATTRIBUTES, true)) {
            return 'Not configured';
        }

        $value = $this->{$attr};

        if (empty($value)) {
            return 'Not configured';
        }

        $length = strlen($value);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 4) . str_repeat('*', min($length - 8, 20)) . substr($value, -4);
    }

    /**
     * Can we call the Google Places API?
     */
    public function hasPlacesKey(): bool
    {
        return !empty($this->googlePlacesApiKey);
    }

    /**
     * Can we call an LLM (key and provider both present)?
     */
    public function hasLlm(): bool
    {
        return !empty($this->llmApiKey) && !empty($this->llmProvider);
    }

    /**
     * Can we call the Google Custom Search API? Needs the key AND the engine id.
     */
    public function hasSearchKey(): bool
    {
        return !empty($this->googleSearchApiKey) && !empty($this->googleSearchEngineId);
    }

    /**
     * Enough SMTP detail to attempt an authenticated send.
     */
    public function smtpConfigured(): bool
    {
        return !empty($this->smtpHost)
            && !empty($this->smtpPort)
            && !empty($this->smtpUsername)
            && !empty($this->smtpPassword)
            && !empty($this->smtpFromEmail);
    }

    /**
     * Enough IMAP detail to attempt a mailbox login.
     */
    public function imapConfigured(): bool
    {
        return !empty($this->imapHost)
            && !empty($this->imapPort)
            && !empty($this->imapUsername)
            && !empty($this->imapPassword);
    }

    /**
     * How many emails this account may send today.
     *
     * During warm-up the ceiling starts at warmupStartCap and grows by
     * warmupIncrementPerDay for each day since warmupStartedOn, but it can never
     * exceed the user's own dailySendCap nor the hard MAX_DAILY_SEND_CAP.
     * Never returns 0 - a zero would silently freeze the cron with no explanation.
     */
    public function effectiveDailyCap(): int
    {
        $cap = (int) $this->dailySendCap;

        if ($this->warmupEnabled && !empty($this->warmupStartedOn)) {
            $startedOn = Carbon::parse($this->warmupStartedOn)->startOfDay();
            $today = Carbon::now('Asia/Manila')->startOfDay();

            // Signed diff: a future warmupStartedOn must not produce a negative ramp.
            $daysSinceStart = max(0, (int) $startedOn->diffInDays($today, false));

            $warmupCap = (int) $this->warmupStartCap
                + ((int) $this->warmupIncrementPerDay * $daysSinceStart);

            $cap = min($cap, $warmupCap);
        }

        return max(1, min($cap, self::MAX_DAILY_SEND_CAP));
    }

    /**
     * sendDays CSV ('1,2,3,4,5') as sorted unique ISO day ints, 1 = Monday.
     * An empty or garbage value yields an empty array, which the decision service reads
     * as "no sending days configured" and refuses to send - the safe direction.
     */
    public function sendDaysArray(): array
    {
        $raw = trim((string) $this->sendDays);

        if ($raw === '') {
            return [];
        }

        $days = [];
        foreach (explode(',', $raw) as $piece) {
            $day = (int) trim($piece);
            if ($day >= 1 && $day <= 7) {
                $days[$day] = $day;
            }
        }

        $days = array_values($days);
        sort($days);

        return $days;
    }

    /**
     * Human sending-days summary, e.g. "Mon, Tue, Wed, Thu, Fri".
     */
    public function getSendDaysLabelAttribute(): string
    {
        $labels = self::getDayLabels();
        $parts = [];

        foreach ($this->sendDaysArray() as $day) {
            $parts[] = substr($labels[$day] ?? (string) $day, 0, 3);
        }

        return empty($parts) ? 'No days selected' : implode(', ', $parts);
    }

    /**
     * Human sending-window summary, e.g. "08:30 - 17:00".
     */
    public function getSendWindowLabelAttribute(): string
    {
        $start = substr((string) $this->sendWindowStart, 0, 5);
        $end = substr((string) $this->sendWindowEnd, 0, 5);

        if ($start === '' || $end === '') {
            return 'Not set';
        }

        return $start . ' - ' . $end;
    }

    /**
     * LLM provider label for display.
     */
    public function getProviderLabelAttribute(): string
    {
        return self::getProviderLabels()[$this->llmProvider] ?? (string) $this->llmProvider;
    }

    /**
     * Master kill-switch badge for the dashboard / settings header.
     */
    public function getOutreachStatusBadgeAttribute(): string
    {
        return $this->outreachEnabled
            ? '<span class="badge bg-success">Outreach ON</span>'
            : '<span class="badge bg-warning text-dark">Outreach OFF</span>';
    }

    /**
     * Last connection-test badge.
     */
    public function getTestStatusBadgeAttribute(): string
    {
        switch ($this->lastTestStatus) {
            case self::STATUS_SUCCESS:
                return '<span class="badge bg-success">Connected</span>';
            case self::STATUS_FAILED:
                return '<span class="badge bg-danger">Failed</span>';
            default:
                return '<span class="badge bg-warning text-dark">Not Tested</span>';
        }
    }
}
