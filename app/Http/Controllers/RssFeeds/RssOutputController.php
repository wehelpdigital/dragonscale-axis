<?php

namespace App\Http\Controllers\RssFeeds;

use App\Http\Controllers\Controller;
use App\Models\RssBundle;
use App\Services\RssFeeds\RssBundleOutput;
use App\Services\RssFeeds\RssRefresher;

/**
 * The public address of a combined feed: /rss/{token}.xml, no login, the way
 * an RSS.app address works.
 *
 * When the feed's sources are long overdue (the scheduler is not running),
 * the stories on hand are sent first and the overdue sources are read after
 * the response has gone, so the next request is fresh and this one is not
 * kept waiting. Content-Length is set so the reader can close the connection
 * as soon as it has the document.
 */
class RssOutputController extends Controller
{
    public function show(string $token, RssBundleOutput $output)
    {
        $bundle = RssBundle::active()->where('bundleToken', $token)->where('isActive', true)->first();
        if (! $bundle) {
            abort(404);
        }

        $xml = $output->cachedXml($bundle);

        $stale = $bundle->liveSources()->filter->isStale()->sortBy(fn ($s) => $s->lastFetchedAt?->getTimestamp() ?? 0);
        if ($stale->isNotEmpty()) {
            app()->terminating(function () use ($stale) {
                @set_time_limit(150);
                ignore_user_abort(true);
                $refresher = app(RssRefresher::class);
                $deadline = microtime(true) + 120;
                foreach ($stale->take(4) as $source) {
                    if (microtime(true) > $deadline - 10) {
                        break;
                    }
                    $refresher->refresh($source, $deadline);
                }
            });
        }

        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
            'Content-Length' => (string) strlen($xml),
            'Cache-Control' => 'public, max-age=300',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
