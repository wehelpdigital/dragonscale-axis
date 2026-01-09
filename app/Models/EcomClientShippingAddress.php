<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class EcomClientShippingAddress extends BaseModel
{
    use HasFactory;

    protected $table = 'ecom_client_shipping_addresses';

    protected $fillable = [
        'clientId',
        'orderId',
        'addressLabel',
        'firstName',
        'middleName',
        'lastName',
        'phoneNumber',
        'emailAddress',
        'houseNumber',
        'street',
        'zone',
        'municipality',
        'province',
        'zipCode',
        'deleteStatus',
    ];

    protected $casts = [
        'clientId' => 'integer',
        'orderId' => 'integer',
        'deleteStatus' => 'integer',
    ];

    /**
     * Scope for active records (soft delete).
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Get the client that owns this address.
     */
    public function client()
    {
        return $this->belongsTo(ClientAllDatabase::class, 'clientId', 'id');
    }

    /**
     * Get the order that created this address.
     */
    public function order()
    {
        return $this->belongsTo(EcomOrder::class, 'orderId', 'id');
    }

    /**
     * Get the full recipient name.
     */
    public function getFullNameAttribute()
    {
        return trim(implode(' ', array_filter([
            $this->firstName,
            $this->middleName,
            $this->lastName
        ])));
    }

    /**
     * Get the full address.
     */
    public function getFullAddressAttribute()
    {
        return trim(implode(', ', array_filter([
            $this->houseNumber,
            $this->street,
            $this->zone ? 'Zone ' . $this->zone : null,
            $this->municipality,
            $this->province,
            $this->zipCode
        ])));
    }
}
