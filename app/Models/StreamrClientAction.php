<?php

namespace App\Models;

/**
 * An action a DS AXIS admin sent to Streamr from Streamr > Clients
 * (streamr_client_actions): what was asked, by whom, and what Streamr said.
 * A failed one can be sent again (a new row, `payload.retry_of` = this uuid).
 */
class StreamrClientAction extends BaseModel
{
    protected $table = 'streamr_client_actions';

    public const SENT = 'sent';

    public const APPLIED = 'applied';

    public const FAILED = 'failed';

    public const STATUS_BADGES = [
        self::SENT => 'bg-info text-white',
        self::APPLIED => 'bg-success',
        self::FAILED => 'bg-danger',
    ];

    /** The catalogue Streamr applies (its /api/mother/clients/{id}/actions). */
    public const ACTIONS = [
        'suspend_subscription' => 'Suspend subscription',
        'unsuspend_subscription' => 'Unsuspend subscription',
        'revoke_subscription' => 'End plan now',
        'extend_days' => 'Extend days',
        'set_expiry' => 'Change end date',
        'cancel_queued' => 'Cancel upcoming period',
        'change_plan' => 'Change plan',
        'adjust_credits' => 'Adjust credits',
        'update_profile' => 'Edit client details',
        'suspend_account' => 'Suspend account',
        'restore_account' => 'Restore account',
        'send_login_link' => 'Send login link',
        'change_email' => 'Change login email',
        'add_note' => 'Add note',
        'resync' => 'Refresh from Streamr',
    ];

    protected $fillable = [
        'uuid', 'streamrUserId', 'action', 'payload', 'requestedBy', 'requestedByName', 'status',
        'httpStatus', 'result', 'error', 'durationMs', 'attempts', 'delete_status',
    ];

    protected $casts = [
        'streamrUserId' => 'integer',
        'payload' => 'array',
        'result' => 'array',
        'requestedBy' => 'integer',
        'httpStatus' => 'integer',
        'durationMs' => 'integer',
        'attempts' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    public function label(): string
    {
        return self::ACTIONS[$this->action] ?? ucfirst(str_replace('_', ' ', (string) $this->action));
    }
}
