<?php

namespace App\Modules\OutreachEngine\Models;

use App\Models\BaseModel;

/**
 * An address that must never be mailed again.
 *
 * Keyed on the address rather than the lead, because a bounce or a complaint is
 * a fact about a mailbox. One mailbox can appear on several leads, and
 * suppressing only the lead that happened to trigger it would let the next one
 * sharing that address be mailed anyway.
 *
 * A soft bounce is recorded but flagged as such: a full mailbox or a server
 * having a bad afternoon is temporary, and permanently burning the address for
 * it would quietly shrink the list every time someone's inbox filled up. Only
 * hard bounces and complaints are treated as final.
 */
class OutreachSuppression extends BaseModel
{
    protected $table = 'outreach_suppressions';

    protected $fillable = [
        'usersId',
        'email',
        'reason',
        'bounceType',
        'source',
        'detail',
        'leadId',
        'emailLogId',
        'suppressedAt',
        'delete_status',
    ];

    protected $casts = [
        'usersId' => 'integer',
        'leadId' => 'integer',
        'emailLogId' => 'integer',
        'suppressedAt' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    const REASON_BOUNCED = 'bounced';
    const REASON_SPAM = 'spam';
    const REASON_UNSUBSCRIBED = 'unsubscribed';
    const REASON_MANUAL = 'manual';

    const REASONS = [self::REASON_BOUNCED, self::REASON_SPAM, self::REASON_UNSUBSCRIBED, self::REASON_MANUAL];

    const BOUNCE_HARD = 'hard';
    const BOUNCE_SOFT = 'soft';
    const BOUNCE_UNKNOWN = 'unknown';

    /**
     * @return array<string, string>
     */
    public static function getReasonLabels(): array
    {
        return [
            self::REASON_BOUNCED => 'Bounced',
            self::REASON_SPAM => 'Spam complaint',
            self::REASON_UNSUBSCRIBED => 'Unsubscribed',
            self::REASON_MANUAL => 'Added by hand',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Scope: suppressions that permanently block sending.
     *
     * Soft bounces are excluded - they are a record, not a sentence.
     */
    public function scopeBlocking($query)
    {
        return $query->where(function ($inner) {
            $inner->where('reason', '!=', self::REASON_BOUNCED)
                ->orWhere('bounceType', '!=', self::BOUNCE_SOFT);
        });
    }

    public function lead()
    {
        return $this->belongsTo(OutreachLead::class, 'leadId');
    }

    /**
     * Is this address blocked for this account?
     *
     * The single question the send path asks before every message. Kept here so
     * there is one definition of "blocked" rather than a copy in each caller.
     */
    public static function blocks(int $userId, string $email): bool
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            return false;
        }

        return static::query()
            ->active()
            ->forUser($userId)
            ->blocking()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->exists();
    }

    /**
     * Record a suppression, or upgrade one that already exists.
     *
     * Upgrading matters: an address suppressed as a soft bounce that later
     * generates a complaint has to become permanent, and a plain insert would
     * hit the unique index and be discarded.
     */
    public static function record(
        int $userId,
        string $email,
        string $reason,
        string $bounceType = self::BOUNCE_UNKNOWN,
        ?string $source = null,
        ?string $detail = null,
        ?int $leadId = null,
        ?int $emailLogId = null
    ): ?self {
        $email = strtolower(trim($email));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $row = static::query()->forUser($userId)->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($row === null) {
            return static::create([
                'usersId' => $userId,
                'email' => $email,
                'reason' => $reason,
                'bounceType' => $bounceType,
                'source' => $source ? mb_substr($source, 0, 190) : null,
                'detail' => $detail ? mb_substr($detail, 0, 1000) : null,
                'leadId' => $leadId,
                'emailLogId' => $emailLogId,
                'suppressedAt' => now('Asia/Manila'),
                'delete_status' => 'active',
            ]);
        }

        // Never downgrade. A complaint outranks a bounce, and a hard bounce
        // outranks a soft one; anything else leaves the existing record alone.
        $outranks = ($reason === self::REASON_SPAM && $row->reason !== self::REASON_SPAM)
            || ($bounceType === self::BOUNCE_HARD && $row->bounceType !== self::BOUNCE_HARD);

        if ($outranks) {
            $row->reason = $reason;
            $row->bounceType = $bounceType;
            $row->detail = $detail ? mb_substr($detail, 0, 1000) : $row->detail;
            $row->suppressedAt = now('Asia/Manila');
        }

        // A row that was removed by hand and has now bounced again is genuinely
        // suppressed once more.
        $row->delete_status = 'active';
        $row->save();

        return $row;
    }

    /**
     * Badge for the reason, per CLAUDE.md section 12.2 contrast rules.
     */
    public function getReasonBadgeAttribute(): string
    {
        switch ($this->reason) {
            case self::REASON_SPAM:
                return '<span class="badge bg-danger">Spam complaint</span>';
            case self::REASON_UNSUBSCRIBED:
                return '<span class="badge bg-secondary">Unsubscribed</span>';
            case self::REASON_MANUAL:
                return '<span class="badge bg-light text-dark">Added by hand</span>';
            default:
                return $this->bounceType === self::BOUNCE_SOFT
                    ? '<span class="badge bg-warning text-dark">Soft bounce</span>'
                    : '<span class="badge bg-danger">Hard bounce</span>';
        }
    }
}
