<?php

namespace App\Models;

/**
 * A game the studio website features (games_site_games), edited in Games >
 * Website > Games. The website reads every live and coming-soon game from
 * /api/games-site/v1/site.
 *
 *   platforms    [{key: web|android|ios|steam|console, status: live|soon, url}]
 *   features     [{title, text}]
 *   screenshots  [{url, caption}]  (an address, or a path on the public disk)
 */
class GameSiteGame extends BaseModel
{
    protected $table = 'games_site_games';

    public const STATUSES = [
        'live' => 'Live',
        'coming_soon' => 'Coming soon',
        'hidden' => 'Hidden',
    ];

    public const PLATFORMS = [
        'web' => 'Web browser',
        'android' => 'Android',
        'ios' => 'iPhone & iPad',
        'steam' => 'Steam (PC)',
        'console' => 'Consoles',
    ];

    protected $fillable = [
        'gameKey', 'slug', 'name', 'tagline', 'summary', 'description', 'genre', 'status', 'playUrl',
        'accent', 'logoImage', 'heroImage', 'platforms', 'features', 'screenshots', 'sortOrder', 'delete_status',
    ];

    protected $casts = [
        'platforms' => 'array',
        'features' => 'array',
        'screenshots' => 'array',
        'sortOrder' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /** Shown on the website: live and coming soon, in their order. */
    public function scopeOnSite($query)
    {
        return $query->active()->whereIn('status', ['live', 'coming_soon'])->orderBy('sortOrder')->orderBy('id');
    }
}
