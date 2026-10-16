<?php

namespace App\Services\RssFeeds;

use App\Models\RssItem;
use App\Models\RssSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Keeps the stories current: reads a source, keeps what is new, then fills in
 * what the new stories are missing (picture, summary, real date) from their
 * own article pages.
 *
 * The two steps are apart on purpose. A Google News search or a news page
 * gives a headline and an address and nothing else; fetching every article
 * page in the same breath would make one read take minutes. So a story is
 * kept at once, marked detailsFetchedAt = null, and the details pass fills a
 * few per read, newest first, skipping stories the source's own filter would
 * never show anyway.
 *
 * Every read runs against a deadline (microtime) so the scheduler, "Refresh
 * now" and the feed address's own catch-up all stop in time.
 */
class RssRefresher
{
    /** Stories older than this are deleted. */
    public const KEEP_DAYS = 45;

    /** Article pages read for details per source per read, at most. */
    public int $detailsLimit = 10;

    public function __construct(private RssSourceReader $reader)
    {
    }

    /**
     * Reads every source that is due, oldest read first, until the deadline.
     * $report(source, result) is told after each one.
     *
     * @return array<int, array> source id => result
     */
    public function refreshDue(float $deadline, bool $all = false, ?callable $report = null): array
    {
        $results = [];
        $sources = RssSource::active()->where('isActive', true)->get()
            ->filter(fn (RssSource $s) => $all || $s->isDue())
            ->sortBy(fn (RssSource $s) => $s->lastFetchedAt?->getTimestamp() ?? 0);
        foreach ($sources as $source) {
            if (microtime(true) > $deadline - 5) {
                break;
            }
            $results[$source->id] = $this->refresh($source, $deadline);
            if ($report) {
                $report($source, $results[$source->id]);
            }
        }
        $this->prune();

        return $results;
    }

    /** @return array{ok: bool, new: int, message: string} */
    public function refresh(RssSource $source, float $deadline): array
    {
        $lock = Cache::lock('rss-source-' . $source->id, 300);
        if (! $lock->get()) {
            return ['ok' => true, 'new' => 0, 'message' => 'Already being read right now.'];
        }
        try {
            // Marked read before reading: a run that dies on this source (out of
            // memory, killed) must not put it first in line on every later run.
            $source->forceFill(['lastFetchedAt' => now()])->save();
            $known = RssItem::where('sourceId', $source->id)->pluck('guidHash')->flip();
            $read = $this->reader->read($source, $deadline, fn (string $guid) => isset($known[sha1($guid)]));
            $new = $this->keep($source, $read['items'], $known);
            $filled = $source->fetchDetails ? $this->fillDetails($source, $deadline) : 0;
            $message = $read['message'] . ' ' . ($new ? $new . ' new.' : 'Nothing new.');
            $source->forceFill([
                'lastFetchedAt' => now(),
                'lastStatus' => 'ok',
                'lastMessage' => Str::limit($message, 490, ''),
                'lastItemCount' => count($read['items']),
                'failCount' => 0,
            ])->save();
            if ($new || $filled) {
                self::touch();
            }

            return ['ok' => true, 'new' => $new, 'message' => $message];
        } catch (\Throwable $e) {
            $source->forceFill([
                'lastFetchedAt' => now(),
                'lastStatus' => 'failed',
                'lastMessage' => Str::limit($e->getMessage(), 490, ''),
                'failCount' => $source->failCount + 1,
            ])->save();

            return ['ok' => false, 'new' => 0, 'message' => $e->getMessage()];
        } finally {
            $lock->release();
        }
    }

    /** Saves the stories the source has not given before. */
    private function keep(RssSource $source, array $items, $known): int
    {
        $rows = [];
        $now = now();
        foreach ($items as $item) {
            $guidHash = sha1((string) $item['guid']);
            if (isset($known[$guidHash]) || isset($rows[$guidHash]) || ! preg_match('#^https?://#i', (string) $item['link'])) {
                continue;
            }
            $published = $item['publishedAt'] instanceof Carbon ? $item['publishedAt'] : null;
            // A date in the future is a feed's mistake; a missing one means "now".
            if (! $published || $published->gt($now)) {
                $published = $now;
            }
            $image = $item['image'] ?? null;
            $rows[$guidHash] = [
                'sourceId' => $source->id,
                'guidHash' => $guidHash,
                'linkHash' => sha1(RssItem::canonicalLink($item['link'])),
                'itemTitle' => Str::limit((string) $item['title'], 490, ''),
                'itemLink' => Str::limit((string) $item['link'], 1000, ''),
                'itemSummary' => ($item['summary'] ?? '') !== '' ? Str::limit((string) $item['summary'], 4000, '') : null,
                'itemImage' => $image && preg_match('#^https?://#i', $image) && strlen($image) <= 1000 ? $image : null,
                'itemPublisher' => ($item['publisher'] ?? '') !== '' ? Str::limit((string) $item['publisher'], 180, '') : null,
                'publishedAt' => $published->format('Y-m-d H:i:s'),
                // A watched page is its own article; there is nothing more to fetch.
                'detailsFetchedAt' => $source->sourceType === 'page_watch' ? $now->format('Y-m-d H:i:s') : null,
                'delete_status' => 'active',
                'created_at' => $now->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ];
        }
        $kept = 0;
        foreach (array_chunk(array_values($rows), 100) as $chunk) {
            $kept += RssItem::insertOrIgnore($chunk);
        }

        return $kept;
    }

    /**
     * Fills the newest stories that are missing a picture or a summary from
     * their own pages. A news page's story also takes the page's real title
     * and date, since all the list page gave was the link's words.
     */
    private function fillDetails(RssSource $source, float $deadline): int
    {
        $filter = new RssKeywordFilter($source->includeKeywords, $source->excludeKeywords);
        $pending = RssItem::where('sourceId', $source->id)->whereNull('detailsFetchedAt')->where('delete_status', 'active')
            ->orderByDesc('publishedAt')->limit($this->detailsLimit * 3)->get();
        $filled = 0;
        $tried = 0;
        foreach ($pending as $item) {
            $complete = $item->itemImage && $item->itemSummary && $source->sourceType !== 'webpage';
            if ($complete || ! $filter->passes($item->itemTitle, $item->itemSummary)) {
                $item->forceFill(['detailsFetchedAt' => now()])->save();

                continue;
            }
            if ($tried >= $this->detailsLimit || microtime(true) > $deadline - 8) {
                break;
            }
            $tried++;
            // Marked first, for the same reason as the source: a page that kills
            // the run is not tried again.
            $item->forceFill(['detailsFetchedAt' => now()])->save();
            $update = [];
            try {
                $res = RssHttp::get($item->itemLink, 8, ['Accept' => 'text/html,application/xhtml+xml;q=0.9,*/*;q=0.8']);
                if ($res->successful() && str_contains(strtolower((string) $res->header('Content-Type')), 'html')) {
                    $d = RssHtml::details(RssHttp::text($res), $item->itemLink);
                    if (! $item->itemImage && $d['image'] && strlen($d['image']) <= 1000 && preg_match('#^https?://#i', $d['image'])) {
                        $update['itemImage'] = $d['image'];
                    }
                    if (! $item->itemSummary && $d['description']) {
                        $update['itemSummary'] = Str::limit(RssHtml::plain($d['description']), 4000, '');
                    }
                    if ($source->sourceType === 'webpage') {
                        if ($d['title']) {
                            $update['itemTitle'] = Str::limit(RssHtml::plain($d['title']), 490, '');
                        }
                        if ($d['publishedAt']) {
                            try {
                                $date = Carbon::parse($d['publishedAt'])->timezone(config('app.timezone'));
                                if ($date->lte(now())) {
                                    $update['publishedAt'] = $date;
                                }
                            } catch (\Throwable $e) {
                            }
                        }
                    }
                    $filled++;
                }
            } catch (\Throwable $e) {
                // A page that will not open keeps what the feed gave.
            }
            if ($update) {
                $item->forceFill($update)->save();
            }
        }

        return $filled;
    }

    /** Deletes stories past KEEP_DAYS, at most once an hour. */
    public function prune(): void
    {
        if (! Cache::add('rss-pruned', 1, 3600)) {
            return;
        }
        $cutoff = now()->subDays(self::KEEP_DAYS);
        RssItem::where('publishedAt', '<', $cutoff)->where('created_at', '<', $cutoff)->limit(5000)->delete();
    }

    /** Tells the cached feed outputs that something changed. */
    public static function touch(): void
    {
        Cache::forever('rss-version', (int) Cache::get('rss-version', 0) + 1);
    }

    public static function version(): int
    {
        return (int) Cache::get('rss-version', 0);
    }
}
