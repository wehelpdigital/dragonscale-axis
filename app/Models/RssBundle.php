<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * A feed served at /rss/{bundleToken}.xml (RSS Feeds module), the way RSS.app
 * serves a bundle. Two kinds, by bundleType:
 *
 *   combined     the stories of several sources, through its own keyword
 *                filter, newest first
 *   compilation  the stories of several combined feeds, each story tagged
 *                with the combined feeds it came from; named for what it is
 *                used for (usedFor), so feeds can be grouped by their use
 *
 * The token is random so the address cannot be guessed; it is the only key
 * the public route takes.
 */
class RssBundle extends BaseModel
{
    protected $table = 'axis_rss_bundles';

    protected $fillable = [
        'usersId',
        'bundleName',
        'bundleDescription',
        'bundleToken',
        'bundleType',
        'usedFor',
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

    public function scopeCombined($query)
    {
        return $query->where('bundleType', 'combined');
    }

    public function scopeCompilations($query)
    {
        return $query->where('bundleType', 'compilation');
    }

    public function isCompilation(): bool
    {
        return $this->bundleType === 'compilation';
    }

    public function sources()
    {
        return $this->belongsToMany(RssSource::class, 'axis_rss_bundle_sources', 'bundleId', 'sourceId')->withTimestamps();
    }

    /** A compilation's combined feeds, in the order its sections are served. */
    public function members()
    {
        return $this->belongsToMany(RssBundle::class, 'axis_rss_bundle_members', 'bundleId', 'memberBundleId')
            ->withPivot('memberOrder')->withTimestamps()
            ->orderBy('axis_rss_bundle_members.memberOrder')->orderBy('axis_rss_bundles.bundleName');
    }

    /** The compilations a combined feed is part of. */
    public function partOfCompilations()
    {
        return $this->belongsToMany(RssBundle::class, 'axis_rss_bundle_members', 'memberBundleId', 'bundleId')->withTimestamps();
    }

    /** The live sources the feed reads: its own, or its combined feeds' for a compilation. */
    public function liveSources(): Collection
    {
        if (! $this->isCompilation()) {
            return $this->sources()->active()->where('isActive', true)->get();
        }

        return RssSource::active()->where('isActive', true)
            ->whereIn('id', function ($q) {
                $q->select('sourceId')->from('axis_rss_bundle_sources')
                    ->whereIn('bundleId', $this->members()->active()->where('isActive', true)->pluck('axis_rss_bundles.id'));
            })->get();
    }

    public function outputUrl(): string
    {
        return route('rss.output', ['token' => $this->bundleToken]);
    }
}
