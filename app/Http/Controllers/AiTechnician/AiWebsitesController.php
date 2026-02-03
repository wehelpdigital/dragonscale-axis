<?php

namespace App\Http\Controllers\AiTechnician;

use App\Http\Controllers\Controller;
use App\Models\AiWebsite;
use App\Models\AiWebsitePage;
use App\Models\AiWebsiteSetting;
use App\Services\WebScraperService;
use App\Services\WebsitePineconeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AiWebsitesController extends Controller
{
    /**
     * Display the websites management page.
     */
    public function index()
    {
        $websites = AiWebsite::active()
            ->forUser(Auth::id())
            ->byPriority()
            ->get();

        $scrapeTypes = AiWebsite::getScrapeTypeLabels();
        $scrapeTypeDescriptions = AiWebsite::getScrapeTypeDescriptions();
        $frequencies = AiWebsite::getFrequencyLabels();

        // Load Pinecone settings for current user
        $settings = AiWebsiteSetting::getOrCreateForUser(Auth::id());

        return view('ai-technician.websites', compact(
            'websites',
            'scrapeTypes',
            'scrapeTypeDescriptions',
            'frequencies',
            'settings'
        ));
    }

    /**
     * Store a new website.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'websiteName' => 'required|string|max:255',
            'websiteUrl' => 'required|url|max:1000',
            'description' => 'nullable|string|max:1000',
            'scrapeType' => 'required|in:full_page,specific_selector,sitemap,api_endpoint,whole_site',
            'maxPages' => 'nullable|integer|min:1|max:1000',
            'maxDepth' => 'nullable|integer|min:1|max:30',
            'cssSelector' => 'required_if:scrapeType,specific_selector|nullable|string|max:500',
            'allowedPaths' => 'nullable|string|max:2000',
            'excludedPaths' => 'nullable|string|max:2000',
            'scrapeFrequency' => 'required|in:manual,hourly,daily,weekly,monthly',
            'priority' => 'nullable|integer|min:0|max:100',
            'isActive' => 'nullable|boolean',
        ], [
            'websiteName.required' => 'Website name is required.',
            'websiteUrl.required' => 'Website URL is required.',
            'websiteUrl.url' => 'Please enter a valid URL (including http:// or https://).',
            'scrapeType.required' => 'Please select a scrape type.',
            'cssSelector.required_if' => 'CSS selector is required when using "Specific CSS Selector" type.',
            'scrapeFrequency.required' => 'Please select a scrape frequency.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Parse paths from textarea (one per line)
            $allowedPaths = $this->parsePathsFromText($request->allowedPaths);
            $excludedPaths = $this->parsePathsFromText($request->excludedPaths);

            $website = AiWebsite::create([
                'usersId' => Auth::id(),
                'websiteName' => $request->websiteName,
                'websiteUrl' => rtrim($request->websiteUrl, '/'),
                'description' => $request->description,
                'scrapeType' => $request->scrapeType,
                'cssSelector' => $request->cssSelector,
                'allowedPaths' => $allowedPaths,
                'excludedPaths' => $excludedPaths,
                'scrapeFrequency' => $request->scrapeFrequency,
                'maxPages' => $request->maxPages ?? 500,
                'maxDepth' => $request->maxDepth ?? 5,
                'priority' => $request->priority ?? 0,
                'isActive' => $request->boolean('isActive', true),
                'lastScrapeStatus' => AiWebsite::STATUS_PENDING,
                'delete_status' => 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Website added successfully.',
                'data' => [
                    'id' => $website->id,
                    'websiteName' => $website->websiteName,
                    'domain' => $website->domain,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AI Website create error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to add website. Please try again.',
            ], 500);
        }
    }

    /**
     * Get a single website for editing.
     */
    public function show($id)
    {
        $website = AiWebsite::where('id', $id)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->first();

        if (!$website) {
            return response()->json([
                'success' => false,
                'message' => 'Website not found.',
            ], 404);
        }

        // Convert paths arrays to newline-separated strings for editing
        $website->allowedPathsText = is_array($website->allowedPaths)
            ? implode("\n", $website->allowedPaths)
            : '';
        $website->excludedPathsText = is_array($website->excludedPaths)
            ? implode("\n", $website->excludedPaths)
            : '';

        return response()->json([
            'success' => true,
            'data' => $website,
        ]);
    }

    /**
     * Update an existing website.
     */
    public function update(Request $request, $id)
    {
        $website = AiWebsite::where('id', $id)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->first();

        if (!$website) {
            return response()->json([
                'success' => false,
                'message' => 'Website not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'websiteName' => 'required|string|max:255',
            'websiteUrl' => 'required|url|max:1000',
            'description' => 'nullable|string|max:1000',
            'scrapeType' => 'required|in:full_page,specific_selector,sitemap,api_endpoint,whole_site',
            'maxPages' => 'nullable|integer|min:1|max:1000',
            'maxDepth' => 'nullable|integer|min:1|max:30',
            'cssSelector' => 'required_if:scrapeType,specific_selector|nullable|string|max:500',
            'allowedPaths' => 'nullable|string|max:2000',
            'excludedPaths' => 'nullable|string|max:2000',
            'scrapeFrequency' => 'required|in:manual,hourly,daily,weekly,monthly',
            'priority' => 'nullable|integer|min:0|max:100',
            'isActive' => 'nullable|boolean',
        ], [
            'websiteName.required' => 'Website name is required.',
            'websiteUrl.required' => 'Website URL is required.',
            'websiteUrl.url' => 'Please enter a valid URL (including http:// or https://).',
            'scrapeType.required' => 'Please select a scrape type.',
            'cssSelector.required_if' => 'CSS selector is required when using "Specific CSS Selector" type.',
            'scrapeFrequency.required' => 'Please select a scrape frequency.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Parse paths from textarea
            $allowedPaths = $this->parsePathsFromText($request->allowedPaths);
            $excludedPaths = $this->parsePathsFromText($request->excludedPaths);

            $website->update([
                'websiteName' => $request->websiteName,
                'websiteUrl' => rtrim($request->websiteUrl, '/'),
                'description' => $request->description,
                'scrapeType' => $request->scrapeType,
                'cssSelector' => $request->cssSelector,
                'allowedPaths' => $allowedPaths,
                'excludedPaths' => $excludedPaths,
                'scrapeFrequency' => $request->scrapeFrequency,
                'maxPages' => $request->maxPages ?? 500,
                'maxDepth' => $request->maxDepth ?? 5,
                'priority' => $request->priority ?? 0,
                'isActive' => $request->boolean('isActive', true),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Website updated successfully.',
                'data' => [
                    'id' => $website->id,
                    'websiteName' => $website->websiteName,
                    'domain' => $website->domain,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AI Website update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update website. Please try again.',
            ], 500);
        }
    }

    /**
     * Delete a website (soft delete).
     */
    public function destroy($id)
    {
        $website = AiWebsite::where('id', $id)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->first();

        if (!$website) {
            return response()->json([
                'success' => false,
                'message' => 'Website not found.',
            ], 404);
        }

        try {
            // Delete from Pinecone RAG if indexed
            if ($website->pineconeFileId) {
                $pineconeService = new WebsitePineconeService(Auth::id());
                if ($pineconeService->isConfigured()) {
                    $deleteResult = $pineconeService->deleteFromPinecone($website->pineconeFileId);
                    Log::info("Deleted website from Pinecone RAG: {$website->websiteName}", [
                        'fileId' => $website->pineconeFileId,
                        'success' => $deleteResult,
                    ]);
                }
            }

            // Soft delete the website record
            $website->update(['delete_status' => 'deleted']);

            // Also soft delete all associated pages
            AiWebsitePage::where('websiteId', $website->id)
                ->where('delete_status', 'active')
                ->update(['delete_status' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => 'Website deleted successfully (also removed from knowledge base).',
            ]);
        } catch (\Exception $e) {
            Log::error('AI Website delete error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete website. Please try again.',
            ], 500);
        }
    }

    /**
     * Toggle website active status.
     */
    public function toggleStatus($id)
    {
        $website = AiWebsite::where('id', $id)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->first();

        if (!$website) {
            return response()->json([
                'success' => false,
                'message' => 'Website not found.',
            ], 404);
        }

        try {
            $website->update(['isActive' => !$website->isActive]);

            return response()->json([
                'success' => true,
                'message' => $website->isActive ? 'Website enabled.' : 'Website disabled.',
                'data' => [
                    'isActive' => $website->isActive,
                    'statusBadge' => $website->status_badge,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AI Website toggle error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle website status.',
            ], 500);
        }
    }

    /**
     * Test website connectivity and scraping.
     */
    public function testScrape($id)
    {
        $website = AiWebsite::where('id', $id)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->first();

        if (!$website) {
            return response()->json([
                'success' => false,
                'message' => 'Website not found.',
            ], 404);
        }

        try {
            $website->update(['lastScrapeStatus' => AiWebsite::STATUS_IN_PROGRESS]);

            // Make a request to the website
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; DSAxisBot/1.0; +https://dsaxis.com)',
                ])
                ->get($website->websiteUrl);

            if ($response->successful()) {
                $contentLength = strlen($response->body());
                $contentType = $response->header('Content-Type');

                $website->update([
                    'lastScrapedAt' => now(),
                    'lastScrapeStatus' => AiWebsite::STATUS_SUCCESS,
                    'lastScrapeError' => null,
                    'scrapeCount' => $website->scrapeCount + 1,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Website is accessible and ready for scraping.',
                    'data' => [
                        'statusCode' => $response->status(),
                        'contentType' => $contentType,
                        'contentLength' => $this->formatBytes($contentLength),
                        'lastScrapedAt' => $website->lastScrapedAt->format('Y-m-d H:i:s'),
                    ],
                ]);
            } else {
                $website->update([
                    'lastScrapeStatus' => AiWebsite::STATUS_FAILED,
                    'lastScrapeError' => 'HTTP ' . $response->status() . ': ' . $response->reason(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Website returned an error: HTTP ' . $response->status(),
                    'data' => [
                        'statusCode' => $response->status(),
                        'error' => $response->reason(),
                    ],
                ], 400);
            }
        } catch (\Exception $e) {
            $website->update([
                'lastScrapeStatus' => AiWebsite::STATUS_FAILED,
                'lastScrapeError' => $e->getMessage(),
            ]);

            Log::error('AI Website test scrape error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to website: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all active websites for AI processing.
     */
    public function getActiveWebsites()
    {
        $websites = AiWebsite::active()
            ->forUser(Auth::id())
            ->enabled()
            ->byPriority()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $websites,
        ]);
    }

    /**
     * Parse paths from textarea text (one path per line).
     */
    private function parsePathsFromText(?string $text): ?array
    {
        if (empty($text)) {
            return null;
        }

        $lines = explode("\n", $text);
        $paths = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $paths[] = $line;
            }
        }

        return empty($paths) ? null : $paths;
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Initiate a full scrape of the website.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function scrape(Request $request, $id)
    {
        $website = AiWebsite::where('id', $id)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->first();

        if (!$website) {
            return response()->json([
                'success' => false,
                'message' => 'Website not found.',
            ], 404);
        }

        try {
            // Check if this is a continue request or fresh start
            $mode = $request->input('mode', 'full'); // 'full', 'continue', 'discover_only'

            $scraper = new WebScraperService($website, $mode);
            $result = $scraper->scrape();

            if ($result['success']) {
                $pagesScraped = $result['pagesScraped'];
                $pagesDiscovered = $result['data']['pagesDiscovered'] ?? 0;
                $pagesSkipped = $result['data']['pagesSkipped'] ?? 0;
                $remainingInQueue = $result['data']['remainingInQueue'] ?? 0;
                $reachedLimit = $result['data']['reachedMaxPagesLimit'] ?? false;
                $hasMore = $result['data']['hasMore'] ?? false;
                $batchSize = $result['data']['batchSize'] ?? 15;

                // Build detailed message
                $message = "Scraped {$pagesScraped} page(s) in this batch.";
                if ($pagesSkipped > 0) {
                    $message .= " Skipped {$pagesSkipped} already-scraped.";
                }
                if ($hasMore) {
                    $message .= " {$remainingInQueue} pages remaining.";
                }

                // Get total pages scraped for this website
                $totalPagesScraped = AiWebsitePage::where('websiteId', $website->id)
                    ->where('delete_status', 'active')
                    ->where('scrapeStatus', AiWebsitePage::STATUS_COMPLETED)
                    ->count();

                // Get Pinecone stats (website-level, not auto-uploading)
                $pineconeService = new WebsitePineconeService(Auth::id());
                $pineconeStats = $pineconeService->isConfigured()
                    ? $pineconeService->getWebsiteStats($website)
                    : null;

                // Note if RAG needs sync after scraping new content
                if ($pineconeStats && $pineconeStats['needsUpload']) {
                    $message .= " (RAG sync needed)";
                }

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'pagesScraped' => $pagesScraped,
                        'pagesDiscovered' => $pagesDiscovered,
                        'pagesSkipped' => $pagesSkipped,
                        'totalPagesScraped' => $totalPagesScraped,
                        'errors' => $result['errors'],
                        'lastScrapedAt' => $website->fresh()->lastScrapedAt?->format('Y-m-d H:i:s'),
                        'scrapeStatusBadge' => $website->fresh()->scrape_status_badge,
                        'remainingInQueue' => $remainingInQueue,
                        'reachedMaxPagesLimit' => $reachedLimit,
                        'hasMore' => $hasMore,
                        'batchSize' => $batchSize,
                        'pineconeStats' => $pineconeStats,
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Scraping failed.',
                    'data' => [
                        'pagesScraped' => $result['pagesScraped'],
                        'errors' => $result['errors'],
                        'scrapeStatusBadge' => $website->fresh()->scrape_status_badge,
                    ],
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('AI Website scrape error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Scraping failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get scraped pages for a website with pagination.
     */
    public function getPages(Request $request, $id)
    {
        $website = AiWebsite::where('id', $id)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->first();

        if (!$website) {
            return response()->json([
                'success' => false,
                'message' => 'Website not found.',
            ], 404);
        }

        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);
        $search = $request->input('search', '');

        $query = AiWebsitePage::active()
            ->forWebsite($id);

        // Apply search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        $totalCount = $query->count();
        $pages = $query->orderBy('depth')
            ->orderBy('lastScrapedAt', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(function ($page) {
                return [
                    'id' => $page->id,
                    'url' => $page->url,
                    'title' => $page->title,
                    'wordCount' => $page->wordCount,
                    'pageSize' => $page->formatted_page_size,
                    'depth' => $page->depth,
                    'httpStatus' => $page->httpStatus,
                    'scrapeStatus' => $page->scrapeStatus,
                    'statusBadge' => $page->status_badge,
                    'hasChanges' => $page->hasChanges,
                    'lastScrapedAt' => $page->lastScrapedAt?->format('Y-m-d H:i:s'),
                    'contentPreview' => $page->content_preview,
                    'pineconeStatus' => $page->pineconeStatus,
                    'pineconeStatusBadge' => $page->pinecone_status_badge,
                    'pineconeIndexedAt' => $page->pineconeIndexedAt?->format('Y-m-d H:i:s'),
                ];
            });

        $totalPages = ceil($totalCount / $perPage);

        // Get Pinecone stats
        $pineconeService = new WebsitePineconeService(Auth::id());
        $pineconeStats = $pineconeService->isConfigured()
            ? $pineconeService->getWebsiteStats($website)
            : null;

        return response()->json([
            'success' => true,
            'data' => [
                'pages' => $pages,
                'pagination' => [
                    'currentPage' => (int) $page,
                    'perPage' => (int) $perPage,
                    'totalItems' => $totalCount,
                    'totalPages' => $totalPages,
                    'hasMore' => $page < $totalPages,
                ],
                'totalSize' => $website->formatted_total_size,
                'pineconeConfigured' => $pineconeService->isConfigured(),
                'pineconeStats' => $pineconeStats,
            ],
        ]);
    }

    /**
     * Get page content for viewing.
     */
    public function getPageContent($websiteId, $pageId)
    {
        $website = AiWebsite::where('id', $websiteId)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->first();

        if (!$website) {
            return response()->json([
                'success' => false,
                'message' => 'Website not found.',
            ], 404);
        }

        $page = AiWebsitePage::where('id', $pageId)
            ->where('websiteId', $websiteId)
            ->where('delete_status', 'active')
            ->first();

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'url' => $page->url,
                'title' => $page->title,
                'metaDescription' => $page->metaDescription,
                'cleanContent' => $page->cleanContent,
                'headings' => $page->headings,
                'wordCount' => $page->wordCount,
                'language' => $page->language,
                'httpStatus' => $page->httpStatus,
                'pageSize' => $page->formatted_page_size,
                'responseTime' => $page->responseTime,
                'depth' => $page->depth,
                'parentUrl' => $page->parentUrl,
                'isIndexable' => $page->isIndexable,
                'firstScrapedAt' => $page->firstScrapedAt?->format('Y-m-d H:i:s'),
                'lastScrapedAt' => $page->lastScrapedAt?->format('Y-m-d H:i:s'),
                'hasChanges' => $page->hasChanges,
                'linksCount' => count($page->links ?? []),
                'imagesCount' => count($page->images ?? []),
            ],
        ]);
    }

    /**
     * Delete a scraped page.
     */
    public function deletePage($websiteId, $pageId)
    {
        $website = AiWebsite::where('id', $websiteId)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->first();

        if (!$website) {
            return response()->json([
                'success' => false,
                'message' => 'Website not found.',
            ], 404);
        }

        $page = AiWebsitePage::where('id', $pageId)
            ->where('websiteId', $websiteId)
            ->where('delete_status', 'active')
            ->first();

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found.',
            ], 404);
        }

        try {
            $page->update(['delete_status' => 'deleted']);

            // Note: With compiled RAG files, deleting a page requires re-sync
            // The website's RAG will need to be re-uploaded to reflect this change

            return response()->json([
                'success' => true,
                'message' => 'Page deleted. Re-sync RAG to update.',
            ]);
        } catch (\Exception $e) {
            Log::error('AI Website page delete error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete page.',
            ], 500);
        }
    }

    /**
     * Clear all scraped pages for a website.
     */
    public function clearPages($id)
    {
        $website = AiWebsite::where('id', $id)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->first();

        if (!$website) {
            return response()->json([
                'success' => false,
                'message' => 'Website not found.',
            ], 404);
        }

        try {
            // Delete all from Pinecone first
            $pineconeService = new WebsitePineconeService(Auth::id());
            if ($pineconeService->isConfigured()) {
                $pineconeService->deleteWebsiteFromPinecone($website);
            }

            AiWebsitePage::where('websiteId', $id)
                ->where('delete_status', 'active')
                ->update(['delete_status' => 'deleted']);

            $website->update([
                'pagesScraped' => 0,
                'crawlQueue' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'All scraped pages cleared.',
            ]);
        } catch (\Exception $e) {
            Log::error('AI Website clear pages error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear pages.',
            ], 500);
        }
    }

    /**
     * Manually upload website to Pinecone (compiled as single file).
     */
    public function uploadToPinecone(Request $request, $id)
    {
        $website = AiWebsite::where('id', $id)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->first();

        if (!$website) {
            return response()->json([
                'success' => false,
                'message' => 'Website not found.',
            ], 404);
        }

        try {
            $pineconeService = new WebsitePineconeService(Auth::id());

            if (!$pineconeService->isConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pinecone is not configured. Please set up API settings in the Settings tab.',
                ], 400);
            }

            // Clean up old per-page files first (one-time migration)
            $cleanupResult = $pineconeService->cleanupOldPageFiles($website);
            if ($cleanupResult['deleted'] > 0) {
                Log::info("Cleaned up {$cleanupResult['deleted']} old per-page Pinecone files for website: {$website->websiteName}");
            }

            // Upload website as single compiled file
            $result = $pineconeService->uploadWebsite($website);

            $stats = $pineconeService->getWebsiteStats($website);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => [
                    'pagesCompiled' => $result['pagesCompiled'] ?? 0,
                    'wasUpdate' => $result['wasUpdate'] ?? false,
                    'fileId' => $result['fileId'] ?? null,
                    'stats' => $stats,
                    'cleanedUp' => $cleanupResult['deleted'] ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AI Website Pinecone upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload to Pinecone: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh Pinecone status for a website.
     */
    public function refreshPineconeStatus($id)
    {
        $website = AiWebsite::where('id', $id)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->first();

        if (!$website) {
            return response()->json([
                'success' => false,
                'message' => 'Website not found.',
            ], 404);
        }

        try {
            $pineconeService = new WebsitePineconeService(Auth::id());

            if (!$pineconeService->isConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pinecone is not configured.',
                ], 400);
            }

            // Refresh website status from Pinecone
            $result = $pineconeService->refreshWebsiteStatus($website);
            $stats = $pineconeService->getWebsiteStats($website);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => [
                    'status' => $result['status'] ?? null,
                    'stats' => $stats,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AI Website Pinecone refresh error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check processing status for multiple websites.
     * Used for dynamic polling when page loads.
     */
    public function checkProcessingStatus(Request $request)
    {
        try {
            $websiteIds = $request->input('websiteIds', []);

            if (empty($websiteIds)) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }

            $pineconeService = new WebsitePineconeService(Auth::id());

            if (!$pineconeService->isConfigured()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Pinecone not configured',
                ]);
            }

            // Get websites that are in processing state
            $websites = AiWebsite::whereIn('id', $websiteIds)
                ->where('usersId', Auth::id())
                ->where('delete_status', 'active')
                ->whereIn('pineconeStatus', ['processing', 'pending'])
                ->get();

            $results = [];

            foreach ($websites as $website) {
                // Check if file exists and get its status
                $refreshResult = $pineconeService->refreshWebsiteStatus($website);

                $results[] = [
                    'id' => $website->id,
                    'pineconeStatus' => $website->fresh()->pineconeStatus,
                    'pineconeStatusBadge' => $website->fresh()->pinecone_status_badge,
                    'lastRagSyncHuman' => $website->fresh()->last_rag_sync_human,
                    'isComplete' => in_array($website->fresh()->pineconeStatus, ['indexed', 'failed']),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('AI Website check processing status error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save Pinecone settings.
     */
    public function saveSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'apiKey' => 'nullable|string|max:500',
            'indexName' => 'nullable|string|max:255',
            'indexHost' => 'nullable|string|max:500',
            'email' => 'nullable|email|max:255',
        ], [
            'apiKey.max' => 'API key is too long.',
            'indexName.max' => 'Index name is too long.',
            'indexHost.max' => 'Index host URL is too long.',
            'email.email' => 'Please enter a valid email address.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $settings = AiWebsiteSetting::getOrCreateForUser(Auth::id());

            // Only update apiKey if provided (not empty)
            $updateData = [
                'indexName' => $request->indexName,
                'indexHost' => $request->indexHost,
                'email' => $request->email,
            ];

            // Only update API key if a new value is provided
            if ($request->filled('apiKey')) {
                $updateData['apiKey'] = $request->apiKey;
            }

            $settings->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Pinecone settings saved successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('AI Website settings save error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save settings. Please try again.',
            ], 500);
        }
    }

    /**
     * Test Pinecone connection.
     */
    public function testSettings()
    {
        try {
            $settings = AiWebsiteSetting::getOrCreateForUser(Auth::id());

            if (!$settings->apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'API key is not configured.',
                ], 400);
            }

            if (!$settings->indexName) {
                return response()->json([
                    'success' => false,
                    'message' => 'Index/Assistant name is not configured.',
                ], 400);
            }

            // Test the Pinecone connection by listing assistants
            $response = Http::timeout(30)
                ->withHeaders([
                    'Api-Key' => $settings->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->get('https://api.pinecone.io/assistant/assistants');

            if ($response->successful()) {
                $data = $response->json();
                $assistants = $data['assistants'] ?? [];

                // Check if our configured assistant exists
                $found = collect($assistants)->firstWhere('name', $settings->indexName);

                if ($found) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Connection successful! Assistant "' . $settings->indexName . '" found.',
                        'data' => [
                            'assistantName' => $found['name'],
                            'status' => $found['status'] ?? 'unknown',
                        ],
                    ]);
                } else {
                    return response()->json([
                        'success' => true,
                        'message' => 'Connection successful! But assistant "' . $settings->indexName . '" was not found. Available: ' . implode(', ', array_column($assistants, 'name')),
                        'data' => [
                            'assistants' => array_column($assistants, 'name'),
                        ],
                    ]);
                }
            } else {
                $error = $response->json()['error']['message'] ?? $response->reason();
                return response()->json([
                    'success' => false,
                    'message' => 'Connection failed: ' . $error,
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('AI Website settings test error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
