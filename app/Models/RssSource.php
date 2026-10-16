<?php

namespace App\Models;

use Illuminate\Support\Carbon;

/**
 * One place stories come from (RSS Feeds module).
 *
 *   rss          any RSS or Atom feed
 *   google_news  a Google News search; its links are turned back into the
 *                publisher's own article address before they are kept
 *   webpage      a news page with no feed: its article links are the stories
 *   page_watch   a page that is rewritten in place (PAGASA's forecast): each
 *                new version of the watched part is one story
 *
 * sourceSettings holds what only one type needs: region/language/withinDays
 * for google_news, linkSelector/linkContains for webpage, contentSelector
 * for page_watch.
 */
class RssSource extends BaseModel
{
    protected $table = 'axis_rss_sources';

    public const TYPES = [
        'rss' => 'RSS feed',
        'google_news' => 'Google News search',
        'webpage' => 'News page (no feed)',
        'page_watch' => 'Page watch',
    ];

    protected $fillable = [
        'usersId',
        'sourceName',
        'sourceType',
        'sourceUrl',
        'searchQuery',
        'sourceSettings',
        'includeKeywords',
        'excludeKeywords',
        'fetchDetails',
        'refreshMinutes',
        'isActive',
        'lastFetchedAt',
        'lastStatus',
        'lastMessage',
        'lastItemCount',
        'failCount',
        'delete_status',
    ];

    protected $casts = [
        'usersId' => 'integer',
        'sourceSettings' => 'array',
        'fetchDetails' => 'boolean',
        'refreshMinutes' => 'integer',
        'isActive' => 'boolean',
        'lastFetchedAt' => 'datetime',
        'lastItemCount' => 'integer',
        'failCount' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    public function items()
    {
        return $this->hasMany(RssItem::class, 'sourceId');
    }

    public function bundles()
    {
        return $this->belongsToMany(RssBundle::class, 'axis_rss_bundle_sources', 'sourceId', 'bundleId')->withTimestamps();
    }

    public function setting(string $key, $default = null)
    {
        return $this->sourceSettings[$key] ?? $default;
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->sourceType] ?? $this->sourceType;
    }

    /** Where the source is read from, as a person would describe it. */
    public function target(): string
    {
        return $this->sourceType === 'google_news' ? (string) $this->searchQuery : (string) $this->sourceUrl;
    }

    /**
     * Whether the source is due a read. A source that keeps failing waits
     * longer each time (up to a day), so a dead site is not hammered.
     */
    public function isDue(?Carbon $now = null): bool
    {
        if (! $this->lastFetchedAt) {
            return true;
        }
        $minutes = max(5, $this->refreshMinutes);
        if ($this->failCount >= 3) {
            $minutes = min(1440, $minutes * (2 ** min(6, $this->failCount - 2)));
        }

        return $this->lastFetchedAt->copy()->addMinutes($minutes)->lte($now ?? now());
    }

    /** Overdue by much more than its interval: the scheduler is probably not running. */
    public function isStale(): bool
    {
        return ! $this->lastFetchedAt || $this->lastFetchedAt->copy()->addMinutes(max(5, $this->refreshMinutes) * 3 + 10)->isPast();
    }
}
