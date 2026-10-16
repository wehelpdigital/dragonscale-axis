<?php

namespace App\Models;

/**
 * One story a source gave (RSS Feeds module). Kept once per source by
 * guidHash; linkHash is the same story's address without tracking bits, so a
 * combined feed shows a story once even when two sources both carry it.
 * delete_status 'deleted' hides a story from every combined feed.
 */
class RssItem extends BaseModel
{
    protected $table = 'axis_rss_items';

    protected $fillable = [
        'sourceId',
        'guidHash',
        'linkHash',
        'itemTitle',
        'itemLink',
        'itemSummary',
        'itemImage',
        'itemPublisher',
        'publishedAt',
        'detailsFetchedAt',
        'delete_status',
    ];

    protected $casts = [
        'sourceId' => 'integer',
        'publishedAt' => 'datetime',
        'detailsFetchedAt' => 'datetime',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    public function source()
    {
        return $this->belongsTo(RssSource::class, 'sourceId');
    }

    /** A link without its tracking bits, fragment or trailing slash. */
    public static function canonicalLink(string $link): string
    {
        $p = parse_url($link);
        $q = [];
        parse_str($p['query'] ?? '', $q);
        $q = array_filter($q, fn ($k) => ! preg_match('/^(utm_|fbclid|gclid|mc_|ref$|mibextid|oc$)/i', (string) $k), ARRAY_FILTER_USE_KEY);
        ksort($q);

        return strtolower(preg_replace('/^www\./', '', (string) ($p['host'] ?? ''))) . rtrim((string) ($p['path'] ?? ''), '/') . ($q ? '?' . http_build_query($q) : '');
    }
}
