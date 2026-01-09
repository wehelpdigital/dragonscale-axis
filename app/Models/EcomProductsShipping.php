<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcomProductsShipping extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_products_shipping';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shippingName',
        'shippingDescription',
        'shippingType',
        'restrictionType',
        'defaultPrice',
        'defaultMaxQuantity',
        'isActive',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'defaultPrice' => 'decimal:2',
        'defaultMaxQuantity' => 'integer',
        'isActive' => 'boolean',
        'deleteStatus' => 'boolean',
        'shippingType' => 'array', // JSON array of shipping types
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Available shipping types
     */
    public const SHIPPING_TYPES = [
        'Regular' => 'Regular',
        'Cash on Delivery' => 'Cash on Delivery',
        'Cash on Pickup' => 'Cash on Pickup',
    ];

    /**
     * Get badge class for shipping type - returns HTML for multiple badges
     */
    public function getShippingTypeBadgeClassAttribute()
    {
        // For backwards compatibility, handle both string and array
        $types = $this->getShippingTypesArray();

        if (count($types) === 1) {
            return $this->getBadgeClassForType($types[0]);
        }

        // Return primary badge class for multiple types (will be rendered individually in view)
        return 'bg-primary text-white';
    }

    /**
     * Get the shipping types as an array
     */
    public function getShippingTypesArray(): array
    {
        $types = $this->shippingType;

        // Handle legacy string values
        if (is_string($types)) {
            return [$types];
        }

        // Handle array values
        if (is_array($types)) {
            return $types;
        }

        // Default to Regular if empty
        return ['Regular'];
    }

    /**
     * Check if this shipping method supports a specific type
     */
    public function hasShippingType(string $type): bool
    {
        return in_array($type, $this->getShippingTypesArray());
    }

    /**
     * Get badge class for a specific shipping type
     */
    public function getBadgeClassForType(string $type): string
    {
        return match($type) {
            'Cash on Delivery' => 'bg-info text-white',
            'Cash on Pickup' => 'bg-warning text-dark',
            default => 'bg-primary text-white',
        };
    }

    /**
     * Get formatted shipping types for display (comma-separated or badges HTML)
     */
    public function getFormattedShippingTypesAttribute(): string
    {
        return implode(', ', $this->getShippingTypesArray());
    }

    /**
     * Scope to get only active shipping methods (isActive = 1 and deleteStatus = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('isActive', 1)->where('deleteStatus', 1);
    }

    /**
     * Get formatted default price attribute
     */
    public function getFormattedDefaultPriceAttribute()
    {
        return $this->defaultPrice ? '₱' . number_format($this->defaultPrice, 2) : '₱0.00';
    }

    /**
     * Get excerpt of shipping description
     */
    public function getDescriptionExcerptAttribute()
    {
        if (!$this->shippingDescription) {
            return 'No description';
        }

        $description = strip_tags($this->shippingDescription);
        return strlen($description) > 50 ? substr($description, 0, 50) . '...' : $description;
    }

    /**
     * Get badge class for restriction type
     */
    public function getRestrictionTypeBadgeClassAttribute()
    {
        return match($this->restrictionType) {
            'stores' => 'bg-info text-white',
            'products' => 'bg-warning text-dark',
            default => 'bg-success text-white',
        };
    }

    /**
     * Get display text for restriction type
     */
    public function getRestrictionTypeDisplayAttribute()
    {
        return match($this->restrictionType) {
            'stores' => 'Specific Stores',
            'products' => 'Specific Products',
            default => 'All Products',
        };
    }

    /**
     * Get the restrictions for this shipping method.
     */
    public function restrictions()
    {
        return $this->hasMany(EcomProductShippingRestriction::class, 'shippingId')
            ->where('deleteStatus', 1);
    }
}
