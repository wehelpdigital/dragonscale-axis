<?php

namespace App\Services\RssFeeds;

use App\Models\RssSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Reads one source and gives back its stories, newest first, as plain arrays:
 * guid, title, link, summary, image, publisher, publishedAt (Carbon|null).
 *
 * It does not save anything, so the same code answers "Test" in the source
 * form and the scheduled refresh. $isKnown(guid) lets the refresh skip the
 * costly part of a story it already has (for Google News, turning the Google
 * link back into the publisher's address: two requests to Google per story).
 */
class RssSourceReader
{
    /**
     * A newsroom's own name, from its address. A feed's channel title is not
     * enough: ABS-CBN's says "Push.com.ph", Philstar's "philstar.com - RSS
     * Business Agriculture".
     */
    private const PUBLISHERS = [
        'inquirer.net' => 'Inquirer', 'tribune.net.ph' => 'Daily Tribune', 'rappler.com' => 'Rappler', 'gmanetwork.com' => 'GMA News',
        'manilatimes.net' => 'The Manila Times', 'philstar.com' => 'Philstar', 'mb.com.ph' => 'Manila Bulletin', 'pna.gov.ph' => 'Philippine News Agency',
        'abs-cbn.com' => 'ABS-CBN News', 'bworldonline.com' => 'BusinessWorld', 'businessmirror.com.ph' => 'BusinessMirror', 'pageone.ph' => 'PageOne',
        'sunstar.com.ph' => 'SunStar', 'manilastandard.net' => 'Manila Standard', 'pia.gov.ph' => 'Philippine Information Agency',
        'da.gov.ph' => 'Department of Agriculture', 'dar.gov.ph' => 'Department of Agrarian Reform', 'philrice.gov.ph' => 'PhilRice',
        'pagasa.dost.gov.ph' => 'PAGASA', 'ptvnews.ph' => 'PTV News', 'dailyguardian.com.ph' => 'Daily Guardian', 'irri.org' => 'IRRI',
        'onenews.ph' => 'One News', 'bilyonaryo.com' => 'Bilyonaryo', 'agriculture.com.ph' => 'Agriculture Monthly', 'fao.org' => 'FAO',
        'reuters.com' => 'Reuters', 'bloomberg.com' => 'Bloomberg', 'bangkokpost.com' => 'Bangkok Post', 'scmp.com' => 'South China Morning Post',
    ];

    /** Google links turned into publisher links per read, at most. */
    public int $decodeLimit = 25;

    /** Stories a news page (webpage source) gives per read, at most. */
    public int $pageLinkLimit = 40;

    /** Set per feed read: the feed writes Manila time labelled as UTC. */
    private bool $localTimes = false;

    public function read(RssSource $source, ?float $deadline = null, ?callable $isKnown = null): array
    {
        $deadline ??= microtime(true) + 60;
        $isKnown ??= fn (string $guid) => false;

        return match ($source->sourceType) {
            'rss' => $this->feed($source, $this->body((string) $source->sourceUrl)),
            'google_news' => $this->googleNews($source, $deadline, $isKnown),
            'webpage' => $this->webpage($source),
            'page_watch' => $this->pageWatch($source),
            default => throw new \RuntimeException('Unknown source type.'),
        };
    }

    public static function googleNewsUrl(RssSource $source): string
    {
        $q = trim((string) $source->searchQuery);
        if (! preg_match('/\bwhen:\d+[hdy]\b/i', $q)) {
            $q .= ' when:' . max(1, min(30, (int) $source->setting('withinDays', 7))) . 'd';
        }
        $region = strtoupper((string) $source->setting('region', 'PH')) ?: 'PH';
        $lang = strtolower((string) $source->setting('language', 'en')) ?: 'en';

        return 'https://news.google.com/rss/search?' . http_build_query(['q' => $q, 'hl' => $lang . '-' . $region, 'gl' => $region, 'ceid' => $region . ':' . $lang]);
    }

    // ------------------------------------------------------------------
    // RSS and Atom
    // ------------------------------------------------------------------

    private function body(string $url): string
    {
        $res = RssHttp::get($url, 25, ['Accept' => 'application/rss+xml, application/atom+xml, application/xml;q=0.9, text/xml;q=0.9, */*;q=0.8']);
        if (! $res->successful()) {
            throw new \RuntimeException($this->refusal($res->status(), $url));
        }

        return RssHttp::text($res);
    }

    private function refusal(int $status, string $url): string
    {
        return 'The site answered ' . $status . match (true) {
            $status === 402 => ' (payment required).',
            in_array($status, [401, 403, 429, 503], true) => ' (it turns automated readers away). A Google News search for site:'
                . preg_replace('/^www\./', '', (string) parse_url($url, PHP_URL_HOST)) . ' reaches the same stories.',
            default => '.',
        };
    }

    private function feed(RssSource $source, string $body): array
    {
        $xml = @simplexml_load_string(ltrim($body), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        if (! $xml) {
            throw new \RuntimeException(str_contains(strtolower(substr($body, 0, 600)), '<html') ? 'The address gave a web page, not a feed. Use the "News page" type for it.' : 'The address did not give an RSS or Atom feed.');
        }
        $base = (string) $source->sourceUrl;
        $items = [];
        $this->localTimes = false;
        $root = strtolower($xml->getName());
        if (! in_array($root, ['rss', 'feed', 'rdf'], true)) {
            throw new \RuntimeException('The address did not give an RSS or Atom feed.');
        }
        if ($root === 'feed') {
            $publisher = $this->clean((string) $xml->title);
            foreach ($xml->entry as $entry) {
                $items[] = $this->atomEntry($entry, $publisher, $base);
            }
        } else {
            $channel = $root === 'rss' ? $xml->channel : $xml->children('http://purl.org/rss/1.0/')->channel;
            $publisher = $this->clean((string) ($channel->title ?? '')) ?: $source->sourceName;
            $list = $root === 'rss' ? $xml->channel->item : $xml->children('http://purl.org/rss/1.0/')->item;
            $this->localTimes = $root === 'rss' && $this->writesLocalTimeAsUtc($channel, $list);
            foreach ($list as $item) {
                $items[] = $this->rssItem($item, $publisher, $base);
            }
        }
        $items = array_values(array_filter($items));

        return ['items' => $this->newestFirst($items), 'message' => count($items) . ' stories in the feed.'];
    }

    private function rssItem(\SimpleXMLElement $it, string $publisher, string $base): ?array
    {
        $title = $this->clean((string) $it->title);
        $link = trim((string) $it->link);
        $guid = trim((string) $it->guid);
        if ($link === '' && $guid !== '' && preg_match('#^https?://#i', $guid)) {
            $link = $guid;
        }
        $link = $link !== '' ? RssHtml::absolute($link, $base) : null;
        if (! $link || $title === '') {
            return null;
        }
        $content = (string) $it->children('http://purl.org/rss/1.0/modules/content/')->encoded;
        $description = (string) $it->description;
        $dc = $it->children('http://purl.org/dc/elements/1.1/');

        return [
            'guid' => $guid !== '' ? $guid : $link,
            'title' => $title,
            'link' => $link,
            'summary' => RssHtml::plain($description !== '' ? $description : $content),
            'image' => $this->mediaImage($it, $description . ' ' . $content, $link),
            'publisher' => $this->clean((string) $it->source) ?: self::publisher($link, $publisher),
            'publishedAt' => $this->date((string) $it->pubDate ?: (string) $dc->date),
        ];
    }

    private function atomEntry(\SimpleXMLElement $e, string $publisher, string $base): ?array
    {
        $title = $this->clean((string) $e->title);
        $link = null;
        foreach ($e->link as $l) {
            $rel = (string) $l['rel'];
            if ($rel === '' || $rel === 'alternate') {
                $link = (string) $l['href'];
                break;
            }
        }
        $link ??= isset($e->link[0]) ? (string) $e->link[0]['href'] : '';
        $link = $link !== '' ? RssHtml::absolute($link, $base) : null;
        if (! $link || $title === '') {
            return null;
        }
        $summary = (string) $e->summary ?: (string) $e->content;
        $media = $e->children('http://search.yahoo.com/mrss/');
        $id = trim((string) $e->id);

        return [
            'guid' => $id !== '' ? $id : $link,
            'title' => $title,
            'link' => $link,
            'summary' => RssHtml::plain($summary),
            'image' => $this->mediaImage($e, $summary, $link) ?? (isset($media->group->thumbnail) ? RssHtml::absolute(self::attr($media->group->thumbnail, 'url'), $link) : null),
            'publisher' => self::publisher($link, $publisher),
            'publishedAt' => $this->date((string) $e->published ?: (string) $e->updated),
        ];
    }

    private function mediaImage(\SimpleXMLElement $it, string $html, string $link): ?string
    {
        $media = $it->children('http://search.yahoo.com/mrss/');
        $candidates = [];
        foreach ([$media->content, $media->group->content ?? null] as $list) {
            foreach ($list ?? [] as $c) {
                $type = self::attr($c, 'type');
                $medium = self::attr($c, 'medium');
                $url = self::attr($c, 'url') ?: trim((string) $c);
                if ($medium === 'image' || str_starts_with($type, 'image/') || ($medium === '' && $type === '' && preg_match('/\.(jpe?g|png|webp|gif)(\?|$)/i', $url))) {
                    $candidates[] = $url;
                }
            }
        }
        if (isset($media->thumbnail)) {
            $candidates[] = self::attr($media->thumbnail, 'url');
        }
        if (isset($it->enclosure) && str_starts_with(self::attr($it->enclosure, 'type'), 'image/')) {
            $candidates[] = self::attr($it->enclosure, 'url');
        }
        // Some newsrooms put the picture's address in a tag of their own.
        foreach (['thumbimage', 'image', 'thumbnail'] as $tag) {
            if (isset($it->{$tag}) && preg_match('#^https?://#i', trim((string) $it->{$tag}))) {
                $candidates[] = trim((string) $it->{$tag});
            }
        }
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $m)) {
            $candidates[] = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);
        }
        foreach ($candidates as $c) {
            $url = $c !== '' ? RssHtml::absolute($c, $link) : null;
            if ($url && ! preg_match('/(feedburner|pixel|tracking|1x1|spacer)/i', $url)) {
                return $url;
            }
        }

        return null;
    }

    // ------------------------------------------------------------------
    // Google News
    // ------------------------------------------------------------------

    private function googleNews(RssSource $source, float $deadline, callable $isKnown): array
    {
        $feed = $this->feed($source, $this->body(self::googleNewsUrl($source)));
        // A headline the source's own filter would drop is not worth two requests to Google.
        $filter = new RssKeywordFilter($source->includeKeywords, $source->excludeKeywords);
        $kept = [];
        $decoded = 0;
        $waiting = 0;
        foreach ($feed['items'] as $item) {
            if ($isKnown((string) $item['guid'])) {
                $kept[] = $item;

                continue;
            }
            if (! $filter->passes($item['title'])) {
                continue;
            }
            // A site: search also finds tag, category and section pages, which are not stories.
            $headline = trim(preg_replace('/\s+[-–|]\s+[^-–|]+$/u', '', (string) $item['title']));
            if (mb_strlen($headline) < 20 || preg_match('/^(tags?|categor(y|ies)|topics?|authors?|archives?|section)\s*:/iu', $headline)) {
                continue;
            }
            if ($decoded >= $this->decodeLimit || microtime(true) > $deadline) {
                $waiting++;

                continue;
            }
            $decoded++;
            $real = $this->publisherLink((string) $item['link']);
            if (! $real) {
                $waiting++;

                continue;
            }
            // "Headline - Publisher": the publisher is already its own field.
            $publisher = (string) $item['publisher'];
            if ($publisher !== '' && str_ends_with($item['title'], ' - ' . $publisher)) {
                $item['title'] = trim(mb_substr($item['title'], 0, -mb_strlen(' - ' . $publisher)));
            }
            // Google's description is only the headline again, as a link.
            $item['summary'] = '';
            $item['image'] = null;
            $item['link'] = $real;
            $item['publisher'] = self::publisher($real, $publisher);
            $kept[] = $item;
            usleep(150000);
        }

        return ['items' => $kept, 'message' => count($feed['items']) . ' stories in the search' . ($waiting ? ', ' . $waiting . ' left for the next read' : '') . '.'];
    }

    /**
     * Google News gives its own link for every story. The publisher's address
     * is behind it, but only Google's page script knows it: the page carries a
     * signature and a timestamp, and Google's batchexecute endpoint trades
     * them for the address. Null when Google does not answer as expected.
     */
    public function publisherLink(string $googleLink): ?string
    {
        if (! preg_match('#news\.google\.com/(?:rss/)?(?:articles|read)/([A-Za-z0-9_-]+)#', $googleLink, $m)) {
            return preg_match('#^https?://#i', $googleLink) && ! str_contains($googleLink, 'news.google.com') ? $googleLink : null;
        }
        $id = $m[1];
        try {
            $page = RssHttp::get('https://news.google.com/rss/articles/' . $id, 15);
            $html = $page->successful() ? RssHttp::text($page) : '';
            if (! preg_match('/data-n-a-sg="([^"]+)"/', $html, $sg) || ! preg_match('/data-n-a-ts="([^"]+)"/', $html, $ts)) {
                return null;
            }
            $inner = json_encode(['garturlreq', [['X', 'X', ['X', 'X'], null, null, 1, 1, 'US:en', null, 1, null, null, null, null, null, 0, 1], 'X', 'X', 1, [1, 1, 1], 1, 1, null, 0, 0, null, 0], $id, (int) $ts[1], $sg[1]]);
            $res = RssHttp::postForm('https://news.google.com/_/DotsSplashUi/data/batchexecute', ['f.req' => json_encode([[['Fbv4je', $inner, null, 'generic']]])], 15);
            if (! $res->successful()) {
                return null;
            }
            $body = RssHttp::text($res);
            $json = json_decode(substr($body, (int) strpos($body, '[')), true);
            foreach (is_array($json) ? $json : [] as $row) {
                if (is_array($row) && ($row[0] ?? null) === 'wrb.fr' && is_string($row[2] ?? null)) {
                    $payload = json_decode($row[2], true);
                    $url = $payload[1] ?? null;
                    if (is_string($url) && preg_match('#^https?://#i', $url)) {
                        return $url;
                    }
                }
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    // ------------------------------------------------------------------
    // A news page with no feed
    // ------------------------------------------------------------------

    private function webpage(RssSource $source): array
    {
        $url = (string) $source->sourceUrl;
        $html = $this->html($url);
        $xp = RssHtml::dom($html);
        $baseTag = $xp->query('//base[@href]')->item(0);
        $base = $baseTag ? (RssHtml::absolute($baseTag->getAttribute('href'), $url) ?? $url) : $url;
        $host = strtolower(preg_replace('/^www\./', '', (string) parse_url($url, PHP_URL_HOST)));
        $selector = trim((string) $source->setting('linkSelector', ''));
        $contains = trim((string) $source->setting('linkContains', ''));

        if ($selector !== '') {
            $anchors = [];
            foreach (RssHtml::select($xp, $selector) as $el) {
                if (strtolower($el->tagName) === 'a') {
                    $anchors[] = $el;
                } else {
                    foreach ($el->getElementsByTagName('a') as $a) {
                        $anchors[] = $a;
                    }
                }
            }
        } else {
            // Menus, headers and footers are links to sections, not stories.
            $anchors = iterator_to_array($xp->query('//a[@href][not(ancestor::nav or ancestor::header or ancestor::footer or ancestor::aside'
                . ' or ancestor::*[contains(concat(" ", normalize-space(@class), " "), " menu ") or contains(@class, "navbar") or contains(@class, "nav-") or contains(@class, "breadcrumb") or contains(@class, "footer") or contains(@class, "sidebar") or contains(@class, "widget") or contains(@id, "menu") or contains(@id, "footer")])]') ?: []);
        }

        $found = [];
        foreach ($anchors as $a) {
            $link = RssHtml::absolute($a->getAttribute('href'), $base);
            if (! $link || rtrim($link, '/') === rtrim($url, '/')) {
                continue;
            }
            $linkHost = strtolower(preg_replace('/^www\./', '', (string) parse_url($link, PHP_URL_HOST)));
            if ($contains !== '' && ! str_contains($link, $contains)) {
                continue;
            }
            if ($selector === '' && $contains === '' && ($linkHost !== $host || ! $this->looksLikeArticle($link))) {
                continue;
            }
            if (preg_match('/\.(pdf|jpe?g|png|gif|webp|zip|docx?|xlsx?)(\?|$)/i', $link)) {
                continue;
            }
            $text = trim(preg_replace('/\s+/u', ' ', $a->textContent)) ?: trim((string) $a->getAttribute('title'));
            if (preg_match('/^(read more|continue reading|more|read|view|details|learn more|»|›)$/i', $text)) {
                $text = '';
            }
            if (! isset($found[$link]) || mb_strlen($text) > mb_strlen($found[$link])) {
                $found[$link] = $text;
            }
        }

        $items = [];
        foreach (array_slice($found, 0, $this->pageLinkLimit, true) as $link => $text) {
            $items[] = [
                'guid' => $link,
                'title' => $text !== '' ? Str::limit($text, 300, '') : $this->titleFromLink($link),
                'link' => $link,
                'summary' => '',
                'image' => null,
                'publisher' => self::publisher($link, $source->sourceName),
                'publishedAt' => null,
            ];
        }
        if (! $items) {
            throw new \RuntimeException('No article links were found on the page.' . ($selector === '' ? ' Try giving the links\' CSS selector.' : ' Check the CSS selector.'));
        }

        return ['items' => $items, 'message' => count($items) . ' article links on the page.'];
    }

    /** An address that reads like one story: a long slug or a number id. */
    private function looksLikeArticle(string $link): bool
    {
        $path = trim((string) parse_url($link, PHP_URL_PATH), '/');
        if ($path === '' || preg_match('#(^|/)(category|categories|tag|tags|author|page|search|about|contact|login|wp-admin|wp-content|feed)(/|$)#i', $path)) {
            return false;
        }
        $last = basename($path);
        $last = preg_replace('/\.(html?|php|aspx?)$/i', '', $last);

        return count(preg_split('/[-_]+/', $last)) >= 4 || preg_match('#(^|/)\d{4,}(/|$|-)#', $path);
    }

    private function titleFromLink(string $link): string
    {
        $slug = preg_replace('/\.(html?|php|aspx?)$/i', '', basename(trim((string) parse_url($link, PHP_URL_PATH), '/')));

        return Str::ucfirst(trim(preg_replace('/[-_]+/', ' ', $slug)));
    }

    // ------------------------------------------------------------------
    // A page watched for changes
    // ------------------------------------------------------------------

    private function pageWatch(RssSource $source): array
    {
        $url = (string) $source->sourceUrl;
        $xp = RssHtml::dom($this->html($url));
        $selector = trim((string) $source->setting('contentSelector', ''));
        if ($selector !== '') {
            $node = RssHtml::select($xp, $selector)[0] ?? null;
            if (! $node) {
                throw new \RuntimeException('The part to watch ("' . $selector . '") is not on the page anymore.');
            }
        } else {
            $node = $xp->query('//main')->item(0) ?? $xp->query('//article')->item(0) ?? $xp->query('//body')->item(0);
        }
        $text = $node ? Str::limit(RssHtml::text($node), 5000, '') : '';
        if ($text === '') {
            throw new \RuntimeException('The watched part of the page has no text.');
        }
        $first = Str::limit(strtok($text, "\n") ?: $text, 160, '');
        $hash = sha1($text);

        return ['items' => [[
            'guid' => 'watch:' . $hash,
            'title' => $source->sourceName . ': ' . $first,
            'link' => $url . (str_contains($url, '?') ? '&' : '?') . 'update=' . now()->format('Ymd-Hi'),
            'summary' => $text,
            'image' => null,
            'publisher' => self::publisher($url, $source->sourceName),
            'publishedAt' => null,
        ]], 'message' => 'The watched part reads: ' . $first];
    }

    private function html(string $url): string
    {
        $res = RssHttp::get($url, 25, ['Accept' => 'text/html,application/xhtml+xml;q=0.9,*/*;q=0.8']);
        if (! $res->successful()) {
            throw new \RuntimeException($this->refusal($res->status(), $url));
        }
        $body = RssHttp::text($res);
        if (stripos($body, '<title>Just a moment...</title>') !== false || str_contains($body, 'cf_chl_opt')) {
            throw new \RuntimeException('The site turns automated readers away (a Cloudflare check). A Google News search for site:'
                . preg_replace('/^www\./', '', (string) parse_url($url, PHP_URL_HOST)) . ' reaches the same stories.');
        }

        return $body;
    }

    // ------------------------------------------------------------------

    public static function publisher(string $link, string $fallback): string
    {
        $host = strtolower(preg_replace('/^www\./', '', (string) parse_url($link, PHP_URL_HOST)));
        foreach (self::PUBLISHERS as $domain => $name) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return $name;
            }
        }
        // "Site name - RSS Business Agriculture" or "Site / Section": the site's name.
        $name = trim(preg_split('/\s+(?:-|–|—|\||\/|::)\s+/u', $fallback)[0] ?? '');
        $name = trim(preg_replace('/\s*\b(rss|atom)?\s*(feed|rss)\b\s*$/i', '', $name));

        return $name !== '' ? $name : $host;
    }

    private function clean(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    /**
     * An attribute without a namespace. On an element reached through
     * children($ns), $el['url'] looks in that namespace and finds nothing.
     */
    private static function attr(\SimpleXMLElement $el, string $name): string
    {
        return trim((string) ($el->attributes()->{$name} ?? ''));
    }

    /**
     * Some newsrooms write Manila time and label it +0000 (Inquirer and Panay
     * News do). Read as UTC, every story lands eight hours late, and the newest
     * one later than the feed itself was built, which cannot be. So when the
     * newest story is more than an hour after the feed's lastBuildDate, the
     * whole feed's times are read as Manila time.
     */
    private function writesLocalTimeAsUtc(\SimpleXMLElement $channel, $list): bool
    {
        try {
            $built = trim((string) ($channel->lastBuildDate ?: $channel->pubDate));
            if ($built === '') {
                return false;
            }
            $built = Carbon::parse($built);
            $newest = null;
            foreach ($list as $item) {
                $raw = trim((string) $item->pubDate);
                if ($raw !== '') {
                    $d = Carbon::parse($raw);
                    $newest = ! $newest || $d->gt($newest) ? $d : $newest;
                }
            }
            if (! $newest || $newest->utcOffset() !== 0 || $newest->lte($built->copy()->addHour())) {
                return false;
            }
            // Read as Manila time, the newest story must then fit before the build.
            return $newest->copy()->shiftTimezone(config('app.timezone'))->lte($built->copy()->addHour());
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function date(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        try {
            $date = Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
        if ($this->localTimes) {
            return $date->shiftTimezone(config('app.timezone'))->timezone(config('app.timezone'));
        }
        // A single story dated hours ahead is most likely the same mistake.
        if ($date->gt(now()->addHour())) {
            $local = $date->copy()->shiftTimezone(config('app.timezone'));
            if ($local->lte(now()->addHour())) {
                $date = $local;
            }
        }

        return $date->timezone(config('app.timezone'));
    }

    private function newestFirst(array $items): array
    {
        usort($items, fn ($a, $b) => ($b['publishedAt']?->getTimestamp() ?? 0) <=> ($a['publishedAt']?->getTimestamp() ?? 0));

        return $items;
    }
}
