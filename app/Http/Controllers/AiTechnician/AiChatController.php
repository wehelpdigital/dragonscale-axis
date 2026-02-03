<?php

namespace App\Http\Controllers\AiTechnician;

use App\Http\Controllers\Controller;
use App\Models\AiChatAvatarSetting;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\AiCurrencySetting;
use App\Models\AiReplyFlow;
use App\Services\ReplyFlowProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AiChatController extends Controller
{
    /**
     * Maximum number of images allowed per message.
     */
    const MAX_IMAGES = 10;

    /**
     * Maximum file size for images (5MB).
     */
    const MAX_IMAGE_SIZE = 5 * 1024 * 1024;

    /**
     * Allowed image mime types.
     */
    const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    /**
     * Number of sessions to load per page.
     */
    const SESSIONS_PER_PAGE = 15;

    /**
     * Display the chat interface.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        // Get paginated chat sessions for the user
        $sessionsQuery = AiChatSession::active()
            ->forUser($userId)
            ->orderBy('lastMessageAt', 'desc')
            ->orderBy('created_at', 'desc');

        $totalSessions = $sessionsQuery->count();
        $sessions = $sessionsQuery->take(self::SESSIONS_PER_PAGE)->get();
        $hasMoreSessions = $totalSessions > self::SESSIONS_PER_PAGE;

        // Get current session if specified, or create new
        $currentSessionId = $request->get('session');
        $currentSession = null;
        $messages = collect();

        if ($currentSessionId) {
            $currentSession = AiChatSession::where('id', $currentSessionId)
                ->where('usersId', $userId)
                ->where('delete_status', 'active')
                ->first();

            if ($currentSession) {
                $messages = $currentSession->messages()->get();
            }
        }

        // Get reply flow status
        $replyFlow = AiReplyFlow::getOrCreateForUser($userId);

        // Get currency settings for exchange rate
        $currencySettings = AiCurrencySetting::getOrCreateForUser($userId);

        // Get avatar settings for chat display
        $avatarSettings = AiChatAvatarSetting::getOrCreateForUser($userId);

        return view('ai-technician.chat.index', compact(
            'sessions',
            'currentSession',
            'messages',
            'replyFlow',
            'hasMoreSessions',
            'totalSessions',
            'currencySettings',
            'avatarSettings'
        ));
    }

    /**
     * Create a new chat session.
     */
    public function createSession(Request $request)
    {
        try {
            $userId = Auth::id();

            // Get current reply flow
            $replyFlow = AiReplyFlow::getOrCreateForUser($userId);

            $session = AiChatSession::create([
                'usersId' => $userId,
                'sessionName' => null,
                'replyFlowId' => $replyFlow->id,
                'lastMessageAt' => now(),
                'messageCount' => 0,
                'delete_status' => 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'New chat session created.',
                'data' => [
                    'id' => $session->id,
                    'displayName' => $session->display_name,
                    'createdAt' => $session->created_at->format('M d, Y H:i'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Create chat session error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create chat session.',
            ], 500);
        }
    }

    /**
     * Get messages for a session.
     */
    public function getMessages($sessionId)
    {
        $session = AiChatSession::where('id', $sessionId)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found.',
            ], 404);
        }

        $messages = $session->messages()->get()->map(function ($msg) {
            // Get searched images from metadata (AI-generated + web images)
            $metadata = $msg->metadata ?? [];
            $searchedImages = $metadata['images'] ?? [];

            return [
                'id' => $msg->id,
                'role' => $msg->role,
                'content' => $msg->content,
                'images' => $msg->image_urls,
                'hasImages' => $msg->has_images,
                'searchedImages' => $searchedImages, // AI and web images
                'formattedTime' => $msg->formatted_time,
                'formattedDate' => $msg->formatted_date,
                'processingTime' => $msg->processing_time_formatted,
                'flowLog' => $metadata['flowLog'] ?? null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'session' => [
                    'id' => $session->id,
                    'displayName' => $session->display_name,
                ],
                'messages' => $messages,
            ],
        ]);
    }

    /**
     * Send a message in a chat session.
     */
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sessionId' => 'required|integer',
            'message' => 'required_without:images|string|max:10000',
            'images' => 'nullable|array|max:' . self::MAX_IMAGES,
            'images.*' => 'file|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ], [
            'message.required_without' => 'Please enter a message or upload images.',
            'images.max' => 'You can upload a maximum of ' . self::MAX_IMAGES . ' images.',
            'images.*.max' => 'Each image must be less than 5MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $userId = Auth::id();

        // Get session
        $session = AiChatSession::where('id', $request->sessionId)
            ->where('usersId', $userId)
            ->where('delete_status', 'active')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found.',
            ], 404);
        }

        try {
            $startTime = microtime(true);

            // Handle image uploads
            $imagePaths = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $filename = Str::uuid() . '.' . $image->getClientOriginalExtension();
                    $path = $image->storeAs('chat-images/' . $userId, $filename, 'public');
                    $imagePaths[] = $path;
                }
            }

            $messageContent = $request->input('message', '');

            // If only images and no text, set a default message
            if (empty($messageContent) && !empty($imagePaths)) {
                $messageContent = '[Image' . (count($imagePaths) > 1 ? 's' : '') . ' uploaded]';
            }

            // Save user message
            $userMessage = AiChatMessage::create([
                'sessionId' => $session->id,
                'role' => AiChatMessage::ROLE_USER,
                'content' => $messageContent,
                'images' => $imagePaths,
                'metadata' => null,
                'delete_status' => 'active',
            ]);

            $session->recordNewMessage();

            // Update session name if this is the first message
            if ($session->messageCount <= 1 && !$session->sessionName) {
                $session->update([
                    'sessionName' => Str::limit($messageContent, 50),
                ]);
            }

            // Process with Reply Flow
            $processor = new ReplyFlowProcessor($userId);
            $result = $processor->process($session, $messageContent, $imagePaths);

            $processingTime = microtime(true) - $startTime;

            // Save thinking reply if exists
            $thinkingMessage = null;
            if (!empty($result['thinkingReply'])) {
                $thinkingMessage = AiChatMessage::create([
                    'sessionId' => $session->id,
                    'role' => AiChatMessage::ROLE_THINKING,
                    'content' => $result['thinkingReply'],
                    'metadata' => ['temporary' => true],
                    'delete_status' => 'active',
                ]);
            }

            // Save assistant response
            $assistantMessage = AiChatMessage::create([
                'sessionId' => $session->id,
                'role' => AiChatMessage::ROLE_ASSISTANT,
                'content' => $result['response'],
                'metadata' => $result['metadata'],
                'processingTime' => $processingTime,
                'delete_status' => 'active',
            ]);

            $session->recordNewMessage();

            // Mark thinking message as deleted (it was just temporary)
            if ($thinkingMessage) {
                $thinkingMessage->update(['delete_status' => 'deleted']);
            }

            Log::debug('Chat response prepared', [
                'sessionId' => $session->id,
                'userMessageId' => $userMessage->id,
                'assistantMessageId' => $assistantMessage->id,
                'responseLength' => strlen($result['response']),
                'hasThinkingReply' => !empty($result['thinkingReply']),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'userMessage' => [
                        'id' => $userMessage->id,
                        'role' => $userMessage->role,
                        'content' => $userMessage->content,
                        'images' => $userMessage->image_urls,
                        'hasImages' => $userMessage->has_images,
                        'formattedTime' => $userMessage->formatted_time,
                    ],
                    'thinkingReply' => $result['thinkingReply'] ?? null,
                    'assistantMessage' => [
                        'id' => $assistantMessage->id,
                        'role' => $assistantMessage->role,
                        'content' => $assistantMessage->content,
                        'formattedTime' => $assistantMessage->formatted_time,
                        'processingTime' => $assistantMessage->processing_time_formatted,
                    ],
                    'sessionName' => $session->display_name,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Send message error: ' . $e->getMessage(), [
                'sessionId' => $session->id,
                'userId' => $userId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process message. Please try again.',
            ], 500);
        }
    }

    /**
     * Send a message with streaming response (thinking reply sent immediately).
     * Uses Server-Sent Events to push thinking reply before main processing.
     */
    public function sendMessageStream(Request $request)
    {
        $sessionId = $request->input('sessionId');
        $messageContent = $request->input('message', '');
        $questionType = $request->input('questionType', 'new'); // 'new' or 'followup'
        $lastQuestion = $request->input('lastQuestion', '');
        $userId = Auth::id();

        // Get session
        $session = AiChatSession::where('id', $sessionId)
            ->where('usersId', $userId)
            ->where('delete_status', 'active')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found.',
            ], 404);
        }

        // Handle image uploads first (before streaming starts)
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filename = Str::uuid() . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('chat-images/' . $userId, $filename, 'public');
                $imagePaths[] = $path;
            }
        }

        // If only images and no text, set a default message
        if (empty($messageContent) && !empty($imagePaths)) {
            $messageContent = '[Image' . (count($imagePaths) > 1 ? 's' : '') . ' uploaded]';
        }

        // Save user message before streaming
        $userMessage = AiChatMessage::create([
            'sessionId' => $session->id,
            'role' => AiChatMessage::ROLE_USER,
            'content' => $messageContent,
            'images' => $imagePaths,
            'metadata' => null,
            'delete_status' => 'active',
        ]);

        $session->recordNewMessage();

        // Update session name if first message
        if ($session->messageCount <= 1 && !$session->sessionName) {
            $session->update([
                'sessionName' => Str::limit($messageContent, 50),
            ]);
        }

        return response()->stream(function () use ($session, $userMessage, $messageContent, $imagePaths, $userId, $questionType, $lastQuestion) {
            // Disable ALL output buffering levels for real-time streaming
            while (ob_get_level()) {
                ob_end_flush();
            }

            // Set unlimited execution time for long-running requests
            set_time_limit(300);

            // Disable implicit flush
            @ini_set('implicit_flush', 1);
            @ini_set('zlib.output_compression', 0);

            // Send initial padding to force buffer flush (Apache workaround)
            echo ":" . str_repeat(" ", 2048) . "\n\n";
            flush();

            Log::debug('=== SSE STREAM STARTED ===', [
                'sessionId' => $session->id,
                'userId' => $userId,
                'messageContent' => substr($messageContent, 0, 100),
            ]);

            try {
                $startTime = microtime(true);

                // Send user message confirmation first
                Log::debug('Sending user_message event...');
                $this->sendSSE('user_message', [
                    'id' => $userMessage->id,
                    'role' => $userMessage->role,
                    'content' => $userMessage->content,
                    'images' => $userMessage->image_urls,
                    'hasImages' => $userMessage->has_images,
                    'formattedTime' => $userMessage->formatted_time,
                ]);

                // Create processor
                Log::debug('Creating ReplyFlowProcessor...');
                $processor = new ReplyFlowProcessor($userId);

                // Determine topic context for follow-up questions
                $topicContextForBlocker = null;
                if ($questionType === 'followup' && !empty($lastQuestion)) {
                    $topicContextForBlocker = $lastQuestion;
                }

                // STEP 1: Check Blocker AND get thinking reply (quick operations)
                // Pass topic context so blocker can understand meta-questions (like "check online")
                Log::debug('Checking blocker and getting thinking reply...', [
                    'hasTopicContext' => !empty($topicContextForBlocker),
                ]);
                $preCheck = $processor->checkBlockerAndGetThinkingReply($session, $messageContent, $imagePaths, $topicContextForBlocker);
                Log::debug('PreCheck result', [
                    'blocked' => $preCheck['blocked'],
                    'hasThinkingReply' => !empty($preCheck['thinkingReply']),
                    'specialState' => $preCheck['specialState'] ?? null,
                ]);

                // If BLOCKED, send block message and stop
                if ($preCheck['blocked']) {
                    Log::debug('Request BLOCKED, sending blocked event...');
                    // Send blocked message immediately
                    $this->sendSSE('blocked', [
                        'content' => $preCheck['blockMessage'],
                        'formattedTime' => now()->format('h:i A'),
                    ]);

                    // Save as assistant response
                    $assistantMessage = AiChatMessage::create([
                        'sessionId' => $session->id,
                        'role' => AiChatMessage::ROLE_ASSISTANT,
                        'content' => $preCheck['blockMessage'],
                        'metadata' => ['blocked' => true],
                        'processingTime' => microtime(true) - $startTime,
                        'delete_status' => 'active',
                    ]);

                    $session->recordNewMessage();

                    // Send done signal
                    $this->sendSSE('done', ['success' => true, 'blocked' => true]);
                    Log::debug('=== SSE STREAM COMPLETED (BLOCKED) ===');
                    return;
                }

                // FOLLOW-UP RELEVANCE CHECK
                if ($questionType === 'followup' && !empty($lastQuestion)) {
                    // Get the last AI response to provide context for relevance check
                    $lastAiResponse = $session->messages()
                        ->where('role', AiChatMessage::ROLE_ASSISTANT)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    $previousResponse = $lastAiResponse ? $lastAiResponse->content : null;

                    Log::debug('Checking follow-up relevance...', [
                        'lastQuestion' => $lastQuestion,
                        'currentQuestion' => $messageContent,
                        'hasPreviousResponse' => !empty($previousResponse),
                    ]);

                    $isRelated = $processor->checkFollowupRelevance($lastQuestion, $messageContent, $previousResponse);

                    if (!$isRelated) {
                        Log::debug('Follow-up NOT related to original question');

                        $notRelatedMessage = "Mukhang iba na po ang topic ng tanong niyo sa previous question. Para mas malinaw ang sagot, mas mainam po na mag-click kayo ng 'New Question' button para makapag-start ng fresh na conversation tungkol dito.";

                        $this->sendSSE('not_related', [
                            'content' => $notRelatedMessage,
                            'formattedTime' => now()->format('h:i A'),
                        ]);

                        // Save as assistant response
                        AiChatMessage::create([
                            'sessionId' => $session->id,
                            'role' => AiChatMessage::ROLE_ASSISTANT,
                            'content' => $notRelatedMessage,
                            'metadata' => ['notRelated' => true],
                            'processingTime' => microtime(true) - $startTime,
                            'delete_status' => 'active',
                        ]);

                        $session->recordNewMessage();

                        $this->sendSSE('done', ['success' => true, 'notRelated' => true]);
                        Log::debug('=== SSE STREAM COMPLETED (NOT RELATED) ===');
                        return;
                    }
                }

                // STEP 2: Send thinking reply IMMEDIATELY (if available)
                // NOTE: Thinking reply is now random (40% chance) for more natural feel
                if ($preCheck['thinkingReply']) {
                    Log::debug('Sending thinking event...');
                    $this->sendSSE('thinking', [
                        'content' => $preCheck['thinkingReply'],
                        'formattedTime' => now()->format('h:i A'),
                    ]);
                }

                // STEP 3: Now process the full flow (slow operation)
                // This includes handling special conversation states (gender greeting, etc.)
                Log::debug('Processing main flow...');

                // Pass topic context for follow-up questions to maintain conversation coherence
                $topicContext = null;
                if ($questionType === 'followup' && !empty($lastQuestion)) {
                    $topicContext = $lastQuestion;
                }

                // ========================================================
                // IMAGE ANALYSIS: If images are uploaded, perform deep analysis
                // ========================================================
                $imageAnalysisResult = null;
                if (!empty($imagePaths)) {
                    Log::debug('Images detected, performing deep analysis...', [
                        'imageCount' => count($imagePaths),
                    ]);

                    // Send a "thinking" event for image analysis
                    $this->sendSSE('thinking', [
                        'content' => 'Sinusuri ko po ang ' . count($imagePaths) . ' na larawan na in-upload niyo...',
                        'formattedTime' => now()->format('h:i A'),
                    ]);

                    // Perform deep image analysis
                    $imageAnalysisResult = $processor->analyzeUploadedImages(
                        $imagePaths,
                        $messageContent,
                        $topicContext
                    );

                    Log::debug('Image analysis result', [
                        'success' => $imageAnalysisResult['success'] ?? false,
                        'analysisLength' => strlen($imageAnalysisResult['analysis'] ?? ''),
                    ]);
                }

                // Process main flow (includes chat history and context)
                $result = $processor->processMainFlow($session, $messageContent, $imagePaths, $topicContext);
                Log::debug('Main flow completed', [
                    'responseLength' => strlen($result['response'] ?? ''),
                    'hasMetadata' => !empty($result['metadata']),
                ]);

                // If we have image analysis, combine it with the main response
                if ($imageAnalysisResult && $imageAnalysisResult['success'] && !empty($imageAnalysisResult['analysis'])) {
                    // If main response is generic (no real answer), use image analysis as the response
                    $mainResponse = $result['response'] ?? '';

                    // Check if main response is minimal or generic
                    $isGenericResponse = strlen($mainResponse) < 100 ||
                                        stripos($mainResponse, 'image') !== false ||
                                        stripos($mainResponse, 'larawan') !== false;

                    if ($isGenericResponse) {
                        // Use image analysis as the main response
                        $result['response'] = $imageAnalysisResult['analysis'];
                    } else {
                        // Combine both responses
                        $result['response'] = "=== PAGSUSURI NG LARAWAN ===\n\n" .
                                            $imageAnalysisResult['analysis'] .
                                            "\n\n=== KARAGDAGANG IMPORMASYON ===\n\n" .
                                            $mainResponse;
                    }

                    // Add analysis metadata
                    $result['metadata']['imageAnalysis'] = [
                        'success' => true,
                        'imageCount' => $imageAnalysisResult['imageCount'] ?? count($imagePaths),
                        'summary' => $imageAnalysisResult['summary'] ?? '',
                    ];

                    Log::debug('Combined image analysis with response', [
                        'totalLength' => strlen($result['response']),
                    ]);
                }

                // POST-PROCESSING: Run cleanup and product enhancement AFTER image analysis is combined
                // This ensures the enhancement can find problems diagnosed in the image analysis
                // NOTE: Only run post-processing here if we had images to analyze
                // For text-only questions, post-processing already ran inside processMainFlow
                if (!empty($result['response']) && !empty($imagePaths)) {
                    $postProcessed = $processor->postProcessCombinedResponse($result['response']);
                    $result['response'] = $postProcessed['response'];

                    // Merge any additional product images found during post-processing
                    if (!empty($postProcessed['productImages'])) {
                        // Merge with existing productImages instead of replacing
                        $existingImages = $result['productImages'] ?? [];
                        foreach ($postProcessed['productImages'] as $newImg) {
                            $exists = false;
                            foreach ($existingImages as $existingImg) {
                                if (($existingImg['url'] ?? '') === ($newImg['url'] ?? '')) {
                                    $exists = true;
                                    break;
                                }
                            }
                            if (!$exists) {
                                $existingImages[] = $newImg;
                            }
                        }
                        $result['productImages'] = $existingImages;
                        Log::debug('Post-processing merged product images', [
                            'count' => count($result['productImages']),
                        ]);
                    }
                }

                // Check if user requested images and generate educational visuals
                // BUT NOT if user uploaded their own images for analysis
                $searchImages = [];
                $userUploadedImages = !empty($imagePaths);

                if (!$userUploadedImages && $processor->isImageRequest($messageContent)) {
                    Log::debug('Image request detected, generating educational images...');

                    // Extract search query from the message
                    $imageQuery = $processor->extractImageSearchQuery($messageContent);

                    // If we have topic context (follow-up), combine it
                    if (!empty($topicContext)) {
                        $imageQuery = $topicContext . ' ' . $imageQuery;
                    }

                    // Generate images: 2 AI-generated + 2 from web search
                    $aiResponse = $result['response'] ?? '';
                    $searchImages = $processor->searchImages($imageQuery, 4, $aiResponse);
                    Log::debug('Image search completed', ['imagesFound' => count($searchImages)]);
                } elseif ($userUploadedImages) {
                    Log::debug('User uploaded images for analysis, skipping image generation');
                }

                // Add extracted images from AI response (always show - these are part of the answer)
                if (!empty($result['images'])) {
                    foreach ($result['images'] as $extractedImg) {
                        // Ensure proper badge format
                        if (empty($extractedImg['badgeClass'])) {
                            $extractedImg['badgeClass'] = 'product-badge';
                        }
                        if (empty($extractedImg['badgeText'])) {
                            $extractedImg['badgeText'] = 'Product';
                        }
                        $extractedImg['imageType'] = 'product';
                        array_unshift($searchImages, $extractedImg); // Add to beginning
                    }
                    Log::debug('Added extracted images from AI response', ['count' => count($result['images'])]);
                }

                // Add product images from RAG ONLY if:
                // 1. User explicitly asked for a product/treatment (gamot, spray, etc.)
                // 2. OR response actually contains a product recommendation section
                if (!empty($result['productImages'])) {
                    $responseText = $result['response'] ?? '';
                    $userAskedForProduct = preg_match('/\b(gamot|spray|pataba|abono|fertilizer|pesticide|insecticide|fungicide|recommend|rekomenda|treatment|solusyon|ilagay|i-apply|i-spray)\b/i', $messageContent);
                    $responseHasProductRecommendation = stripos($responseText, 'INIREREKOMENDANG PRODUKTO') !== false;

                    if ($userAskedForProduct || $responseHasProductRecommendation) {
                        foreach ($result['productImages'] as $productImg) {
                            // Skip if already added from extracted images
                            $alreadyAdded = false;
                            foreach ($searchImages as $existing) {
                                if (($existing['url'] ?? '') === ($productImg['url'] ?? '')) {
                                    $alreadyAdded = true;
                                    break;
                                }
                            }
                            if ($alreadyAdded) continue;

                            // Add product badge type
                            $productImg['imageType'] = 'product';
                            $productImg['badgeText'] = 'Product';
                            $productImg['badgeClass'] = 'bg-warning text-dark';
                            array_unshift($searchImages, $productImg); // Add to beginning
                        }
                        Log::debug('Added product images from RAG', ['count' => count($result['productImages'])]);
                    } else {
                        Log::debug('Skipping product images - user did not ask for product and response has no recommendation', [
                            'userMessage' => substr($messageContent, 0, 100),
                        ]);
                    }
                }

                $processingTime = microtime(true) - $startTime;

                // Get flow log from processor
                $flowLog = $processor->getFlowLog();
                $flowLog['processingTime'] = round($processingTime, 2);

                // Save assistant response
                Log::debug('Saving assistant message...');
                $assistantMessage = AiChatMessage::create([
                    'sessionId' => $session->id,
                    'role' => AiChatMessage::ROLE_ASSISTANT,
                    'content' => $result['response'],
                    'metadata' => array_merge($result['metadata'] ?? [], [
                        'flowLog' => $flowLog,
                        'images' => $searchImages,
                    ]),
                    'processingTime' => $processingTime,
                    'delete_status' => 'active',
                ]);

                $session->recordNewMessage();

                // Send final response with flow log and images
                Log::debug('Sending response event...');
                $this->sendSSE('response', [
                    'id' => $assistantMessage->id,
                    'role' => $assistantMessage->role,
                    'content' => $assistantMessage->content,
                    'formattedTime' => $assistantMessage->formatted_time,
                    'processingTime' => $assistantMessage->processing_time_formatted,
                    'sessionName' => $session->display_name,
                    'flowLog' => $flowLog,
                    'images' => $searchImages,
                ]);

                // Send done signal
                Log::debug('Sending done event...');
                $this->sendSSE('done', ['success' => true]);
                Log::debug('=== SSE STREAM COMPLETED SUCCESSFULLY ===');

            } catch (\Exception $e) {
                Log::error('=== SSE STREAM ERROR ===', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Send error event to client
                $this->sendSSE('error', [
                    'message' => 'An error occurred: ' . $e->getMessage(),
                ]);
            }

        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Helper to send Server-Sent Event.
     */
    private function sendSSE(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";

        // Aggressive flushing for Windows/XAMPP compatibility
        while (ob_get_level()) {
            ob_flush();
        }
        flush();

        // Log for debugging
        Log::debug('SSE event sent', ['event' => $event, 'dataSize' => strlen(json_encode($data))]);
    }

    /**
     * Rename a chat session.
     */
    public function renameSession(Request $request, $sessionId)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $session = AiChatSession::where('id', $sessionId)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found.',
            ], 404);
        }

        $session->update(['sessionName' => $request->name]);

        // Mark title as generated to prevent AI regeneration
        $session->markTitleGenerated();

        return response()->json([
            'success' => true,
            'message' => 'Session renamed successfully.',
            'data' => [
                'displayName' => $session->display_name,
            ],
        ]);
    }

    /**
     * Delete a chat session.
     */
    public function deleteSession($sessionId)
    {
        $session = AiChatSession::where('id', $sessionId)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found.',
            ], 404);
        }

        $session->update(['delete_status' => 'deleted']);

        // Also soft delete all messages
        AiChatMessage::where('sessionId', $sessionId)
            ->update(['delete_status' => 'deleted']);

        return response()->json([
            'success' => true,
            'message' => 'Chat session deleted.',
        ]);
    }

    /**
     * Delete a specific message.
     */
    public function deleteMessage($messageId)
    {
        $message = AiChatMessage::where('id', $messageId)
            ->where('delete_status', 'active')
            ->whereHas('session', function ($q) {
                $q->where('usersId', Auth::id());
            })
            ->first();

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found.',
            ], 404);
        }

        $message->update(['delete_status' => 'deleted']);

        return response()->json([
            'success' => true,
            'message' => 'Message deleted.',
        ]);
    }

    /**
     * Clear all messages in a session.
     */
    public function clearSession($sessionId)
    {
        $session = AiChatSession::where('id', $sessionId)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found.',
            ], 404);
        }

        AiChatMessage::where('sessionId', $sessionId)
            ->update(['delete_status' => 'deleted']);

        $session->update(['messageCount' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Chat history cleared.',
        ]);
    }

    /**
     * Generate AI title for a chat session based on its content.
     */
    public function generateTitle($sessionId)
    {
        $userId = Auth::id();

        Log::info('Generate title request received', [
            'sessionId' => $sessionId,
            'userId' => $userId,
        ]);

        $session = AiChatSession::where('id', $sessionId)
            ->where('usersId', $userId)
            ->where('delete_status', 'active')
            ->first();

        if (!$session) {
            Log::warning('Generate title - session not found', ['sessionId' => $sessionId]);
            return response()->json([
                'success' => false,
                'message' => 'Session not found.',
            ], 404);
        }

        // Check if title was already AI-generated - skip to save API calls
        if ($session->isTitleGenerated()) {
            Log::info('Generate title - already generated, skipping', [
                'sessionId' => $sessionId,
                'currentTitle' => $session->sessionName,
            ]);
            return response()->json([
                'success' => true,
                'title' => $session->sessionName,
                'sessionId' => $sessionId,
                'skipped' => true,
                'message' => 'Title already generated.',
            ]);
        }

        // Get the first few messages to understand the conversation topic
        $messages = AiChatMessage::where('sessionId', $sessionId)
            ->where('delete_status', 'active')
            ->orderBy('created_at', 'asc')
            ->limit(4)
            ->get();

        if ($messages->isEmpty()) {
            Log::warning('Generate title - no messages in session', ['sessionId' => $sessionId]);
            return response()->json([
                'success' => false,
                'message' => 'No messages in session.',
            ]);
        }

        Log::info('Generate title - building context', ['messageCount' => $messages->count()]);

        // Build context from messages
        $context = '';
        foreach ($messages as $msg) {
            $role = $msg->role === 'user' ? 'User' : 'AI';
            $content = Str::limit($msg->content, 200);
            $context .= "{$role}: {$content}\n";
        }

        try {
            // Use Gemini to generate a short title
            $processor = new ReplyFlowProcessor($userId);
            $title = $processor->generateChatTitle($context);

            if (!empty($title)) {
                // Clean up the title
                $title = trim($title);
                $title = str_replace(['"', "'", "\n", "\r"], '', $title);
                $title = Str::limit($title, 60);

                // Update session name
                $session->update(['sessionName' => $title]);

                // Mark title as AI-generated to prevent regeneration
                $session->markTitleGenerated();

                return response()->json([
                    'success' => true,
                    'title' => $title,
                    'sessionId' => $sessionId,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Could not generate title.',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate chat title: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating title.',
            ]);
        }
    }

    /**
     * Search chat sessions by text and date range.
     */
    public function searchSessions(Request $request)
    {
        $userId = Auth::id();
        $searchText = $request->input('q', '');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = AiChatSession::where('usersId', $userId)
            ->where('delete_status', 'active');

        // Text search in session name
        if (!empty($searchText)) {
            $query->where(function ($q) use ($searchText, $userId) {
                // Search in session name
                $q->where('sessionName', 'LIKE', "%{$searchText}%");

                // Also search in message content
                $sessionIdsWithMatchingMessages = AiChatMessage::where('delete_status', 'active')
                    ->where('content', 'LIKE', "%{$searchText}%")
                    ->whereHas('session', function ($sq) use ($userId) {
                        $sq->where('usersId', $userId)->where('delete_status', 'active');
                    })
                    ->pluck('sessionId')
                    ->unique();

                if ($sessionIdsWithMatchingMessages->isNotEmpty()) {
                    $q->orWhereIn('id', $sessionIdsWithMatchingMessages);
                }
            });
        }

        // Date range filter
        if (!empty($startDate)) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if (!empty($endDate)) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $sessions = $query->orderBy('lastMessageAt', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'name' => $session->display_name,
                    'lastMessageAgo' => $session->last_message_ago,
                    'createdAt' => $session->created_at->format('M d, Y'),
                    'messageCount' => $session->messageCount,
                    'titleGenerated' => $session->isTitleGenerated(),
                ];
            });

        return response()->json([
            'success' => true,
            'sessions' => $sessions,
            'count' => $sessions->count(),
        ]);
    }

    /**
     * Load more chat sessions for pagination.
     */
    public function loadMoreSessions(Request $request)
    {
        $userId = Auth::id();
        $offset = (int) $request->input('offset', 0);
        $limit = self::SESSIONS_PER_PAGE;

        $query = AiChatSession::active()
            ->forUser($userId)
            ->orderBy('lastMessageAt', 'desc')
            ->orderBy('created_at', 'desc');

        $totalSessions = $query->count();
        $sessions = $query->skip($offset)->take($limit)->get();

        $sessionsData = $sessions->map(function ($session) {
            return [
                'id' => $session->id,
                'displayName' => $session->display_name,
                'lastMessageAgo' => $session->last_message_ago,
                'titleGenerated' => $session->isTitleGenerated(),
            ];
        });

        $hasMore = ($offset + $limit) < $totalSessions;

        return response()->json([
            'success' => true,
            'sessions' => $sessionsData,
            'hasMore' => $hasMore,
            'totalSessions' => $totalSessions,
            'nextOffset' => $offset + $limit,
        ]);
    }
}
