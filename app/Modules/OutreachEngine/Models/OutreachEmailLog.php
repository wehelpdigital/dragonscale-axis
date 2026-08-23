<?php

namespace App\Modules\OutreachEngine\Models;

use App\Models\BaseModel;
use App\Models\User;

/**
 * One row per outbound email attempt - the audit trail AND the throttle's source
 * of truth (the daily cap counts 'sent' rows dated today, Asia/Manila).
 *
 * subjectUsed / bodyUsed hold the fully rendered copy rather than a template
 * reference: the template can be edited or deleted afterwards and the inbox thread
 * must still show exactly what the recipient received.
 *
 * messageId is the RFC 5322 Message-ID generated at send time; the IMAP reader
 * matches an inbound In-Reply-To/References header back to it to link a reply to
 * its lead.
 */
class OutreachEmailLog extends BaseModel
{
    protected $table = 'outreach_email_logs';

    protected $fillable = [
        'usersId',
        'leadId',
        'templateId',
        'messageId',
        'trackingId',
        'taskId',
        'subjectUsed',
        'bodyUsed',
        'status',
        'smtpResponse',
        'errorMessage',
        'aiRephrased',
        'sentAt',
        'openedAt',
        'lastOpenedAt',
        'openCount',
        'bouncedAt',
        'bounceType',
        'complainedAt',
        'isFollowUp',
        'delete_status',
    ];

    /**
     * NOTE: BaseModel declares the created_at/updated_at casts; redeclaring $casts here
     * replaces that array, so both timestamps are repeated to keep this additive.
     *
     * @var array
     */
    protected $casts = [
        'usersId' => 'integer',
        'leadId' => 'integer',
        'templateId' => 'integer',
        'aiRephrased' => 'boolean',
        'sentAt' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    // Send lifecycle constants - services and commands must use these, not bare strings.
    const STATUS_QUEUED = 'queued';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';
    const STATUS_BOUNCED = 'bounced';

    /**
     * Status => label, for filter dropdowns and report legends.
     */
    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_QUEUED => 'Queued',
            self::STATUS_SENT => 'Sent',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_BOUNCED => 'Bounced',
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
     * Scope: Successful sends - what the daily cap actually counts.
     */
    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope: Prepared but not yet handed to SMTP.
     */
    public function scopeQueued($query)
    {
        return $query->where('status', self::STATUS_QUEUED);
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the admin user this send belongs to.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    /**
     * Get the business this email was aimed at.
     */
    public function lead()
    {
        return $this->belongsTo(OutreachLead::class, 'leadId');
    }

    /**
     * Get the template the copy came from. Null once the template is deleted -
     * there are no foreign keys here, and subjectUsed/bodyUsed keep the record whole.
     */
    public function template()
    {
        return $this->belongsTo(OutreachEmailTemplate::class, 'templateId');
    }

    // ==================== HELPERS ====================

    /**
     * Status badge for the log tables and the lead detail drawer.
     * Colours follow the contrast rules in CLAUDE.md section 12.2.
     */
    public function getStatusBadgeAttribute(): string
    {
        switch ($this->status) {
            case self::STATUS_SENT:
                return '<span class="badge bg-success">Sent</span>';
            case self::STATUS_FAILED:
                return '<span class="badge bg-danger">Failed</span>';
            case self::STATUS_BOUNCED:
                return '<span class="badge bg-dark text-white">Bounced</span>';
            case self::STATUS_QUEUED:
            default:
                return '<span class="badge bg-warning text-dark">Queued</span>';
        }
    }

    /**
     * "AI" marker for the log table - shows when the copy was rephrased before sending.
     */
    public function getRephrasedBadgeAttribute(): string
    {
        return $this->aiRephrased
            ? '<span class="badge bg-info text-white">AI</span>'
            : '<span class="badge bg-light text-dark">Original</span>';
    }

    /**
     * Plain-text preview of the sent HTML body, for list rows.
     */
    public function getBodyPreviewAttribute(): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $this->bodyUsed)));

        if ($text === '') {
            return '';
        }

        return mb_strlen($text) > 160 ? mb_substr($text, 0, 160) . '...' : $text;
    }

    /**
     * Did this attempt actually leave the building?
     */
    public function wasSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }
}
