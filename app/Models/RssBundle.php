<?php

namespace App\Models;

use Illuminate\Support\Str;

/**
 * A combined feed (RSS Feeds module): the stories of several sources, filtered
 * and newest first, served as one RSS address at /rss/{bundleToken}.xml, the
 * way RSS.app serves a bundle. The token is random so the address cannot be
 * guessed; it is the only key the public route takes.
 */
class RssBundle extends BaseModel
{
    protected $table = 'axis_rss_bundles';

    protected $fillable = [
        'usersId',
        'bundleName',
        'bundleDescription',
        'bundleToken',
        'includeKeywords',
        'excludeKeywords',
        'maxItems',
        'maxAgeDays',
        'isActive',
        'delete_status',
    ];

    protected $casts = [
        'usersId' => 'integer',
        'maxItems' => 'integer',
        'maxAgeDays' => 'integer',
        'isActive' => 'boolean',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bundle) {
            if (empty($bundle->bundleToken)) {
                $bundle->bundleToken = static::newToken();
            }
        });
    }

    public static function newToken(): string
    {
        do {
            $token = Str::random(20);
        } while (static::where('bundleToken', $token)->exists());

        return $token;
    }

    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    public function sources()
    {
        return $this->belongsToMany(RssSource::class, 'axis_rss_bundle_sources', 'bundleId', 'sourceId')->withTimestamps();
    }

    public function outputUrl(): string
    {
        return route('rss.output', ['token' => $this->bundleToken]);
    }
}
