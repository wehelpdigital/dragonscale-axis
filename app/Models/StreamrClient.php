<?php

namespace App\Models;

use Illuminate\Support\Carbon;

/**
 * A Streamr user as Streamr last described them (streamr_clients): profile,
 * store, subscription, credits, totals and usage. Written only from the
 * snapshots Streamr pushes (PUT /api/streamr/v1/clients/{id}) or returns
 * after an action; the full snapshot is kept in `snapshot`.
 */
class StreamrClient extends BaseModel
{
    protected $table = 'streamr_clients';

    public const STATES = [
        'active' => 'Active',
        'grace' => 'Grace period',
        'expired' => 'Expired',
        'suspended' => 'Suspended',
        'none' => 'No plan',
    ];

    public const STATE_BADGES = [
        'active' => 'bg-success',
        'grace' => 'bg-warning text-dark',
        'expired' => 'bg-secondary',
        'suspended' => 'bg-danger',
        'none' => 'bg-light text-secondary',
    ];

    public const ACCOUNTS = [
        'active' => 'Active',
        'suspended' => 'Suspended',
        'revoked' => 'Revoked',
        'deletion_requested' => 'Deletion requested',
        'deleted' => 'Deleted',
    ];

    public const ACCOUNT_BADGES = [
        'active' => 'bg-success',
        'suspended' => 'bg-danger',
        'revoked' => 'bg-danger',
        'deletion_requested' => 'bg-warning text-dark',
        'deleted' => 'bg-dark',
    ];

    public const PLANS = [
        'monthly' => 'Monthly',
        'yearly' => 'Yearly',
        'lifetime' => 'Lifetime',
    ];

    public const ADMIN_ROLES = ['admin', 'super_admin'];

    protected $fillable = [
        'streamrUserId', 'email', 'firstName', 'lastName', 'phone', 'role', 'accountStatus',
        'storeId', 'storeName', 'storeHandle', 'storeUrl', 'customDomain',
        'planKey', 'planName', 'planPrice', 'subscriptionState', 'startsAt', 'expiresAt', 'graceUntil', 'isLifetime',
        'planCredits', 'purchasedCredits', 'availableCredits', 'planCycleEndsAt',
        'totalPaid', 'ordersCount', 'lastOrderNumber',
        'livesCount', 'liveMinutes', 'leadsCount', 'productsCount', 'openTickets', 'notesCount', 'storageBytes',
        'recentNotes', 'snapshot', 'crmClientId',
        'registeredAt', 'lastLoginAt', 'lastLiveAt', 'snapshotAt', 'syncedAt', 'delete_status',
    ];

    protected $casts = [
        'streamrUserId' => 'integer',
        'storeId' => 'integer',
        'planPrice' => 'decimal:2',
        'totalPaid' => 'decimal:2',
        'isLifetime' => 'boolean',
        'planCredits' => 'integer',
        'purchasedCredits' => 'integer',
        'availableCredits' => 'integer',
        'ordersCount' => 'integer',
        'livesCount' => 'integer',
        'liveMinutes' => 'integer',
        'leadsCount' => 'integer',
        'productsCount' => 'integer',
        'openTickets' => 'integer',
        'notesCount' => 'integer',
        'storageBytes' => 'integer',
        'crmClientId' => 'integer',
        'recentNotes' => 'array',
        'snapshot' => 'array',
        'startsAt' => 'datetime',
        'expiresAt' => 'datetime',
        'graceUntil' => 'datetime',
        'planCycleEndsAt' => 'datetime',
        'registeredAt' => 'datetime',
        'lastLoginAt' => 'datetime',
        'lastLiveAt' => 'datetime',
        'snapshotAt' => 'datetime',
        'syncedAt' => 'datetime',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    public function fullName(): string
    {
        $name = trim(trim((string) $this->firstName) . ' ' . trim((string) $this->lastName));

        return $name !== '' ? $name : (string) $this->email;
    }

    /** Streamr admins have every feature and unlimited credits (no subscription to manage). */
    public function isAdmin(): bool
    {
        return in_array($this->role, self::ADMIN_ROLES, true);
    }

    /**
     * The state as of now: the registry can lag Streamr, so an "active" plan
     * whose end date has passed reads as expired here until the next push.
     */
    public function computedState(?Carbon $now = null): string
    {
        $now ??= now();
        $state = (string) ($this->subscriptionState ?: 'none');

        if ($state === 'active' && ! $this->isLifetime && $this->expiresAt && $this->expiresAt->lte($now)) {
            return 'expired';
        }
        if ($state === 'grace' && $this->graceUntil && $this->graceUntil->lte($now)) {
            return 'expired';
        }

        return array_key_exists($state, self::STATES) ? $state : 'none';
    }

    /** The queued (upcoming) periods from the stored snapshot: [{id, plan_key, starts_at, expires_at}]. */
    public function queuedPeriods(): array
    {
        $queued = $this->snapshot['subscription']['queued'] ?? [];

        return array_values(array_filter(is_array($queued) ? $queued : [], fn ($q) => is_array($q) && isset($q['id'])));
    }
}
