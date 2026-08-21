<?php

namespace App\Modules\OutreachEngine\Models;

use App\Models\BaseModel;
use App\Models\User;

/**
 * A reusable outreach email. Bodies are HTML and hold {business_name}-style
 * placeholders that TemplateRenderService resolves against a lead at send time.
 *
 * sendOrder keeps several variants in a deliberate rotation; timesUsed is a
 * denormalised counter so the templates screen can show usage without joining the
 * (much larger) email-log table.
 */
class OutreachEmailTemplate extends BaseModel
{
    protected $table = 'outreach_email_templates';

    protected $fillable = [
        'usersId',
        'name',
        'subjectTemplate',
        'bodyTemplate',
        'isActive',
        'sendOrder',
        'timesUsed',
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
        'isActive' => 'boolean',
        'sendOrder' => 'integer',
        'timesUsed' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Placeholder token => human label. The single source of truth for both the
     * templates editor's insert buttons and TemplateRenderService's replacements.
     */
    const PLACEHOLDERS = [
        '{business_name}' => 'Business name',
        '{city}' => 'City',
        '{province}' => 'Province',
        '{address}' => 'Address',
        '{phone}' => 'Phone',
        '{website}' => 'Website',
        '{category}' => 'Category',
        '{sender_name}' => 'Sender name',
        '{sender_email}' => 'Sender email',
        '{date}' => 'Today (date)',
    ];

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
     * Scope: Only templates the admin has switched on.
     *
     * Distinct from active() - that one is the soft-delete filter, this one is the
     * user's own on/off toggle. The send pipeline needs BOTH.
     */
    public function scopeEnabled($query)
    {
        return $query->where('isActive', true);
    }

    /**
     * Scope: Rotation order the send pipeline walks.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sendOrder', 'asc')->orderBy('id', 'asc');
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the admin user this template belongs to.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    /**
     * Get every send that used this template.
     */
    public function emailLogs()
    {
        return $this->hasMany(OutreachEmailLog::class, 'templateId');
    }

    // ==================== HELPERS ====================

    /**
     * On/off badge for the templates table.
     * Colours follow the contrast rules in CLAUDE.md section 12.2.
     */
    public function getStatusBadgeAttribute(): string
    {
        return $this->isActive
            ? '<span class="badge bg-success">Active</span>'
            : '<span class="badge bg-warning text-dark">Inactive</span>';
    }

    /**
     * Plain-text preview of the HTML body for list rows and tooltips.
     */
    public function getBodyPreviewAttribute(): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $this->bodyTemplate)));

        if ($text === '') {
            return '';
        }

        return mb_strlen($text) > 140 ? mb_substr($text, 0, 140) . '...' : $text;
    }

    /**
     * Placeholder token => label, for the editor's insert buttons.
     */
    public function availablePlaceholders(): array
    {
        return self::PLACEHOLDERS;
    }

    /**
     * Bump the usage counter after a successful send.
     *
     * increment() writes straight to the DB, so two cron runs racing on the same
     * template still add up instead of overwriting each other with a stale value.
     */
    public function markUsed(): void
    {
        $this->increment('timesUsed');
    }
}
