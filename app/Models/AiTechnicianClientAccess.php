<?php

namespace App\Models;

use Carbon\Carbon;

class AiTechnicianClientAccess extends BaseModel
{
    protected $table = 'ai_technician_client_access';

    protected $fillable = [
        'usersId',
        'accessClientId',
        'grantedAt',
        'expirationDate',
        'isActive',
        'notes',
        'delete_status',
    ];

    protected $casts = [
        'grantedAt' => 'datetime',
        'expirationDate' => 'datetime',
        'isActive' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope: Active (non-deleted) records only.
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Scope: Filter by user (owner).
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Scope: Only enabled (isActive = true) records.
     */
    public function scopeEnabled($query)
    {
        return $query->where('isActive', true);
    }

    /**
     * Scope: Only non-expired records.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expirationDate')
              ->orWhere('expirationDate', '>=', Carbon::now());
        });
    }

    /**
     * Scope: Only expired records.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expirationDate')
                     ->where('expirationDate', '<', Carbon::now());
    }

    /**
     * Relationship: The client login record.
     */
    public function clientLogin()
    {
        return $this->belongsTo(ClientAccessLogin::class, 'accessClientId');
    }

    /**
     * Relationship: The admin user who granted access.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    /**
     * Check if access has expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        if (is_null($this->expirationDate)) {
            return false;
        }
        return $this->expirationDate < Carbon::now();
    }

    /**
     * Get days remaining until expiration.
     */
    public function getDaysRemainingAttribute(): ?int
    {
        if (is_null($this->expirationDate)) {
            return null; // Lifetime access
        }

        $now = Carbon::now();
        if ($this->expirationDate < $now) {
            return 0; // Already expired
        }

        return $now->diffInDays($this->expirationDate);
    }

    /**
     * Get formatted expiration date.
     */
    public function getFormattedExpirationAttribute(): string
    {
        if (is_null($this->expirationDate)) {
            return 'Lifetime';
        }
        return $this->expirationDate->format('M j, Y');
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        if (!$this->isActive) {
            return 'Inactive';
        }
        if ($this->is_expired) {
            return 'Expired';
        }
        return 'Active';
    }

    /**
     * Get status badge HTML.
     */
    public function getStatusBadgeAttribute(): string
    {
        if (!$this->isActive) {
            return '<span class="badge bg-secondary">Inactive</span>';
        }
        if ($this->is_expired) {
            return '<span class="badge bg-danger">Expired</span>';
        }

        // Check if expiring soon (within 7 days)
        if ($this->days_remaining !== null && $this->days_remaining <= 7) {
            return '<span class="badge bg-warning text-dark">Expiring Soon</span>';
        }

        return '<span class="badge bg-success">Active</span>';
    }

    /**
     * Get expiration badge HTML.
     */
    public function getExpirationBadgeAttribute(): string
    {
        if (is_null($this->expirationDate)) {
            return '<span class="badge bg-info text-white">Lifetime</span>';
        }

        if ($this->is_expired) {
            return '<span class="badge bg-danger">Expired ' . $this->expirationDate->diffForHumans() . '</span>';
        }

        $days = $this->days_remaining;
        if ($days <= 7) {
            return '<span class="badge bg-warning text-dark">' . $days . ' days left</span>';
        }

        return '<span class="badge bg-secondary">' . $this->formatted_expiration . '</span>';
    }
}
