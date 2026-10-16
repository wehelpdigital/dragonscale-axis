<?php

namespace App\Services\RssFeeds;

use App\Models\RssBundle;
use App\Models\RssItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * What a feed shows, and the RSS document that shows it.
 *
 * A combined feed: the stories of its sources, newest first, within
 * maxAgeDays, through each source's own filter and then the feed's, one per
 * story (by linkHash), up to maxItems.
 *
 * A compilation: what each of its combined feeds shows, merged, newest first,
 * within its own maxAgeDays and maxItems. A story two combined feeds both
 * show appears once, with both their names in `categories`.
 *
 * The document is written the way RSS.app writes its feeds (RSS 2.0, the
 * picture in <media:content> and again at the top of <description>,
 * the publisher in <dc:creator>), so anything that read an RSS.app address
 * reads this one the same way, anee.io's news roundup included. A
 * compilation's stories also carry <category>, one per combined feed.
 */
class RssBundleOutput
{
    public function items(RssBundle $bundle): Collection
    {
        if ($bundle->isCompilation()) {
            return $this->compiled($bundle);
        }
        $sources = $bundle->sources()->active()->where('isActive', true)->get()->keyBy('id');
        if ($sources->isEmpty()) {
            return collect();
        }
        $filters = $sources->map(fn ($s) => new RssKeywordFilter($s->includeKeywords, $s->excludeKeywords));
        $bundleFilter = new RssKeywordFilter($bundle->includeKeywords, $bundle->excludeKeywords);

        $out = collect();
        $seen = [];
        $query = RssItem::whereIn('sourceId', $sources->keys())
            ->where('delete_status', 'active')
            ->where('publishedAt', '>=', now()->subDays(max(1, $bundle->maxAgeDays)))
            ->orderByDesc('publishedAt')->orderByDesc('id')
            ->select(['id', 'sourceId', 'linkHash', 'itemTitle', 'itemLink', 'itemSummary', 'itemImage', 'itemPublisher', 'publishedAt', 'created_at']);
        foreach ($query->lazy(300) as $item) {
            if (isset($seen[$item->linkHash])) {
                continue;
            }
            if (! $filters[$item->sourceId]->passes($item->itemTitle, $item->itemSummary) || ! $bundleFilter->passes($item->itemTitle, $item->itemSummary)) {
                continue;
            }
            $seen[$item->linkHash] = true;
            $item->setRelation('source', $sources[$item->sourceId]);
            $out->push($item);
            if ($out->count() >= max(1, $bundle->maxItems)) {
                break;
            }
        }

        return $out;
    }

    /**
     * A compilation's stories, section by section: the newest maxItems of all
     * its combined feeds, then grouped under the first combined feed (in the
     * compilation's order) that shows them, newest first within each.
     */
    private function compiled(RssBundle $compilation): Collection
    {
        $since = now()->subDays(max(1, $compilation->maxAgeDays));
        $order = [];
        $byLink = [];
        foreach ($this->members($compilation) as $member) {
            $order[$member->bundleName] = count($order);
            foreach ($this->items($member) as $item) {
                if ($item->publishedAt && $item->publishedAt->lt($since)) {
                    continue;
                }
                if (isset($byLink[$item->linkHash])) {
                    $byLink[$item->linkHash]->setAttribute('categories', [...$byLink[$item->linkHash]->categories, $member->bundleName]);

                    continue;
                }
                $item->setAttribute('categories', [$member->bundleName]);
                $byLink[$item->linkHash] = $item;
            }
        }
        $newest = fn (RssItem $a, RssItem $b) => [$b->publishedAt?->getTimestamp() ?? 0, $b->id] <=> [$a->publishedAt?->getTimestamp() ?? 0, $a->id];
        $items = array_values($byLink);
        usort($items, $newest);
        $items = array_slice($items, 0, max(1, $compilation->maxItems));
        usort($items, fn (RssItem $a, RssItem $b) => ($order[$a->categories[0]] <=> $order[$b->categories[0]]) ?: $newest($a, $b));

        return collect($items);
    }

    private function members(RssBundle $compilation): Collection
    {
        return $compilation->members()->active()->combined()->where('isActive', true)->get();
    }

    /**
     * The feed's stories as sections for a person to read: one per combined
     * feed for a compilation (a story is listed under every combined feed that
     * shows it), or a single section for a combined feed.
     *
     * @return array<int, array{name: string, url: string, items: Collection}>
     */
    public function sections(RssBundle $bundle, Collection $items): array
    {
        if (! $bundle->isCompilation()) {
            return [['name' => $bundle->bundleName, 'url' => $bundle->outputUrl(), 'items' => $items]];
        }
        $sections = [];
        foreach ($this->members($bundle) as $member) {
            $sections[] = [
                'name' => $member->bundleName,
                'url' => $member->outputUrl(),
                'items' => $items->filter(fn (RssItem $i) => in_array($member->bundleName, (array) $i->categories, true))
                    ->sortByDesc(fn (RssItem $i) => [$i->publishedAt?->getTimestamp() ?? 0, $i->id])->values(),
            ];
        }

        return $sections;
    }

    /** The page a browser is shown at the feed's address, cached like the RSS. */
    public function cachedPage(RssBundle $bundle): string
    {
        $key = 'rss-page-' . $bundle->id . '-' . $bundle->updated_at?->getTimestamp() . '-' . RssRefresher::version();

        return Cache::remember($key, 300, function () use ($bundle) {
            $items = $this->items($bundle);

            return view('rss-feeds.public', [
                'bundle' => $bundle,
                'sections' => $this->sections($bundle, $items),
                'total' => $items->count(),
                'newestAt' => $items->max('publishedAt'),
            ])->render();
        });
    }

    /** The feed's RSS, cached until a story or a feed changes (5 minutes at most). */
    public function cachedXml(RssBundle $bundle): string
    {
        $key = 'rss-out-' . $bundle->id . '-' . $bundle->updated_at?->getTimestamp() . '-' . RssRefresher::version();

        return Cache::remember($key, 300, fn () => $this->xml($bundle, $this->items($bundle)));
    }

    public function xml(RssBundle $bundle, Collection $items): string
    {
        $w = new \XMLWriter();
        $w->openMemory();
        $w->setIndent(true);
        $w->startDocument('1.0', 'UTF-8');
        $w->startElement('rss');
        $w->writeAttribute('version', '2.0');
        $w->writeAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $w->writeAttribute('xmlns:content', 'http://purl.org/rss/1.0/modules/content/');
        $w->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $w->writeAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');
        $w->startElement('channel');
        $w->writeElement('title', $bundle->bundleName);
        $w->writeElement('link', $bundle->outputUrl());
        $w->startElement('atom:link');
        $w->writeAttribute('href', $bundle->outputUrl());
        $w->writeAttribute('rel', 'self');
        $w->writeAttribute('type', 'application/rss+xml');
        $w->endElement();
        $w->writeElement('description', $bundle->bundleDescription ?: $bundle->bundleName);
        $w->writeElement('language', 'en');
        $w->writeElement('generator', 'DS AXIS RSS Feeds');
        $w->writeElement('lastBuildDate', now()->toRssString());

        foreach ($items as $item) {
            $w->startElement('item');
            $w->writeElement('title', $item->itemTitle);
            $w->writeElement('link', $item->itemLink);
            $w->startElement('guid');
            $w->writeAttribute('isPermaLink', 'false');
            $w->text($item->linkHash);
            $w->endElement();
            if ($item->itemPublisher) {
                $w->writeElement('dc:creator', $item->itemPublisher);
            }
            foreach ((array) $item->categories as $category) {
                $w->writeElement('category', $category);
            }
            $w->writeElement('pubDate', ($item->publishedAt ?? $item->created_at)->toRssString());
            $w->writeElement('description', $this->description($item));
            if ($item->itemImage) {
                $w->startElement('media:content');
                $w->writeAttribute('medium', 'image');
                $w->writeAttribute('url', $item->itemImage);
                $w->endElement();
            }
            $w->endElement();
        }

        $w->endElement();
        $w->endElement();
        $w->endDocument();

        return $w->outputMemory();
    }

    /** RSS.app's description: the picture, then the words, as HTML. */
    private function description(RssItem $item): string
    {
        $html = '<div>';
        if ($item->itemImage) {
            $html .= '<img src="' . e($item->itemImage) . '" style="width: 100%;" />';
        }
        $paragraphs = array_filter(array_map('trim', explode("\n", (string) $item->itemSummary)));
        $html .= '<div>' . implode('<br />', array_map('e', $paragraphs)) . '</div>';

        return $html . '</div>';
    }
}
