<?php

namespace App\Models;

/**
 * Streamr's identity of one /ecom-orders order (streamr_orders): its uuid in
 * Streamr, what was bought, the method, the AI verdict and whether Streamr
 * approved it on its own. The order itself is the ecom_orders row.
 */
class StreamrOrder extends BaseModel
{
    protected $table = 'streamr_orders';

    public const METHODS = [
        'manual_gcash' => 'GCash QR',
        'manual_maya' => 'Maya',
        'manual_bank' => 'Bank transfer',
    ];

    public const KINDS = [
        'plan' => 'Plan',
        'credits' => 'Credits',
    ];

    protected $fillable = [
        'uuid', 'orderNumber', 'ecomOrderId', 'streamrUserId', 'kind', 'itemKey', 'sku', 'variantName', 'total',
        'method', 'crmClientId', 'aiVerdict', 'aiScore', 'autoApproved', 'decidedBy', 'proofsCount', 'delete_status',
    ];

    protected $casts = [
        'ecomOrderId' => 'integer',
        'streamrUserId' => 'integer',
        'total' => 'decimal:2',
        'crmClientId' => 'integer',
        'aiScore' => 'integer',
        'autoApproved' => 'boolean',
        'proofsCount' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    public function ecomOrder()
    {
        return $this->belongsTo(EcomOrder::class, 'ecomOrderId');
    }

    public function proofs()
    {
        return $this->hasMany(StreamrPaymentProof::class, 'streamrOrderId');
    }
}
