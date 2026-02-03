<?php

namespace App\Http\Controllers\AiTechnician;

use App\Http\Controllers\Controller;
use App\Models\AiRagSetting;
use App\Models\AiRagFile;
use App\Models\AiWebsite;
use App\Models\AiWebsiteSetting;
use App\Models\AiKbImage;
use App\Models\AiKbImageSetting;
use App\Models\AiExternalProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RagSettingsController extends Controller
{
    /**
     * Pinecone Assistant API base URL.
     */
    const PINECONE_API_BASE = 'https://api.pinecone.io';
    const PINECONE_PROD_DATA = 'https://prod-1-data.ke.pinecone.io';

    /**
     * Display the RAG settings page with tabs.
     */
    public function index()
    {
        $settings = AiRagSetting::getOrCreateForUser(Auth::id());
        $files = AiRagFile::active()
            ->forUser(Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('ai-technician.rag-settings', compact('settings', 'files'));
    }

    /**
     * Display the unified Knowledge Base page.
     */
    public function unifiedIndex()
    {
        // Main RAG settings (used for API key and documents index)
        $settings = AiRagSetting::getOrCreateForUser(Auth::id());

        // Website settings (for websites index name)
        $websiteSettings = AiWebsiteSetting::getOrCreateForUser(Auth::id());

        // Get all documents
        $files = AiRagFile::active()
            ->forUser(Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        // Get all websites
        $websites = AiWebsite::active()
            ->forUser(Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        // Get all images
        $images = AiKbImage::active()
            ->forUser(Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        // Get all external products
        $products = AiExternalProduct::active()
            ->forUser(Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('ai-technician.knowledge-base', compact(
            'settings',
            'websiteSettings',
            'files',
            'websites',
            'images',
            'products'
        ));
    }

    /**
     * Save unified settings (single RAG index for all content types).
     */
    public function saveUnifiedSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'apiKey' => 'required|string|min:10',
            'indexName' => 'required|string|max:255',
            'indexHost' => 'nullable|string|max:500',
            'email' => 'nullable|email|max:255',
        ], [
            'apiKey.required' => 'API Key is required.',
            'apiKey.min' => 'API Key must be at least 10 characters.',
            'indexName.required' => 'Index/Assistant Name is required.',
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
            // Save main RAG settings (single source of truth)
            $settings = AiRagSetting::getOrCreateForUser(Auth::id());
            $settings->update([
                'apiKey' => $request->apiKey,
                'indexName' => $request->indexName,
                'indexHost' => $request->indexHost,
                'email' => $request->email,
            ]);

            // Sync website settings to use the SAME index
            $websiteSettings = AiWebsiteSetting::getOrCreateForUser(Auth::id());
            $websiteSettings->update([
                'apiKey' => $request->apiKey,
                'indexName' => $request->indexName, // Same index as docs
                'indexHost' => $request->indexHost,
                'email' => $request->email,
            ]);

            // Sync image settings to use the SAME index
            $imageSettings = AiKbImageSetting::getOrCreateForUser(Auth::id());
            $imageSettings->update([
                'apiKey' => $request->apiKey,
                'indexName' => $request->indexName, // Same index as docs
                'indexHost' => $request->indexHost,
                'email' => $request->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Settings saved successfully. All content types will use the same knowledge base.',
            ]);
        } catch (\Exception $e) {
            Log::error('Unified settings save error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save settings. Please try again.',
            ], 500);
        }
    }

    /**
     * Store or update RAG settings.
     */
    public function storeSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'apiKey' => 'required|string|min:10',
            'indexName' => 'required|string|max:255',
            'indexHost' => 'nullable|string|max:500',
            'email' => 'nullable|email|max:255',
        ], [
            'apiKey.required' => 'API Key is required.',
            'apiKey.min' => 'API Key must be at least 10 characters.',
            'indexName.required' => 'Index/Assistant Name is required.',
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
            $settings = AiRagSetting::getOrCreateForUser(Auth::id());

            $settings->update([
                'apiKey' => $request->apiKey,
                'indexName' => $request->indexName,
                'indexHost' => $request->indexHost,
                'email' => $request->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Settings saved successfully.',
                'data' => [
                    'maskedApiKey' => $settings->masked_api_key,
                    'indexName' => $settings->indexName,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('RAG Settings save error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save settings. Please try again.',
            ], 500);
        }
    }

    /**
     * Test the Pinecone connection.
     */
    public function testConnection(Request $request)
    {
        $settings = AiRagSetting::getOrCreateForUser(Auth::id());

        if (!$settings->apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Please configure your API Key first.',
            ], 400);
        }

        try {
            // Test connection by listing assistants
            $response = Http::withHeaders([
                'Api-Key' => $settings->apiKey,
                'Content-Type' => 'application/json',
            ])->get(self::PINECONE_API_BASE . '/assistant/assistants');

            if ($response->successful()) {
                $assistants = $response->json('assistants', []);
                return response()->json([
                    'success' => true,
                    'message' => 'Connection successful! Found ' . count($assistants) . ' assistant(s).',
                    'data' => [
                        'assistants' => $assistants,
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection failed: ' . ($response->json('message') ?? 'Unknown error'),
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Pinecone connection test error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload a file to Pinecone.
     */
    public function uploadFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:51200|mimes:pdf,txt,md,doc,docx,json,csv',
        ], [
            'file.required' => 'Please select a file to upload.',
            'file.max' => 'File size must be less than 50MB.',
            'file.mimes' => 'Supported formats: PDF, TXT, MD, DOC, DOCX, JSON, CSV.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $settings = AiRagSetting::getOrCreateForUser(Auth::id());

        if (!$settings->apiKey || !$settings->indexName) {
            return response()->json([
                'success' => false,
                'message' => 'Please configure your API settings first.',
            ], 400);
        }

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $fileType = $file->getClientMimeType();
            $extension = $file->getClientOriginalExtension();

            // Calculate file hash for duplicate detection
            $fileHash = hash_file('sha256', $file->getRealPath());

            // Check for duplicate by file hash (same content, possibly different name)
            $duplicateByHash = AiRagFile::active()
                ->forUser(Auth::id())
                ->where('fileHash', $fileHash)
                ->first();

            if ($duplicateByHash) {
                return response()->json([
                    'success' => false,
                    'message' => 'This file has already been uploaded. Duplicate detected: "' . $duplicateByHash->originalName . '"',
                    'isDuplicate' => true,
                    'duplicateFile' => [
                        'id' => $duplicateByHash->id,
                        'name' => $duplicateByHash->originalName,
                        'uploadedAt' => $duplicateByHash->created_at->format('M d, Y H:i'),
                    ],
                ], 409);
            }

            // Check for duplicate by original name (same name, possibly different content)
            $duplicateByName = AiRagFile::active()
                ->forUser(Auth::id())
                ->where('originalName', $originalName)
                ->first();

            if ($duplicateByName) {
                return response()->json([
                    'success' => false,
                    'message' => 'A file with this name already exists: "' . $originalName . '". Please rename the file or delete the existing one first.',
                    'isDuplicate' => true,
                    'duplicateFile' => [
                        'id' => $duplicateByName->id,
                        'name' => $duplicateByName->originalName,
                        'uploadedAt' => $duplicateByName->created_at->format('M d, Y H:i'),
                    ],
                ], 409);
            }

            // Generate unique filename
            $fileName = Str::uuid() . '.' . $extension;

            // Store file temporarily
            $filePath = $file->storeAs('rag-uploads', $fileName, 'public');

            // Create database record
            $ragFile = AiRagFile::create([
                'usersId' => Auth::id(),
                'fileName' => $fileName,
                'originalName' => $originalName,
                'fileSize' => $fileSize,
                'fileType' => $fileType,
                'fileHash' => $fileHash,
                'filePath' => $filePath,
                'status' => AiRagFile::STATUS_PROCESSING,
                'pineconeNamespace' => $settings->indexName,
                'delete_status' => 'active',
            ]);

            // Upload to Pinecone
            $uploadResult = $this->uploadToPinecone($settings, $ragFile, $file);

            if ($uploadResult['success']) {
                // Check Pinecone status - file might be "processing" or "available"
                $pineconeStatus = $uploadResult['pineconeStatus'] ?? 'processing';
                $status = ($pineconeStatus === 'available') ? AiRagFile::STATUS_INDEXED : AiRagFile::STATUS_PROCESSING;
                $message = ($status === AiRagFile::STATUS_INDEXED)
                    ? 'File uploaded and indexed successfully.'
                    : 'File uploaded. Pinecone is processing it (this may take a few minutes).';

                $ragFile->update([
                    'status' => $status,
                    'pineconeFileId' => $uploadResult['fileId'],
                    'indexedAt' => ($status === AiRagFile::STATUS_INDEXED) ? now() : null,
                    'vectorCount' => $uploadResult['vectorCount'] ?? 0,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'id' => $ragFile->id,
                        'fileName' => $ragFile->originalName,
                        'status' => $ragFile->status,
                        'statusDisplay' => $ragFile->status_display,
                        'statusBadgeClass' => $ragFile->status_badge_class,
                        'fileSize' => $ragFile->formatted_file_size,
                        'createdAt' => $ragFile->created_at->format('M d, Y H:i'),
                    ],
                ]);
            } else {
                $ragFile->update([
                    'status' => AiRagFile::STATUS_FAILED,
                    'errorMessage' => $uploadResult['message'],
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload to Pinecone: ' . $uploadResult['message'],
                    'data' => [
                        'id' => $ragFile->id,
                        'status' => $ragFile->status,
                    ],
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('RAG file upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload file to Pinecone Assistant.
     */
    private function uploadToPinecone($settings, $ragFile, $uploadedFile)
    {
        try {
            // First, ensure the assistant exists
            $assistantCheck = $this->ensureAssistantExists($settings);
            if (!$assistantCheck['success']) {
                return $assistantCheck;
            }

            // Upload file to Pinecone
            $response = Http::withHeaders([
                'Api-Key' => $settings->apiKey,
            ])->attach(
                'file',
                file_get_contents($uploadedFile->getRealPath()),
                $ragFile->originalName
            )->post(self::PINECONE_PROD_DATA . '/assistant/files/' . $settings->indexName);

            Log::info('Pinecone file upload response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'assistant' => $settings->indexName,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $pineconeStatus = strtolower($data['status'] ?? 'processing');
                return [
                    'success' => true,
                    'message' => 'File uploaded successfully.',
                    'fileId' => $data['id'] ?? null,
                    'pineconeStatus' => $pineconeStatus,
                    'vectorCount' => $data['metadata']['records'] ?? 0,
                ];
            } else {
                $error = $response->json();
                $errorMsg = $error['message'] ?? $error['error'] ?? $error['detail'] ?? json_encode($error);
                Log::error('Pinecone file upload failed', [
                    'status' => $response->status(),
                    'error' => $errorMsg,
                    'fullResponse' => $error,
                ]);
                return [
                    'success' => false,
                    'message' => $errorMsg,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Pinecone upload error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Ensure the Pinecone assistant exists, create if not.
     */
    private function ensureAssistantExists($settings)
    {
        try {
            // Check if assistant exists
            $response = Http::withHeaders([
                'Api-Key' => $settings->apiKey,
                'Content-Type' => 'application/json',
            ])->get(self::PINECONE_API_BASE . '/assistant/assistants/' . $settings->indexName);

            Log::info('Pinecone assistant check', [
                'assistant' => $settings->indexName,
                'status' => $response->status(),
            ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Assistant exists.'];
            }

            // Create assistant if it doesn't exist
            if ($response->status() === 404) {
                Log::info('Creating new Pinecone assistant', ['name' => $settings->indexName]);

                $createResponse = Http::withHeaders([
                    'Api-Key' => $settings->apiKey,
                    'Content-Type' => 'application/json',
                ])->post(self::PINECONE_API_BASE . '/assistant/assistants', [
                    'name' => $settings->indexName,
                    'instructions' => 'You are a helpful assistant that answers questions based on the uploaded knowledge base.',
                    'metadata' => [
                        'created_by' => 'ds-axis',
                        'email' => $settings->email,
                    ],
                ]);

                if ($createResponse->successful()) {
                    Log::info('Pinecone assistant created successfully', ['name' => $settings->indexName]);
                    return ['success' => true, 'message' => 'Assistant created.'];
                } else {
                    $error = $createResponse->json();
                    $errorMsg = $error['message'] ?? $error['error'] ?? $error['detail'] ?? json_encode($error);
                    Log::error('Failed to create Pinecone assistant', [
                        'status' => $createResponse->status(),
                        'error' => $errorMsg,
                        'fullResponse' => $error,
                    ]);
                    return [
                        'success' => false,
                        'message' => 'Failed to create assistant: ' . $errorMsg,
                    ];
                }
            }

            $error = $response->json();
            $errorMsg = $error['message'] ?? $error['error'] ?? $error['detail'] ?? json_encode($error);
            Log::error('Failed to verify Pinecone assistant', [
                'status' => $response->status(),
                'error' => $errorMsg,
            ]);
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
     * Get list of files.
     */
    public function getFiles()
    {
        $files = AiRagFile::active()
            ->forUser(Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($file) {
                return [
                    'id' => $file->id,
                    'originalName' => $file->originalName,
                    'fileSize' => $file->formatted_file_size,
                    'fileType' => $file->fileType,
                    'status' => $file->status,
                    'statusDisplay' => $file->status_display,
                    'statusBadgeClass' => $file->status_badge_class,
                    'vectorCount' => $file->vectorCount,
                    'errorMessage' => $file->errorMessage,
                    'createdAt' => $file->created_at->format('M d, Y H:i'),
                    'indexedAt' => $file->indexedAt ? $file->indexedAt->format('M d, Y H:i') : null,
                    'canRetry' => $file->canRetry(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $files,
        ]);
    }

    /**
     * Delete a file.
     */
    public function deleteFile($id)
    {
        $file = AiRagFile::where('id', $id)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->first();

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.',
            ], 404);
        }

        try {
            // Try to delete from Pinecone if it has a Pinecone file ID
            if ($file->pineconeFileId) {
                $settings = AiRagSetting::getOrCreateForUser(Auth::id());
                if ($settings->apiKey && $settings->indexName) {
                    $deleteResult = $this->deleteFromPinecone($settings, $file->pineconeFileId);
                    if (!$deleteResult['success']) {
                        Log::warning('Pinecone delete warning: ' . $deleteResult['message'], [
                            'fileId' => $file->id,
                            'pineconeFileId' => $file->pineconeFileId,
                        ]);
                    }
                }
            }

            // Delete local file
            if ($file->filePath && Storage::disk('public')->exists($file->filePath)) {
                Storage::disk('public')->delete($file->filePath);
            }

            // Soft delete
            $file->update(['delete_status' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('RAG file delete error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete file from Pinecone Assistant.
     *
     * @param AiRagSetting $settings
     * @param string $pineconeFileId The Pinecone file ID to delete
     * @return array
     */
    private function deleteFromPinecone($settings, string $pineconeFileId): array
    {
        try {
            $response = Http::withHeaders([
                'Api-Key' => $settings->apiKey,
            ])->delete(self::PINECONE_PROD_DATA . '/assistant/files/' . $settings->indexName . '/' . $pineconeFileId);

            Log::info('Pinecone file delete response', [
                'status' => $response->status(),
                'pineconeFileId' => $pineconeFileId,
                'assistant' => $settings->indexName,
            ]);

            if ($response->successful() || $response->status() === 404) {
                // 404 means file already doesn't exist, which is fine
                return ['success' => true, 'message' => 'File deleted from Pinecone'];
            }

            return [
                'success' => false,
                'message' => 'Pinecone returned status ' . $response->status() . ': ' . $response->body(),
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to delete file from Pinecone: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Retry a failed upload.
     */
    public function retryFile($id)
    {
        $file = AiRagFile::where('id', $id)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->where('status', AiRagFile::STATUS_FAILED)
            ->first();

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'File not found or cannot be retried.',
            ], 404);
        }

        $settings = AiRagSetting::getOrCreateForUser(Auth::id());

        if (!$settings->apiKey || !$settings->indexName) {
            return response()->json([
                'success' => false,
                'message' => 'Please configure your API settings first.',
            ], 400);
        }

        try {
            // Check if local file exists
            if (!$file->filePath || !Storage::disk('public')->exists($file->filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Original file not found. Please upload again.',
                ], 400);
            }

            $file->update([
                'status' => AiRagFile::STATUS_PROCESSING,
                'errorMessage' => null,
            ]);

            // Ensure assistant exists first
            $assistantCheck = $this->ensureAssistantExists($settings);
            if (!$assistantCheck['success']) {
                $file->update([
                    'status' => AiRagFile::STATUS_FAILED,
                    'errorMessage' => $assistantCheck['message'],
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Retry failed: ' . $assistantCheck['message'],
                ], 400);
            }

            // Get file contents
            $filePath = Storage::disk('public')->path($file->filePath);

            // Re-upload to Pinecone
            Log::info('Retrying Pinecone upload', [
                'fileId' => $file->id,
                'assistant' => $settings->indexName,
                'filePath' => $filePath,
            ]);

            $response = Http::withHeaders([
                'Api-Key' => $settings->apiKey,
            ])->attach(
                'file',
                file_get_contents($filePath),
                $file->originalName
            )->post(self::PINECONE_PROD_DATA . '/assistant/files/' . $settings->indexName);

            Log::info('Pinecone retry response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $file->update([
                    'status' => AiRagFile::STATUS_INDEXED,
                    'indexedAt' => now(),
                    'vectorCount' => $data['metadata']['records'] ?? 0,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'File re-indexed successfully.',
                    'data' => [
                        'status' => $file->status,
                        'statusDisplay' => $file->status_display,
                        'statusBadgeClass' => $file->status_badge_class,
                    ],
                ]);
            } else {
                $error = $response->json();
                $errorMsg = $error['message'] ?? $error['error'] ?? $error['detail'] ?? json_encode($error);
                Log::error('Pinecone retry upload failed', [
                    'status' => $response->status(),
                    'error' => $errorMsg,
                    'fullResponse' => $error,
                ]);
                $file->update([
                    'status' => AiRagFile::STATUS_FAILED,
                    'errorMessage' => $errorMsg,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Retry failed: ' . $errorMsg,
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('RAG file retry error: ' . $e->getMessage());
            $file->update([
                'status' => AiRagFile::STATUS_FAILED,
                'errorMessage' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Retry failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Pinecone assistant files (sync with Pinecone).
     */
    public function syncFiles()
    {
        $settings = AiRagSetting::getOrCreateForUser(Auth::id());

        if (!$settings->apiKey || !$settings->indexName) {
            return response()->json([
                'success' => false,
                'message' => 'Please configure your API settings first.',
            ], 400);
        }

        try {
            $response = Http::withHeaders([
                'Api-Key' => $settings->apiKey,
            ])->get(self::PINECONE_PROD_DATA . '/assistant/files/' . $settings->indexName);

            if ($response->successful()) {
                $pineconeFiles = $response->json('files', []);

                return response()->json([
                    'success' => true,
                    'message' => 'Synced ' . count($pineconeFiles) . ' file(s) from Pinecone.',
                    'data' => $pineconeFiles,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to sync: ' . ($response->json('message') ?? 'Unknown error'),
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh file status from Pinecone.
     */
    public function refreshFileStatus($id)
    {
        $file = AiRagFile::where('id', $id)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->first();

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.',
            ], 404);
        }

        if (!$file->pineconeFileId) {
            return response()->json([
                'success' => false,
                'message' => 'No Pinecone file ID associated with this file.',
            ], 400);
        }

        $settings = AiRagSetting::getOrCreateForUser(Auth::id());

        if (!$settings->apiKey || !$settings->indexName) {
            return response()->json([
                'success' => false,
                'message' => 'Please configure your API settings first.',
            ], 400);
        }

        try {
            // Get file status from Pinecone
            $response = Http::withHeaders([
                'Api-Key' => $settings->apiKey,
            ])->get(self::PINECONE_PROD_DATA . '/assistant/files/' . $settings->indexName . '/' . $file->pineconeFileId);

            if ($response->successful()) {
                $data = $response->json();
                $pineconeStatus = strtolower($data['status'] ?? 'processing');

                // Map Pinecone status to our status
                $newStatus = match($pineconeStatus) {
                    'available' => AiRagFile::STATUS_INDEXED,
                    'processing' => AiRagFile::STATUS_PROCESSING,
                    'failed' => AiRagFile::STATUS_FAILED,
                    default => $file->status,
                };

                $updateData = ['status' => $newStatus];

                if ($newStatus === AiRagFile::STATUS_INDEXED && !$file->indexedAt) {
                    $updateData['indexedAt'] = now();
                }

                if ($newStatus === AiRagFile::STATUS_FAILED) {
                    $updateData['errorMessage'] = $data['error_message'] ?? 'Processing failed in Pinecone';
                }

                $file->update($updateData);

                return response()->json([
                    'success' => true,
                    'message' => 'Status updated: ' . $file->status_display,
                    'data' => [
                        'id' => $file->id,
                        'status' => $file->status,
                        'statusDisplay' => $file->status_display,
                        'statusBadgeClass' => $file->status_badge_class,
                        'pineconeStatus' => $pineconeStatus,
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get status from Pinecone.',
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('RAG file status refresh error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Batch check RAG index status for all items (docs, websites, images).
     * Called on page load to verify items are still indexed.
     */
    public function batchCheckIndexStatus()
    {
        $settings = AiRagSetting::getOrCreateForUser(Auth::id());

        if (!$settings->apiKey || !$settings->indexName) {
            return response()->json([
                'success' => false,
                'message' => 'RAG settings not configured.',
                'results' => [],
            ]);
        }

        $results = [
            'docs' => [],
            'websites' => [],
            'images' => [],
            'indexed_count' => 0,
            'missing_count' => 0,
            'processing_count' => 0,
        ];

        try {
            // Get list of all files in Pinecone assistant
            $response = Http::timeout(30)->withHeaders([
                'Api-Key' => $settings->apiKey,
            ])->get(self::PINECONE_PROD_DATA . '/assistant/files/' . $settings->indexName);

            $pineconeFiles = [];
            if ($response->successful()) {
                $data = $response->json();
                $files = $data['files'] ?? $data ?? [];
                foreach ($files as $file) {
                    if (isset($file['id'])) {
                        $pineconeFiles[$file['id']] = $file['status'] ?? 'unknown';
                    }
                }
            }

            // Check documents
            $docs = AiRagFile::active()->forUser(Auth::id())->whereNotNull('pineconeFileId')->get();
            foreach ($docs as $doc) {
                $isIndexed = isset($pineconeFiles[$doc->pineconeFileId]);
                $pineconeStatus = $pineconeFiles[$doc->pineconeFileId] ?? null;

                $results['docs'][$doc->id] = [
                    'indexed' => $isIndexed,
                    'pineconeStatus' => $pineconeStatus,
                    'localStatus' => $doc->status,
                ];

                if ($isIndexed && strtolower($pineconeStatus) === 'available') {
                    $results['indexed_count']++;
                    // Update local status if needed
                    if ($doc->status !== AiRagFile::STATUS_INDEXED) {
                        $doc->update(['status' => AiRagFile::STATUS_INDEXED, 'indexedAt' => now()]);
                    }
                } elseif ($isIndexed && strtolower($pineconeStatus) === 'processing') {
                    $results['processing_count']++;
                    if ($doc->status !== AiRagFile::STATUS_PROCESSING) {
                        $doc->update(['status' => AiRagFile::STATUS_PROCESSING]);
                    }
                } elseif (!$isIndexed && $doc->pineconeFileId) {
                    $results['missing_count']++;
                    // File was deleted from Pinecone - mark as pending
                    $doc->update([
                        'status' => AiRagFile::STATUS_PENDING,
                        'pineconeFileId' => null,
                        'indexedAt' => null,
                    ]);
                }
            }

            // Check websites
            $websites = AiWebsite::active()->forUser(Auth::id())->whereNotNull('pineconeFileId')->get();
            foreach ($websites as $website) {
                $isIndexed = isset($pineconeFiles[$website->pineconeFileId]);
                $pineconeStatus = $pineconeFiles[$website->pineconeFileId] ?? null;

                $results['websites'][$website->id] = [
                    'indexed' => $isIndexed,
                    'pineconeStatus' => $pineconeStatus,
                    'localStatus' => $website->pineconeStatus,
                ];

                if ($isIndexed && strtolower($pineconeStatus) === 'available') {
                    $results['indexed_count']++;
                    if ($website->pineconeStatus !== 'indexed') {
                        $website->update(['pineconeStatus' => 'indexed', 'lastRagSync' => now()]);
                    }
                } elseif ($isIndexed && strtolower($pineconeStatus) === 'processing') {
                    $results['processing_count']++;
                } elseif (!$isIndexed && $website->pineconeFileId) {
                    $results['missing_count']++;
                    $website->update([
                        'pineconeStatus' => 'pending',
                        'pineconeFileId' => null,
                        'lastRagSync' => null,
                    ]);
                }
            }

            // Check images
            $images = AiKbImage::active()->forUser(Auth::id())->whereNotNull('pineconeFileId')->get();
            foreach ($images as $image) {
                $isIndexed = isset($pineconeFiles[$image->pineconeFileId]);
                $pineconeStatus = $pineconeFiles[$image->pineconeFileId] ?? null;

                $results['images'][$image->id] = [
                    'indexed' => $isIndexed,
                    'pineconeStatus' => $pineconeStatus,
                    'localStatus' => $image->pineconeStatus,
                ];

                if ($isIndexed && strtolower($pineconeStatus) === 'available') {
                    $results['indexed_count']++;
                    if ($image->pineconeStatus !== AiKbImage::STATUS_INDEXED) {
                        $image->update(['pineconeStatus' => AiKbImage::STATUS_INDEXED]);
                    }
                } elseif ($isIndexed && strtolower($pineconeStatus) === 'processing') {
                    $results['processing_count']++;
                } elseif (!$isIndexed && $image->pineconeFileId) {
                    $results['missing_count']++;
                    $image->update([
                        'pineconeStatus' => AiKbImage::STATUS_PENDING,
                        'pineconeFileId' => null,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Checked RAG status: {$results['indexed_count']} indexed, {$results['processing_count']} processing, {$results['missing_count']} missing",
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('Batch RAG status check error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check RAG status: ' . $e->getMessage(),
                'results' => $results,
            ], 500);
        }
    }
}
