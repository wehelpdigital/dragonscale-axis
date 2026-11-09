<?php

namespace App\Models;

/**
 * One receipt a Streamr buyer sent (streamr_payment_proofs), n = 1..3 per
 * order. The bytes are kept here because receipts carry names, numbers and
 * balances: this app's own disk is wiped on deploy and its public disk is
 * public. They are streamed only to signed-in admins (/streamr-proofs).
 * Lists select the columns they need; `bytes` is never serialised.
 */
class StreamrPaymentProof extends BaseModel
{
    protected $table = 'streamr_payment_proofs';

    /** Every column but the bytes, for lists. */
    public const LIGHT = [
        'id', 'uuid', 'streamrOrderId', 'orderNumber', 'n', 'ecomPaymentId', 'kind', 'name', 'mime', 'size', 'sha256',
        'reference', 'refKey', 'aiVerdict', 'aiScore', 'aiReport', 'aiCheckedAt', 'submittedAt', 'delete_status',
        'created_at', 'updated_at',
    ];

    protected $fillable = [
        'uuid', 'streamrOrderId', 'orderNumber', 'n', 'ecomPaymentId', 'kind', 'name', 'mime', 'size', 'sha256', 'bytes',
        'reference', 'refKey', 'aiVerdict', 'aiScore', 'aiReport', 'aiCheckedAt', 'submittedAt', 'delete_status',
    ];

    protected $hidden = ['bytes'];

    protected $casts = [
        'streamrOrderId' => 'integer',
        'n' => 'integer',
        'ecomPaymentId' => 'integer',
        'size' => 'integer',
        'aiScore' => 'integer',
        'aiReport' => 'array',
        'aiCheckedAt' => 'datetime',
        'submittedAt' => 'datetime',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    public function hasFile(): bool
    {
        return in_array($this->kind, ['image', 'pdf'], true);
    }

    public function extension(): string
    {
        return match ((string) $this->mime) {
            'application/pdf' => 'pdf',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/heic' => 'heic',
            'image/heif' => 'heif',
            default => 'jpg',
        };
    }
}
