<?php

namespace App\Models;

class EcomTriggerFlow extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_trigger_flows';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'usersId',
        'storeId',
        'flowName',
        'flowDescription',
        'flowType',
        'flowPriority',
        'triggerTagId',
        'flowData',
        'isActive',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'usersId' => 'integer',
        'storeId' => 'integer',
        'triggerTagId' => 'integer',
        'flowData' => 'array',
        'isActive' => 'boolean',
        'deleteStatus' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Scope to get only active flows (deleteStatus = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Scope to get only enabled flows (isActive = true)
     */
    public function scopeEnabled($query)
    {
        return $query->where('isActive', true);
    }

    /**
     * Scope to filter by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Get the user who created this flow.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    /**
     * Get the trigger tag that starts this flow.
     */
    public function triggerTag()
    {
        return $this->belongsTo(EcomTriggerTag::class, 'triggerTagId');
    }

    /**
     * Get the store associated with this flow (for SMTP settings).
     */
    public function store()
    {
        return $this->belongsTo(EcomProductStore::class, 'storeId');
    }

    /**
     * Get the SMTP settings for this flow's store.
     */
    public function getSmtpSettings()
    {
        if (!$this->storeId) {
            return null;
        }

        return EcomStoreSmtpSetting::where('storeId', $this->storeId)
            ->where('deleteStatus', 1)
            ->where('isActive', true)
            ->first();
    }

    /**
     * Get node count from flow data.
     */
    public function getNodeCountAttribute()
    {
        $flowData = $this->flowData;
        if (!$flowData || !isset($flowData['nodes'])) {
            return 0;
        }
        return count($flowData['nodes']);
    }

    /**
     * Available merge tags for email/SMS/WhatsApp nodes.
     */
    public static function getMergeTags()
    {
        return [
            // Client Details
            '{{client_name}}' => 'Client full name',
            '{{client_first_name}}' => 'Client first name',
            '{{client_email}}' => 'Client email address',
            '{{client_phone}}' => 'Client phone number',

            // Login Details
            '{{user_login}}' => 'User login/username',
            '{{user_password}}' => 'User password (if generated)',
            '{{login_url}}' => 'Login page URL',

            // Product Details
            '{{product_name}}' => 'Product name',
            '{{product_price}}' => 'Product price',
            '{{variant_name}}' => 'Variant name',
            '{{store_name}}' => 'Store name',

            // Order Details
            '{{order_number}}' => 'Order number',
            '{{order_total}}' => 'Order grand total',
            '{{order_subtotal}}' => 'Order subtotal',
            '{{order_status}}' => 'Order status',
            '{{purchase_date}}' => 'Date of purchase',

            // Discount Details
            '{{discount_name}}' => 'Applied discount name',
            '{{discount_amount}}' => 'Discount amount',
            '{{discount_code}}' => 'Discount code used',

            // Shipping Details
            '{{shipping_address}}' => 'Full shipping address',
            '{{shipping_city}}' => 'Shipping city',
            '{{shipping_state}}' => 'Shipping state/province',
            '{{shipping_zip}}' => 'Shipping postal code',
            '{{shipping_country}}' => 'Shipping country',
            '{{shipping_method}}' => 'Shipping method name',
            '{{shipping_cost}}' => 'Shipping cost',
            '{{tracking_number}}' => 'Tracking number',
            '{{tracking_url}}' => 'Tracking URL',
            '{{estimated_delivery}}' => 'Estimated delivery date',

            // Affiliate Details
            '{{affiliate_name}}' => 'Affiliate full name',
            '{{affiliate_email}}' => 'Affiliate email',
            '{{affiliate_code}}' => 'Affiliate referral code',
            '{{commission_amount}}' => 'Commission amount earned',
            '{{commission_rate}}' => 'Commission rate (%)',
            '{{affiliate_balance}}' => 'Affiliate total balance',
            '{{referral_count}}' => 'Total referral count',

            // Course/Access Details
            '{{course_name}}' => 'Course name',
            '{{access_expiry}}' => 'Access expiration date',
            '{{days_remaining}}' => 'Days until expiration',
        ];
    }
}
