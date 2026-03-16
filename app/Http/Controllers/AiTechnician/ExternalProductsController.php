<?php

namespace App\Http\Controllers\AiTechnician;

use App\Http\Controllers\Controller;
use App\Models\AiApiSetting;
use App\Models\AiExternalProduct;
use App\Models\AiExternalProductDocument;
use App\Models\AiExternalProductImage;
use App\Models\AiRagSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ExternalProductsController extends Controller
{
    // Pinecone API endpoints
    const PINECONE_PROD_DATA = 'https://prod-1-data.ke.pinecone.io';

    /**
     * Get all products for the current user.
     */
    public function index()
    {
        $products = AiExternalProduct::active()
                        ->with([
                'images' => function ($q) {
                    $q->where('delete_status', 'active')->orderBy('isPrimary', 'desc')->orderBy('sortOrder');
                },
                'documents' => function ($q) {
                    $q->where('delete_status', 'active')->orderBy('sortOrder');
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'productName' => $product->productName,
                    'brandName' => $product->brandName,
                    'productType' => $product->productType,
                    'typeDisplay' => $product->type_display,
                    'primaryImageUrl' => $product->primary_image_url,
                    'imageCount' => $product->images->count(),
                    'documentCount' => $product->documents->count(),
                    'ragStatus' => $product->ragStatus,
                    'ragStatusBadge' => $product->rag_status_badge,
                    'summary' => $product->summary,
                    'searchTags' => $product->search_tags,
                    'isIndexed' => $product->isIndexed(),
                    'createdAt' => $product->created_at->format('M d, Y H:i'),
                ];
            }),
        ]);
    }

    /**
     * Store a new product.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'productName' => 'required|string|max:255',
            'brandName' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'productType' => 'required|string|in:' . implode(',', array_keys(AiExternalProduct::getProductTypes())),
            'manualText' => 'nullable|string|max:10000',
            'images' => 'nullable|array|max:10', // Allow up to 10 images
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max per image
            'documents' => 'nullable|array|max:5', // Allow up to 5 documents
            'documents.*' => 'file|mimes:pdf,txt,doc,docx|max:51200', // 50MB max per document
        ], [
            'productName.required' => 'Product name is required.',
            'productType.required' => 'Please select a product type.',
            'productType.in' => 'Invalid product type selected.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.max' => 'Each image size must not exceed 10MB.',
            'images.max' => 'You can upload a maximum of 10 images.',
            'documents.*.mimes' => 'Documents must be PDF, TXT, DOC, or DOCX format.',
            'documents.*.max' => 'Each document size must not exceed 50MB.',
            'documents.max' => 'You can upload a maximum of 5 documents.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $userId = Auth::id();

            // Create the product record
            $product = AiExternalProduct::create([
                'usersId' => $userId,
                'productName' => $request->productName,
                'brandName' => $request->brandName,
                'manufacturer' => $request->manufacturer,
                'productType' => $request->productType,
                'manualText' => $request->manualText,
                'ragStatus' => AiExternalProduct::RAG_PENDING,
                'delete_status' => 'active',
            ]);

            // Handle multiple image uploads
            $uploadedImages = [];
            if ($request->hasFile('images')) {
                $files = $request->file('images');
                foreach ($files as $index => $file) {
                    $filename = 'product_' . $product->id . '_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                    $imagePath = $file->storeAs('ai-products', $filename, 'public');

                    $productImage = AiExternalProductImage::create([
                        'productId' => $product->id,
                        'usersId' => $userId,
                        'imagePath' => $imagePath,
                        'imageUrl' => asset('storage/' . $imagePath),
                        'originalName' => $file->getClientOriginalName(),
                        'fileSize' => $file->getSize(),
                        'mimeType' => $file->getMimeType(),
                        'sortOrder' => $index,
                        'isPrimary' => $index === 0, // First image is primary
                        'status' => AiExternalProductImage::STATUS_PENDING,
                        'delete_status' => 'active',
                    ]);

                    $uploadedImages[] = $productImage;
                }

                // Set primary image path on product for backward compatibility
                if (count($uploadedImages) > 0) {
                    $product->update([
                        'imagePath' => $uploadedImages[0]->imagePath,
                        'imageUrl' => $uploadedImages[0]->imageUrl,
                    ]);
                }
            }

            // Handle multiple document uploads
            $uploadedDocuments = [];
            if ($request->hasFile('documents')) {
                $documentFiles = $request->file('documents');
                foreach ($documentFiles as $index => $file) {
                    $extension = strtolower($file->getClientOriginalExtension());
                    $filename = 'doc_' . $product->id . '_' . time() . '_' . Str::random(8) . '.' . $extension;
                    $documentPath = $file->storeAs('ai-products/documents', $filename, 'public');

                    $productDocument = AiExternalProductDocument::create([
                        'productId' => $product->id,
                        'usersId' => $userId,
                        'documentPath' => $documentPath,
                        'documentUrl' => asset('storage/' . $documentPath),
                        'originalName' => $file->getClientOriginalName(),
                        'fileSize' => $file->getSize(),
                        'mimeType' => $file->getMimeType(),
                        'fileExtension' => $extension,
                        'sortOrder' => $index,
                        'status' => AiExternalProductDocument::STATUS_PENDING,
                        'delete_status' => 'active',
                    ]);

                    $uploadedDocuments[] = $productDocument;
                }
            }

            Log::info('External product created', [
                'productId' => $product->id,
                'productName' => $product->productName,
                'imageCount' => count($uploadedImages),
                'documentCount' => count($uploadedDocuments),
            ]);

            $message = 'Product uploaded successfully';
            if (count($uploadedImages) > 0 || count($uploadedDocuments) > 0) {
                $parts = [];
                if (count($uploadedImages) > 0) $parts[] = count($uploadedImages) . ' image(s)';
                if (count($uploadedDocuments) > 0) $parts[] = count($uploadedDocuments) . ' document(s)';
                $message .= ' with ' . implode(' and ', $parts) . '. Processing will begin shortly.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'product' => [
                    'id' => $product->id,
                    'productName' => $product->productName,
                    'ragStatus' => $product->ragStatus,
                    'imageCount' => count($uploadedImages),
                    'documentCount' => count($uploadedDocuments),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create external product: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload product: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add images to an existing product.
     */
    public function addImages(Request $request, $id)
    {
        $product = AiExternalProduct::where('id', $id)
                        ->where('delete_status', 'active')
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $userId = Auth::id();
            $existingCount = $product->images()->count();
            $maxImages = 10;

            if ($existingCount >= $maxImages) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maximum of ' . $maxImages . ' images allowed per product.',
                ], 422);
            }

            $uploadedImages = [];
            $files = $request->file('images');
            $sortOrder = $existingCount;

            foreach ($files as $file) {
                if (count($uploadedImages) + $existingCount >= $maxImages) {
                    break;
                }

                $filename = 'product_' . $product->id . '_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                $imagePath = $file->storeAs('ai-products', $filename, 'public');

                $productImage = AiExternalProductImage::create([
                    'productId' => $product->id,
                    'usersId' => $userId,
                    'imagePath' => $imagePath,
                    'imageUrl' => asset('storage/' . $imagePath),
                    'originalName' => $file->getClientOriginalName(),
                    'fileSize' => $file->getSize(),
                    'mimeType' => $file->getMimeType(),
                    'sortOrder' => $sortOrder++,
                    'isPrimary' => $existingCount === 0 && count($uploadedImages) === 0,
                    'status' => AiExternalProductImage::STATUS_PENDING,
                    'delete_status' => 'active',
                ]);

                $uploadedImages[] = $productImage;
            }

            // Update product to pending if new images need processing
            if (count($uploadedImages) > 0 && $product->ragStatus === AiExternalProduct::RAG_INDEXED) {
                $product->update(['ragStatus' => AiExternalProduct::RAG_PENDING]);
            }

            return response()->json([
                'success' => true,
                'message' => count($uploadedImages) . ' image(s) added successfully.',
                'data' => [
                    'addedCount' => count($uploadedImages),
                    'totalCount' => $existingCount + count($uploadedImages),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to add images to product: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to add images.',
            ], 500);
        }
    }

    /**
     * Delete a product image.
     */
    public function deleteImage($productId, $imageId)
    {
        $image = AiExternalProductImage::where('id', $imageId)
            ->where('productId', $productId)
                        ->where('delete_status', 'active')
            ->first();

        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found.',
            ], 404);
        }

        try {
            // Delete file from storage
            if ($image->imagePath && Storage::disk('public')->exists($image->imagePath)) {
                Storage::disk('public')->delete($image->imagePath);
            }

            // If this was the primary image, set another one as primary
            $wasPrimary = $image->isPrimary;

            // Soft delete
            $image->update(['delete_status' => 'deleted']);

            if ($wasPrimary) {
                $nextImage = AiExternalProductImage::where('productId', $productId)
                    ->where('delete_status', 'active')
                    ->orderBy('sortOrder')
                    ->first();

                if ($nextImage) {
                    $nextImage->setAsPrimary();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete product image: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image.',
            ], 500);
        }
    }

    /**
     * Set an image as primary.
     */
    public function setPrimaryImage($productId, $imageId)
    {
        $image = AiExternalProductImage::where('id', $imageId)
            ->where('productId', $productId)
                        ->where('delete_status', 'active')
            ->first();

        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found.',
            ], 404);
        }

        try {
            $image->setAsPrimary();

            // Update product's legacy imagePath for backward compatibility
            $product = $image->product;
            if ($product) {
                $product->update([
                    'imagePath' => $image->imagePath,
                    'imageUrl' => $image->imageUrl,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Primary image updated.',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to set primary image: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update primary image.',
            ], 500);
        }
    }

    /**
     * Add documents to an existing product.
     */
    public function addDocuments(Request $request, $id)
    {
        $product = AiExternalProduct::where('id', $id)
                        ->where('delete_status', 'active')
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'documents' => 'required|array|min:1|max:5',
            'documents.*' => 'file|mimes:pdf,txt,doc,docx|max:51200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $userId = Auth::id();
            $existingCount = $product->documents()->count();
            $maxDocuments = 5;

            if ($existingCount >= $maxDocuments) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maximum number of documents (' . $maxDocuments . ') reached for this product.',
                ], 422);
            }

            $uploadedDocuments = [];
            $documentFiles = $request->file('documents');
            $newCount = min(count($documentFiles), $maxDocuments - $existingCount);

            foreach (array_slice($documentFiles, 0, $newCount) as $index => $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                $filename = 'doc_' . $product->id . '_' . time() . '_' . Str::random(8) . '.' . $extension;
                $documentPath = $file->storeAs('ai-products/documents', $filename, 'public');

                $productDocument = AiExternalProductDocument::create([
                    'productId' => $product->id,
                    'usersId' => $userId,
                    'documentPath' => $documentPath,
                    'documentUrl' => asset('storage/' . $documentPath),
                    'originalName' => $file->getClientOriginalName(),
                    'fileSize' => $file->getSize(),
                    'mimeType' => $file->getMimeType(),
                    'fileExtension' => $extension,
                    'sortOrder' => $existingCount + $index,
                    'status' => AiExternalProductDocument::STATUS_PENDING,
                    'delete_status' => 'active',
                ]);

                $uploadedDocuments[] = [
                    'id' => $productDocument->id,
                    'originalName' => $productDocument->originalName,
                    'fileExtension' => $productDocument->fileExtension,
                    'fileSizeHuman' => $productDocument->file_size_human,
                    'documentIcon' => $productDocument->document_icon,
                    'status' => $productDocument->status,
                ];
            }

            // Reset RAG status to pending for re-processing
            if ($product->ragStatus === AiExternalProduct::RAG_INDEXED) {
                $product->updateRagStatus(AiExternalProduct::RAG_PENDING);
            }

            return response()->json([
                'success' => true,
                'message' => count($uploadedDocuments) . ' document(s) added successfully.',
                'documents' => $uploadedDocuments,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to add documents to product: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload documents.',
            ], 500);
        }
    }

    /**
     * Delete a document from a product.
     */
    public function deleteDocument($productId, $documentId)
    {
        $document = AiExternalProductDocument::where('id', $documentId)
            ->where('productId', $productId)
                        ->where('delete_status', 'active')
            ->first();

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found.',
            ], 404);
        }

        try {
            // Delete file from storage
            if ($document->documentPath && Storage::disk('public')->exists($document->documentPath)) {
                Storage::disk('public')->delete($document->documentPath);
            }

            // Soft delete
            $document->update(['delete_status' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete product document: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document.',
            ], 500);
        }
    }

    /**
     * Process a product: OCR all images, AI analysis, and RAG upload.
     */
    public function process($id)
    {
        $product = AiExternalProduct::where('id', $id)
                        ->where('delete_status', 'active')
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        try {
            // Step 1: Update status to processing
            $product->updateRagStatus(AiExternalProduct::RAG_PROCESSING);

            // Step 2: Process all images (OCR)
            $product->updateRagStatus(AiExternalProduct::RAG_ANALYZING);
            $images = $product->images()->where('status', '!=', AiExternalProductImage::STATUS_ANALYZED)->get();
            $allOcrTexts = [];

            foreach ($images as $image) {
                $image->updateStatus(AiExternalProductImage::STATUS_PROCESSING);

                $ocrResult = $this->extractTextFromProductImage($image);

                if ($ocrResult && isset($ocrResult['text'])) {
                    // Ensure text is always a string
                    $ocrText = is_array($ocrResult['text']) ? implode("\n", $ocrResult['text']) : (string) $ocrResult['text'];
                    $analysis = $ocrResult['analysis'] ?? [];

                    $image->markAsAnalyzed($ocrText, $analysis);
                    $allOcrTexts[] = $ocrText;
                } else {
                    $image->markAsFailed('OCR extraction failed');
                }
            }

            // Also handle legacy single image if exists
            if ($product->imagePath && !$product->ocrText) {
                $ocrText = $this->extractTextFromImagePath($product->imagePath, $product->usersId);
                if ($ocrText) {
                    $product->update(['ocrText' => $ocrText]);
                    $allOcrTexts[] = $ocrText;
                }
            }

            // Also include already analyzed images' OCR text
            $analyzedImages = $product->analyzedImages;
            foreach ($analyzedImages as $img) {
                if ($img->ocrText && !in_array($img->ocrText, $allOcrTexts)) {
                    $allOcrTexts[] = $img->ocrText;
                }
            }

            Log::info('OCR completed for product images', [
                'productId' => $product->id,
                'imageCount' => $images->count(),
                'ocrCount' => count($allOcrTexts),
            ]);

            // Step 2.5: Process all documents (text extraction)
            $documents = $product->documents()->where('status', '!=', AiExternalProductDocument::STATUS_EXTRACTED)->get();
            $allDocTexts = [];

            foreach ($documents as $document) {
                $document->updateStatus(AiExternalProductDocument::STATUS_PROCESSING);

                $extractedText = $this->extractTextFromDocument($document);

                if ($extractedText) {
                    $document->markAsExtracted($extractedText, [
                        'wordCount' => str_word_count($extractedText),
                        'extractedAt' => now()->toISOString(),
                    ]);
                    $allDocTexts[] = $extractedText;
                } else {
                    $document->markAsFailed('Text extraction failed');
                }
            }

            // Also include already extracted documents' text
            $extractedDocuments = $product->extractedDocuments;
            foreach ($extractedDocuments as $doc) {
                if ($doc->extractedText && !in_array($doc->extractedText, $allDocTexts)) {
                    $allDocTexts[] = $doc->extractedText;
                }
            }

            Log::info('Text extraction completed for product documents', [
                'productId' => $product->id,
                'documentCount' => $documents->count(),
                'extractedCount' => count($allDocTexts),
            ]);

            // Step 3: AI Analysis to generate comprehensive product details
            $product->updateRagStatus(AiExternalProduct::RAG_ANALYZING);
            $aiAnalysis = $this->analyzeProductWithAI($product);
            $product->update(['aiAnalysis' => $aiAnalysis]);

            Log::info('AI analysis completed for product', [
                'productId' => $product->id,
                'hasSummary' => !empty($aiAnalysis['summary']),
                'tagCount' => count($aiAnalysis['searchTags'] ?? []),
            ]);

            // Step 4: Build RAG content (includes all images' OCR text)
            $ragContent = $product->buildRagContent();
            $product->update(['ragContent' => $ragContent]);

            // Step 5: Upload to RAG (Pinecone)
            $product->updateRagStatus(AiExternalProduct::RAG_UPLOADING);
            $pineconeFileId = $this->uploadToRag($product, $ragContent);

            if ($pineconeFileId) {
                $product->markAsIndexed($pineconeFileId);
                Log::info('Product indexed in RAG', [
                    'productId' => $product->id,
                    'pineconeFileId' => $pineconeFileId,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Product processed and indexed successfully.',
                    'data' => [
                        'id' => $product->id,
                        'ragStatus' => $product->ragStatus,
                        'pineconeFileId' => $pineconeFileId,
                        'imagesProcessed' => $images->count(),
                        'documentsProcessed' => $documents->count(),
                    ],
                ]);
            } else {
                throw new \Exception('Failed to upload to RAG');
            }

        } catch (\Exception $e) {
            Log::error('Failed to process product: ' . $e->getMessage(), [
                'productId' => $product->id,
                'trace' => $e->getTraceAsString(),
            ]);

            $product->markAsFailed($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Processing failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Extract text from a product image model using Gemini Vision.
     */
    protected function extractTextFromProductImage(AiExternalProductImage $image): ?array
    {
        $geminiSetting = AiApiSetting::where('usersId', $image->usersId)
            ->where('provider', AiApiSetting::PROVIDER_GEMINI)
            ->where('isActive', true)
            ->where('delete_status', 'active')
            ->first();

        if (!$geminiSetting || !$geminiSetting->apiKey) {
            Log::warning('Gemini API not configured for OCR');
            return null;
        }

        $fullPath = Storage::disk('public')->path($image->imagePath);
        if (!file_exists($fullPath)) {
            Log::warning('Product image file not found', ['path' => $fullPath]);
            return null;
        }

        $imageData = base64_encode(file_get_contents($fullPath));
        $mimeType = mime_content_type($fullPath);

        $model = 'gemini-2.0-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $geminiSetting->apiKey;

        $prompt = "=== OCR + ANALYSIS: Agricultural Product Image ===\n\n";
        $prompt .= "This is an image of an agricultural product (pesticide, fertilizer, herbicide, etc.).\n\n";
        $prompt .= "Please:\n";
        $prompt .= "1. Extract ALL visible text from the product label/packaging\n";
        $prompt .= "2. Identify what part of the product this image shows\n\n";
        $prompt .= "Return a JSON response with this structure:\n";
        $prompt .= "{\n";
        $prompt .= '  "imageType": "front_label|back_label|ingredients|instructions|warnings|other",' . "\n";
        $prompt .= '  "extractedText": "All text visible in the image, structured by sections",' . "\n";
        $prompt .= '  "summary": "Brief description of what this image shows",' . "\n";
        $prompt .= '  "relevantInfo": ["Key point 1", "Key point 2", ...]' . "\n";
        $prompt .= "}\n\n";
        $prompt .= "Include in extractedText:\n";
        $prompt .= "- Product name and brand\n";
        $prompt .= "- Active ingredients and concentrations\n";
        $prompt .= "- Directions for use and dosage\n";
        $prompt .= "- Target pests/diseases/crops\n";
        $prompt .= "- Cautions and warnings\n";
        $prompt .= "- Registration numbers\n";
        $prompt .= "- Manufacturer information\n\n";
        $prompt .= "Return ONLY the JSON object, no markdown.";

        $requestData = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $imageData,
                            ],
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'maxOutputTokens' => 4096,
                'temperature' => 0.1,
            ],
        ];

        try {
            $response = Http::timeout(60)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $requestData);

            if ($response->successful()) {
                $data = $response->json();
                $responseText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                // Try to parse JSON
                $analysis = $this->parseImageAnalysisResponse($responseText);

                if ($analysis) {
                    // Ensure extractedText is always a string
                    $extractedText = $analysis['extractedText'] ?? $responseText;
                    if (is_array($extractedText)) {
                        $extractedText = implode("\n", $extractedText);
                    }

                    return [
                        'text' => (string) $extractedText,
                        'analysis' => $analysis,
                    ];
                }

                // If JSON parsing fails, return raw text
                return [
                    'text' => $responseText,
                    'analysis' => [
                        'imageType' => 'other',
                        'extractedText' => $responseText,
                        'summary' => 'Product label image',
                        'relevantInfo' => [],
                    ],
                ];
            }

            Log::error('Gemini OCR failed', [
                'status' => $response->status(),
                'error' => $response->json('error.message'),
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('OCR exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract text from image path (legacy support).
     */
    protected function extractTextFromImagePath(string $imagePath, int $userId): ?string
    {
        $geminiSetting = AiApiSetting::where('usersId', $userId)
            ->where('provider', AiApiSetting::PROVIDER_GEMINI)
            ->where('isActive', true)
            ->where('delete_status', 'active')
            ->first();

        if (!$geminiSetting || !$geminiSetting->apiKey) {
            return null;
        }

        $fullPath = Storage::disk('public')->path($imagePath);
        if (!file_exists($fullPath)) {
            return null;
        }

        $imageData = base64_encode(file_get_contents($fullPath));
        $mimeType = mime_content_type($fullPath);

        $model = 'gemini-2.0-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $geminiSetting->apiKey;

        $prompt = "Extract ALL text from this agricultural product image. Include product name, ingredients, directions, dosage, warnings, and any other visible text.";

        $requestData = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $imageData,
                            ],
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'maxOutputTokens' => 4096,
                'temperature' => 0.1,
            ],
        ];

        try {
            $response = Http::timeout(60)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $requestData);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }
            return null;

        } catch (\Exception $e) {
            Log::error('OCR exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse image analysis response.
     */
    protected function parseImageAnalysisResponse(string $response): ?array
    {
        $response = trim($response);

        // Remove markdown code blocks if present
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $response, $matches)) {
            $response = $matches[1];
        }

        // Try to find JSON object
        if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
            $json = $matches[0];
            $decoded = json_decode($json, true);

            if ($decoded && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Analyze product with AI to generate comprehensive details.
     */
    protected function analyzeProductWithAI(AiExternalProduct $product): array
    {
        $geminiSetting = AiApiSetting::where('usersId', $product->usersId)
            ->where('provider', AiApiSetting::PROVIDER_GEMINI)
            ->where('isActive', true)
            ->where('delete_status', 'active')
            ->first();

        if (!$geminiSetting || !$geminiSetting->apiKey) {
            Log::warning('Gemini API not configured for AI analysis');
            return $this->getDefaultAnalysis($product);
        }

        $model = 'gemini-2.0-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $geminiSetting->apiKey;

        // Build the analysis prompt
        $prompt = $this->buildAnalysisPrompt($product);

        $requestData = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'maxOutputTokens' => 8192,
                'temperature' => 0.3,
            ],
        ];

        try {
            $response = Http::timeout(90)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $requestData);

            if ($response->successful()) {
                $data = $response->json();
                $responseText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                // Parse JSON from response
                $analysis = $this->parseAnalysisResponse($responseText);

                if ($analysis) {
                    return $analysis;
                }
            }

            Log::error('Gemini analysis failed', [
                'status' => $response->status(),
                'error' => $response->json('error.message'),
            ]);

        } catch (\Exception $e) {
            Log::error('AI analysis exception: ' . $e->getMessage());
        }

        return $this->getDefaultAnalysis($product);
    }

    /**
     * Build the AI analysis prompt.
     */
    protected function buildAnalysisPrompt(AiExternalProduct $product): string
    {
        $prompt = "=== ULTRATHINK: Agricultural Product Analysis ===\n\n";
        $prompt .= "You are an expert agricultural scientist analyzing a product for Filipino farmers.\n";
        $prompt .= "Your task is to create a COMPREHENSIVE knowledge base entry for this product.\n\n";

        $prompt .= "=== PRODUCT INFORMATION ===\n";
        $prompt .= "Product Name: {$product->productName}\n";
        if ($product->brandName) {
            $prompt .= "Brand: {$product->brandName}\n";
        }
        if ($product->manufacturer) {
            $prompt .= "Manufacturer: {$product->manufacturer}\n";
        }
        $prompt .= "Product Type: {$product->type_display}\n\n";

        if ($product->ocrText) {
            $prompt .= "=== TEXT EXTRACTED FROM PRODUCT LABEL ===\n";
            $prompt .= $product->ocrText . "\n\n";
        }

        if ($product->manualText) {
            $prompt .= "=== ADDITIONAL INFORMATION PROVIDED ===\n";
            $prompt .= $product->manualText . "\n\n";
        }

        $prompt .= "=== YOUR ANALYSIS TASK ===\n";
        $prompt .= "Based on the above information, create a comprehensive product analysis.\n";
        $prompt .= "Use your knowledge of Philippine agriculture and local farming practices.\n\n";

        $prompt .= "IMPORTANT CONSIDERATIONS:\n";
        $prompt .= "1. This is a PHILIPPINE product - use local context (Philippine pests, diseases, crops)\n";
        $prompt .= "2. Include PRACTICAL advice for Filipino farmers\n";
        $prompt .= "3. If information is not clear, make educated inferences based on product type\n";
        $prompt .= "4. Generate comprehensive search tags for easy retrieval\n";
        $prompt .= "5. Include both English and Filipino/Tagalog terms where relevant\n\n";

        $prompt .= "=== OUTPUT FORMAT (JSON) ===\n";
        $prompt .= "Return ONLY valid JSON with this structure:\n";
        $prompt .= "{\n";
        $prompt .= '  "summary": "A 2-3 sentence summary of what this product is and its main use",' . "\n";
        $prompt .= '  "purpose": "Detailed explanation of the product\'s purpose and benefits",' . "\n";
        $prompt .= '  "activeIngredients": [' . "\n";
        $prompt .= '    {"name": "Ingredient name", "concentration": "Amount/concentration", "purpose": "What this ingredient does"}' . "\n";
        $prompt .= '  ],' . "\n";
        $prompt .= '  "targetPests": ["List of pests this controls - use both English and local names"],' . "\n";
        $prompt .= '  "targetDiseases": ["List of diseases this treats/prevents"],' . "\n";
        $prompt .= '  "targetCrops": ["Crops this can be used on"],' . "\n";
        $prompt .= '  "applicationMethod": "How to apply this product",' . "\n";
        $prompt .= '  "dosage": "Recommended dosage and dilution rates",' . "\n";
        $prompt .= '  "applicationTiming": "When to apply (growth stage, time of day, etc.)",' . "\n";
        $prompt .= '  "safetyPrecautions": ["List of safety warnings and precautions"],' . "\n";
        $prompt .= '  "preHarvestInterval": "Days to wait before harvest after application",' . "\n";
        $prompt .= '  "reEntryInterval": "How long to wait before re-entering treated area",' . "\n";
        $prompt .= '  "compatibility": ["Products it can be mixed with"],' . "\n";
        $prompt .= '  "incompatibility": ["Products it should NOT be mixed with"],' . "\n";
        $prompt .= '  "storageInstructions": "How to properly store this product",' . "\n";
        $prompt .= '  "searchTags": ["COMPREHENSIVE list of 20-30 search terms including: product name, brand, ingredients, pests, diseases, crops, Filipino terms, common farmer queries, symptoms it treats"],' . "\n";
        $prompt .= '  "relatedProblems": ["Farm problems this product solves - written as farmer would describe them"],' . "\n";
        $prompt .= '  "localAvailability": "Where farmers can typically buy this in the Philippines",' . "\n";
        $prompt .= '  "estimatedPrice": "Approximate price range in PHP if known"' . "\n";
        $prompt .= "}\n\n";

        $prompt .= "CRITICAL: Return ONLY the JSON object, no explanations or markdown.";

        return $prompt;
    }

    /**
     * Parse AI analysis response to extract JSON.
     */
    protected function parseAnalysisResponse(string $response): ?array
    {
        // Try to extract JSON from the response
        $response = trim($response);

        // Remove markdown code blocks if present
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $response, $matches)) {
            $response = $matches[1];
        }

        // Try to find JSON object
        if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
            $json = $matches[0];
            $decoded = json_decode($json, true);

            if ($decoded && is_array($decoded)) {
                return $decoded;
            }
        }

        // Direct decode attempt
        $decoded = json_decode($response, true);
        if ($decoded && is_array($decoded)) {
            return $decoded;
        }

        Log::warning('Failed to parse AI analysis response', [
            'responsePreview' => substr($response, 0, 500),
        ]);

        return null;
    }

    /**
     * Get default analysis when AI fails.
     */
    protected function getDefaultAnalysis(AiExternalProduct $product): array
    {
        return [
            'summary' => "Agricultural product: {$product->productName}",
            'purpose' => $product->manualText ?? 'Product details to be updated.',
            'activeIngredients' => [],
            'targetPests' => [],
            'targetDiseases' => [],
            'targetCrops' => [],
            'applicationMethod' => '',
            'dosage' => '',
            'applicationTiming' => '',
            'safetyPrecautions' => [],
            'searchTags' => [
                strtolower($product->productName),
                strtolower($product->brandName ?? ''),
                $product->productType,
            ],
        ];
    }

    /**
     * Upload product to RAG (Pinecone).
     */
    protected function uploadToRag(AiExternalProduct $product, string $content): ?string
    {
        $settings = AiRagSetting::active()->forUser($product->usersId)->first();

        if (!$settings || !$settings->apiKey || !$settings->indexName) {
            throw new \Exception('RAG settings not configured. Please configure Pinecone in Settings.');
        }

        // Create a temporary file with the content
        $filename = 'product_' . $product->id . '_' . Str::slug($product->productName) . '.txt';
        $tempPath = storage_path('app/temp/' . $filename);

        // Ensure temp directory exists
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        file_put_contents($tempPath, $content);

        try {
            // Upload to Pinecone Assistant
            $response = Http::timeout(120)
                ->withHeaders([
                    'Api-Key' => $settings->apiKey,
                ])
                ->attach('file', file_get_contents($tempPath), $filename)
                ->post(self::PINECONE_PROD_DATA . '/assistant/files/' . $settings->indexName);

            // Clean up temp file
            @unlink($tempPath);

            if ($response->successful()) {
                $data = $response->json();
                return $data['id'] ?? $data['file_id'] ?? null;
            }

            $error = $response->json('error.message') ?? $response->json('message') ?? 'Unknown error';
            throw new \Exception('Pinecone upload failed: ' . $error);

        } catch (\Exception $e) {
            @unlink($tempPath);
            throw $e;
        }
    }

    /**
     * Delete a product and all its images.
     */
    public function destroy($id)
    {
        $product = AiExternalProduct::where('id', $id)
                        ->where('delete_status', 'active')
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        try {
            // Delete from Pinecone if indexed
            if ($product->pineconeFileId) {
                $this->deleteFromRag($product);
            }

            // Delete all product images
            $images = $product->images;
            foreach ($images as $image) {
                if ($image->imagePath && Storage::disk('public')->exists($image->imagePath)) {
                    Storage::disk('public')->delete($image->imagePath);
                }
                $image->update(['delete_status' => 'deleted']);
            }

            // Delete legacy image file if exists
            if ($product->imagePath && Storage::disk('public')->exists($product->imagePath)) {
                Storage::disk('public')->delete($product->imagePath);
            }

            // Soft delete product
            $product->update(['delete_status' => 'deleted']);

            Log::info('External product deleted', [
                'productId' => $product->id,
                'imagesDeleted' => $images->count(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete product: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product.',
            ], 500);
        }
    }

    /**
     * Delete product from RAG.
     */
    protected function deleteFromRag(AiExternalProduct $product): bool
    {
        $settings = AiRagSetting::active()->forUser($product->usersId)->first();

        if (!$settings || !$settings->apiKey || !$product->pineconeFileId) {
            return false;
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Api-Key' => $settings->apiKey,
                ])
                ->delete(self::PINECONE_PROD_DATA . '/assistant/files/' . $settings->indexName . '/' . $product->pineconeFileId);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Failed to delete product from RAG: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Retry processing a failed product.
     */
    public function retry($id)
    {
        return $this->process($id);
    }

    /**
     * Get product details.
     */
    public function show($id)
    {
        $product = AiExternalProduct::where('id', $id)
                        ->where('delete_status', 'active')
            ->with([
                'images' => function ($q) {
                    $q->where('delete_status', 'active')->orderBy('isPrimary', 'desc')->orderBy('sortOrder');
                },
                'documents' => function ($q) {
                    $q->where('delete_status', 'active')->orderBy('sortOrder');
                }
            ])
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $product->id,
                'productName' => $product->productName,
                'brandName' => $product->brandName,
                'manufacturer' => $product->manufacturer,
                'productType' => $product->productType,
                'typeDisplay' => $product->type_display,
                'manualText' => $product->manualText,
                'combinedOcrText' => $product->combined_ocr_text,
                'combinedDocumentText' => $product->combined_document_text,
                'aiAnalysis' => $product->aiAnalysis,
                'ragContent' => $product->ragContent,
                'primaryImageUrl' => $product->primary_image_url,
                'images' => $product->images->map(function ($img) {
                    return [
                        'id' => $img->id,
                        'imageUrl' => $img->image_url,
                        'originalName' => $img->originalName,
                        'fileSize' => $img->file_size_human,
                        'status' => $img->status,
                        'statusBadge' => $img->status_badge,
                        'isPrimary' => $img->isPrimary,
                        'imageType' => $img->image_type_display,
                        'ocrText' => $img->ocrText,
                        'aiAnalysis' => $img->aiAnalysis,
                    ];
                }),
                'documents' => $product->documents->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'documentUrl' => $doc->document_url,
                        'originalName' => $doc->originalName,
                        'shortName' => $doc->short_name,
                        'fileSize' => $doc->file_size_human,
                        'fileExtension' => $doc->fileExtension,
                        'documentIcon' => $doc->document_icon,
                        'status' => $doc->status,
                        'statusBadge' => $doc->status_badge,
                        'wordCount' => $doc->word_count,
                        'textPreview' => $doc->text_preview,
                        'extractedText' => $doc->extractedText,
                    ];
                }),
                'imageCount' => $product->images->count(),
                'documentCount' => $product->documents->count(),
                'ragStatus' => $product->ragStatus,
                'ragStatusBadge' => $product->rag_status_badge,
                'ragError' => $product->ragError,
                'isIndexed' => $product->isIndexed(),
                'createdAt' => $product->created_at->format('M d, Y H:i'),
                'updatedAt' => $product->updated_at->format('M d, Y H:i'),
            ],
        ]);
    }

    /**
     * Get product types for dropdown.
     */
    public function getProductTypes()
    {
        return response()->json([
            'success' => true,
            'data' => AiExternalProduct::getProductTypes(),
            'categories' => AiExternalProduct::getProductTypeCategories(),
        ]);
    }

    /**
     * Extract text from a document based on its file type.
     */
    protected function extractTextFromDocument(AiExternalProductDocument $document): ?string
    {
        $fullPath = Storage::disk('public')->path($document->documentPath);
        if (!file_exists($fullPath)) {
            Log::warning('Document file not found', ['path' => $fullPath]);
            return null;
        }

        try {
            $extension = strtolower($document->fileExtension);

            switch ($extension) {
                case 'txt':
                    return $this->extractTextFromTxt($fullPath);

                case 'pdf':
                    return $this->extractTextFromPdf($fullPath, $document);

                case 'doc':
                case 'docx':
                    return $this->extractTextFromWord($fullPath, $extension, $document);

                default:
                    Log::warning('Unsupported document type', ['extension' => $extension]);
                    return null;
            }
        } catch (\Exception $e) {
            Log::error('Document text extraction failed', [
                'documentId' => $document->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Extract text from a plain text file.
     */
    protected function extractTextFromTxt(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }
        // Clean up and normalize whitespace
        return trim(preg_replace('/\r\n|\r/', "\n", $content));
    }

    /**
     * Extract text from a PDF file using Gemini Vision API.
     * PDFs are converted to images page by page and analyzed with AI.
     */
    protected function extractTextFromPdf(string $filePath, AiExternalProductDocument $document): ?string
    {
        // First, try to extract text directly from PDF using basic parsing
        $directText = $this->extractPdfTextDirect($filePath);

        if (!empty($directText) && strlen($directText) > 100) {
            // If we got meaningful text, return it
            return $directText;
        }

        // Fallback: Use Gemini to analyze the PDF (requires uploading the file)
        // For now, we'll use the direct extraction or return null
        Log::info('PDF direct extraction result', [
            'documentId' => $document->id,
            'textLength' => strlen($directText ?? ''),
        ]);

        return $directText ?: null;
    }

    /**
     * Extract text directly from PDF file using basic parsing.
     * This works for text-based PDFs but not scanned images.
     */
    protected function extractPdfTextDirect(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        $text = '';

        // Method 1: Extract text from PDF streams (works for most text PDFs)
        // Look for text between BT (begin text) and ET (end text) markers
        preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $matches);
        foreach ($matches[1] as $match) {
            // Extract text from Tj and TJ operators
            preg_match_all('/\[(.*?)\]\s*TJ|\((.*?)\)\s*Tj/s', $match, $textMatches, PREG_SET_ORDER);
            foreach ($textMatches as $textMatch) {
                if (!empty($textMatch[1])) {
                    // TJ array format
                    preg_match_all('/\((.*?)\)/', $textMatch[1], $arrayTexts);
                    $text .= implode('', $arrayTexts[1]) . ' ';
                } elseif (!empty($textMatch[2])) {
                    // Tj simple format
                    $text .= $textMatch[2] . ' ';
                }
            }
        }

        // Method 2: Look for stream objects with FlateDecode
        preg_match_all('/stream(.*?)endstream/s', $content, $streamMatches);
        foreach ($streamMatches[1] as $stream) {
            // Try to decompress if it's zlib compressed
            $decompressed = @gzuncompress(trim($stream));
            if ($decompressed === false) {
                $decompressed = @gzinflate(trim($stream));
            }
            if ($decompressed !== false) {
                // Extract readable text from decompressed content
                $readable = preg_replace('/[^\x20-\x7E\n\r]/', ' ', $decompressed);
                if (strlen(trim($readable)) > 20) {
                    $text .= ' ' . $readable;
                }
            }
        }

        // Clean up the extracted text
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/[^\x20-\x7E\n]/', '', $text);
        $text = trim($text);

        // If we got some text, return it
        if (strlen($text) > 50) {
            return $text;
        }

        // Method 3: Simple fallback - extract anything that looks like text
        preg_match_all('/\(([^)]{3,})\)/', $content, $parenMatches);
        $simpleText = '';
        foreach ($parenMatches[1] as $match) {
            // Filter out binary/control characters
            $cleaned = preg_replace('/[^\x20-\x7E]/', '', $match);
            if (strlen($cleaned) > 3 && preg_match('/[a-zA-Z]{2,}/', $cleaned)) {
                $simpleText .= $cleaned . ' ';
            }
        }

        if (strlen(trim($simpleText)) > 50) {
            return trim($simpleText);
        }

        return null;
    }

    /**
     * Extract text from Word documents (.doc, .docx).
     */
    protected function extractTextFromWord(string $filePath, string $extension, AiExternalProductDocument $document): ?string
    {
        if ($extension === 'docx') {
            return $this->extractTextFromDocx($filePath);
        }

        // For .doc files, try basic extraction
        return $this->extractTextFromDoc($filePath);
    }

    /**
     * Extract text from .docx file (ZIP-based XML format).
     */
    protected function extractTextFromDocx(string $filePath): ?string
    {
        // DOCX is a ZIP file containing XML
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            Log::warning('Failed to open DOCX as ZIP');
            return null;
        }

        $content = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!$content) {
            return null;
        }

        // Parse XML and extract text
        $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOWARNING);
        if (!$xml) {
            // Fallback: strip XML tags
            return trim(strip_tags($content));
        }

        // Register namespace
        $namespaces = $xml->getNamespaces(true);
        $text = '';

        // Extract text from paragraphs
        if (isset($namespaces['w'])) {
            $xml->registerXPathNamespace('w', $namespaces['w']);
            $paragraphs = $xml->xpath('//w:p');
            foreach ($paragraphs as $paragraph) {
                $paragraph->registerXPathNamespace('w', $namespaces['w']);
                $texts = $paragraph->xpath('.//w:t');
                $paragraphText = '';
                foreach ($texts as $t) {
                    $paragraphText .= (string)$t;
                }
                if (!empty($paragraphText)) {
                    $text .= $paragraphText . "\n";
                }
            }
        }

        return trim($text) ?: null;
    }

    /**
     * Extract text from .doc file (legacy binary format).
     * Note: This is a simplified extraction and may not work for all .doc files.
     */
    protected function extractTextFromDoc(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        // Try to extract readable text from binary
        // This is a basic approach - for full support, use a library like antiword
        $text = '';

        // Look for text between common markers
        // DOC files have text mixed with binary, try to extract printable characters
        $length = strlen($content);
        $currentWord = '';

        for ($i = 0; $i < $length; $i++) {
            $char = $content[$i];
            $ord = ord($char);

            // Printable ASCII or common extended characters
            if (($ord >= 32 && $ord <= 126) || $ord === 10 || $ord === 13) {
                $currentWord .= $char;
            } else {
                if (strlen($currentWord) > 2) {
                    $text .= $currentWord . ' ';
                }
                $currentWord = '';
            }
        }

        // Add final word
        if (strlen($currentWord) > 2) {
            $text .= $currentWord;
        }

        // Clean up
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // Filter out likely binary garbage
        $words = explode(' ', $text);
        $validWords = array_filter($words, function ($word) {
            // Keep words that look like real words (have at least 2 letters)
            return preg_match('/[a-zA-Z]{2,}/', $word);
        });

        return !empty($validWords) ? implode(' ', $validWords) : null;
    }
}
