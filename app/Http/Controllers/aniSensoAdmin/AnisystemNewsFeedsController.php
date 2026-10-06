<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * AniSystem > Latest in Agriculture (2026-10-07): the RSS feeds anee.io's
 * farm news roundups are written from, and the roundups' own log.
 *
 * The roundup itself lives in anee.io (App\Services\NewsRoundup): every few
 * days it reads these feeds, keeps each story once (as_news_items), and has
 * Anee write a roundup of the new ones under /blog. Nothing is written when
 * there is no new story, and no story is featured twice.
 *
 * Here: the feeds (add, rename, pause, remove), how often a roundup may be
 * written (news.every_days), the cron address to give a cron service, and
 * "Write one now", which calls that address with force=1.
 */
class AnisystemNewsFeedsController extends Controller
{
    public function index()
    {
        $ready = Schema::hasTable('as_news_feeds');
        $feeds = collect();
        $runs = collect();
        $stats = ['items' => 0, 'featured' => 0, 'waiting' => 0, 'roundups' => 0];
        $cronUrl = null;
        $every = 3;
        if ($ready) {
            $counts = DB::table('as_news_items')->selectRaw('feedId, count(*) as n, sum(case when featuredPageId is not null then 1 else 0 end) as f')
                ->groupBy('feedId')->get()->keyBy('feedId');
            $feeds = DB::table('as_news_feeds')->where('deleteStatus', 1)->orderBy('id')->get()
                ->each(function ($f) use ($counts) {
                    $f->kept = (int) ($counts[$f->id]->n ?? 0);
                    $f->featured = (int) ($counts[$f->id]->f ?? 0);
                });
            $runs = DB::table('as_news_runs as r')->leftJoin('as_site_pages as p', 'p.id', '=', 'r.pageId')
                ->orderByDesc('r.id')->limit(25)->get(['r.*', 'p.slug', 'p.section', 'p.title as pageTitle']);
            $stats = [
                'items' => DB::table('as_news_items')->count(),
                'featured' => DB::table('as_news_items')->whereNotNull('featuredPageId')->count(),
                'waiting' => DB::table('as_news_items')->whereNull('featuredPageId')->where('publishedAt', '>=', now()->subDays(10))->count(),
                'roundups' => DB::table('as_news_runs')->where('status', 'created')->count(),
            ];
            $key = (string) DB::table('as_site_settings')->where('key', 'news.cron_key')->value('value');
            $every = (int) (DB::table('as_site_settings')->where('key', 'news.every_days')->value('value') ?: 3);
            $cronUrl = $key !== '' ? self::anee('/cron/news-roundup') . '?key=' . $key : null;
        }

        return view('aniSensoAdmin.news-feeds.index', compact('ready', 'feeds', 'runs', 'stats', 'cronUrl', 'every'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['url' => 'required|url|max:600', 'label' => 'nullable|string|max:190']);
        $url = trim($data['url']);
        if (DB::table('as_news_feeds')->where('urlHash', sha1($url))->where('deleteStatus', 1)->exists()) {
            return back()->with('error', 'That feed is already on the list.');
        }
        // A feed is read before it is kept: an address that gives no RSS is refused.
        $title = null;
        try {
            $res = Http::timeout(20)->withHeaders(['User-Agent' => 'anee.io news roundup'])->get($url);
            $xml = $res->successful() ? @simplexml_load_string($res->body(), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET) : false;
            if (! $xml || ! isset($xml->channel)) {
                return back()->withInput()->with('error', 'That address did not answer with an RSS feed. Check it and try again.');
            }
            $title = trim((string) $xml->channel->title);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'That address could not be reached: ' . Str::limit($e->getMessage(), 120));
        }
        $label = trim((string) ($data['label'] ?? '')) ?: Str::limit($title ?: parse_url($url, PHP_URL_HOST), 180, '');
        $existing = DB::table('as_news_feeds')->where('urlHash', sha1($url))->first();
        if ($existing) {
            DB::table('as_news_feeds')->where('id', $existing->id)->update(['deleteStatus' => 1, 'isActive' => 1, 'label' => $label, 'updated_at' => now()]);
        } else {
            DB::table('as_news_feeds')->insert(['url' => $url, 'urlHash' => sha1($url), 'label' => $label, 'isActive' => 1, 'deleteStatus' => 1,
                'created_at' => now(), 'updated_at' => now()]);
        }

        return back()->with('success', 'Feed added: ' . $label . '. Its stories are read at the next roundup.');
    }

    public function update(Request $request)
    {
        $data = $request->validate(['id' => 'required|integer', 'label' => 'nullable|string|max:190', 'isActive' => 'nullable|boolean']);
        $row = [];
        if ($request->has('label')) {
            $row['label'] = trim((string) $data['label']) ?: null;
        }
        if ($request->has('isActive')) {
            $row['isActive'] = (bool) $data['isActive'];
        }
        if ($row) {
            DB::table('as_news_feeds')->where('id', $data['id'])->update($row + ['updated_at' => now()]);
        }

        return $request->expectsJson() ? response()->json(['success' => true]) : back()->with('success', 'Feed saved.');
    }

    public function destroy(Request $request)
    {
        $id = (int) $request->input('id');
        DB::table('as_news_feeds')->where('id', $id)->update(['deleteStatus' => 0, 'isActive' => 0, 'updated_at' => now()]);

        return back()->with('success', 'Feed removed. The stories it already gave stay in the log.');
    }

    public function settings(Request $request)
    {
        $data = $request->validate(['every' => 'required|integer|min:1|max:30']);
        DB::table('as_site_settings')->updateOrInsert(['key' => 'news.every_days'], ['value' => (string) $data['every'], 'updated_at' => now(), 'created_at' => now()]);

        return back()->with('success', 'A roundup is now written at most every ' . $data['every'] . ' ' . ($data['every'] == 1 ? 'day' : 'days') . '.');
    }

    /** "Write one now": anee.io's own cron address, forced. */
    public function run()
    {
        $key = (string) DB::table('as_site_settings')->where('key', 'news.cron_key')->value('value');
        if ($key === '') {
            return back()->with('error', 'anee.io has not made its cron key yet. It appears once its latest release has run.');
        }
        try {
            $res = Http::timeout(30)->acceptJson()->get(self::anee('/cron/news-roundup'), ['key' => $key, 'force' => 1, 'by' => 'mother']);
            $j = $res->json() ?: [];
            if (! $res->successful()) {
                return back()->with('error', 'anee.io answered ' . $res->status() . ': ' . ($j['message'] ?? 'no message'));
            }
            $status = $j['status'] ?? '';

            return back()->with($status === 'skipped' ? 'error' : 'success', match ($status) {
                'started' => 'Anee is reading the feeds and writing. Refresh this page in a minute or two to see the result below.',
                'created' => 'A roundup was written: ' . ($j['title'] ?? ''),
                'skipped' => 'Not written: ' . ($j['reason'] ?? 'no new stories'),
                default => 'anee.io answered: ' . ($j['reason'] ?? $j['message'] ?? $status),
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'anee.io could not be reached: ' . Str::limit($e->getMessage(), 160));
        }
    }

    private static function anee(string $path): string
    {
        return rtrim((string) config('anisystem.url'), '/') . $path;
    }
}
