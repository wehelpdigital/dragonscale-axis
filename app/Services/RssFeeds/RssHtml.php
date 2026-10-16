<?php

namespace App\Services\RssFeeds;

use Symfony\Component\CssSelector\CssSelectorConverter;

/**
 * Reading HTML for the RSS Feeds module: loading a page into DOM, CSS
 * selectors, a node's readable text, absolute links, and the details an
 * article page announces about itself (og: tags, publish date).
 */
class RssHtml
{
    public static function dom(string $html): \DOMXPath
    {
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new \DOMXPath($dom);
    }

    /** A CSS selector as XPath; throws with a readable message when it is not valid. */
    public static function xpath(string $selector): string
    {
        try {
            return (new CssSelectorConverter())->toXPath($selector);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException('"' . $selector . '" is not a CSS selector this reader understands.');
        }
    }

    /** @return \DOMElement[] */
    public static function select(\DOMXPath $xp, string $selector): array
    {
        $nodes = $xp->query(self::xpath($selector));

        return $nodes ? array_values(array_filter(iterator_to_array($nodes), fn ($n) => $n instanceof \DOMElement)) : [];
    }

    /**
     * A node's words as lines: blocks and rows start new lines, table cells are
     * joined with a middle dot, scripts and styles are left out.
     */
    public static function text(\DOMNode $node): string
    {
        $out = '';
        self::walk($node, $out);
        $lines = array_map(fn ($l) => trim(preg_replace('/[ \t\x{00A0}]+/u', ' ', $l), " ·\t"), explode("\n", $out));

        return implode("\n", array_values(array_filter($lines, fn ($l) => $l !== '')));
    }

    private static function walk(\DOMNode $node, string &$out): void
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMText) {
                $out .= $child->nodeValue;

                continue;
            }
            if (! $child instanceof \DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if (in_array($tag, ['script', 'style', 'noscript', 'template', 'svg', 'iframe', 'form', 'button'], true)) {
                continue;
            }
            $block = in_array($tag, ['p', 'div', 'br', 'li', 'tr', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'table', 'section', 'article', 'ul', 'ol', 'header', 'footer', 'blockquote', 'pre', 'hr', 'dd', 'dt'], true);
            if ($block) {
                $out .= "\n";
            }
            self::walk($child, $out);
            if (in_array($tag, ['td', 'th'], true)) {
                $out .= ' · ';
            } elseif ($block) {
                $out .= "\n";
            }
        }
    }

    /** Plain text from a fragment of HTML (a feed's description). */
    public static function plain(?string $html): string
    {
        $text = html_entity_decode(strip_tags(preg_replace('#<(br|/p|/div|/li)[^>]*>#i', ' ', (string) $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        // WordPress closes every excerpt with where it was first posted.
        $text = preg_replace('/\s*(The post .{1,300}? appeared first on .{1,150}?\.?|\[(…|\.\.\.)\]|Continue reading.{0,80}|Read more.{0,40})$/iu', '', $text);

        return trim((string) $text);
    }

    public static function absolute(string $href, string $base): ?string
    {
        $href = trim(html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($href === '' || preg_match('#^(javascript|mailto|tel|data):#i', $href) || str_starts_with($href, '#')) {
            return null;
        }
        if (preg_match('#^https?://#i', $href)) {
            $url = $href;
        } elseif (str_starts_with($href, '//')) {
            $url = (parse_url($base, PHP_URL_SCHEME) ?: 'https') . ':' . $href;
        } else {
            $b = parse_url($base);
            if (empty($b['host'])) {
                return null;
            }
            $root = ($b['scheme'] ?? 'https') . '://' . $b['host'] . (isset($b['port']) ? ':' . $b['port'] : '');
            if (str_starts_with($href, '/')) {
                $url = $root . $href;
            } else {
                $dir = preg_replace('#/[^/]*$#', '/', $b['path'] ?? '/');
                $url = $root . $dir . $href;
            }
        }

        return explode('#', $url, 2)[0];
    }

    /**
     * What an article page says about itself.
     *
     * @return array{title: ?string, description: ?string, image: ?string, publishedAt: ?string, siteName: ?string}
     */
    public static function details(string $html, string $url): array
    {
        $xp = self::dom($html);
        $meta = [];
        foreach ($xp->query('//meta[@content]') ?: [] as $m) {
            $key = strtolower(trim($m->getAttribute('property') ?: $m->getAttribute('name') ?: $m->getAttribute('itemprop')));
            if ($key !== '' && ! isset($meta[$key])) {
                $meta[$key] = trim(html_entity_decode($m->getAttribute('content'), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
        }
        $published = $meta['article:published_time'] ?? $meta['og:published_time'] ?? $meta['datepublished'] ?? $meta['pubdate'] ?? $meta['date'] ?? null;
        if (! $published && preg_match('/"datePublished"\s*:\s*"([^"]+)"/', $html, $m)) {
            $published = $m[1];
        }
        if (! $published) {
            $time = $xp->query('//time[@datetime]')->item(0);
            $published = $time ? $time->getAttribute('datetime') : null;
        }
        $image = $meta['og:image'] ?? $meta['og:image:url'] ?? $meta['twitter:image'] ?? $meta['twitter:image:src'] ?? null;
        $title = $meta['og:title'] ?? $meta['twitter:title'] ?? null;
        if (! $title) {
            $t = $xp->query('//title')->item(0);
            $title = $t ? trim(preg_replace('/\s+/u', ' ', $t->textContent)) : null;
        }
        // "Headline - Site name": the site's name is not part of the headline.
        $site = trim((string) ($meta['og:site_name'] ?? ''));
        if ($title && $site !== '') {
            $title = trim(preg_replace('/\s+[-–—|:]\s+' . preg_quote($site, '/') . '\s*$/iu', '', $title));
        }

        return [
            'title' => $title ?: null,
            'description' => ($meta['og:description'] ?? $meta['description'] ?? $meta['twitter:description'] ?? null) ?: null,
            'image' => $image ? self::absolute($image, $url) : null,
            'publishedAt' => $published ?: null,
            'siteName' => ($meta['og:site_name'] ?? null) ?: null,
        ];
    }
}
