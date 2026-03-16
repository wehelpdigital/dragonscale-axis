<?php

namespace App\Models;

class AsHomepageItem extends BaseModel
{
    protected $table = 'as_homepage_items';

    protected $fillable = [
        'sectionId',
        'itemType',
        'title',
        'subtitle',
        'description',
        'image',
        'image2',
        'icon',
        'linkUrl',
        'linkText',
        'extraData',
        'itemOrder',
        'isActive',
        'deleteStatus'
    ];

    protected $casts = [
        'extraData' => 'array',
        'itemOrder' => 'integer',
        'isActive' => 'boolean'
    ];

    /**
     * Get the section this item belongs to
     */
    public function section()
    {
        return $this->belongsTo(AsHomepageSection::class, 'sectionId');
    }

    /**
     * Scope for active items
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 'active');
    }

    /**
     * Scope for enabled items
     */
    public function scopeEnabled($query)
    {
        return $query->where('isActive', true);
    }

    /**
     * Get items by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('itemType', $type);
    }

    /**
     * Get extra data value
     */
    public function getExtra(string $key, $default = null)
    {
        $data = $this->extraData ?? [];
        return $data[$key] ?? $default;
    }

    /**
     * Set extra data value
     */
    public function setExtra(string $key, $value)
    {
        $data = $this->extraData ?? [];
        $data[$key] = $value;
        $this->extraData = $data;
        return $this;
    }

    /**
     * Get the image URL attribute
     */
    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        // If it's already a full URL, return it
        if (str_starts_with($this->image, 'http')) {
            return $this->image;
        }

        // Return relative path (assumes starts with /)
        return $this->image;
    }
}
