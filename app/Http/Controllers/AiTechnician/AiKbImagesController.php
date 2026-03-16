<?php

namespace App\Http\Controllers\AiTechnician;

use App\Http\Controllers\Controller;
use App\Models\AiKbImage;
use App\Models\AiKbImageSetting;
use App\Services\VisionAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AiKbImagesController extends Controller
{
    /**
     * Pinecone API endpoints.
     */
    const PINECONE_API_BASE = 'https://api.pinecone.io';
    const PINECONE_PROD_DATA = 'https://prod-1-data.ke.pinecone.io';

    /**
     * Display the KB Images settings page.
     */
    public function index()
    {
        // Get KB Images specific Pinecone settings
        $settings = AiKbImageSetting::getOrCreate();

        // Get user's images
        $images = AiKbImage::active()
                        ->orderBy('created_at', 'desc')
            ->get();

        return view('ai-technician.kb-images', compact('settings', 'images'));
    }

    /**
     * Get images list via AJAX.
     */
    public function getImages()
    {
        $images = AiKbImage::active()
                        ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($image) {
                return [
                    'id' => $image->id,
                    'originalName' => $image->originalName,
                    'description' => $image->description,
                    'descriptionShort' => $image->description_short,
                    'fileSize' => $image->file_size_human,
                    'mimeType' => $image->mimeType,
                    'thumbnailUrl' => $image->thumbnail_url,
                    'pineconeStatus' => $image->pineconeStatus,
                    'statusDisplay' => $image->pinecone_status_display,
                    'statusBadgeClass' => $image->pinecone_status_badge,
                    'errorMessage' => $image->errorMessage,
                    'createdAt' => $image->created_at->format('M d, Y H:i'),
                    'indexedAt' => $image->indexedAt ? $image->indexedAt->format('M d, Y H:i') : null,
                    'canRetry' => $image->canRetry(),
                    'isProcessing' => $image->isProcessing(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $images,
        ]);
    }

    /**
     * Upload a new image with description and automatically sync to RAG.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,webp',
            'description' => 'required|string|min:10|max:5000',
        ], [
            'image.required' => 'Please select an image to upload.',
            'image.max' => 'Image size must be less than 10MB.',
            'image.mimes' => 'Supported formats: JPG, PNG, GIF, WebP.',
            'description.required' => 'Please provide a description for the image.',
            'description.min' => 'Description must be at least 10 characters.',
            'description.max' => 'Description must not exceed 5000 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $file = $request->file('image');
            $originalName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $mimeType = $file->getClientMimeType();
            $extension = strtolower($file->getClientOriginalExtension());

            // Calculate file hash for duplicate detection
            $fileHash = hash_file('sha256', $file->getRealPath());

            // Check for duplicate by hash
            $duplicate = AiKbImage::active()
                                ->where('fileHash', $fileHash)
                ->first();

            if ($duplicate) {
                return response()->json([
                    'success' => false,
                    'message' => 'This image has already been uploaded: "' . $duplicate->originalName . '"',
                    'isDuplicate' => true,
                ], 409);
            }

            // Generate unique filename
            $fileName = Str::uuid() . '.' . $extension;

            // Ensure directory exists
            if (!Storage::disk('public')->exists('kb-images')) {
                Storage::disk('public')->makeDirectory('kb-images');
            }

            // Store image locally
            $filePath = $file->storeAs('kb-images', $fileName, 'public');

            // Create database record
            $image = AiKbImage::create([
                'usersId' => Auth::id(),
                'fileName' => $fileName,
                'originalName' => $originalName,
                'filePath' => $filePath,
                'description' => $request->description,
                'fileSize' => $fileSize,
                'mimeType' => $mimeType,
                'fileHash' => $fileHash,
                'pineconeStatus' => AiKbImage::STATUS_PENDING,
                'delete_status' => 'active',
            ]);

            // Check if Pinecone is configured and auto-sync to RAG
            $settings = AiKbImageSetting::getOrCreate();
            $ragSyncResult = null;

            if ($settings && $settings->apiKey && $settings->indexName) {
                // Auto-sync to Pinecone RAG
                $ragSyncResult = $this->syncImageToPinecone($image, $settings);
            }

            // Refresh image to get updated status
            $image->refresh();

            $message = 'Image uploaded successfully.';
            if ($ragSyncResult) {
                if ($ragSyncResult['success']) {
                    $message = $ragSyncResult['message'];
                } else {
                    $message = 'Image saved locally. RAG sync failed: ' . $ragSyncResult['message'];
                }
            } else {
                $message = 'Image saved locally. Configure Pinecone settings to enable RAG sync.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'id' => $image->id,
                    'originalName' => $image->originalName,
                    'description' => $image->description,
                    'thumbnailUrl' => $image->thumbnail_url,
                    'pineconeStatus' => $image->pineconeStatus,
                    'statusDisplay' => $image->pinecone_status_display,
                    'statusBadgeClass' => $image->pinecone_status_badge,
                    'ragSynced' => $ragSyncResult ? $ragSyncResult['success'] : false,
                    'hasAiAnalysis' => !empty($image->aiAnalysis),
                    'aiProvider' => $image->aiProvider,
                    'aiModel' => $image->aiModel,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('KB Image upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ensure Pinecone assistant exists, create if not.
     */
    private function ensureAssistantExists(AiKbImageSetting $settings): array
    {
        try {
            // Check if assistant exists
            $response = Http::timeout(30)
                ->withHeaders([
                    'Api-Key' => $settings->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->get(self::PINECONE_API_BASE . '/assistant/assistants/' . $settings->indexName);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Assistant exists.'];
            }

            // Create assistant if it doesn't exist (404)
            if ($response->status() === 404) {
                Log::info('Creating new Pinecone assistant for KB Images', ['name' => $settings->indexName]);

                $createResponse = Http::timeout(30)
                    ->withHeaders([
                        'Api-Key' => $settings->apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post(self::PINECONE_API_BASE . '/assistant/assistants', [
                        'name' => $settings->indexName,
                        'instructions' => 'You are a helpful assistant that answers questions based on the uploaded images and their descriptions.',
                        'metadata' => [
                            'created_by' => 'ds-axis-kb-images',
                            'email' => $settings->email,
                        ],
                    ]);

                if ($createResponse->successful()) {
                    Log::info('Pinecone assistant created successfully for KB Images', ['name' => $settings->indexName]);
                    return ['success' => true, 'message' => 'Assistant created.'];
                } else {
                    $error = $createResponse->json();
                    $errorMsg = $error['message']
                        ?? ($error['error']['message'] ?? null)
                        ?? $error['detail']
                        ?? json_encode($error);
                    return [
                        'success' => false,
                        'message' => 'Failed to create assistant: ' . $errorMsg,
                    ];
                }
            }

            $error = $response->json();
            $errorMsg = $error['message']
                ?? ($error['error']['message'] ?? null)
                ?? $error['detail']
                ?? json_encode($error);
            return [
                'success' => false,
                'message' => 'Failed to verify assistant: ' . $errorMsg,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sync image to Pinecone RAG (internal method).
     */
    private function syncImageToPinecone(AiKbImage $image, AiKbImageSetting $settings): array
    {
        try {
            // Ensure assistant exists first
            $assistantCheck = $this->ensureAssistantExists($settings);
            if (!$assistantCheck['success']) {
                $image->update([
                    'pineconeStatus' => AiKbImage::STATUS_FAILED,
                    'errorMessage' => $assistantCheck['message'],
                ]);
                return $assistantCheck;
            }

            // Update status to processing
            $image->update([
                'pineconeStatus' => AiKbImage::STATUS_PROCESSING,
                'errorMessage' => null,
            ]);

            // Get file path
            $filePath = Storage::disk('public')->path($image->filePath);

            if (!file_exists($filePath)) {
                throw new \Exception('Image file not found on disk.');
            }

            // Use Vision AI to analyze the image
            $visionService = new VisionAnalysisService(Auth::id());
            $aiAnalysis = null;
            $aiProvider = null;
            $aiModel = null;

            if ($visionService->isAvailable()) {
                Log::info('Analyzing image with Vision AI', [
                    'imageId' => $image->id,
                    'provider' => $visionService->getProvider(),
                ]);

                $analysisResult = $visionService->analyzeImage($filePath, $image->description);

                if ($analysisResult['success']) {
                    $aiAnalysis = $analysisResult['analysis'];
                    $aiProvider = $analysisResult['provider'];
                    $aiModel = $analysisResult['model'];

                    // Store the AI analysis in the database
                    $image->update([
                        'aiAnalysis' => $aiAnalysis,
                        'aiProvider' => $aiProvider,
                        'aiModel' => $aiModel,
                    ]);

                    Log::info('Vision AI analysis completed', [
                        'imageId' => $image->id,
                        'provider' => $aiProvider,
                        'model' => $aiModel,
                        'analysisLength' => strlen($aiAnalysis),
                    ]);
                } else {
                    Log::warning('Vision AI analysis failed, using user description only', [
                        'imageId' => $image->id,
                        'error' => $analysisResult['message'],
                    ]);
                }
            } else {
                Log::info('Vision AI not configured, using user description only', [
                    'imageId' => $image->id,
                ]);
            }

            // Build comprehensive text content for RAG
            $textContent = "IMAGE KNOWLEDGE BASE ENTRY\n";
            $textContent .= "==========================\n\n";
            $textContent .= "Image File: " . $image->originalName . "\n";
            $textContent .= "File Type: " . $image->mimeType . "\n";
            $textContent .= "File Size: " . $this->formatFileSize($image->fileSize) . "\n";
            $textContent .= "Uploaded: " . $image->created_at->format('Y-m-d H:i:s') . "\n\n";

            $textContent .= "USER DESCRIPTION:\n";
            $textContent .= "-----------------\n";
            $textContent .= $image->description . "\n\n";

            if ($aiAnalysis) {
                $textContent .= "AI VISION ANALYSIS:\n";
                $textContent .= "-------------------\n";
                $textContent .= "(Analyzed by " . $aiProvider . " - " . $aiModel . ")\n\n";
                $textContent .= $aiAnalysis . "\n\n";
            }

            $textContent .= "---\n";
            $textContent .= "Source: KB Images | ID: " . $image->id . "\n";

            $textFilename = pathinfo($image->originalName, PATHINFO_FILENAME) . '_kb-image.txt';

            $metadata = json_encode([
                'type' => 'image-description',
                'imageId' => $image->id,
                'originalImageName' => $image->originalName,
                'mimeType' => $image->mimeType,
                'source' => 'kb-images',
                'hasAiAnalysis' => !empty($aiAnalysis),
                'aiProvider' => $aiProvider,
            ]);

            $response = Http::withHeaders([
                'Api-Key' => $settings->apiKey,
            ])->asMultipart()->post(self::PINECONE_PROD_DATA . '/assistant/files/' . $settings->indexName, [
                [
                    'name' => 'file',
                    'contents' => $textContent,
                    'filename' => $textFilename,
                ],
                [
                    'name' => 'metadata',
                    'contents' => $metadata,
                ],
            ]);

            Log::info('Pinecone KB image upload response', [
                'imageId' => $image->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $pineconeStatus = strtolower($data['status'] ?? 'processing');
                $newStatus = ($pineconeStatus === 'available')
                    ? AiKbImage::STATUS_INDEXED
                    : AiKbImage::STATUS_PROCESSING;

                $image->update([
                    'pineconeStatus' => $newStatus,
                    'pineconeFileId' => $data['id'] ?? null,
                    'indexedAt' => ($newStatus === AiKbImage::STATUS_INDEXED) ? now() : null,
                ]);

                $message = ($newStatus === AiKbImage::STATUS_INDEXED)
                    ? 'Image uploaded and indexed in RAG successfully.'
                    : 'Image uploaded to RAG. Processing in progress.';

                return [
                    'success' => true,
                    'message' => $message,
                    'status' => $newStatus,
                    'fileId' => $data['id'] ?? null,
                ];
            } else {
                $error = $response->json();

                // Handle nested error structure from Pinecone API
                // Response format: {"error":{"code":"NOT_FOUND","message":"Resource not found"},"status":404}
                $errorMsg = $error['message']
                    ?? ($error['error']['message'] ?? null)
                    ?? $error['detail']
                    ?? (is_array($error['error'] ?? null) ? json_encode($error['error']) : ($error['error'] ?? null))
                    ?? json_encode($error);

                $image->update([
                    'pineconeStatus' => AiKbImage::STATUS_FAILED,
                    'errorMessage' => is_string($errorMsg) ? $errorMsg : json_encode($errorMsg),
                ]);

                return [
                    'success' => false,
                    'message' => is_string($errorMsg) ? $errorMsg : json_encode($errorMsg),
                ];
            }

        } catch (\Exception $e) {
            Log::error('KB Image Pinecone sync error: ' . $e->getMessage());

            $image->update([
                'pineconeStatus' => AiKbImage::STATUS_FAILED,
                'errorMessage' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete an image.
     */
    public function destroy($id)
    {
        $image = AiKbImage::where('id', $id)
                        ->where('delete_status', 'active')
            ->first();

        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found.',
            ], 404);
        }

        try {
            // Delete from Pinecone if it has a Pinecone file ID
            if ($image->pineconeFileId) {
                $settings = AiKbImageSetting::getOrCreate();
                if ($settings && $settings->apiKey && $settings->indexName) {
                    $deleteResult = $this->deleteFromPinecone($settings, $image->pineconeFileId);
                    if (!$deleteResult['success']) {
                        Log::warning('Pinecone delete warning for image: ' . $deleteResult['message'], [
                            'imageId' => $image->id,
                            'pineconeFileId' => $image->pineconeFileId,
                        ]);
                    }
                }
            }

            // Delete local file
            if ($image->filePath && Storage::disk('public')->exists($image->filePath)) {
                Storage::disk('public')->delete($image->filePath);
            }

            // Soft delete
            $image->update(['delete_status' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('KB Image delete error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload image to Pinecone (manual sync).
     */
    public function uploadToPinecone($id)
    {
        $image = AiKbImage::where('id', $id)
                        ->where('delete_status', 'active')
            ->first();

        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found.',
            ], 404);
        }

        if ($image->pineconeStatus === AiKbImage::STATUS_INDEXED) {
            return response()->json([
                'success' => false,
                'message' => 'Image is already indexed in Pinecone.',
            ], 400);
        }

        $settings = AiKbImageSetting::getOrCreate();

        if (!$settings || !$settings->apiKey || !$settings->indexName) {
            return response()->json([
                'success' => false,
                'message' => 'Please configure your Pinecone API settings first (in Settings tab).',
            ], 400);
        }

        // Use shared sync method
        $result = $this->syncImageToPinecone($image, $settings);
        $image->refresh();

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'status' => $image->pineconeStatus,
                    'statusDisplay' => $image->pinecone_status_display,
                    'statusBadgeClass' => $image->pinecone_status_badge,
                ],
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Pinecone upload failed: ' . $result['message'],
            ], 400);
        }
    }

    /**
     * Refresh Pinecone status for an image.
     */
    public function refreshPineconeStatus($id)
    {
        $image = AiKbImage::where('id', $id)
                        ->where('delete_status', 'active')
            ->first();

        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found.',
            ], 404);
        }

        if (!$image->pineconeFileId) {
            return response()->json([
                'success' => false,
                'message' => 'No Pinecone file ID associated with this image.',
            ], 400);
        }

        $settings = AiKbImageSetting::getOrCreate();

        if (!$settings || !$settings->apiKey || !$settings->indexName) {
            return response()->json([
                'success' => false,
                'message' => 'Please configure your Pinecone API settings first (in Settings tab).',
            ], 400);
        }

        try {
            $response = Http::withHeaders([
                'Api-Key' => $settings->apiKey,
            ])->get(self::PINECONE_PROD_DATA . '/assistant/files/' . $settings->indexName . '/' . $image->pineconeFileId);

            if ($response->successful()) {
                $data = $response->json();
                $pineconeStatus = strtolower($data['status'] ?? 'processing');

                $newStatus = match($pineconeStatus) {
                    'available' => AiKbImage::STATUS_INDEXED,
                    'processing' => AiKbImage::STATUS_PROCESSING,
                    'failed' => AiKbImage::STATUS_FAILED,
                    default => $image->pineconeStatus,
                };

                $updateData = ['pineconeStatus' => $newStatus];

                if ($newStatus === AiKbImage::STATUS_INDEXED && !$image->indexedAt) {
                    $updateData['indexedAt'] = now();
                }

                if ($newStatus === AiKbImage::STATUS_FAILED) {
                    $updateData['errorMessage'] = $data['error_message'] ?? 'Processing failed';
                }

                $image->update($updateData);

                return response()->json([
                    'success' => true,
                    'message' => 'Status: ' . $image->fresh()->pinecone_status_display,
                    'data' => [
                        'status' => $image->fresh()->pineconeStatus,
                        'statusDisplay' => $image->fresh()->pinecone_status_display,
                        'statusBadgeClass' => $image->fresh()->pinecone_status_badge,
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get status from Pinecone.',
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('KB Image status refresh error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retry failed Pinecone upload.
     */
    public function retryUpload($id)
    {
        $image = AiKbImage::where('id', $id)
                        ->where('delete_status', 'active')
            ->where('pineconeStatus', AiKbImage::STATUS_FAILED)
            ->first();

        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found or cannot be retried.',
            ], 404);
        }

        return $this->uploadToPinecone($id);
    }

    /**
     * Update image description.
     */
    public function update(Request $request, $id)
    {
        $image = AiKbImage::where('id', $id)
                        ->where('delete_status', 'active')
            ->first();

        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'required|string|min:10|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $image->update([
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Description updated successfully.',
            'data' => [
                'description' => $image->description,
            ],
        ]);
    }

    /**
     * Delete image from Pinecone.
     *
     * @param AiKbImageSetting $settings
     * @param string $pineconeFileId The Pinecone file ID to delete
     * @return array
     */
    private function deleteFromPinecone($settings, string $pineconeFileId): array
    {
        try {
            $response = Http::withHeaders([
                'Api-Key' => $settings->apiKey,
            ])->delete(self::PINECONE_PROD_DATA . '/assistant/files/' . $settings->indexName . '/' . $pineconeFileId);

            Log::info('Pinecone image delete response', [
                'status' => $response->status(),
                'pineconeFileId' => $pineconeFileId,
                'assistant' => $settings->indexName,
            ]);

            if ($response->successful() || $response->status() === 404) {
                // 404 means file already doesn't exist, which is fine
                return ['success' => true, 'message' => 'Image deleted from Pinecone'];
            }

            return [
                'success' => false,
                'message' => 'Pinecone returned status ' . $response->status() . ': ' . $response->body(),
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to delete image from Pinecone: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Format file size for display.
     */
    private function formatFileSize($bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    /**
     * Save Pinecone settings for KB Images.
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
            $settings = AiKbImageSetting::getOrCreate();

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
            Log::error('KB Images settings save error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save settings. Please try again.',
            ], 500);
        }
    }

    /**
     * Test Pinecone connection for KB Images.
     */
    public function testSettings()
    {
        try {
            $settings = AiKbImageSetting::getOrCreate();

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
                    $availableNames = array_column($assistants, 'name');
                    return response()->json([
                        'success' => true,
                        'message' => 'Connection successful! But assistant "' . $settings->indexName . '" was not found.' .
                            (count($availableNames) > 0 ? ' Available: ' . implode(', ', $availableNames) : ''),
                        'data' => [
                            'assistants' => $availableNames,
                        ],
                    ]);
                }
            } else {
                $error = $response->json();
                $errorMsg = $error['message'] ?? $error['error'] ?? 'Unknown error';
                return response()->json([
                    'success' => false,
                    'message' => 'Connection failed: ' . $errorMsg,
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('KB Images Pinecone connection test error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
