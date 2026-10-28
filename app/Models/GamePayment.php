<?php

namespace App\Models;

/**
 * One web checkout (games_payments): a purchase in a web game, paid on the
 * studio website. The row is keyed by the game's checkout token, so a link
 * opened twice is one payment. Prices come from the game server, never from
 * the website; the item is granted by the game server once the payment is
 * checked (App\Services\Games\GamePayments).
 */
class GamePayment extends BaseModel
{
    protected $table = 'games_payments';

    public const AWAITING = 'awaiting_payment';

    public const CHECKING = 'checking';

    public const REVIEW = 'review';

    /** Accepted (by the AI or a person); the game is being told to grant the item. */
    public const APPROVED = 'approved';

    public const PAID = 'paid';

    public const REJECTED = 'rejected';

    public const EXPIRED = 'expired';

    public const STATUSES = [
        self::AWAITING => 'Waiting for payment',
        self::CHECKING => 'Checking the receipt',
        self::REVIEW => 'Needs a person',
        self::APPROVED => 'Approved, giving the item',
        self::PAID => 'Paid',
        self::REJECTED => 'Rejected',
        self::EXPIRED => 'Expired',
    ];

    public const STATUS_BADGES = [
        self::AWAITING => 'bg-secondary',
        self::CHECKING => 'bg-info text-white',
        self::REVIEW => 'bg-warning text-dark',
        self::APPROVED => 'bg-primary',
        self::PAID => 'bg-success',
        self::REJECTED => 'bg-danger',
        self::EXPIRED => 'bg-light text-dark',
    ];

    public const METHODS = [
        'gcash' => 'GCash',
        'paypal' => 'PayPal',
    ];

    protected $fillable = [
        'gameKey', 'checkoutToken', 'orderId', 'playerId', 'username', 'email', 'itemName', 'itemCategory', 'itemSlug',
        'priceUsdCents', 'pricePhp', 'feePhp', 'totalPhp', 'method', 'status', 'refNumber', 'proofPath', 'proofMime',
        'proofSha', 'proofUploadedAt', 'proofUploads', 'aiStatus', 'aiScore', 'aiReport', 'aiCheckedAt', 'reviewNote', 'reviewedBy',
        'reviewedAt', 'fulfilledAt', 'fulfillAttempts', 'fulfillError', 'buyerIp', 'expiresAt', 'delete_status',
    ];

    protected $casts = [
        'playerId' => 'integer',
        'priceUsdCents' => 'integer',
        'pricePhp' => 'decimal:2',
        'feePhp' => 'decimal:2',
        'totalPhp' => 'decimal:2',
        'aiScore' => 'integer',
        'proofUploads' => 'integer',
        'aiReport' => 'array',
        'fulfillAttempts' => 'integer',
        'proofUploadedAt' => 'datetime',
        'aiCheckedAt' => 'datetime',
        'reviewedAt' => 'datetime',
        'fulfilledAt' => 'datetime',
        'expiresAt' => 'datetime',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $hidden = ['checkoutToken'];

    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    public function scopeForGame($query, string $gameKey)
    {
        return $query->where('gameKey', $gameKey);
    }

    /** Receipts a buyer may send for one payment (each is read by the AI). */
    public const MAX_UPLOADS = 3;

    /**
     * A buyer can (still) send a receipt: while waiting for payment, or after
     * the AI could not accept one and before a person has looked (the wrong
     * picture, say), up to MAX_UPLOADS.
     */
    public function takesProof(): bool
    {
        if ($this->status === self::AWAITING) {
            return ! $this->isExpired();
        }

        return $this->status === self::REVIEW && $this->reviewedAt === null && $this->proofUploads < self::MAX_UPLOADS;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt->isPast();
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [self::PAID, self::REJECTED, self::EXPIRED], true);
    }
}
