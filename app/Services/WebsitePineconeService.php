<?php

namespace App\Services;

use App\Models\AiWebsite;
use App\Models\AiWebsitePage;
use App\Models\AiRagSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebsitePineconeService
{
    /**
     * Pinecone API endpoints.
     */
    const PINECONE_API_BASE = 'https://api.pinecone.io';
    const PINECONE_PROD_DATA = 'https://prod-1-data.ke.pinecone.io';

    /**
     * The Pinecone settings for the current user.
     * Uses main RAG settings as single source of truth.
     */
    protected ?AiRagSetting $settings;

    /**
     * Initialize the service with user settings.
     * Uses the unified RAG settings (single knowledge base for all content types).
     *
     * @param int $userId
     */
    public function __construct(int $userId)
    {
        $this->settings = AiRagSetting::getOrCreateForUser($userId);
    }

    /**
     * Check if Pinecone is configured.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return $this->settings
            && !empty($this->settings->apiKey)
            && !empty($this->settings->indexName);
    }

    /**
     * Get the current settings.
     *
     * @return AiRagSetting|null
     */
    public function getSettings(): ?AiRagSetting
    {
        return $this->settings;
    }

    /**
     * Upload/update a website's compiled content to Pinecone.
     * All pages are compiled into a single file.
     *
     * @param AiWebsite $website
     * @return array
     */
    public function uploadWebsite(AiWebsite $website): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Pinecone is not configured. Please set up API settings first.',
            ];
        }

        // Ensure assistant exists
        $assistantCheck = $this->ensureAssistantExists();
        if (!$assistantCheck['success']) {
            return [
                'success' => false,
                'message' => 'Failed to verify Pinecone assistant: ' . $assistantCheck['message'],
            ];
        }

        try {
            // Mark as processing
            $website->update([
                'pineconeStatus' => AiWebsite::PINECONE_PROCESSING,
                'pineconeError' => null,
            ]);

            // Get all completed pages for this website
            $pages = AiWebsitePage::active()
                ->forWebsite($website->id)
                ->completed()
                ->orderBy('depth')
                ->orderBy('url')
                ->get();

            if ($pages->isEmpty()) {
                $website->update([
                    'pineconeStatus' => null,
                    'pineconeError' => 'No pages to upload',
                ]);

                return [
                    'success' => false,
                    'message' => 'No scraped pages found for this website.',
                ];
            }

            // If website already has a Pinecone file, delete it first (for updates)
            $wasUpdate = false;
            if ($website->pineconeFileId) {
                $wasUpdate = true;
                Log::info("Updating Pinecone content for website: {$website->websiteName} (deleting old file: {$website->pineconeFileId})");
                $this->deleteFromPinecone($website->pineconeFileId);
            }

            // Compile all pages into one document
            $content = $this->compileWebsiteContent($website, $pages);

            if (empty($content)) {
                $website->update([
                    'pineconeStatus' => AiWebsite::PINECONE_FAILED,
                    'pineconeError' => 'No content to upload',
                ]);

                return [
                    'success' => false,
                    'message' => 'No content to upload after compilation.',
                ];
            }

            // Generate filename for the website
            $filename = $this->generateFilename($website);

            // Upload to Pinecone
            $uploadResult = $this->uploadToPinecone($content, $filename);

            if ($uploadResult['success']) {
                $pineconeStatus = strtolower($uploadResult['pineconeStatus'] ?? 'processing');
                $status = ($pineconeStatus === 'available')
                    ? AiWebsite::PINECONE_INDEXED
                    : AiWebsite::PINECONE_PROCESSING;

                $website->update([
                    'pineconeFileId' => $uploadResult['fileId'],
                    'pineconeStatus' => $status,
                    'pineconeError' => null,
                    'lastRagSyncAt' => now(),
                ]);

                Log::info("Pinecone upload successful for website: {$website->websiteName}", [
                    'fileId' => $uploadResult['fileId'],
                    'status' => $status,
                    'pagesCompiled' => $pages->count(),
                    'wasUpdate' => $wasUpdate,
                ]);

                return [
                    'success' => true,
                    'message' => ($wasUpdate ? 'Updated' : 'Uploaded') . " {$pages->count()} pages as single file.",
                    'fileId' => $uploadResult['fileId'],
                    'pagesCompiled' => $pages->count(),
                    'wasUpdate' => $wasUpdate,
                ];
            } else {
                $website->update([
                    'pineconeStatus' => AiWebsite::PINECONE_FAILED,
                    'pineconeError' => $uploadResult['message'],
                ]);

                Log::error("Pinecone upload failed for website: {$website->websiteName}", [
                    'error' => $uploadResult['message'],
                ]);

                return [
                    'success' => false,
                    'message' => $uploadResult['message'],
                ];
            }
        } catch (\Exception $e) {
            $website->update([
                'pineconeStatus' => AiWebsite::PINECONE_FAILED,
                'pineconeError' => $e->getMessage(),
            ]);

            Log::error("Pinecone upload exception for website: {$website->websiteName}", [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Compile all pages of a website into a single document.
     *
     * @param AiWebsite $website
     * @param \Illuminate\Support\Collection $pages
     * @return string
     */
    protected function compileWebsiteContent(AiWebsite $website, $pages): string
    {
        $parts = [];

        // Header with website info
        $parts[] = "=" . str_repeat("=", 78);
        $parts[] = "WEBSITE: {$website->websiteName}";
        $parts[] = "URL: {$website->websiteUrl}";
        if ($website->description) {
            $parts[] = "DESCRIPTION: {$website->description}";
        }
        $parts[] = "TOTAL PAGES: {$pages->count()}";
        $parts[] = "COMPILED: " . now()->format('Y-m-d H:i:s');
        $parts[] = "=" . str_repeat("=", 78);
        $parts[] = "";

        // Add each page's content
        foreach ($pages as $index => $page) {
            $pageNum = $index + 1;
            $parts[] = "-" . str_repeat("-", 78);
            $parts[] = "PAGE {$pageNum}: {$page->title}";
            $parts[] = "URL: {$page->url}";
            $parts[] = "-" . str_repeat("-", 78);

            // Meta description
            if ($page->metaDescription) {
                $parts[] = "Description: {$page->metaDescription}";
                $parts[] = "";
            }

            // Main content
            if ($page->cleanContent) {
                $parts[] = $page->cleanContent;
            }

            // Headings summary (if useful)
            if (!empty($page->headings) && count($page->headings) > 0) {
                $headingTexts = array_map(fn($h) => $h['text'] ?? '', $page->headings);
                $headingTexts = array_filter($headingTexts);
                if (!empty($headingTexts) && count($headingTexts) <= 20) {
                    $parts[] = "";
                    $parts[] = "Key Topics: " . implode(' | ', array_slice($headingTexts, 0, 10));
                }
            }

            $parts[] = "";
            $parts[] = "";
        }

        // Footer
        $parts[] = "=" . str_repeat("=", 78);
        $parts[] = "END OF COMPILED WEBSITE CONTENT";
        $parts[] = "=" . str_repeat("=", 78);

        return implode("\n", $parts);
    }

    /**
     * Generate a unique filename for the website.
     *
     * @param AiWebsite $website
     * @return string
     */
    protected function generateFilename(AiWebsite $website): string
    {
        // Create a readable filename from the website name and domain
        $parsed = parse_url($website->websiteUrl);
        $host = $parsed['host'] ?? 'website';

        // Clean up the name
        $nameSlug = preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower($website->websiteName));
        $nameSlug = trim($nameSlug, '-');
        $nameSlug = substr($nameSlug, 0, 40) ?: 'website';

        // Include website ID for uniqueness
        return "{$nameSlug}_{$host}_{$website->id}.txt";
    }

    /**
     * Upload content to Pinecone Assistant as a file.
     *
     * @param string $content
     * @param string $filename
     * @return array
     */
    protected function uploadToPinecone(string $content, string $filename): array
    {
        try {
            $response = Http::timeout(120) // Longer timeout for larger files
                ->withHeaders([
                    'Api-Key' => $this->settings->apiKey,
                ])
                ->attach('file', $content, $filename)
                ->post(self::PINECONE_PROD_DATA . '/assistant/files/' . $this->settings->indexName);

            Log::info('Pinecone website upload response', [
                'status' => $response->status(),
                'filename' => $filename,
                'assistant' => $this->settings->indexName,
                'contentSize' => strlen($content),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'fileId' => $data['id'] ?? null,
                    'pineconeStatus' => $data['status'] ?? 'processing',
                ];
            } else {
                $error = $response->json();
                $errorMsg = $error['message'] ?? $error['error'] ?? $error['detail'] ?? json_encode($error);

                Log::error('Pinecone website upload failed', [
                    'status' => $response->status(),
                    'error' => $errorMsg,
                ]);

                return [
                    'success' => false,
                    'message' => $errorMsg,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Pinecone upload exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete a file from Pinecone.
     *
     * @param string $fileId
     * @return bool
     */
    public function deleteFromPinecone(string $fileId): bool
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Api-Key' => $this->settings->apiKey,
                ])
                ->delete(self::PINECONE_PROD_DATA . '/assistant/files/' . $this->settings->indexName . '/' . $fileId);

            Log::info("Pinecone file delete response for {$fileId}", [
                'status' => $response->status(),
            ]);

            return $response->successful() || $response->status() === 404;
        } catch (\Exception $e) {
            Log::warning("Failed to delete Pinecone file {$fileId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete website's file from Pinecone.
     *
     * @param AiWebsite $website
     * @return array
     */
    public function deleteWebsiteFromPinecone(AiWebsite $website): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Pinecone is not configured.',
            ];
        }

        if (!$website->pineconeFileId) {
            return [
                'success' => true,
                'message' => 'No Pinecone file to delete.',
            ];
        }

        $deleted = $this->deleteFromPinecone($website->pineconeFileId);

        if ($deleted) {
            $website->update([
                'pineconeFileId' => null,
                'pineconeStatus' => null,
                'pineconeError' => null,
            ]);

            return [
                'success' => true,
                'message' => 'Website removed from Pinecone.',
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to delete from Pinecone.',
        ];
    }

    /**
     * Clean up old per-page Pinecone files.
     * This removes all individual page files that were uploaded before.
     *
     * @param AiWebsite $website
     * @return array
     */
    public function cleanupOldPageFiles(AiWebsite $website): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Pinecone is not configured.',
                'deleted' => 0,
            ];
        }

        // Get all pages with pineconeFileId
        $pages = AiWebsitePage::active()
            ->forWebsite($website->id)
            ->whereNotNull('pineconeFileId')
            ->get();

        $deleted = 0;
        $failed = 0;

        foreach ($pages as $page) {
            if ($this->deleteFromPinecone($page->pineconeFileId)) {
                $page->update([
                    'pineconeFileId' => null,
                    'pineconeStatus' => null,
                    'pineconeError' => null,
                    'pineconeIndexedAt' => null,
                ]);
                $deleted++;
            } else {
                $failed++;
            }

            usleep(100 * 1000); // Small delay
        }

        return [
            'success' => true,
            'message' => "Cleaned up {$deleted} old page files. {$failed} failed.",
            'deleted' => $deleted,
            'failed' => $failed,
        ];
    }

    /**
     * Ensure the Pinecone assistant exists, create if not.
     *
     * @return array
     */
    protected function ensureAssistantExists(): array
    {
        try {
            // Check if assistant exists
            $response = Http::timeout(30)
                ->withHeaders([
                    'Api-Key' => $this->settings->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->get(self::PINECONE_API_BASE . '/assistant/assistants/' . $this->settings->indexName);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Assistant exists.'];
            }

            // Create assistant if it doesn't exist
            if ($response->status() === 404) {
                Log::info('Creating new Pinecone assistant for websites', ['name' => $this->settings->indexName]);

                $createResponse = Http::timeout(30)
                    ->withHeaders([
                        'Api-Key' => $this->settings->apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post(self::PINECONE_API_BASE . '/assistant/assistants', [
                        'name' => $this->settings->indexName,
                        'instructions' => 'You are a helpful assistant that answers questions based on the uploaded website content and knowledge base.',
                        'metadata' => [
                            'created_by' => 'ds-axis-websites',
                            'email' => $this->settings->email,
                        ],
                    ]);

                if ($createResponse->successful()) {
                    Log::info('Pinecone assistant created successfully', ['name' => $this->settings->indexName]);
                    return ['success' => true, 'message' => 'Assistant created.'];
                } else {
                    $error = $createResponse->json();
                    $errorMsg = $error['message'] ?? $error['error'] ?? $error['detail'] ?? json_encode($error);
                    return [
                        'success' => false,
                        'message' => 'Failed to create assistant: ' . $errorMsg,
                    ];
                }
            }

            $error = $response->json();
            $errorMsg = $error['message'] ?? $error['error'] ?? $error['detail'] ?? json_encode($error);
            return [
                'success' => false,
                'message' => 'Failed to verify assistant: ' . $errorMsg,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error checking assistant: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Refresh the Pinecone status for a website.
     *
     * @param AiWebsite $website
     * @return array
     */
    public function refreshWebsiteStatus(AiWebsite $website): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Pinecone is not configured.',
            ];
        }

        if (!$website->pineconeFileId) {
            return [
                'success' => false,
                'message' => 'Website has no Pinecone file ID.',
            ];
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Api-Key' => $this->settings->apiKey,
                ])
                ->get(self::PINECONE_PROD_DATA . '/assistant/files/' . $this->settings->indexName . '/' . $website->pineconeFileId);

            if ($response->successful()) {
                $data = $response->json();
                $pineconeStatus = strtolower($data['status'] ?? 'processing');

                $newStatus = match ($pineconeStatus) {
                    'available' => AiWebsite::PINECONE_INDEXED,
                    'processing' => AiWebsite::PINECONE_PROCESSING,
                    'failed' => AiWebsite::PINECONE_FAILED,
                    default => $website->pineconeStatus,
                };

                $updateData = ['pineconeStatus' => $newStatus];

                if ($newStatus === AiWebsite::PINECONE_FAILED) {
                    $updateData['pineconeError'] = $data['error_message'] ?? 'Processing failed in Pinecone';
                }

                $website->update($updateData);

                return [
                    'success' => true,
                    'message' => 'Status updated.',
                    'status' => $newStatus,
                    'pineconeStatus' => $pineconeStatus,
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to get status from Pinecone.',
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error refreshing status: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get Pinecone indexing statistics for a website.
     *
     * @param AiWebsite $website
     * @return array
     */
    public function getWebsiteStats(AiWebsite $website): array
    {
        $totalPages = AiWebsitePage::active()->forWebsite($website->id)->completed()->count();

        return [
            'totalPages' => $totalPages,
            'isIndexed' => $website->isIndexedInPinecone(),
            'status' => $website->pineconeStatus,
            'fileId' => $website->pineconeFileId,
            'lastSyncAt' => $website->lastRagSyncAt?->format('Y-m-d H:i:s'),
            'needsUpload' => $website->needsPineconeUpload(),
        ];
    }
}
