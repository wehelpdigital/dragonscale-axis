<?php

namespace App\Models;

/**
 * One advertisement anee.io's free plan can carry: a Google AdSense slot, a
 * picture that links somewhere, or a script an ad network handed over.
 * Managed here (AniSystem > Ads); drawn by anee.io (App\Support\Ads there).
 */
class AsAdUnit extends BaseModel
{
    protected $table = 'as_ad_units';

    public const KINDS = [
        'adsense' => 'Google AdSense slot',
        'image' => 'Picture with a link',
        'script' => 'Ad network script',
    ];

    /** The surfaces a unit may be placed on; empty placements = all of them. */
    public const PLACEMENTS = [
        'dashboard' => 'Dashboard',
        'schedules' => 'Schedules page',
        'activities' => 'Activities board',
        'modules' => 'Other schedule modules',
        'community' => 'Community',
        'pricing' => 'Public pricing page',
        'upgrade' => 'Upgrade / renewal pages',
    ];

    protected $fillable = [
        'name', 'kind', 'placements', 'weight', 'isActive', 'startsAt', 'endsAt',
        'adClient', 'adSlot', 'adFormat', 'imagePath', 'imageAlt', 'linkUrl', 'scriptHtml',
        'impressions', 'clicks', 'sortOrder', 'deleteStatus',
    ];

    protected $casts = [
        'placements' => 'array',
        'isActive' => 'boolean',
        'startsAt' => 'datetime',
        'endsAt' => 'datetime',
        'weight' => 'integer',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'sortOrder' => 'integer',
    ];

    public function scopeActive($q)
    {
        return $q->where('as_ad_units.deleteStatus', 1);
    }

    /** The picture's address: the bucket's for a stored path, as given for a URL. */
    public function imageUrl(): ?string
    {
        if (blank($this->imagePath)) {
            return null;
        }
        if (preg_match('#^https?://#i', $this->imagePath)) {
            return $this->imagePath;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->imagePath);
    }
}
