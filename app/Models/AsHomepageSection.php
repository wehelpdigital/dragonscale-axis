<?php

namespace App\Models;

class AsHomepageSection extends BaseModel
{
    protected $table = 'as_homepage_sections';

    protected $fillable = [
        'sectionKey',
        'sectionName',
        'sectionIcon',
        'isEnabled',
        'sectionOrder',
        'settings'
    ];

    protected $casts = [
        'isEnabled' => 'boolean',
        'sectionOrder' => 'integer',
        'settings' => 'array'
    ];

    /**
     * Get items for this section
     */
    public function items()
    {
        return $this->hasMany(AsHomepageItem::class, 'sectionId')
            ->where('deleteStatus', 'active')
            ->orderBy('itemOrder');
    }

    /**
     * Get active items
     */
    public function activeItems()
    {
        return $this->hasMany(AsHomepageItem::class, 'sectionId')
            ->where('deleteStatus', 'active')
            ->where('isActive', true)
            ->orderBy('itemOrder');
    }

    /**
     * Scope for enabled sections
     */
    public function scopeEnabled($query)
    {
        return $query->where('isEnabled', true);
    }

    /**
     * Get section by key
     */
    public static function getByKey(string $key)
    {
        return static::where('sectionKey', $key)->first();
    }

    /**
     * Get setting value
     */
    public function getSetting(string $key, $default = null)
    {
        $settings = $this->settings ?? [];
        return $settings[$key] ?? $default;
    }

    /**
     * Set setting value
     */
    public function setSetting(string $key, $value)
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->settings = $settings;
        return $this;
    }
}
