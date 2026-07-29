<?php

namespace App\Models;

use Carbon\Carbon;

class AnisystemSubscription extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'anisystem_subscriptions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'userId',
        'planId',
        'planKey',
        'planName',
        'price',
        'durationDays',
        'ecomOrderId',
        'orderNumber',
        'status',
        'startsAt',
        'expiresAt',
        'verifiedAt',
        'suspendedAt',
        'cancelledAt',
        'expiryNotifiedAt',
        'notes',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'userId' => 'integer',
        'planId' => 'integer',
        'price' => 'decimal:2',
        'durationDays' => 'integer',
        'ecomOrderId' => 'integer',
        'startsAt' => 'datetime:Y-m-d H:i:s',
        'expiresAt' => 'datetime:Y-m-d H:i:s',
        'verifiedAt' => 'datetime:Y-m-d H:i:s',
        'suspendedAt' => 'datetime:Y-m-d H:i:s',
        'cancelledAt' => 'datetime:Y-m-d H:i:s',
        'expiryNotifiedAt' => 'datetime:Y-m-d H:i:s',
        'deleteStatus' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Scope to get only active rows (deleteStatus = 1).
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * The AniSystem client this subscription belongs to.
     */
    public function user()
    {
        return $this->belongsTo(AnisystemUser::class, 'userId');
    }

    /**
     * The linked e-commerce order (payment vehicle in the shared DB).
     */
    public function order()
    {
        return $this->belongsTo(EcomOrder::class, 'ecomOrderId');
    }

    /**
     * True when the row says "active" but the expiry date has already passed.
     */
    public function isLapsed(): bool
    {
        return $this->status === 'active'
            && $this->expiresAt
            && Carbon::parse($this->expiresAt)->timezone('Asia/Manila')->isPast();
    }

    /**
     * Status with the computed "expired" state applied on top of the raw
     * column (an 'active' row whose expiresAt has passed reads as expired).
     */
    public function getEffectiveStatusAttribute(): string
    {
        return $this->isLapsed() ? 'expired' : ($this->status ?? 'pending');
    }
}
