<?php

namespace App\Modules\OutreachEngine\Models;

use App\Models\BaseModel;
use App\Models\User;

/**
 * The module's mailbox mirror. Despite the name it stores BOTH sides of a
 * conversation - direction = 'outbound' rows are the admin's quick replies - so the
 * inbox screen renders a real thread from one table.
 *
 * (usersId, messageUid) is unique because the IMAP poller reads with BODY.PEEK[]:
 * messages stay unread server-side and this table owns read state, so every poll
 * sees the same UIDs again and must not duplicate them. messageUid is NULLABLE and
 * outbound rows leave it null, which keeps our own replies out of that index.
 */
class OutreachInboundMessage extends BaseModel
{
    protected $table = 'outreach_inbound_messages';

    protected $fillable = [
        'usersId',
        'leadId',
        'uidValidity',
        'messageUid',
        'messageId',
        'inReplyTo',
        'senderEmail',
        'senderName',
        'subject',
        'bodyText',
        'bodyHtml',
        'direction',
        'isBounce',
        'readAt',
        'isReplied',
        'receivedAt',
        'delete_status',
    ];

    /**
     * NOTE: BaseModel declares the created_at/updated_at casts; redeclaring $casts here
     * replaces that array, so both timestamps are repeated to keep this additive.
     *
     * messageUid stays a string - IMAP UIDs are opaque tokens, not numbers to do
     * arithmetic on, and uidValidity is likewise stored verbatim.
     *
     * @var array
     */
    protected $casts = [
        'usersId' => 'integer',
        'leadId' => 'integer',
        'isBounce' => 'boolean',
        'isReplied' => 'boolean',
        'readAt' => 'datetime:Y-m-d H:i:s',
        'receivedAt' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    // Which way the message travelled.
    const DIRECTION_INBOUND = 'inbound';
    const DIRECTION_OUTBOUND = 'outbound';

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
     * Scope: Messages that came from a lead.
     */
    public function scopeInbound($query)
    {
        return $query->where('direction', self::DIRECTION_INBOUND);
    }

    /**
     * Scope: Our own quick replies.
     */
    public function scopeOutbound($query)
    {
        return $query->where('direction', self::DIRECTION_OUTBOUND);
    }

    /**
     * Scope: Unread inbound mail - the inbox badge count.
     *
     * Read state lives here, not on the IMAP server: the poller peeks, so the
     * server-side \Seen flag never changes.
     */
    public function scopeUnread($query)
    {
        return $query->where('direction', self::DIRECTION_INBOUND)->whereNull('readAt');
    }

    /**
     * Scope: Bounce notifications only.
     */
    public function scopeBounces($query)
    {
        return $query->where('isBounce', true);
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the admin user whose mailbox this came through.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    /**
     * Get the lead this message was matched to. Null when the sender matched no
     * lead - unmatched mail is still stored so nothing silently disappears.
     */
    public function lead()
    {
        return $this->belongsTo(OutreachLead::class, 'leadId');
    }

    // ==================== HELPERS ====================

    /**
     * Direction badge for the thread view.
     * Colours follow the contrast rules in CLAUDE.md section 12.2.
     */
    public function getDirectionBadgeAttribute(): string
    {
        if ($this->isBounce) {
            return '<span class="badge bg-warning text-dark">Bounce</span>';
        }

        return $this->direction === self::DIRECTION_OUTBOUND
            ? '<span class="badge bg-primary">Sent</span>'
            : '<span class="badge bg-info text-white">Received</span>';
    }

    /**
     * Best available display name for the thread list.
     */
    public function getDisplaySenderAttribute(): string
    {
        $name = trim((string) $this->senderName);

        return $name !== '' ? $name : (string) $this->senderEmail;
    }

    /**
     * One-line plain-text preview. Prefers the text part and falls back to the HTML
     * one stripped down, because plenty of senders ship HTML only.
     */
    public function getSnippetAttribute(): string
    {
        $source = trim((string) $this->bodyText);

        if ($source === '') {
            $source = strip_tags((string) $this->bodyHtml);
        }

        $text = trim(preg_replace('/\s+/', ' ', $source));

        if ($text === '') {
            return '';
        }

        return mb_strlen($text) > 120 ? mb_substr($text, 0, 120) . '...' : $text;
    }

    /**
     * Has the admin opened this one yet?
     */
    public function isUnread(): bool
    {
        return $this->direction === self::DIRECTION_INBOUND && empty($this->readAt);
    }
}
