<?php

namespace App\Services;

use App\Models\AiWebsite;
use App\Models\AiWebsitePage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use DOMDocument;
use DOMXPath;

class WebScraperService
{
    protected AiWebsite $website;
    protected array $visitedUrls = [];
    protected array $queuedUrls = [];
    protected int $pagesScraped = 0;
    protected int $maxPages;
    protected int $maxDepth;
    protected string $baseHost;
    protected array $allowedPaths;
    protected array $excludedPaths;
    protected array $errors = [];

    // Phase tracking
    protected array $discoveredUrls = [];
    protected array $alreadyScrapedUrls = [];
    protected int $pagesSkipped = 0;
    protected int $pagesDiscovered = 0;

    // Batch processing
    protected int $maxPagesPerBatch = 15; // Max pages to scrape per request to avoid timeout
    protected bool $hasMorePages = false;
    protected string $scrapeMode = 'full'; // 'full', 'continue', 'discover_only'

    /**
     * User agent for requests.
     */
    protected string $userAgent = 'Mozilla/5.0 (compatible; DSAxisBot/1.0; +https://dsaxis.com)';

    /**
     * Request timeout in seconds.
     */
    protected int $timeout = 20; // Reduced timeout for faster failure detection

    /**
     * Delay between requests in milliseconds (for discovery phase - faster).
     */
    protected int $discoveryDelay = 150;

    /**
     * Min/Max delay between scraping requests in milliseconds (random for anti-timeout).
     */
    protected int $scrapeDelayMin = 300;
    protected int $scrapeDelayMax = 800;

    /**
     * Legacy property - kept for backwards compatibility.
     * @deprecated Use $scrapeDelayMin and $scrapeDelayMax instead
     */
    protected int $requestDelay = 500;

    /**
     * Initialize the scraper for a website.
     *
     * @param AiWebsite $website
     * @param string $mode 'full' (default), 'continue' (resume from queue), 'discover_only'
     */
    public function __construct(AiWebsite $website, string $mode = 'full')
    {
        $this->website = $website;
        $this->maxPages = $website->maxPages ?? 500;
        $this->maxDepth = $website->maxDepth ?? 5;
        $this->allowedPaths = $website->allowedPaths ?? [];
        $this->excludedPaths = $website->excludedPaths ?? [];
        $this->scrapeMode = $mode;

        $parsed = parse_url($website->websiteUrl);
        $this->baseHost = $parsed['host'] ?? '';
    }

    /**
     * Start the scraping process.
     */
    public function scrape(): array
    {
        $this->website->update([
            'lastScrapeStatus' => AiWebsite::STATUS_IN_PROGRESS,
            'lastScrapeError' => null,
        ]);

        try {
            $result = match ($this->website->scrapeType) {
                AiWebsite::SCRAPE_FULL_PAGE => $this->scrapeSinglePage(),
                AiWebsite::SCRAPE_SPECIFIC_SELECTOR => $this->scrapeWithSelector(),
                AiWebsite::SCRAPE_SITEMAP => $this->scrapeSitemap(),
                AiWebsite::SCRAPE_API_ENDPOINT => $this->scrapeApiEndpoint(),
                AiWebsite::SCRAPE_WHOLE_SITE => $this->scrapeWholeSite(),
                default => throw new \Exception('Unknown scrape type: ' . $this->website->scrapeType),
            };

            $this->website->update([
                'lastScrapedAt' => now(),
                'lastScrapeStatus' => AiWebsite::STATUS_SUCCESS,
                'lastScrapeError' => null,
                'scrapeCount' => $this->website->scrapeCount + 1,
                'pagesScraped' => $this->pagesScraped,
            ]);

            return [
                'success' => true,
                'pagesScraped' => $this->pagesScraped,
                'errors' => $this->errors,
                'data' => $result,
            ];
        } catch (\Exception $e) {
            Log::error('WebScraperService error: ' . $e->getMessage(), [
                'website_id' => $this->website->id,
                'url' => $this->website->websiteUrl,
            ]);

            $this->website->update([
                'lastScrapeStatus' => AiWebsite::STATUS_FAILED,
                'lastScrapeError' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'pagesScraped' => $this->pagesScraped,
                'errors' => $this->errors,
            ];
        }
    }

    /**
     * Scrape a single page.
     */
    protected function scrapeSinglePage(): array
    {
        $pageData = $this->fetchAndParsePage($this->website->websiteUrl, 0);

        if ($pageData) {
            $this->savePage($pageData);
            return ['pages' => [$pageData['url']]];
        }

        throw new \Exception('Failed to scrape the page');
    }

    /**
     * Scrape with CSS selector.
     */
    protected function scrapeWithSelector(): array
    {
        $response = $this->makeRequest($this->website->websiteUrl);

        if (!$response || !$response->successful()) {
            throw new \Exception('Failed to fetch page: HTTP ' . ($response ? $response->status() : 'unknown'));
        }

        $html = $response->body();
        $content = $this->extractBySelector($html, $this->website->cssSelector);

        $pageData = [
            'url' => $this->website->websiteUrl,
            'title' => $this->extractTitle($html),
            'metaDescription' => $this->extractMetaDescription($html),
            'cleanContent' => $content,
            'rawHtml' => $html,
            'httpStatus' => $response->status(),
            'contentType' => $response->header('Content-Type'),
            'pageSize' => strlen($html),
            'depth' => 0,
        ];

        $this->savePage($pageData);
        return ['pages' => [$pageData['url']], 'contentLength' => strlen($content)];
    }

    /**
     * Scrape pages from sitemap with proper nested sitemap support and batching.
     *
     * Phase 1: Discover ALL sitemaps (including nested ones)
     * Phase 2: Extract ALL page URLs from all sitemaps
     * Phase 3: Deduplicate and filter URLs
     * Phase 4: Scrape in batches (same as whole site)
     */
    protected function scrapeSitemap(): array
    {
        Log::info("Starting sitemap scrape for: {$this->website->websiteUrl} (mode: {$this->scrapeMode})");

        $urlsToScrape = [];

        // ========================================
        // Check if we're continuing from a saved queue
        // ========================================
        if ($this->scrapeMode === 'continue') {
            Log::info("Continue mode: Loading sitemap URLs from saved queue...");
            $savedQueue = $this->website->crawlQueue ?? [];

            if (empty($savedQueue)) {
                Log::info("No saved queue found, starting fresh sitemap discovery...");
                $this->scrapeMode = 'full';
            } else {
                $urlsToScrape = $this->filterSavedQueue($savedQueue);
                $this->pagesDiscovered = count($savedQueue);
                Log::info("Loaded " . count($urlsToScrape) . " URLs from queue (after filtering)");
            }
        }

        // ========================================
        // PHASE 1 & 2: Discover ALL sitemaps and extract ALL URLs
        // ========================================
        if ($this->scrapeMode === 'full' || $this->scrapeMode === 'discover_only') {
            Log::info("Phase 1: Discovering all sitemaps (including nested)...");

            // Common sitemap locations to check
            $sitemapLocations = [
                rtrim($this->website->websiteUrl, '/') . '/sitemap.xml',
                rtrim($this->website->websiteUrl, '/') . '/sitemap_index.xml',
                rtrim($this->website->websiteUrl, '/') . '/sitemap/',
                rtrim($this->website->websiteUrl, '/') . '/sitemaps.xml',
                rtrim($this->website->websiteUrl, '/') . '/post-sitemap.xml',
                rtrim($this->website->websiteUrl, '/') . '/page-sitemap.xml',
                rtrim($this->website->websiteUrl, '/') . '/wp-sitemap.xml',
            ];

            // Also try to get sitemap from robots.txt
            $robotsSitemaps = $this->getSitemapsFromRobots();
            $sitemapLocations = array_merge($sitemapLocations, $robotsSitemaps);
            $sitemapLocations = array_unique($sitemapLocations);

            // Discover ALL sitemaps first (including nested)
            $allSitemaps = [];
            $processedSitemaps = [];

            foreach ($sitemapLocations as $sitemapUrl) {
                $this->discoverAllSitemaps($sitemapUrl, $allSitemaps, $processedSitemaps);
            }

            Log::info("Found " . count($allSitemaps) . " sitemap(s) to process");

            // ========================================
            // PHASE 2: Extract ALL URLs from ALL sitemaps
            // ========================================
            Log::info("Phase 2: Extracting URLs from all sitemaps...");
            $allPageUrls = [];

            foreach ($allSitemaps as $sitemapUrl) {
                Log::info("Extracting URLs from: $sitemapUrl");
                $urls = $this->extractUrlsFromSitemap($sitemapUrl);
                $allPageUrls = array_merge($allPageUrls, $urls);

                // Small delay between sitemap fetches
                usleep(200 * 1000);
            }

            // Deduplicate
            $allPageUrls = array_unique($allPageUrls);
            $this->pagesDiscovered = count($allPageUrls);
            Log::info("Phase 2 complete: Found {$this->pagesDiscovered} unique URLs from sitemaps");

            if (empty($allPageUrls)) {
                throw new \Exception('No sitemap found or sitemaps are empty');
            }

            // ========================================
            // PHASE 3: Filter and deduplicate with database
            // ========================================
            Log::info("Phase 3: Filtering already-scraped URLs...");

            // Load already-scraped URLs from database
            $existingPages = AiWebsitePage::where('websiteId', $this->website->id)
                ->where('delete_status', 'active')
                ->where('scrapeStatus', AiWebsitePage::STATUS_COMPLETED)
                ->pluck('urlHash')
                ->toArray();
            $existingHashes = array_flip($existingPages);

            foreach ($allPageUrls as $url) {
                $urlHash = hash('sha256', $url);

                // Skip if already scraped
                if (isset($existingHashes[$urlHash])) {
                    $this->pagesSkipped++;
                    continue;
                }

                // Skip if shouldn't scrape
                if (!$this->shouldScrapeUrl($url)) {
                    continue;
                }

                $urlsToScrape[] = [
                    'url' => $url,
                    'normalizedUrl' => $this->normalizeUrl($url),
                    'depth' => 1, // Sitemap URLs are considered depth 1
                    'isPagination' => false,
                ];
            }

            // Limit to maxPages
            $urlsToScrape = array_slice($urlsToScrape, 0, $this->maxPages);
            Log::info("Phase 3 complete: {$this->pagesSkipped} URLs skipped, " . count($urlsToScrape) . " URLs to scrape");

            // Save to queue for recovery
            $this->website->update([
                'crawlQueue' => array_slice($urlsToScrape, 0, 2000),
            ]);

            if ($this->scrapeMode === 'discover_only') {
                return [
                    'pages' => [],
                    'pagesDiscovered' => $this->pagesDiscovered,
                    'pagesSkipped' => $this->pagesSkipped,
                    'remainingInQueue' => count($urlsToScrape),
                    'reachedMaxPagesLimit' => false,
                    'hasMore' => count($urlsToScrape) > 0,
                    'phase' => 'discovery_complete',
                ];
            }
        }

        // ========================================
        // PHASE 4: Scrape URLs in batches
        // ========================================
        Log::info("Phase 4: Scraping content (batch of {$this->maxPagesPerBatch} max)...");
        $scrapedPages = $this->scrapeUrlsInBatch($urlsToScrape);
        Log::info("Phase 4 complete: Scraped {$this->pagesScraped} pages in this batch");

        // Calculate remaining URLs
        $remainingUrls = array_filter($urlsToScrape, fn($u) => !isset($this->visitedUrls[$this->normalizeUrl($u['url'])]));
        $remainingCount = count($remainingUrls);

        // Save remaining queue for next batch/resume
        $this->website->update([
            'crawlQueue' => array_slice(array_values($remainingUrls), 0, 2000),
        ]);

        // Determine if there are more pages to scrape
        $this->hasMorePages = $remainingCount > 0 && $this->pagesScraped < $this->maxPages;

        $reachedMaxPages = $this->pagesScraped >= $this->maxPages && $remainingCount > 0;
        if ($reachedMaxPages) {
            $this->errors[] = "Reached max pages limit ({$this->maxPages}). {$remainingCount} URLs still pending.";
        }

        return [
            'pages' => $scrapedPages,
            'pagesDiscovered' => $this->pagesDiscovered,
            'pagesSkipped' => $this->pagesSkipped,
            'pagesScrapedThisBatch' => $this->pagesScraped,
            'remainingInQueue' => $remainingCount,
            'reachedMaxPagesLimit' => $reachedMaxPages,
            'hasMore' => $this->hasMorePages,
            'batchSize' => $this->maxPagesPerBatch,
        ];
    }

    /**
     * Get sitemap URLs from robots.txt.
     */
    protected function getSitemapsFromRobots(): array
    {
        $robotsUrl = rtrim($this->website->websiteUrl, '/') . '/robots.txt';
        $sitemaps = [];

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => $this->userAgent,
                    'Connection' => 'close',
                ])
                ->withOptions([
                    'verify' => false,
                    'curl' => [
                        CURLOPT_FORBID_REUSE => true,
                        CURLOPT_FRESH_CONNECT => true,
                    ],
                ])
                ->get($robotsUrl);

            if ($response->successful()) {
                $content = $response->body();
                // Find all Sitemap: directives
                if (preg_match_all('/^Sitemap:\s*(.+)$/mi', $content, $matches)) {
                    $sitemaps = array_map('trim', $matches[1]);
                }
            }
        } catch (\Exception $e) {
            Log::debug("Could not fetch robots.txt: " . $e->getMessage());
        }

        return $sitemaps;
    }

    /**
     * Recursively discover ALL sitemaps (including nested sitemap indexes).
     */
    protected function discoverAllSitemaps(string $sitemapUrl, array &$allSitemaps, array &$processedSitemaps): void
    {
        // Skip if already processed
        $normalizedUrl = $this->normalizeUrl($sitemapUrl);
        if (isset($processedSitemaps[$normalizedUrl])) {
            return;
        }
        $processedSitemaps[$normalizedUrl] = true;

        Log::info("Checking sitemap: $sitemapUrl");

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => $this->userAgent,
                    'Accept' => 'text/xml,application/xml,application/xhtml+xml,*/*',
                    'Connection' => 'close',
                ])
                ->withOptions([
                    'verify' => false,
                    'curl' => [
                        CURLOPT_FORBID_REUSE => true,
                        CURLOPT_FRESH_CONNECT => true,
                    ],
                ])
                ->get($sitemapUrl);

            if (!$response->successful()) {
                Log::debug("Sitemap not found or error: $sitemapUrl (HTTP {$response->status()})");
                return;
            }

            $content = $response->body();

            // Try to parse as XML
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($content);

            if (!$xml) {
                Log::debug("Invalid XML in sitemap: $sitemapUrl");
                return;
            }

            // Check if this is a sitemap index (contains other sitemaps)
            if (isset($xml->sitemap)) {
                Log::info("Found sitemap index with " . count($xml->sitemap) . " nested sitemaps");

                // This is a sitemap index - recursively process each nested sitemap
                foreach ($xml->sitemap as $sitemap) {
                    $nestedUrl = (string)$sitemap->loc;
                    if (!empty($nestedUrl)) {
                        // Small delay between sitemap checks
                        usleep(150 * 1000);
                        $this->discoverAllSitemaps($nestedUrl, $allSitemaps, $processedSitemaps);
                    }
                }
            }

            // Check if this sitemap has actual page URLs
            if (isset($xml->url) && count($xml->url) > 0) {
                // This sitemap has page URLs - add to list
                $allSitemaps[] = $sitemapUrl;
                Log::info("Added sitemap with " . count($xml->url) . " URLs: $sitemapUrl");
            }

        } catch (\Exception $e) {
            Log::warning("Error processing sitemap $sitemapUrl: " . $e->getMessage());
        }
    }

    /**
     * Extract page URLs from a single sitemap file.
     */
    protected function extractUrlsFromSitemap(string $sitemapUrl): array
    {
        $urls = [];

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => $this->userAgent,
                    'Accept' => 'text/xml,application/xml,*/*',
                    'Connection' => 'close',
                ])
                ->withOptions([
                    'verify' => false,
                    'curl' => [
                        CURLOPT_FORBID_REUSE => true,
                        CURLOPT_FRESH_CONNECT => true,
                    ],
                ])
                ->get($sitemapUrl);

            if (!$response->successful()) {
                return [];
            }

            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($response->body());

            if (!$xml || !isset($xml->url)) {
                return [];
            }

            foreach ($xml->url as $urlElement) {
                $url = (string)$urlElement->loc;
                if (!empty($url)) {
                    $urls[] = $url;
                }
            }

        } catch (\Exception $e) {
            Log::warning("Error extracting URLs from sitemap $sitemapUrl: " . $e->getMessage());
        }

        return $urls;
    }

    /**
     * Scrape API endpoint.
     */
    protected function scrapeApiEndpoint(): array
    {
        $response = $this->makeRequest($this->website->websiteUrl);

        if (!$response || !$response->successful()) {
            throw new \Exception('Failed to fetch API: HTTP ' . ($response ? $response->status() : 'unknown'));
        }

        $json = $response->json();
        $content = is_array($json) ? json_encode($json, JSON_PRETTY_PRINT) : $response->body();

        $pageData = [
            'url' => $this->website->websiteUrl,
            'title' => 'API Response: ' . $this->website->websiteName,
            'metaDescription' => null,
            'cleanContent' => $content,
            'rawHtml' => $response->body(),
            'structuredData' => $json,
            'httpStatus' => $response->status(),
            'contentType' => $response->header('Content-Type'),
            'pageSize' => strlen($response->body()),
            'depth' => 0,
        ];

        $this->savePage($pageData);
        return ['pages' => [$pageData['url']], 'dataKeys' => is_array($json) ? array_keys($json) : []];
    }

    /**
     * Crawl and scrape the whole website using optimized 3-phase approach with batching.
     *
     * Phase 1: Fast link discovery (lightweight, just extract links) - only on 'full' mode
     * Phase 2: Deduplication (remove already-scraped URLs from database)
     * Phase 3: Content scraping in batches (to avoid timeouts)
     *
     * Supports 'continue' mode to resume from saved queue.
     */
    protected function scrapeWholeSite(): array
    {
        Log::info("Starting scrape for: {$this->website->websiteUrl} (mode: {$this->scrapeMode})");

        $urlsToScrape = [];

        // ========================================
        // Check if we're continuing from a saved queue
        // ========================================
        if ($this->scrapeMode === 'continue') {
            Log::info("Continue mode: Loading from saved queue...");
            $savedQueue = $this->website->crawlQueue ?? [];

            if (empty($savedQueue)) {
                Log::info("No saved queue found, starting fresh discovery...");
                $this->scrapeMode = 'full'; // Fall back to full mode
            } else {
                // Convert saved queue to proper format and filter
                $urlsToScrape = $this->filterSavedQueue($savedQueue);
                $this->pagesDiscovered = count($savedQueue);
                Log::info("Loaded " . count($urlsToScrape) . " URLs from queue (after filtering)");
            }
        }

        // ========================================
        // PHASE 1: Fast Link Discovery (only for 'full' mode)
        // ========================================
        if ($this->scrapeMode === 'full' || $this->scrapeMode === 'discover_only') {
            Log::info("Phase 1: Discovering links...");
            $this->discoverAllLinks();
            Log::info("Phase 1 complete: Discovered {$this->pagesDiscovered} URLs");

            // Save discovered URLs to queue immediately (for recovery if timeout)
            $this->saveDiscoveredToQueue();

            if ($this->scrapeMode === 'discover_only') {
                return [
                    'pages' => [],
                    'pagesDiscovered' => $this->pagesDiscovered,
                    'pagesSkipped' => 0,
                    'remainingInQueue' => $this->pagesDiscovered,
                    'reachedMaxPagesLimit' => false,
                    'hasMore' => $this->pagesDiscovered > 0,
                    'phase' => 'discovery_complete',
                ];
            }

            // ========================================
            // PHASE 2: Deduplication & Filtering
            // ========================================
            Log::info("Phase 2: Deduplicating and filtering...");
            $urlsToScrape = $this->filterAndDeduplicateUrls();
            Log::info("Phase 2 complete: {$this->pagesSkipped} URLs skipped, " . count($urlsToScrape) . " URLs to scrape");

            // Save filtered queue (for recovery)
            $this->website->update([
                'crawlQueue' => array_slice($urlsToScrape, 0, 2000),
            ]);
        }

        // ========================================
        // PHASE 3: Content Scraping in Batches
        // ========================================
        Log::info("Phase 3: Scraping content (batch of {$this->maxPagesPerBatch} max)...");
        $scrapedPages = $this->scrapeUrlsInBatch($urlsToScrape);
        Log::info("Phase 3 complete: Scraped {$this->pagesScraped} pages in this batch");

        // Calculate remaining URLs
        $remainingUrls = array_filter($urlsToScrape, fn($u) => !isset($this->visitedUrls[$this->normalizeUrl($u['url'])]));
        $remainingCount = count($remainingUrls);

        // Save remaining queue for next batch/resume
        $this->website->update([
            'crawlQueue' => array_slice(array_values($remainingUrls), 0, 2000),
        ]);

        // Determine if there are more pages to scrape
        $this->hasMorePages = $remainingCount > 0 && $this->pagesScraped < $this->maxPages;

        $reachedMaxPages = $this->pagesScraped >= $this->maxPages && $remainingCount > 0;
        if ($reachedMaxPages) {
            $this->errors[] = "Reached max pages limit ({$this->maxPages}). {$remainingCount} URLs still pending.";
        }

        return [
            'pages' => $scrapedPages,
            'pagesDiscovered' => $this->pagesDiscovered,
            'pagesSkipped' => $this->pagesSkipped,
            'pagesScrapedThisBatch' => $this->pagesScraped,
            'remainingInQueue' => $remainingCount,
            'reachedMaxPagesLimit' => $reachedMaxPages,
            'hasMore' => $this->hasMorePages,
            'batchSize' => $this->maxPagesPerBatch,
        ];
    }

    /**
     * Save discovered URLs to queue (for recovery if timeout occurs).
     */
    protected function saveDiscoveredToQueue(): void
    {
        $queueData = [];
        foreach ($this->discoveredUrls as $normalizedUrl => $urlData) {
            $queueData[] = [
                'url' => $urlData['url'],
                'depth' => $urlData['depth'],
                'isPagination' => $urlData['isPagination'] ?? false,
            ];
        }

        $this->website->update([
            'crawlQueue' => array_slice($queueData, 0, 2000),
        ]);
    }

    /**
     * Filter saved queue - remove already scraped URLs.
     */
    protected function filterSavedQueue(array $savedQueue): array
    {
        // Load already-scraped URLs from database
        $existingPages = AiWebsitePage::where('websiteId', $this->website->id)
            ->where('delete_status', 'active')
            ->where('scrapeStatus', AiWebsitePage::STATUS_COMPLETED)
            ->pluck('urlHash')
            ->toArray();

        $existingHashes = array_flip($existingPages);

        $filtered = [];
        foreach ($savedQueue as $item) {
            $url = is_array($item) ? ($item['url'] ?? '') : $item;
            if (empty($url)) continue;

            $urlHash = hash('sha256', $url);

            // Skip if already scraped
            if (isset($existingHashes[$urlHash])) {
                $this->pagesSkipped++;
                continue;
            }

            $filtered[] = [
                'url' => $url,
                'normalizedUrl' => $this->normalizeUrl($url),
                'depth' => is_array($item) ? ($item['depth'] ?? 0) : 0,
                'isPagination' => is_array($item) ? ($item['isPagination'] ?? false) : false,
            ];
        }

        return $filtered;
    }

    /**
     * Scrape URLs in a single batch (limited number to avoid timeout).
     */
    protected function scrapeUrlsInBatch(array $urlsToScrape): array
    {
        $scrapedPages = [];
        $batchCount = 0;

        foreach ($urlsToScrape as $urlData) {
            // Check if we've reached batch limit
            if ($batchCount >= $this->maxPagesPerBatch) {
                Log::info("Batch limit reached ({$this->maxPagesPerBatch}), stopping for this request");
                break;
            }

            // Check if we've reached max pages overall
            if ($this->pagesScraped >= $this->maxPages) {
                break;
            }

            $url = $urlData['url'];
            $depth = $urlData['depth'] ?? 0;
            $normalizedUrl = $urlData['normalizedUrl'] ?? $this->normalizeUrl($url);

            // Skip if already visited in this session
            if (isset($this->visitedUrls[$normalizedUrl])) {
                continue;
            }

            $batchCount++;
            Log::info("Scraping [{$batchCount}/{$this->maxPagesPerBatch}]: $url");

            // Fetch and parse with full content extraction
            $pageData = $this->fetchAndParsePage($url, $depth);

            if ($pageData) {
                $this->savePage($pageData);
                $scrapedPages[] = $url;
            }

            $this->visitedUrls[$normalizedUrl] = true;

            // Random pause between requests
            $randomDelay = rand($this->scrapeDelayMin, $this->scrapeDelayMax);
            usleep($randomDelay * 1000);
        }

        return $scrapedPages;
    }

    /**
     * Phase 1: Fast link discovery - crawl site and collect all URLs without full content extraction.
     */
    protected function discoverAllLinks(): void
    {
        $discoveryQueue = [[
            'url' => $this->website->websiteUrl,
            'depth' => 0,
            'isPagination' => false,
        ]];

        $discovered = [];
        $visited = [];
        $maxDiscovery = $this->maxPages * 3; // Discover up to 3x max pages to ensure we find everything

        while (!empty($discoveryQueue) && count($discovered) < $maxDiscovery) {
            $current = array_shift($discoveryQueue);
            $url = $current['url'];
            $depth = $current['depth'];
            $isPagination = $current['isPagination'] ?? false;

            $normalizedUrl = $this->normalizeUrl($url);

            // Skip if already visited in discovery
            if (isset($visited[$normalizedUrl])) {
                continue;
            }

            // Skip if exceeds max depth (pagination doesn't count toward depth)
            if (!$isPagination && $depth > $this->maxDepth) {
                continue;
            }

            // Skip if URL shouldn't be scraped
            if (!$this->shouldScrapeUrl($url)) {
                $visited[$normalizedUrl] = true;
                continue;
            }

            // Mark as visited
            $visited[$normalizedUrl] = true;

            // Lightweight fetch - just get HTML to extract links
            try {
                $html = $this->fetchHtmlOnly($url);
                if (!$html) {
                    continue;
                }

                // Add this URL to discovered list
                $discovered[$normalizedUrl] = [
                    'url' => $url,
                    'normalizedUrl' => $normalizedUrl,
                    'depth' => $depth,
                    'isPagination' => $isPagination,
                ];
                $this->pagesDiscovered++;

                // Extract pagination links (highest priority - add to front)
                $paginationLinks = $this->extractPaginationLinks($html, $url);
                foreach (array_reverse($paginationLinks) as $pagLink) {
                    $normalizedPagLink = $this->normalizeUrl($pagLink);
                    if (!isset($visited[$normalizedPagLink]) && $this->isInternalUrl($pagLink)) {
                        array_unshift($discoveryQueue, [
                            'url' => $pagLink,
                            'depth' => $depth, // Same depth
                            'isPagination' => true,
                        ]);
                    }
                }

                // Extract regular links
                if ($depth < $this->maxDepth) {
                    $links = $this->extractLinksFromHtml($html, $url);
                    foreach ($links as $linkUrl) {
                        $normalizedLinkUrl = $this->normalizeUrl($linkUrl);
                        if (!isset($visited[$normalizedLinkUrl]) && $this->isInternalUrl($linkUrl)) {
                            if (!$this->isPaginationUrl($linkUrl)) {
                                $discoveryQueue[] = [
                                    'url' => $linkUrl,
                                    'depth' => $depth + 1,
                                    'isPagination' => false,
                                ];
                            }
                        }
                    }
                }

                // Short delay for discovery phase
                usleep($this->discoveryDelay * 1000);

            } catch (\Exception $e) {
                Log::warning("Discovery failed for $url: " . $e->getMessage());
                continue;
            }
        }

        $this->discoveredUrls = $discovered;
    }

    /**
     * Phase 2: Filter and deduplicate URLs - remove already-scraped URLs from database.
     */
    protected function filterAndDeduplicateUrls(): array
    {
        // Load already-scraped URLs from database
        $existingPages = AiWebsitePage::where('websiteId', $this->website->id)
            ->where('delete_status', 'active')
            ->where('scrapeStatus', AiWebsitePage::STATUS_COMPLETED)
            ->pluck('urlHash')
            ->toArray();

        // Convert to lookup array
        $existingHashes = array_flip($existingPages);

        $urlsToScrape = [];
        $paginationUrls = [];
        $regularUrls = [];

        foreach ($this->discoveredUrls as $normalizedUrl => $urlData) {
            $urlHash = hash('sha256', $urlData['url']);

            // Skip if already scraped in database
            if (isset($existingHashes[$urlHash])) {
                $this->pagesSkipped++;
                $this->alreadyScrapedUrls[$normalizedUrl] = true;
                continue;
            }

            // Separate pagination URLs (higher priority)
            if ($urlData['isPagination']) {
                $paginationUrls[] = $urlData;
            } else {
                $regularUrls[] = $urlData;
            }
        }

        // Sort by depth (shallower first)
        usort($paginationUrls, fn($a, $b) => $a['depth'] <=> $b['depth']);
        usort($regularUrls, fn($a, $b) => $a['depth'] <=> $b['depth']);

        // Pagination URLs first, then regular URLs
        $urlsToScrape = array_merge($paginationUrls, $regularUrls);

        // Limit to maxPages
        return array_slice($urlsToScrape, 0, $this->maxPages);
    }

    /**
     * Lightweight HTML fetch for discovery phase (no full parsing).
     * Connection is closed after each request to avoid RTO.
     */
    protected function fetchHtmlOnly(string $url): ?string
    {
        try {
            $response = Http::timeout(15) // Shorter timeout for discovery
                ->withHeaders([
                    'User-Agent' => $this->userAgent,
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Connection' => 'close', // Close connection after request
                ])
                ->withOptions([
                    'verify' => false,
                    'curl' => [
                        CURLOPT_FORBID_REUSE => true,    // Don't reuse connection
                        CURLOPT_FRESH_CONNECT => true,   // Force new connection
                    ],
                ])
                ->get($url);

            if (!$response->successful()) {
                return null;
            }

            $contentType = $response->header('Content-Type');
            if (!$this->isHtmlContent($contentType)) {
                return null;
            }

            return $response->body();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract just the links from HTML (lightweight version for discovery).
     */
    protected function extractLinksFromHtml(string $html, string $baseUrl): array
    {
        $links = [];

        if (preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/is', $html, $matches)) {
            foreach ($matches[1] as $href) {
                // Skip empty or javascript links
                if (empty($href) || Str::startsWith($href, ['javascript:', 'mailto:', 'tel:', '#'])) {
                    continue;
                }

                $absoluteUrl = $this->makeAbsoluteUrl($href, $baseUrl);

                // Only add internal URLs
                if ($this->isInternalUrl($absoluteUrl) && $this->shouldScrapeUrl($absoluteUrl)) {
                    $links[] = $absoluteUrl;
                }
            }
        }

        return array_unique($links);
    }

    /**
     * Normalize URL for comparison (removes trailing slashes, sorts query params).
     */
    protected function normalizeUrl(string $url): string
    {
        $parsed = parse_url($url);

        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $path = $parsed['path'] ?? '/';
        $query = $parsed['query'] ?? '';

        // Remove trailing slash from path (except for root)
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = rtrim($path, '/');
        }

        // Sort query parameters for consistent comparison
        if (!empty($query)) {
            parse_str($query, $params);
            ksort($params);
            $query = http_build_query($params);
        }

        $normalized = strtolower($scheme . '://' . $host) . $path;
        if (!empty($query)) {
            $normalized .= '?' . $query;
        }

        return $normalized;
    }

    /**
     * Extract pagination links from HTML.
     * This method discovers ALL pagination pages, not just those directly linked.
     */
    protected function extractPaginationLinks(string $html, string $baseUrl): array
    {
        $paginationLinks = [];
        $maxPageNumber = 1;
        $paginationBaseUrl = null;
        $paginationParamName = null;
        $paginationPathStyle = null;

        // Method 1: Look for rel="next" and rel="prev" links
        if (preg_match_all('/<a[^>]+rel=["\'](?:next|prev)["\'][^>]+href=["\']([^"\']+)["\']|<a[^>]+href=["\']([^"\']+)["\'][^>]+rel=["\'](?:next|prev)["\']/i', $html, $matches)) {
            foreach ($matches[1] as $i => $href) {
                $url = !empty($href) ? $href : ($matches[2][$i] ?? '');
                if (!empty($url)) {
                    $absoluteUrl = $this->makeAbsoluteUrl($url, $baseUrl);
                    $paginationLinks[] = $absoluteUrl;

                    // Detect pagination pattern
                    $this->detectPaginationPattern($absoluteUrl, $paginationBaseUrl, $paginationParamName, $paginationPathStyle, $maxPageNumber);
                }
            }
        }

        // Method 2: Look for pagination containers and extract links AND page numbers
        $paginationPatterns = [
            '/<(?:nav|div|ul)[^>]*class=["\'][^"\']*(?:pagination|pager|page-numbers|pages|wp-pagenavi)[^"\']*["\'][^>]*>(.*?)<\/(?:nav|div|ul)>/is',
            '/<(?:nav|div|ul)[^>]*id=["\'][^"\']*(?:pagination|pager)[^"\']*["\'][^>]*>(.*?)<\/(?:nav|div|ul)>/is',
        ];

        foreach ($paginationPatterns as $pattern) {
            if (preg_match_all($pattern, $html, $containerMatches)) {
                foreach ($containerMatches[1] as $container) {
                    // Extract all links from pagination container
                    if (preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/i', $container, $linkMatches, PREG_SET_ORDER)) {
                        foreach ($linkMatches as $linkMatch) {
                            $href = $linkMatch[1];
                            $linkText = strip_tags($linkMatch[2]);

                            if (!empty($href) && $href !== '#') {
                                $absoluteUrl = $this->makeAbsoluteUrl($href, $baseUrl);
                                if (!in_array($absoluteUrl, $paginationLinks)) {
                                    $paginationLinks[] = $absoluteUrl;
                                }

                                // Detect pagination pattern and max page
                                $this->detectPaginationPattern($absoluteUrl, $paginationBaseUrl, $paginationParamName, $paginationPathStyle, $maxPageNumber);

                                // Check if link text is a page number
                                if (is_numeric(trim($linkText))) {
                                    $pageNum = (int) trim($linkText);
                                    if ($pageNum > $maxPageNumber) {
                                        $maxPageNumber = $pageNum;
                                    }
                                }
                            }
                        }
                    }

                    // Also look for page numbers that might be in spans (current page indicator)
                    if (preg_match_all('/<(?:span|strong|em)[^>]*class=["\'][^"\']*(?:current|active|selected)[^"\']*["\'][^>]*>(\d+)<\/(?:span|strong|em)>/i', $container, $currentMatches)) {
                        foreach ($currentMatches[1] as $pageNum) {
                            if ((int)$pageNum > $maxPageNumber) {
                                $maxPageNumber = (int)$pageNum;
                            }
                        }
                    }

                    // Look for "Page X of Y" or similar patterns
                    if (preg_match('/(?:page\s*)?(\d+)\s*(?:of|\/)\s*(\d+)/i', $container, $pageOfMatch)) {
                        $totalPages = (int)$pageOfMatch[2];
                        if ($totalPages > $maxPageNumber) {
                            $maxPageNumber = $totalPages;
                        }
                    }

                    // Look for last page link (often labeled "Last" or ">>" or has large number)
                    if (preg_match('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(?:(?:last|»|>>|›{2,})|(\d{2,}))<\/a>/i', $container, $lastMatch)) {
                        if (!empty($lastMatch[2])) {
                            $pageNum = (int)$lastMatch[2];
                            if ($pageNum > $maxPageNumber) {
                                $maxPageNumber = $pageNum;
                            }
                        }
                        $absoluteUrl = $this->makeAbsoluteUrl($lastMatch[1], $baseUrl);
                        $this->detectPaginationPattern($absoluteUrl, $paginationBaseUrl, $paginationParamName, $paginationPathStyle, $maxPageNumber);
                    }
                }
            }
        }

        // Method 3: Look for links with page-related query parameters or paths
        if (preg_match_all('/<a[^>]+href=["\']([^"\']*(?:[\?&](?:page|p|pg|paged|offset|start)=\d+|\/page\/\d+)[^"\']*)["\'][^>]*>/i', $html, $matches)) {
            foreach ($matches[1] as $href) {
                $absoluteUrl = $this->makeAbsoluteUrl($href, $baseUrl);
                if (!in_array($absoluteUrl, $paginationLinks) && $this->isInternalUrl($absoluteUrl)) {
                    $paginationLinks[] = $absoluteUrl;
                    $this->detectPaginationPattern($absoluteUrl, $paginationBaseUrl, $paginationParamName, $paginationPathStyle, $maxPageNumber);
                }
            }
        }

        // Method 4: Look for links with pagination-related classes
        if (preg_match_all('/<a[^>]+class=["\'][^"\']*(?:page-link|page-number|pagination-link|next|prev|previous)[^"\']*["\'][^>]+href=["\']([^"\']+)["\']|<a[^>]+href=["\']([^"\']+)["\'][^>]+class=["\'][^"\']*(?:page-link|page-number|pagination-link|next|prev|previous)[^"\']*["\']/i', $html, $matches)) {
            foreach ($matches[1] as $i => $href) {
                $url = !empty($href) ? $href : ($matches[2][$i] ?? '');
                if (!empty($url) && $url !== '#') {
                    $absoluteUrl = $this->makeAbsoluteUrl($url, $baseUrl);
                    if (!in_array($absoluteUrl, $paginationLinks) && $this->isInternalUrl($absoluteUrl)) {
                        $paginationLinks[] = $absoluteUrl;
                        $this->detectPaginationPattern($absoluteUrl, $paginationBaseUrl, $paginationParamName, $paginationPathStyle, $maxPageNumber);
                    }
                }
            }
        }

        // Method 5: Generate ALL pagination page URLs if we detected the pattern
        // This ensures we don't miss pages that aren't directly linked (e.g., page 6-10 when only 1-5 are shown)
        if ($maxPageNumber > 1 && ($paginationBaseUrl || $paginationParamName || $paginationPathStyle)) {
            $generatedLinks = $this->generateAllPaginationUrls(
                $baseUrl,
                $maxPageNumber,
                $paginationBaseUrl,
                $paginationParamName,
                $paginationPathStyle
            );

            foreach ($generatedLinks as $link) {
                if (!in_array($link, $paginationLinks) && $this->isInternalUrl($link)) {
                    $paginationLinks[] = $link;
                }
            }
        }

        return array_unique($paginationLinks);
    }

    /**
     * Detect pagination URL pattern from a URL.
     */
    protected function detectPaginationPattern(
        string $url,
        ?string &$paginationBaseUrl,
        ?string &$paginationParamName,
        ?string &$paginationPathStyle,
        int &$maxPageNumber
    ): void {
        // Check for query parameter style: ?page=2, ?p=2, etc.
        $paramPatterns = [
            '/[\?&](page)=(\d+)/i',
            '/[\?&](p)=(\d+)/i',
            '/[\?&](pg)=(\d+)/i',
            '/[\?&](paged)=(\d+)/i',
        ];

        foreach ($paramPatterns as $pattern) {
            if (preg_match($pattern, $url, $match)) {
                $paginationParamName = strtolower($match[1]);
                $pageNum = (int)$match[2];
                if ($pageNum > $maxPageNumber) {
                    $maxPageNumber = $pageNum;
                }
                // Extract base URL without the page parameter
                $paginationBaseUrl = preg_replace('/([&\?])' . preg_quote($match[1], '/') . '=\d+&?/', '$1', $url);
                $paginationBaseUrl = rtrim($paginationBaseUrl, '?&');
                return;
            }
        }

        // Check for path style: /page/2, /p/2, etc.
        $pathPatterns = [
            '/(\/page\/)(\d+)/i' => '/page/',
            '/(\/p\/)(\d+)/i' => '/p/',
            '/(-page-)(\d+)/i' => '-page-',
            '/(\/pages\/)(\d+)/i' => '/pages/',
        ];

        foreach ($pathPatterns as $pattern => $style) {
            if (preg_match($pattern, $url, $match)) {
                $paginationPathStyle = $style;
                $pageNum = (int)$match[2];
                if ($pageNum > $maxPageNumber) {
                    $maxPageNumber = $pageNum;
                }
                // Extract base URL without the page path segment
                $paginationBaseUrl = preg_replace($pattern, '', $url);
                return;
            }
        }
    }

    /**
     * Generate all pagination URLs from 1 to maxPage.
     */
    protected function generateAllPaginationUrls(
        string $baseUrl,
        int $maxPage,
        ?string $paginationBaseUrl,
        ?string $paginationParamName,
        ?string $paginationPathStyle
    ): array {
        $urls = [];
        $base = $paginationBaseUrl ?: $baseUrl;

        // Limit to prevent runaway generation (safety cap)
        $maxPage = min($maxPage, 100);

        for ($page = 2; $page <= $maxPage; $page++) {
            if ($paginationParamName) {
                // Query parameter style
                $separator = strpos($base, '?') !== false ? '&' : '?';
                $urls[] = $base . $separator . $paginationParamName . '=' . $page;
            } elseif ($paginationPathStyle) {
                // Path style
                $urls[] = rtrim($base, '/') . $paginationPathStyle . $page;
            }
        }

        return $urls;
    }

    /**
     * Check if a URL looks like a pagination URL.
     */
    protected function isPaginationUrl(string $url): bool
    {
        // Check for common pagination patterns in URL
        $paginationPatterns = [
            '/[\?&](page|p|pg|paged|offset|start)=\d+/i',
            '/\/page\/\d+/i',
            '/\/p\/\d+/i',
            '/-page-\d+/i',
            '/_page_\d+/i',
        ];

        foreach ($paginationPatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fetch and parse a single page.
     */
    protected function fetchAndParsePage(string $url, int $depth, ?string $parentUrl = null): ?array
    {
        $startTime = microtime(true);

        try {
            $response = $this->makeRequest($url);

            if (!$response) {
                $this->errors[] = "Failed to fetch: $url";
                return null;
            }

            $responseTime = (int)((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                $this->errors[] = "HTTP {$response->status()}: $url";
                return [
                    'url' => $url,
                    'httpStatus' => $response->status(),
                    'scrapeStatus' => AiWebsitePage::STATUS_FAILED,
                    'scrapeError' => 'HTTP ' . $response->status(),
                    'depth' => $depth,
                    'parentUrl' => $parentUrl,
                    'responseTime' => $responseTime,
                ];
            }

            $contentType = $response->header('Content-Type');

            // Skip non-HTML content
            if (!$this->isHtmlContent($contentType)) {
                return null;
            }

            $html = $response->body();

            return [
                'url' => $url,
                'title' => $this->extractTitle($html),
                'metaDescription' => $this->extractMetaDescription($html),
                'metaKeywords' => $this->extractMetaKeywords($html),
                'rawHtml' => $html,
                'cleanContent' => $this->extractCleanContent($html),
                'structuredData' => $this->extractStructuredData($html),
                'headings' => $this->extractHeadings($html),
                'links' => $this->extractLinks($html, $url),
                'images' => $this->extractImages($html, $url),
                'language' => $this->detectLanguage($html),
                'httpStatus' => $response->status(),
                'contentType' => $contentType,
                'responseTime' => $responseTime,
                'pageSize' => strlen($html),
                'depth' => $depth,
                'parentUrl' => $parentUrl,
                'isIndexable' => $this->checkIndexability($html),
                'scrapeStatus' => AiWebsitePage::STATUS_COMPLETED,
            ];
        } catch (\Exception $e) {
            $this->errors[] = "Error scraping $url: " . $e->getMessage();
            Log::warning("Scrape error for $url: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Save or update a page record.
     */
    protected function savePage(array $pageData): void
    {
        $urlHash = hash('sha256', $pageData['url']);

        $existingPage = AiWebsitePage::where('websiteId', $this->website->id)
            ->where('urlHash', $urlHash)
            ->where('delete_status', 'active')
            ->first();

        $cleanContent = $pageData['cleanContent'] ?? '';
        $newHash = AiWebsitePage::generateContentHash($cleanContent);
        $hasChanges = false;

        if ($existingPage) {
            $hasChanges = $existingPage->contentHash !== $newHash;

            $existingPage->update([
                'title' => $pageData['title'] ?? $existingPage->title,
                'metaDescription' => $pageData['metaDescription'] ?? $existingPage->metaDescription,
                'metaKeywords' => $pageData['metaKeywords'] ?? $existingPage->metaKeywords,
                'rawHtml' => $pageData['rawHtml'] ?? $existingPage->rawHtml,
                'cleanContent' => $cleanContent,
                'structuredData' => $pageData['structuredData'] ?? $existingPage->structuredData,
                'headings' => $pageData['headings'] ?? $existingPage->headings,
                'links' => $pageData['links'] ?? $existingPage->links,
                'images' => $pageData['images'] ?? $existingPage->images,
                'wordCount' => str_word_count($cleanContent),
                'contentLength' => strlen($cleanContent),
                'contentHash' => $newHash,
                'language' => $pageData['language'] ?? $existingPage->language,
                'httpStatus' => $pageData['httpStatus'] ?? $existingPage->httpStatus,
                'contentType' => $pageData['contentType'] ?? $existingPage->contentType,
                'responseTime' => $pageData['responseTime'] ?? $existingPage->responseTime,
                'pageSize' => $pageData['pageSize'] ?? $existingPage->pageSize,
                'depth' => $pageData['depth'] ?? $existingPage->depth,
                'parentUrl' => $pageData['parentUrl'] ?? $existingPage->parentUrl,
                'isIndexable' => $pageData['isIndexable'] ?? true,
                'hasChanges' => $hasChanges,
                'lastScrapedAt' => now(),
                'contentChangedAt' => $hasChanges ? now() : $existingPage->contentChangedAt,
                'scrapeStatus' => $pageData['scrapeStatus'] ?? AiWebsitePage::STATUS_COMPLETED,
                'scrapeError' => $pageData['scrapeError'] ?? null,
            ]);
        } else {
            AiWebsitePage::create([
                'websiteId' => $this->website->id,
                'usersId' => $this->website->usersId,
                'url' => $pageData['url'],
                'title' => $pageData['title'] ?? null,
                'metaDescription' => $pageData['metaDescription'] ?? null,
                'metaKeywords' => $pageData['metaKeywords'] ?? null,
                'rawHtml' => $pageData['rawHtml'] ?? null,
                'cleanContent' => $cleanContent,
                'structuredData' => $pageData['structuredData'] ?? null,
                'headings' => $pageData['headings'] ?? null,
                'links' => $pageData['links'] ?? null,
                'images' => $pageData['images'] ?? null,
                'wordCount' => str_word_count($cleanContent),
                'contentLength' => strlen($cleanContent),
                'contentHash' => $newHash,
                'language' => $pageData['language'] ?? null,
                'httpStatus' => $pageData['httpStatus'] ?? null,
                'contentType' => $pageData['contentType'] ?? null,
                'responseTime' => $pageData['responseTime'] ?? null,
                'pageSize' => $pageData['pageSize'] ?? null,
                'depth' => $pageData['depth'] ?? 0,
                'parentUrl' => $pageData['parentUrl'] ?? null,
                'isIndexable' => $pageData['isIndexable'] ?? true,
                'hasChanges' => false,
                'firstScrapedAt' => now(),
                'lastScrapedAt' => now(),
                'scrapeStatus' => $pageData['scrapeStatus'] ?? AiWebsitePage::STATUS_COMPLETED,
                'scrapeError' => $pageData['scrapeError'] ?? null,
                'delete_status' => 'active',
            ]);
        }

        $this->pagesScraped++;
    }

    /**
     * Make an HTTP request with connection closure after each request.
     * This prevents RTO (Request Timeout) by not keeping connections alive.
     */
    protected function makeRequest(string $url): ?\Illuminate\Http\Client\Response
    {
        try {
            return Http::timeout($this->timeout)
                ->withHeaders([
                    'User-Agent' => $this->userAgent,
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                    'Connection' => 'close', // Close connection after request
                ])
                ->withOptions([
                    'verify' => false, // Skip SSL verification for problematic sites
                    'curl' => [
                        CURLOPT_FORBID_REUSE => true,    // Don't reuse connection
                        CURLOPT_FRESH_CONNECT => true,   // Force new connection
                    ],
                ])
                ->get($url);
        } catch (\Exception $e) {
            Log::warning("HTTP request failed for $url: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse a sitemap XML (legacy method - kept for compatibility).
     * Note: For full sitemap scraping, use scrapeSitemap() which handles nested sitemaps better.
     */
    protected function parseSitemap(string $sitemapUrl): array
    {
        $response = $this->makeRequest($sitemapUrl);

        if (!$response || !$response->successful()) {
            return [];
        }

        $urls = [];
        $content = $response->body();

        try {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($content);

            if (!$xml) {
                return [];
            }

            // Handle sitemap index (contains other sitemaps)
            if (isset($xml->sitemap)) {
                foreach ($xml->sitemap as $sitemap) {
                    $subSitemapUrl = (string)$sitemap->loc;
                    // Small delay between nested sitemap fetches
                    usleep(200 * 1000);
                    $urls = array_merge($urls, $this->parseSitemap($subSitemapUrl));
                }
            }

            // Handle regular sitemap (contains URLs)
            if (isset($xml->url)) {
                foreach ($xml->url as $urlElement) {
                    $urls[] = (string)$urlElement->loc;
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to parse sitemap: $sitemapUrl - " . $e->getMessage());
        }

        return $urls;
    }

    /**
     * Check if URL should be scraped based on filters.
     */
    protected function shouldScrapeUrl(string $url): bool
    {
        // Must be same host
        if (!$this->isInternalUrl($url)) {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH) ?? '/';

        // Check excluded paths
        foreach ($this->excludedPaths as $excludedPath) {
            if (Str::is($excludedPath, $path) || Str::startsWith($path, $excludedPath)) {
                return false;
            }
        }

        // If allowed paths specified, must match one
        if (!empty($this->allowedPaths)) {
            foreach ($this->allowedPaths as $allowedPath) {
                if (Str::is($allowedPath, $path) || Str::startsWith($path, $allowedPath)) {
                    return true;
                }
            }
            return false;
        }

        // Skip common non-content URLs
        $skipPatterns = [
            '*.pdf', '*.jpg', '*.jpeg', '*.png', '*.gif', '*.svg', '*.webp',
            '*.css', '*.js', '*.ico', '*.woff', '*.woff2', '*.ttf',
            '*/wp-admin/*', '*/wp-includes/*', '*/admin/*', '*/login*',
            '*/cart*', '*/checkout*', '*/account*', '*/my-account*',
            '*?*add-to-cart*', '*?*action=*',
        ];

        foreach ($skipPatterns as $pattern) {
            if (Str::is($pattern, $url) || Str::is($pattern, $path)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if URL is internal (same domain).
     */
    protected function isInternalUrl(string $url): bool
    {
        // Handle relative URLs
        if (Str::startsWith($url, '/') && !Str::startsWith($url, '//')) {
            return true;
        }

        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';

        return $host === $this->baseHost || Str::endsWith($host, '.' . $this->baseHost);
    }

    /**
     * Check if content type is HTML.
     */
    protected function isHtmlContent(?string $contentType): bool
    {
        if (!$contentType) {
            return true; // Assume HTML if unknown
        }

        return Str::contains(strtolower($contentType), ['text/html', 'application/xhtml']);
    }

    /**
     * Extract title from HTML.
     */
    protected function extractTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            return html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
        }
        return null;
    }

    /**
     * Extract meta description.
     */
    protected function extractMetaDescription(string $html): ?string
    {
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)["\']|<meta[^>]+content=["\']([^"\']*)["\'][^>]+name=["\']description["\']/i', $html, $matches)) {
            return html_entity_decode(trim($matches[1] ?: $matches[2]), ENT_QUOTES, 'UTF-8');
        }
        return null;
    }

    /**
     * Extract meta keywords.
     */
    protected function extractMetaKeywords(string $html): ?string
    {
        if (preg_match('/<meta[^>]+name=["\']keywords["\'][^>]+content=["\']([^"\']*)["\']|<meta[^>]+content=["\']([^"\']*)["\'][^>]+name=["\']keywords["\']/i', $html, $matches)) {
            return html_entity_decode(trim($matches[1] ?: $matches[2]), ENT_QUOTES, 'UTF-8');
        }
        return null;
    }

    /**
     * Extract clean text content from HTML.
     */
    protected function extractCleanContent(string $html): string
    {
        // Remove scripts, styles, and other non-content elements
        $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
        $html = preg_replace('/<noscript[^>]*>.*?<\/noscript>/is', '', $html);
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        // Remove header, footer, nav, aside (common non-content areas)
        $html = preg_replace('/<header[^>]*>.*?<\/header>/is', '', $html);
        $html = preg_replace('/<footer[^>]*>.*?<\/footer>/is', '', $html);
        $html = preg_replace('/<nav[^>]*>.*?<\/nav>/is', '', $html);
        $html = preg_replace('/<aside[^>]*>.*?<\/aside>/is', '', $html);

        // Convert to text
        $text = strip_tags($html);

        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        return $text;
    }

    /**
     * Extract content by CSS selector.
     */
    protected function extractBySelector(string $html, string $selector): string
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new DOMXPath($dom);

        // Convert CSS selector to XPath (simple conversion)
        $xpathQuery = $this->cssToXPath($selector);

        $nodes = $xpath->query($xpathQuery);
        $content = [];

        if ($nodes) {
            foreach ($nodes as $node) {
                $content[] = $this->extractCleanContent($dom->saveHTML($node));
            }
        }

        libxml_clear_errors();

        return implode("\n\n", $content);
    }

    /**
     * Simple CSS to XPath converter.
     */
    protected function cssToXPath(string $css): string
    {
        $css = trim($css);

        // Handle ID selector
        if (Str::startsWith($css, '#')) {
            $id = substr($css, 1);
            return "//*[@id='$id']";
        }

        // Handle class selector
        if (Str::startsWith($css, '.')) {
            $class = substr($css, 1);
            return "//*[contains(@class, '$class')]";
        }

        // Handle tag selector
        if (preg_match('/^[a-z]+$/i', $css)) {
            return "//$css";
        }

        // Handle tag.class
        if (preg_match('/^([a-z]+)\.([a-z0-9_-]+)$/i', $css, $matches)) {
            return "//{$matches[1]}[contains(@class, '{$matches[2]}')]";
        }

        // Handle tag#id
        if (preg_match('/^([a-z]+)#([a-z0-9_-]+)$/i', $css, $matches)) {
            return "//{$matches[1]}[@id='{$matches[2]}']";
        }

        // Default: treat as tag name
        return "//$css";
    }

    /**
     * Extract headings (h1-h6).
     */
    protected function extractHeadings(string $html): array
    {
        $headings = [];

        for ($i = 1; $i <= 6; $i++) {
            $pattern = '/<h' . $i . '[^>]*>(.*?)<\/h' . $i . '>/is';
            if (preg_match_all($pattern, $html, $matches)) {
                foreach ($matches[1] as $heading) {
                    $headings[] = [
                        'level' => 'h' . $i,
                        'text' => trim(strip_tags($heading)),
                    ];
                }
            }
        }

        return $headings;
    }

    /**
     * Extract links from HTML.
     */
    protected function extractLinks(string $html, string $baseUrl): array
    {
        $links = [];

        if (preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $href = $match[1];
                $text = trim(strip_tags($match[2]));

                // Skip empty or javascript links
                if (empty($href) || Str::startsWith($href, ['javascript:', 'mailto:', 'tel:', '#'])) {
                    continue;
                }

                // Convert relative to absolute URL
                $absoluteUrl = $this->makeAbsoluteUrl($href, $baseUrl);

                $links[] = [
                    'url' => $absoluteUrl,
                    'text' => Str::limit($text, 100),
                    'isInternal' => $this->isInternalUrl($absoluteUrl),
                ];
            }
        }

        return array_slice($links, 0, 500); // Limit to 500 links
    }

    /**
     * Extract images from HTML.
     */
    protected function extractImages(string $html, string $baseUrl): array
    {
        $images = [];

        if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/is', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $src = $match[1];
                $fullTag = $match[0];

                // Extract alt text
                $alt = '';
                if (preg_match('/alt=["\']([^"\']*)["\']/', $fullTag, $altMatch)) {
                    $alt = $altMatch[1];
                }

                // Convert relative to absolute URL
                $absoluteUrl = $this->makeAbsoluteUrl($src, $baseUrl);

                $images[] = [
                    'url' => $absoluteUrl,
                    'alt' => $alt,
                ];
            }
        }

        return array_slice($images, 0, 100); // Limit to 100 images
    }

    /**
     * Extract structured data (JSON-LD).
     */
    protected function extractStructuredData(string $html): ?array
    {
        $data = [];

        if (preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            foreach ($matches[1] as $json) {
                try {
                    $parsed = json_decode(trim($json), true);
                    if ($parsed) {
                        $data[] = $parsed;
                    }
                } catch (\Exception $e) {
                    // Skip invalid JSON
                }
            }
        }

        return empty($data) ? null : $data;
    }

    /**
     * Detect page language.
     */
    protected function detectLanguage(string $html): ?string
    {
        // Check html lang attribute
        if (preg_match('/<html[^>]+lang=["\']([^"\']+)["\']/', $html, $matches)) {
            return strtolower(substr($matches[1], 0, 2));
        }

        // Check meta content-language
        if (preg_match('/<meta[^>]+http-equiv=["\']content-language["\'][^>]+content=["\']([^"\']+)["\']/', $html, $matches)) {
            return strtolower(substr($matches[1], 0, 2));
        }

        return null;
    }

    /**
     * Check if page is indexable (no noindex).
     */
    protected function checkIndexability(string $html): bool
    {
        // Check robots meta tag
        if (preg_match('/<meta[^>]+name=["\']robots["\'][^>]+content=["\']([^"\']*)["\']/', $html, $matches)) {
            if (Str::contains(strtolower($matches[1]), 'noindex')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Convert relative URL to absolute.
     */
    protected function makeAbsoluteUrl(string $href, string $baseUrl): string
    {
        // Already absolute
        if (Str::startsWith($href, ['http://', 'https://', '//'])) {
            if (Str::startsWith($href, '//')) {
                return 'https:' . $href;
            }
            return $href;
        }

        $parsed = parse_url($baseUrl);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $basePath = $parsed['path'] ?? '/';

        // Absolute path
        if (Str::startsWith($href, '/')) {
            return "$scheme://$host$href";
        }

        // Relative path
        $baseDir = dirname($basePath);
        if ($baseDir === '\\' || $baseDir === '.') {
            $baseDir = '/';
        }

        return "$scheme://$host" . rtrim($baseDir, '/') . '/' . $href;
    }
}
