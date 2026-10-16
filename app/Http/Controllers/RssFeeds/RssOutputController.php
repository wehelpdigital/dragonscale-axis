<?php

namespace App\Http\Controllers\RssFeeds;

use App\Http\Controllers\Controller;
use App\Models\RssBundle;
use App\Models\RssSetting;
use App\Models\RssSource;
use App\Services\RssFeeds\RssBundleOutput;
use App\Services\RssFeeds\RssRefresher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * The public address of a feed: /rss/{token}.xml, no login, the way an
 * RSS.app address works.
 *
 * Two answers at one address. A person who opens it in a browser gets a page
 * (the stories laid out by section, rss-feeds.public); everything else (an
 * app, a feed reader, anee.io) gets the RSS. The browser is told apart by
 * Sec-Fetch-Dest: document, which browsers send for a page they navigate to
 * and HTTP clients never send (over plain http, a first choice of text/html).
 * ?raw=1 always gives the RSS. The page is never stored by a shared cache
 * (no-store), so a reader can never be handed it.
 *
 * When the feed's sources are long overdue (the scheduler is not running),
 * the stories on hand are sent first and the overdue sources are read after
 * the response has gone, so the next request is fresh and this one is not
 * kept waiting. Content-Length is set so the reader can close the connection
 * as soon as it has the document.
 */
class RssOutputController extends Controller
{
    public function show(Request $request, string $token, RssBundleOutput $output)
    {
        $bundle = RssBundle::active()->where('bundleToken', $token)->where('isActive', true)->first();
        if (! $bundle) {
            abort(404);
        }

        // Browsers send Sec-Fetch-Dest only over https; over plain http, a browser
        // is the client whose first choice is text/html (feed readers ask for RSS).
        $dest = $request->header('Sec-Fetch-Dest');
        $page = ! $request->boolean('raw')
            && ($dest !== null ? $dest === 'document' : str_starts_with(strtolower((string) $request->header('Accept')), 'text/html'));
        $body = $page ? $output->cachedPage($bundle) : $output->cachedXml($bundle);

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

        return response($body, 200, [
            'Content-Type' => $page ? 'text/html; charset=UTF-8' : 'application/rss+xml; charset=UTF-8',
            'Content-Length' => (string) strlen($body),
            'Cache-Control' => $page ? 'no-store' : 'public, max-age=300',
            'Vary' => 'Sec-Fetch-Dest',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /**
     * The cron address: /rss-cron?key=..., for a cron service to call every
     * few minutes when the app's own scheduler is not running. It answers at
     * once (cron services give up after about 30 seconds) and reads the due
     * sources after the answer has gone. Calling it often is safe: a run that
     * is already going makes the next call a no-op, and a source is only read
     * when its own interval has passed.
     */
    public function cron(Request $request)
    {
        if (! hash_equals(RssSetting::cronKey(), (string) $request->query('key', $request->input('key', '')))) {
            return response()->json(['success' => false, 'message' => 'Wrong key.'], 403);
        }
        $due = RssSource::active()->where('isActive', true)->get()->filter->isDue()->count();
        $lock = Cache::lock('rss-cron', 180);
        if (! $due || ! $lock->get()) {
            return response()->json(['success' => true, 'status' => $due ? 'already running' : 'nothing due', 'due' => $due]);
        }
        app()->terminating(function () use ($lock) {
            @set_time_limit(180);
            ignore_user_abort(true);
            try {
                app(RssRefresher::class)->refreshDue(microtime(true) + 110);
                RssSetting::ranBy('cron');
            } finally {
                $lock->release();
            }
        });

        // Content-Length lets the cron service hang up as soon as it has this
        // answer, on servers that keep the connection open while the reading runs.
        $body = json_encode(['success' => true, 'status' => 'started', 'due' => $due]);

        return response($body, 200, ['Content-Type' => 'application/json', 'Content-Length' => (string) strlen($body), 'Cache-Control' => 'no-store']);
    }
}
