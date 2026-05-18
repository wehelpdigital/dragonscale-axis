<?php

namespace App\Services;

use App\Models\RgContentBlock;
use Illuminate\Support\Facades\DB;

class SeoAnalyzer
{
    public function analyzeSeoPage(int $pageId): array
    {
        $page = DB::table('rg_seo_pages')->where('id', $pageId)->first();
        if (!$page) return $this->emptyResult();
        $keyword = DB::table('rg_keywords')->where('id', $page->keyword_id)->first();
        $blocks = RgContentBlock::where('owner_type', 'seo_page')->where('owner_id', $pageId)->orderBy('sort_order')->get();

        $contentText = $this->extractText($blocks, $page);
        $contentHtml = $this->extractHtml($blocks, $page);
        $phrase = $keyword->phrase ?? '';
        $wordCount = $this->wordCount($contentText);

        $checks = [];
        $checks[] = $this->checkTitle($page->meta_title ?: $page->title);
        $checks[] = $this->checkMetaDesc($page->meta_description);
        $checks[] = $this->checkH1($page->h1);
        $checks[] = $this->checkWordCount($wordCount);
        $checks[] = $this->checkKeywordDensity($contentText, $phrase, $wordCount);
        $checks[] = $this->checkImages($blocks, $contentHtml);
        $checks[] = $this->checkInternalLinks($contentHtml);
        $checks[] = $this->checkFAQ($blocks);
        $checks[] = $this->checkListingSlot($blocks);
        $checks[] = $this->checkPublished($page->is_published);
        $checks[] = $this->checkCanonical($page->canonical_url);

        return $this->scoreResult($checks);
    }

    public function analyzeBlogPost(int $postId): array
    {
        $post = DB::table('rg_blog_posts')->where('id', $postId)->first();
        if (!$post) return $this->emptyResult();
        $blocks = RgContentBlock::where('owner_type', 'blog_post')->where('owner_id', $postId)->orderBy('sort_order')->get();
        $contentText = $this->extractText($blocks);
        $contentHtml = $this->extractHtml($blocks);
        $wordCount = $this->wordCount($contentText);

        $checks = [];
        $checks[] = $this->checkTitle($post->meta_title ?: $post->title);
        $checks[] = $this->checkMetaDesc($post->meta_description);
        $checks[] = $this->checkWordCount($wordCount);
        $checks[] = $this->checkImages($blocks, $contentHtml);
        $checks[] = $this->checkInternalLinks($contentHtml);
        $checks[] = $this->checkExcerpt($post->excerpt);
        $checks[] = $this->checkCover($post->cover_path);
        return $this->scoreResult($checks);
    }

    private function emptyResult(): array
    {
        return ['ok' => false, 'score' => 0, 'label' => 'No data', 'checks' => []];
    }

    private function scoreResult(array $checks): array
    {
        $weights = ['pass' => 1.0, 'warn' => 0.5, 'fail' => 0.0];
        $total = count($checks);
        $points = 0;
        foreach ($checks as $c) $points += $weights[$c['status']] ?? 0;
        $score = $total ? (int) round(($points / $total) * 100) : 0;
        $label = $score >= 80 ? 'Excellent' : ($score >= 60 ? 'Good' : ($score >= 40 ? 'Needs work' : 'Poor'));
        return ['ok' => true, 'score' => $score, 'label' => $label, 'checks' => $checks];
    }

    private function extractText($blocks, $page = null): string
    {
        $parts = [];
        foreach ($blocks as $b) {
            $p = json_decode($b->payload_json, true) ?: [];
            $parts[] = match ($b->block_type) {
                'heading' => $p['text'] ?? '',
                'rich_text' => strip_tags($p['html'] ?? ''),
                'image' => ($p['alt'] ?? '') . ' ' . ($p['caption'] ?? ''),
                'gallery' => implode(' ', array_map(fn($i) => $i['alt'] ?? '', $p['images'] ?? [])),
                'video' => $p['caption'] ?? '',
                'faq' => implode(' ', array_map(fn($f) => ($f['question'] ?? '') . ' ' . ($f['answer'] ?? ''), $p['items'] ?? [])),
                'cta' => ($p['headline'] ?? '') . ' ' . ($p['text'] ?? '') . ' ' . ($p['button_text'] ?? ''),
                'two_column' => strip_tags(($p['left_html'] ?? '') . ' ' . ($p['right_html'] ?? '')),
                'quote' => ($p['text'] ?? '') . ' ' . ($p['author'] ?? ''),
                'custom_html' => strip_tags($p['html'] ?? ''),
                default => '',
            };
        }
        if ($page) {
            $parts[] = strip_tags($page->intro_html ?? '');
            $parts[] = strip_tags($page->body_html ?? '');
        }
        return trim(implode(' ', $parts));
    }

    private function extractHtml($blocks, $page = null): string
    {
        $parts = [];
        foreach ($blocks as $b) {
            $p = json_decode($b->payload_json, true) ?: [];
            $parts[] = match ($b->block_type) {
                'rich_text' => $p['html'] ?? '',
                'two_column' => ($p['left_html'] ?? '') . ' ' . ($p['right_html'] ?? ''),
                'custom_html' => $p['html'] ?? '',
                'image' => '<img src="' . ($p['src'] ?? '') . '" alt="' . ($p['alt'] ?? '') . '">',
                'gallery' => implode('', array_map(fn($i) => '<img src="' . ($i['src'] ?? '') . '" alt="' . ($i['alt'] ?? '') . '">', $p['images'] ?? [])),
                default => '',
            };
        }
        if ($page) {
            $parts[] = $page->intro_html ?? '';
            $parts[] = $page->body_html ?? '';
        }
        return implode("\n", $parts);
    }

    private function wordCount(string $text): int
    {
        return str_word_count(strip_tags($text));
    }

    private function checkTitle(?string $title): array
    {
        $len = mb_strlen((string) $title);
        if (!$title) return $this->fail('title', 'Title / Meta title', 'Missing title. Set a clear, keyword-rich title.');
        if ($len < 30) return $this->warn('title', 'Title length', "Title is short ($len chars). Aim for 50-60.");
        if ($len > 70) return $this->warn('title', 'Title length', "Title is long ($len chars). Aim for 50-60.");
        return $this->pass('title', 'Title length', "Good length ($len chars).");
    }

    private function checkMetaDesc(?string $desc): array
    {
        $len = mb_strlen((string) $desc);
        if ($len === 0) return $this->fail('meta', 'Meta description', 'Missing meta description.');
        if ($len < 110) return $this->warn('meta', 'Meta description', "Short ($len chars). Aim for 140-160.");
        if ($len > 170) return $this->warn('meta', 'Meta description', "Long ($len chars). May get truncated.");
        return $this->pass('meta', 'Meta description', "Good length ($len chars).");
    }

    private function checkH1(?string $h1): array
    {
        if (!$h1) return $this->fail('h1', 'H1 heading', 'Missing H1. Add a primary heading.');
        return $this->pass('h1', 'H1 heading', 'Set.');
    }

    private function checkWordCount(int $words): array
    {
        if ($words < 200) return $this->fail('wc', 'Word count', "Only $words words. Aim for 600+ for SEO depth.");
        if ($words < 600) return $this->warn('wc', 'Word count', "$words words. 600-1500 is ideal for ranking.");
        return $this->pass('wc', 'Word count', "$words words. Healthy.");
    }

    private function checkKeywordDensity(string $text, string $phrase, int $words): array
    {
        if (!$phrase || $words < 50) return $this->warn('kd', 'Keyword usage', 'Not enough content to evaluate density.');
        $count = mb_substr_count(mb_strtolower($text), mb_strtolower($phrase));
        if ($count === 0) return $this->fail('kd', 'Keyword usage', "Keyword \"$phrase\" never appears.");
        if ($count < 2) return $this->warn('kd', 'Keyword usage', "Keyword appears $count time. Aim for 3-6 mentions.");
        if ($count > 12) return $this->warn('kd', 'Keyword usage', "Keyword appears $count times. May look spammy.");
        return $this->pass('kd', 'Keyword usage', "Keyword appears $count times. Good distribution.");
    }

    private function checkImages($blocks, string $html): array
    {
        $imageBlocks = 0; $missingAlt = 0;
        foreach ($blocks as $b) {
            $p = json_decode($b->payload_json, true) ?: [];
            if ($b->block_type === 'image') {
                $imageBlocks++;
                if (empty($p['alt'])) $missingAlt++;
            } elseif ($b->block_type === 'gallery') {
                foreach ($p['images'] ?? [] as $i) {
                    $imageBlocks++;
                    if (empty($i['alt'])) $missingAlt++;
                }
            }
        }
        if ($imageBlocks === 0) return $this->warn('img', 'Images', 'No images yet. Add at least one for visual SEO.');
        if ($missingAlt > 0) return $this->warn('img', 'Image alt tags', "$missingAlt of $imageBlocks images missing alt text.");
        return $this->pass('img', 'Images', "$imageBlocks images, all with alt text.");
    }

    private function checkInternalLinks(string $html): array
    {
        preg_match_all('/<a\s[^>]*href=["\']([^"\']+)["\']/i', $html, $m);
        $links = $m[1] ?? [];
        $internal = 0;
        foreach ($links as $l) {
            if (str_starts_with($l, '/') || str_contains($l, parse_url(config('app.url'), PHP_URL_HOST) ?? '')) $internal++;
        }
        if ($internal === 0) return $this->warn('il', 'Internal links', 'No internal links found. Link to related keyword pages.');
        if ($internal < 3) return $this->warn('il', 'Internal links', "$internal internal link(s). Aim for 3+.");
        return $this->pass('il', 'Internal links', "$internal internal links. Good.");
    }

    private function checkFAQ($blocks): array
    {
        foreach ($blocks as $b) {
            if ($b->block_type === 'faq') {
                $p = json_decode($b->payload_json, true) ?: [];
                $n = count($p['items'] ?? []);
                if ($n >= 4) return $this->pass('faq', 'FAQ block', "$n FAQs. Helps for FAQPage schema.");
                if ($n > 0) return $this->warn('faq', 'FAQ block', "$n FAQ items. Add at least 4 for stronger FAQ schema.");
            }
        }
        return $this->warn('faq', 'FAQ block', 'No FAQ block. Adding one enables FAQPage schema.');
    }

    private function checkListingSlot($blocks): array
    {
        foreach ($blocks as $b) {
            if ($b->block_type === 'listing_slot') return $this->pass('ls', 'Listing slot', 'Present. Paid listings will render here.');
        }
        return $this->warn('ls', 'Listing slot', 'No listing slot block. Add one to monetize this page.');
    }

    private function checkPublished(int $isPub): array
    {
        return $isPub ? $this->pass('pub', 'Published', 'This page is live.') : $this->fail('pub', 'Published', 'Page is in draft. Search engines cannot index it.');
    }

    private function checkCanonical(?string $url): array
    {
        if (!$url) return $this->pass('canon', 'Canonical', 'Default canonical (page URL).');
        return $this->pass('canon', 'Canonical', 'Custom canonical set.');
    }

    private function checkExcerpt(?string $excerpt): array
    {
        if (!$excerpt) return $this->warn('exc', 'Excerpt', 'Empty excerpt. Adds to listings + social shares.');
        return $this->pass('exc', 'Excerpt', 'Set.');
    }

    private function checkCover(?string $cover): array
    {
        if (!$cover) return $this->warn('cov', 'Cover image', 'No cover image. Adds visual appeal on social shares.');
        return $this->pass('cov', 'Cover image', 'Set.');
    }

    private function pass(string $id, string $label, string $message): array
    {
        return compact('id', 'label', 'message') + ['status' => 'pass'];
    }

    private function warn(string $id, string $label, string $message): array
    {
        return compact('id', 'label', 'message') + ['status' => 'warn'];
    }

    private function fail(string $id, string $label, string $message): array
    {
        return compact('id', 'label', 'message') + ['status' => 'fail'];
    }
}
