<?php

namespace App\Models;

/**
 * The one row that says whether anee.io's free plan carries advertising at
 * all, and how: the AdSense publisher id, the words a slot wears, how often
 * the community feed shows one. Managed here (AniSystem > Ads).
 */
class AsAdSetting extends BaseModel
{
    protected $table = 'as_ad_settings';

    protected $fillable = [
        'isEnabled', 'adsenseClient', 'autoAds', 'label', 'upsell', 'feedEvery', 'deleteStatus',
    ];

    protected $casts = [
        'isEnabled' => 'boolean',
        'autoAds' => 'boolean',
        'feedEvery' => 'integer',
    ];

    /** The row, or an unsaved default when none has been made yet. */
    public static function current(): self
    {
        return static::query()->orderBy('id')->first() ?? new static([
            'isEnabled' => false, 'label' => 'Sponsored', 'upsell' => 'Go ad-free with a paid plan', 'feedEvery' => 6,
        ]);
    }
}
