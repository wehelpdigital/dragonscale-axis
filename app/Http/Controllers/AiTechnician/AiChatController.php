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

        // Clean up empty chat sessions (sessions with no messages)
        // This runs on initial load to remove abandoned/empty chats
        $this->cleanupEmptySessions($userId);

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
        $replyFlow = AiReplyFlow::getOrCreate();

        // Get currency settings for exchange rate
        $currencySettings = AiCurrencySetting::getOrCreate();

        // Get avatar settings for chat display
        $avatarSettings = AiChatAvatarSetting::getOrCreate();

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
            $replyFlow = AiReplyFlow::getOrCreate();

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
                    'sessionName' => $session->sessionName,
                    'titleGenerated' => $session->isTitleGenerated(),
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
            // CRITICAL: Continue execution even if client disconnects
            // This ensures the assistant message is saved to database for fallback polling
            ignore_user_abort(true);

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

                // Set progress callback to send SSE progress events
                $controller = $this;
                $processor->setProgressCallback(function ($step, $totalSteps, $stepName, $stepNameTagalog) use ($controller) {
                    $controller->sendSSE('progress', [
                        'step' => $step,
                        'totalSteps' => $totalSteps,
                        'stepName' => $stepName,
                        'stepNameTagalog' => $stepNameTagalog,
                        'percentage' => round(($step / $totalSteps) * 100),
                    ]);
                });

                // Determine topic context for follow-up questions
                $topicContextForBlocker = null;
                if ($questionType === 'followup' && !empty($lastQuestion)) {
                    $topicContextForBlocker = $lastQuestion;
                }

                // STEP 1: Check Blocker AND get thinking reply (quick operations)
                // Pass topic context so blocker can understand meta-questions (like "check online")
                // Also pass user message ID to exclude from chat history (prevents extracting context from current message)
                Log::debug('Checking blocker and getting thinking reply...', [
                    'hasTopicContext' => !empty($topicContextForBlocker),
                    'userMessageId' => $userMessage->id,
                ]);
                $preCheck = $processor->checkBlockerAndGetThinkingReply($session, $messageContent, $imagePaths, $topicContextForBlocker, $userMessage->id);
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

                // ========================================================
                // GIBBERISH DETECTION: If message is unintelligible, ask for clarification
                // ========================================================
                $isGibberish = $this->isGibberishMessage($messageContent);
                if ($isGibberish && empty($imagePaths)) {
                    Log::info('Gibberish message detected without images', [
                        'message' => $messageContent,
                    ]);

                    $clarificationMessage = "Pasensya po, hindi ko po maintindihan ang inyong mensahe. 😅\n\n";
                    $clarificationMessage .= "Pwede po bang ulitin o i-clarify ang tanong ninyo? O kung gusto ninyong i-check ang inyong tanim, mag-send po ng larawan at sasagutin ko kaagad! 📷";

                    // Save user message
                    $userMessage = AiChatMessage::create([
                        'sessionId' => $session->id,
                        'role' => AiChatMessage::ROLE_USER,
                        'content' => $messageContent,
                        'delete_status' => 'active',
                    ]);

                    // Send clarification response
                    $this->sendSSE('response', [
                        'content' => $clarificationMessage,
                        'formattedTime' => now()->format('h:i A'),
                    ]);

                    // Save assistant response
                    AiChatMessage::create([
                        'sessionId' => $session->id,
                        'role' => AiChatMessage::ROLE_ASSISTANT,
                        'content' => $clarificationMessage,
                        'processingTime' => microtime(true) - $startTime,
                        'delete_status' => 'active',
                    ]);

                    $session->recordNewMessage();
                    $this->sendSSE('done', ['success' => true, 'isGibberish' => true]);
                    Log::debug('=== SSE STREAM COMPLETED (GIBBERISH) ===');
                    return;
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
                // FOLLOW-UP: Carry over images from previous message if needed
                // ========================================================
                // If this is a follow-up question with NO new images, check if the original
                // question had images and carry them over for context
                $imagesCarriedOver = false;
                if ($questionType === 'followup' && empty($imagePaths)) {
                    // Find the most recent user message with images in this session
                    $previousUserMessageWithImages = $session->messages()
                        ->where('role', AiChatMessage::ROLE_USER)
                        ->where('id', '!=', $userMessage->id) // Exclude current message
                        ->whereNotNull('images')
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($previousUserMessageWithImages && !empty($previousUserMessageWithImages->images)) {
                        $imagePaths = $previousUserMessageWithImages->images;
                        $imagesCarriedOver = true;
                        Log::debug('Follow-up: Carrying over images from previous message', [
                            'previousMessageId' => $previousUserMessageWithImages->id,
                            'imageCount' => count($imagePaths),
                            'imagePaths' => $imagePaths,
                        ]);
                    }
                }

                // ========================================================
                // IMAGE ANALYSIS: If images are uploaded, perform deep analysis
                // ========================================================
                $imageAnalysisResult = null;
                if (!empty($imagePaths)) {
                    Log::debug('Images detected, checking if re-analysis needed...', [
                        'imageCount' => count($imagePaths),
                        'carriedOver' => $imagesCarriedOver,
                    ]);

                    // For follow-ups (with or without new images), use AI to classify the intent
                    $shouldAnalyzeImages = true;
                    $isComparisonChange = false;
                    $isReferenceImageComparison = false;
                    $newComparisonTarget = null;
                    $followUpType = 'unknown';
                    $confirmedOriginalCrop = null;

                    // ========================================================
                    // EXTRACT ORIGINAL CROP VARIETY from first assistant message
                    // This provides context for follow-up classification
                    // ========================================================
                    $originalCropVariety = null;
                    $previousComparisonTargets = [];

                    $firstAssistantMsg = $session->messages()
                        ->where('role', 'assistant')
                        ->orderBy('created_at', 'asc')
                        ->first();

                    if ($firstAssistantMsg) {
                        // Extract user's original crop variety
                        if (preg_match('/(?:inyong|your)\s+([A-Za-z]+\s*\d+)/i', $firstAssistantMsg->content, $match)) {
                            $originalCropVariety = trim($match[1]);
                        }

                        // Extract any comparison targets mentioned in recent messages
                        $recentMessages = $session->messages()
                            ->whereIn('role', ['user', 'assistant'])
                            ->orderBy('created_at', 'desc')
                            ->limit(4)
                            ->get();

                        foreach ($recentMessages as $msg) {
                            if (preg_match_all('/\b(RC\s*\d+|R\s*\d+|SL-?\d+H?|Longping|NSIC\s*Rc\s*\d+)/i', $msg->content, $matches)) {
                                foreach ($matches[0] as $variety) {
                                    $normalized = strtoupper(preg_replace('/\s+/', '', $variety));
                                    if (!in_array($normalized, $previousComparisonTargets) &&
                                        $normalized !== strtoupper(preg_replace('/\s+/', '', $originalCropVariety ?? ''))) {
                                        $previousComparisonTargets[] = $normalized;
                                    }
                                }
                            }
                        }

                        Log::info('Extracted chat context for follow-up', [
                            'originalCropVariety' => $originalCropVariety,
                            'previousComparisonTargets' => $previousComparisonTargets,
                        ]);
                    }

                    // Check follow-up type for:
                    // 1. Carried-over images (decide if re-analysis needed)
                    // 2. NEW images in follow-up (check if it's a reference_image_comparison)
                    // 3. NEW images with existing chat history (could be new topic - needs classification)
                    $hasExistingChatHistory = $firstAssistantMsg !== null;
                    $hasNewImages = !$imagesCarriedOver && !empty($imagePaths);

                    // CRITICAL FIX: Classify when NEW images are uploaded AND there's existing chat history
                    // This ensures vague messages like "e ito ano satingin mo?" with new images
                    // are detected as new_topic_with_images and context is properly reset
                    $shouldClassifyFollowUp = $imagesCarriedOver ||
                        ($questionType === 'followup' && $hasNewImages) ||
                        ($hasExistingChatHistory && $hasNewImages); // NEW: Also classify when new images + existing chat

                    // Build chat context for follow-up classification
                    $chatContext = [
                        'originalCropVariety' => $originalCropVariety,
                        'previousComparisonTargets' => $previousComparisonTargets,
                        'hasNewImages' => $hasNewImages,
                    ];

                    // ========================================================
                    // SAFETY: When NEW images are uploaded, ALWAYS clear the
                    // growth stage context since the AI should determine the
                    // stage from the NEW images, not from previous chat history
                    // ========================================================
                    if ($hasNewImages) {
                        Log::info('=== NEW IMAGES UPLOADED: Clearing image-specific context ===', [
                            'messageContent' => substr($messageContent, 0, 100),
                            'questionType' => $questionType,
                            'hasExistingChatHistory' => $hasExistingChatHistory,
                        ]);
                        // Clear image-specific context that should be re-detected from new images
                        $processor->clearImageSpecificContext();
                    }

                    if ($shouldClassifyFollowUp) {
                        $followUpDecision = $processor->classifyFollowUp($messageContent, $chatContext);
                        $shouldAnalyzeImages = $followUpDecision['needsImageReanalysis'] ?? true;
                        $followUpType = $followUpDecision['followUpType'] ?? 'unknown';
                        $newComparisonTarget = $followUpDecision['newComparisonTarget'] ?? null;
                        $isComparisonChange = ($followUpType === 'comparison_change' && !empty($newComparisonTarget));
                        $isReferenceImageComparison = ($followUpType === 'reference_image_comparison' && !empty($newComparisonTarget));

                        // Get confirmed original crop variety from classifier (it echoes back what we sent)
                        $confirmedOriginalCrop = $followUpDecision['originalCropVariety'] ?? $originalCropVariety;

                        Log::info('AI Follow-up Classification', [
                            'message' => substr($messageContent, 0, 100),
                            'needsImageReanalysis' => $shouldAnalyzeImages,
                            'usesChatHistory' => $followUpDecision['usesChatHistory'] ?? false,
                            'followUpType' => $followUpType,
                            'newComparisonTarget' => $newComparisonTarget,
                            'originalCropVariety' => $confirmedOriginalCrop,
                            'isComparisonChange' => $isComparisonChange,
                            'isReferenceImageComparison' => $isReferenceImageComparison,
                            'imagesCarriedOver' => $imagesCarriedOver,
                            'reason' => $followUpDecision['reason'] ?? 'N/A',
                        ]);

                        // ========================================================
                        // NEW TOPIC WITH IMAGES: Vague message with new images
                        // Could be a completely different crop - reset context!
                        // ========================================================
                        if ($followUpType === 'new_topic_with_images') {
                            Log::warning('=== NEW TOPIC WITH IMAGES DETECTED ===', [
                                'message' => $messageContent,
                                'action' => 'Resetting ALL context - new images may be different crop',
                                'oldTopicContext' => $topicContext,
                                'oldOriginalCrop' => $originalCropVariety,
                            ]);

                            // Reset context to treat this as a fresh topic
                            $topicContext = null;
                            $originalCropVariety = null;
                            $confirmedOriginalCrop = null;

                            // Reset processor state - IMPORTANT: Clear all context!
                            $processor->setTopicContext(null);
                            $processor->clearExtractedContext(); // This clears chat history context

                            // Also mark that we should NOT use chat history for this message
                            $imagesCarriedOver = false; // Treat as fresh upload
                        }

                        // ========================================================
                        // UNRELATED TOPIC: User asking about something completely
                        // different from the current conversation - ask to create new chat
                        // ========================================================
                        if ($followUpType === 'unrelated_topic') {
                            Log::info('=== UNRELATED TOPIC DETECTED ===', [
                                'message' => $messageContent,
                                'reason' => $followUpDecision['reason'] ?? 'N/A',
                                'action' => 'Asking user to create new chat',
                            ]);

                            // Save user message first
                            $session->messages()->create([
                                'role' => 'user',
                                'content' => $messageContent,
                                'image_paths' => $imagePaths,
                            ]);

                            // Return message asking user to create new chat
                            $unrelatedMessage = "Mukhang ibang topic na ito mula sa pinag-uusapan natin. 🔄\n\n";
                            $unrelatedMessage .= "Para mas maayos ang usapan at hindi magkahalo ang mga impormasyon, mag-click ng **\"New Chat\"** at i-upload ang bagong larawan o tanong mo doon.\n\n";
                            $unrelatedMessage .= "Sa ganitong paraan, makakapag-focus tayo sa isang topic lang at mas accurate ang mga sagot ko! 😊";

                            // Save assistant response
                            $session->messages()->create([
                                'role' => 'assistant',
                                'content' => $unrelatedMessage,
                            ]);

                            return response()->json([
                                'success' => true,
                                'response' => $unrelatedMessage,
                                'sessionId' => $session->id,
                                'isUnrelatedTopic' => true,
                            ]);
                        }
                    }

                    // Detect crop_status_check type
                    $isCropStatusCheck = ($followUpType === 'crop_status_check');

                    if (!$shouldAnalyzeImages) {
                        Log::info('Skipping image re-analysis per AI decision', [
                            'message' => $messageContent,
                            'carriedOver' => true,
                            'isComparisonChange' => $isComparisonChange,
                            'isCropStatusCheck' => $isCropStatusCheck,
                        ]);

                        // ========================================================
                        // CROP STATUS CHECK: User asking about their original crop
                        // Use the FIRST/ORIGINAL analysis, not comparison responses
                        // ========================================================
                        if ($isCropStatusCheck) {
                            // Get the FIRST assistant response (original crop analysis)
                            $originalAnalysis = $session->messages()
                                ->where('role', 'assistant')
                                ->orderBy('created_at', 'asc')
                                ->first();

                            if ($originalAnalysis) {
                                // Extract user's crop variety from the first analysis
                                preg_match('/(?:inyong|your)\s+([A-Za-z]+\s*\d*)/i', $originalAnalysis->content, $varietyMatch);
                                $userCropVariety = $varietyMatch[1] ?? 'crop';

                                $imageAnalysisResult = [
                                    'success' => true,
                                    'analysis' => "CROP STATUS CHECK - USE ORIGINAL ANALYSIS\n\n" .
                                        "The user is asking about their ORIGINAL crop (NOT the comparison varieties).\n" .
                                        "User's crop variety: {$userCropVariety}\n\n" .
                                        "⚠️ CRITICAL: The user's crop is {$userCropVariety}. If they previously compared to other varieties (RC222, RC160, etc.),\n" .
                                        "those were just COMPARISON targets. Their ACTUAL crop is {$userCropVariety}!\n\n" .
                                        "Here is the ORIGINAL analysis of the user's crop:\n" .
                                        "---ORIGINAL ANALYSIS---\n" .
                                        $originalAnalysis->content .
                                        "\n---END ORIGINAL ANALYSIS---\n\n" .
                                        "QUESTION: \"{$messageContent}\"\n\n" .
                                        "INSTRUCTIONS:\n" .
                                        "1. Answer based on the ORIGINAL analysis above\n" .
                                        "2. The user's crop is {$userCropVariety} - NOT RC222, RC160, or other comparison varieties!\n" .
                                        "3. For yield/harvest questions: Calculate based on {$userCropVariety} data from original analysis\n" .
                                        "4. If the original analysis said crop was healthy/on track, confirm that\n" .
                                        "5. Do NOT confuse comparison varieties with the user's actual crop\n" .
                                        "6. Do NOT invent new problems that weren't in the original analysis\n" .
                                        "7. Respond in Tagalog. Always refer to 'inyong {$userCropVariety}' not other varieties",
                                    'provider' => 'crop_status_context',
                                ];

                                Log::info('Built crop status check context', [
                                    'userCropVariety' => $userCropVariety,
                                    'originalAnalysisLength' => strlen($originalAnalysis->content),
                                ]);
                            }
                        }
                        // For comparison changes, extract previous analysis from chat history
                        // IMPORTANT: Do NOT do direct comparison - show variety specs instead
                        elseif ($isComparisonChange && $newComparisonTarget) {
                            // Get the FIRST assistant message (original crop analysis)
                            $originalAnalysis = $session->messages()
                                ->where('role', 'assistant')
                                ->orderBy('created_at', 'asc')
                                ->first();

                            if ($originalAnalysis) {
                                // Extract user's crop variety from the first analysis
                                preg_match('/(?:inyong|your)\s+([A-Za-z]+\s*\d*)/i', $originalAnalysis->content, $varietyMatch);
                                $userCropVariety = $varietyMatch[1] ?? 'crop';

                                // Check if there are multiple comparison targets
                                $targets = preg_split('/[,]|\s+at\s+|\s+and\s+/i', $newComparisonTarget);
                                $targets = array_map('trim', $targets);
                                $targets = array_filter($targets);
                                $targetList = implode(' at ', $targets);

                                // Build context that shows VARIETY SPECS instead of direct comparison
                                $analysisContent = "VARIETY SPECS COMPARISON (NOT DIRECT CROP COMPARISON)\n\n" .
                                    "The user wants to know about: {$targetList}\n\n" .
                                    "User's ORIGINAL crop analysis:\n" .
                                    "---ORIGINAL ANALYSIS---\n" .
                                    $originalAnalysis->content .
                                    "\n---END ORIGINAL ANALYSIS---\n\n" .
                                    "IMPORTANT INSTRUCTION - DO NOT DO DIRECT COMPARISON!\n\n" .
                                    "Instead, follow this response structure:\n\n" .
                                    "1. PAUNAWA (Disclaimer):\n" .
                                    "   Explain that direct comparison between different varieties planted in different locations/conditions " .
                                    "   may not be accurate and can set wrong expectations. Many factors affect crop performance:\n" .
                                    "   - Lokasyon at uri ng lupa\n" .
                                    "   - Klima at panahon\n" .
                                    "   - Pamamaraan ng pagsasaka\n" .
                                    "   - Uri ng pataba at patubig\n" .
                                    "   The best comparison is with the SAME variety planted in the SAME location.\n\n" .
                                    "2. PANGKALAHATANG SPECS NG {$targetList}:\n" .
                                    "   Search for and show the OFFICIAL/TYPICAL specs of the requested variety:\n" .
                                    "   | Katangian | {$targetList} (Typical Specs) |\n" .
                                    "   |-----------|------------------------------|\n" .
                                    "   | Maturity | X-Y days |\n" .
                                    "   | Plant height | X-Y cm |\n" .
                                    "   | Panicles/hill | X-Y |\n" .
                                    "   | Panicle length | X-Y cm |\n" .
                                    "   | Yield potential | X-Y MT/ha (XX-YY cavans/ha) |\n" .
                                    "   | Grain type | ... |\n" .
                                    "   | Resistance | ... |\n\n" .
                                    "3. INYONG KASALUKUYANG OBSERBASYON ({$userCropVariety}):\n" .
                                    "   Show the user's crop observations from original analysis:\n" .
                                    "   | Katangian | Inyong {$userCropVariety} |\n" .
                                    "   |-----------|------------------------|\n" .
                                    "   | [data from original analysis] |\n\n" .
                                    "4. PAALALA:\n" .
                                    "   Remind that the specs table shows TYPICAL/IDEAL conditions.\n" .
                                    "   Actual performance depends on local factors.\n" .
                                    "   For accurate comparison, compare with same variety in same farm.\n\n" .
                                    "Do NOT use markdown headers (###) or bold (**).\n" .
                                    "Use proper markdown table with | pipes.\n" .
                                    "Respond in Tagalog. Keep user's crop data from original analysis.";

                                $imageAnalysisResult = [
                                    'success' => true,
                                    'analysis' => $analysisContent,
                                    'provider' => 'variety_specs_comparison',
                                ];

                                Log::info('Built variety specs comparison context', [
                                    'userCropVariety' => $userCropVariety,
                                    'targetVarieties' => $targetList,
                                    'originalAnalysisLength' => strlen($originalAnalysis->content),
                                ]);
                            }
                        } else {
                            // FALLBACK: For other follow-up types (advice, clarification, etc.)
                            // Still inject context about user's original crop to prevent confusion
                            $originalAnalysis = $session->messages()
                                ->where('role', 'assistant')
                                ->orderBy('created_at', 'asc')
                                ->first();

                            if ($originalAnalysis) {
                                preg_match('/(?:inyong|your)\s+([A-Za-z]+\s*\d*)/i', $originalAnalysis->content, $varietyMatch);
                                $userCropVariety = $varietyMatch[1] ?? 'crop';

                                $imageAnalysisResult = [
                                    'success' => true,
                                    'analysis' => "FOLLOW-UP CONTEXT - PRESERVE USER'S CROP IDENTITY\n\n" .
                                        "User's ORIGINAL crop variety: {$userCropVariety}\n" .
                                        "⚠️ IMPORTANT: If the user previously compared to other varieties (RC222, RC160, Longping, etc.),\n" .
                                        "those were just REFERENCE/COMPARISON targets. The user's ACTUAL crop is {$userCropVariety}!\n\n" .
                                        "Original analysis summary:\n" .
                                        "---ORIGINAL ANALYSIS---\n" .
                                        $originalAnalysis->content .
                                        "\n---END ORIGINAL ANALYSIS---\n\n" .
                                        "QUESTION: \"{$messageContent}\"\n\n" .
                                        "INSTRUCTIONS:\n" .
                                        "1. Answer the question in context of the user's {$userCropVariety} crop\n" .
                                        "2. Do NOT confuse comparison varieties with the user's actual crop\n" .
                                        "3. Respond in Tagalog",
                                    'provider' => 'followup_context',
                                ];

                                Log::info('Built fallback follow-up context', [
                                    'userCropVariety' => $userCropVariety,
                                    'followUpType' => $followUpType ?? 'unknown',
                                ]);
                            }
                        }
                        // The imageAnalysisResult is now set for all follow-up scenarios
                    } else {
                        // Send a "thinking" event for image analysis
                        // Differentiate between new uploads and carried-over images
                        $thinkingMessage = $imagesCarriedOver
                            ? 'Sinusuri ko po ulit ang ' . count($imagePaths) . ' na larawan mula sa inyong nakaraang tanong...'
                            : 'Sinusuri ko po ang ' . count($imagePaths) . ' na larawan na in-upload niyo...';

                        $this->sendSSE('thinking', [
                            'content' => $thinkingMessage,
                            'formattedTime' => now()->format('h:i A'),
                        ]);

                        // ========================================================
                        // SPECIAL CASE: Reference Image Comparison
                        // User uploaded a NEW image as a REFERENCE to compare their previous crop against
                        // ========================================================
                        if ($isReferenceImageComparison && $newComparisonTarget) {
                            // Use confirmed original crop from classifier or context extraction
                            $userCropVariety = $confirmedOriginalCrop ?? $originalCropVariety ?? 'crop';

                            Log::info('Reference Image Comparison detected', [
                                'newComparisonTarget' => $newComparisonTarget,
                                'userCropVariety' => $userCropVariety,
                                'imageCount' => count($imagePaths),
                            ]);

                            // Get the FIRST/ORIGINAL crop analysis from this session
                            // (the user's crop data, not the comparison follow-ups)
                            $originalAnalysis = $session->messages()
                                ->where('role', 'assistant')
                                ->orderBy('created_at', 'asc')
                                ->first();

                            if ($originalAnalysis) {
                                $originalContent = $originalAnalysis->content;

                                // Analyze the NEW image as a REFERENCE
                                $referenceAnalysis = $processor->analyzeUploadedImages(
                                    $imagePaths,
                                    "Describe this {$newComparisonTarget} rice variety. What are the typical characteristics? Panicles per hill, panicle length, leaf health, expected yield.",
                                    null
                                );

                                $referenceData = $referenceAnalysis['success'] ? $referenceAnalysis['analysis'] : 'Reference image analyzed';

                                // Check if variety is a "brand family" without specific sub-type
                                // Examples: "Longping" could be Longping 206, 638, 838, etc.
                                // "SL" could be SL-8H, SL-18H, etc.
                                $isGenericVarietyName = preg_match('/^(Longping|SL|Pioneer|Dekalb|NK|Mestiso|Bigante|Sterling|Sahod Ulan)$/i', $newComparisonTarget);

                                // Build instructions based on whether variety is specific or generic
                                $specsInstructions = "";
                                if ($isGenericVarietyName) {
                                    $specsInstructions = "3. PAGHAHAMBING NG {$newComparisonTarget} (LARAWAN) - OBSERVED ONLY TABLE:\n" .
                                        "   IMPORTANT: '{$newComparisonTarget}' is a brand/family name with MANY sub-varieties.\n" .
                                        "   Different sub-varieties have DIFFERENT specs (maturity, yield, resistance).\n" .
                                        "   Since specific variety type is UNKNOWN, show ONLY what was OBSERVED:\n\n" .
                                        "   | Katangian | Nakita sa Larawan |\n" .
                                        "   |-----------|-------------------|\n" .
                                        "   | Kulay ng Dahon | [observed color] |\n" .
                                        "   | Haba ng Panicle | [observed] cm |\n" .
                                        "   | Grain Filling | [observed] % |\n" .
                                        "   | Plant Health | [observed condition] |\n" .
                                        "   | Uhay Status | [observed] % nakalabas |\n\n" .
                                        "   DO NOT include Maturity, Yield potential, Uri, or Resistance since\n" .
                                        "   the specific {$newComparisonTarget} sub-variety is unknown.\n" .
                                        "   State: 'Hindi maibigay ang tiyak na specs dahil hindi tinukoy kung anong\n" .
                                        "   specific {$newComparisonTarget} variety (hal. {$newComparisonTarget} 206, 638, atbp.)'\n\n";
                                } else {
                                    $specsInstructions = "3. PAGHAHAMBING NG {$newComparisonTarget} (LARAWAN VS TYPICAL SPECS) - COMBINED TABLE:\n" .
                                        "   Create ONE COMBINED TABLE showing BOTH:\n" .
                                        "   - What was OBSERVED in the uploaded {$newComparisonTarget} image\n" .
                                        "   - The TYPICAL/STANDARD specs for that variety (ONLY if you are 100% certain)\n" .
                                        "   For general specs where observation is N/A, use '-' in Nakita column.\n\n" .
                                        "   | Katangian | Nakita sa Larawan | Pamantayan ({$newComparisonTarget}) | Status |\n" .
                                        "   |-----------|-------------------|-------------------------------------|--------|\n" .
                                        "   | Kulay ng Dahon | [observed] | [typical/ideal] | [Katumbas/Mababa/Mataas] |\n" .
                                        "   | Haba ng Panicle | [observed] cm | [typical] cm | [status] |\n" .
                                        "   | Grain Filling | [observed] % | [expected at this stage] | [status] |\n" .
                                        "   | Plant Health | [observed] | [ideal] | [status] |\n" .
                                        "   | Maturity | - | X-Y days (ONLY if 100% certain) | - |\n" .
                                        "   | Yield potential | - | X-Y MT/ha (ONLY if 100% certain) | - |\n" .
                                        "   | Uri | - | Hybrid/Inbred | - |\n" .
                                        "   | Resistance | - | [info ONLY if 100% certain] | - |\n\n" .
                                        "   NOTE: If NOT 100% certain about specs, write 'Hindi tiyak - depende sa specific variety'\n\n";
                                }

                                // Build variety specs context (NOT direct comparison)
                                $imageAnalysisResult = [
                                    'success' => true,
                                    'analysis' => "REFERENCE IMAGE - VARIETY SPECS (NOT DIRECT COMPARISON)\n\n" .
                                        "User's ORIGINAL crop analysis:\n" .
                                        "---ORIGINAL ANALYSIS---\n" .
                                        $originalContent .
                                        "\n---END ORIGINAL ANALYSIS---\n\n" .
                                        "User uploaded a reference image of {$newComparisonTarget}.\n" .
                                        "Reference image analysis:\n" .
                                        $referenceData . "\n\n" .
                                        "IMPORTANT: DO NOT DO DIRECT COMPARISON between the two varieties!\n\n" .
                                        "Follow this response structure:\n\n" .
                                        "1. PAUNAWA:\n" .
                                        "   Salamat sa pag-upload ng reference image ng {$newComparisonTarget}.\n" .
                                        "   Explain that direct comparison between different varieties in different conditions\n" .
                                        "   may not be accurate. Factors affecting performance:\n" .
                                        "   - Lokasyon, lupa, klima\n" .
                                        "   - Pamamaraan ng pagsasaka\n" .
                                        "   Best comparison is same variety, same location.\n\n" .
                                        "2. NAKITA SA REFERENCE IMAGE ({$newComparisonTarget}):\n" .
                                        "   Briefly describe what you see in the uploaded reference image (leaf color, panicle status, etc.).\n\n" .
                                        $specsInstructions .
                                        "4. INYONG {$userCropVariety} (ORIGINAL ANALYSIS):\n" .
                                        "   | Katangian | Inyong {$userCropVariety} |\n" .
                                        "   |-----------|------------------------|\n" .
                                        "   | [from original analysis - panicles/hill, panicle length, leaf health, etc.] |\n\n" .
                                        "5. PAALALA:\n" .
                                        "   Specs are typical/ideal. Actual depends on local factors.\n" .
                                        "   If specific variety unknown, only OBSERVED characteristics should be stated.\n\n" .
                                        "Do NOT use ### headers. Use | pipe tables.\n" .
                                        "Respond in Tagalog.",
                                    'provider' => 'reference_variety_specs',
                                ];

                                Log::info('Built reference image comparison context', [
                                    'userCropVariety' => $userCropVariety,
                                    'referenceTarget' => $newComparisonTarget,
                                    'originalAnalysisLength' => strlen($originalContent),
                                    'referenceDataLength' => strlen($referenceData),
                                ]);
                            }
                        } else {
                            // Standard image analysis flow
                            // Use AI to classify the inquiry type and extract details
                            // This is more robust than regex-based detection
                            $inquiryDetails = $processor->classifyInquiry($messageContent);

                            // Log classification result with all details
                            Log::info('=== AI INQUIRY CLASSIFICATION ===', [
                                'userMessage' => $messageContent,
                                'inquiryType' => $inquiryDetails['inquiryType'] ?? 'unknown',
                                'isComparison' => $inquiryDetails['isComparison'] ?? false,
                                'userCropVariety' => $inquiryDetails['userCropVariety'] ?? null,
                                'comparisonTarget' => $inquiryDetails['comparisonTarget'] ?? null,
                                'comparisonType' => $inquiryDetails['comparisonType'] ?? null,
                                'dat' => $inquiryDetails['dat'] ?? null,
                                'growthStage' => $inquiryDetails['growthStage'] ?? null,
                                'thinking' => $inquiryDetails['thinking'] ?? null,
                                'confidence' => $inquiryDetails['confidence'] ?? 0,
                            ]);

                            // Use GPT-4 Vision for comparison scenarios (more accurate counting/assessment)
                            $isComparisonScenario = $inquiryDetails['isComparison'] ?? false;

                            if ($isComparisonScenario) {
                                $imageAnalysisResult = $processor->analyzeImagesWithGPT(
                                    $imagePaths,
                                $messageContent,
                                $topicContext,
                                $inquiryDetails  // Pass the AI-extracted details
                            );
                            Log::info('Used GPT-4 Vision for comparison scenario', [
                                'userCrop' => $inquiryDetails['userCropVariety'] ?? 'N/A',
                                'target' => $inquiryDetails['comparisonTarget'] ?? 'N/A',
                                'comparisonType' => $inquiryDetails['comparisonType'] ?? 'N/A',
                                'isSameVariety' => ($inquiryDetails['comparisonType'] ?? '') === 'same_variety_standard',
                            ]);
                        } else {
                            // Use Gemini Vision for general image analysis
                            $imageAnalysisResult = $processor->analyzeUploadedImages(
                                $imagePaths,
                                $messageContent,
                                $topicContext
                            );
                            Log::debug('Used Gemini Vision for general analysis');
                        }

                        Log::debug('Image analysis result', [
                            'success' => $imageAnalysisResult['success'] ?? false,
                            'analysisLength' => strlen($imageAnalysisResult['analysis'] ?? ''),
                            'provider' => $imageAnalysisResult['provider'] ?? 'gemini',
                        ]);
                        } // End of standard image analysis (not reference_image_comparison)
                    } // End of else (shouldAnalyzeImages = true)
                }

                // ================================================================
                // CRITICAL: CROP TYPE MISMATCH DETECTION
                // ================================================================
                // After image analysis, check if the detected crop type differs from
                // the chat history. If user uploads RICE images but chat history is
                // about CORN, we should treat this as a NEW TOPIC, not a follow-up.
                // ================================================================
                if ($imageAnalysisResult && $imageAnalysisResult['success'] && !empty($imageAnalysisResult['analysis'])) {
                    $imageAnalysis = $imageAnalysisResult['analysis'];

                    // Detect crop type from image analysis
                    $detectedCropType = null;
                    if (preg_match('/\b(palay|rice|bigas)\b/i', $imageAnalysis)) {
                        $detectedCropType = 'rice';
                    } elseif (preg_match('/\b(mais|corn|maize)\b/i', $imageAnalysis)) {
                        $detectedCropType = 'corn';
                    }

                    // Detect crop type from chat history/topic context
                    $historyCropType = null;
                    $contextToCheck = ($topicContext ?? '') . ' ' . ($originalCropVariety ?? '');
                    if (preg_match('/\b(palay|rice|bigas|jackpot|rc\s*\d+|nsic)/i', $contextToCheck)) {
                        $historyCropType = 'rice';
                    } elseif (preg_match('/\b(mais|corn|maize|nk\s*\d+|pioneer|dekalb)/i', $contextToCheck)) {
                        $historyCropType = 'corn';
                    }

                    // If we detected a crop in the image AND it differs from history, reset context
                    if ($detectedCropType && $historyCropType && $detectedCropType !== $historyCropType) {
                        Log::warning('=== CROP TYPE MISMATCH DETECTED ===', [
                            'detectedInImage' => $detectedCropType,
                            'inChatHistory' => $historyCropType,
                            'action' => 'Resetting topicContext - treating as NEW conversation about ' . $detectedCropType,
                            'oldTopicContext' => $topicContext,
                        ]);

                        // Reset the topic context so this is NOT treated as a follow-up
                        $topicContext = null;

                        // Also clear the originalCropVariety to prevent confusion
                        $originalCropVariety = null;
                        $confirmedOriginalCrop = null;

                        // Update the processor's internal state
                        $processor->setTopicContext(null);
                    } elseif ($detectedCropType) {
                        Log::info('Crop type detection', [
                            'detectedInImage' => $detectedCropType,
                            'inChatHistory' => $historyCropType ?? 'unknown',
                            'match' => ($detectedCropType === $historyCropType) ? 'YES' : 'N/A (no history crop)',
                        ]);
                    }
                }

                // Process main flow (includes chat history and context)
                // Pass precomputed image analysis to avoid re-analyzing (which may return truncated results)
                $precomputedImageAnalysis = ($imageAnalysisResult && $imageAnalysisResult['success'])
                    ? ($imageAnalysisResult['analysis'] ?? null)
                    : null;

                Log::info('Passing precomputed analysis to processMainFlow', [
                    'hasImageAnalysisResult' => !empty($imageAnalysisResult),
                    'imageAnalysisSuccess' => $imageAnalysisResult['success'] ?? false,
                    'analysisLength' => strlen($precomputedImageAnalysis ?? ''),
                    'willPassAnalysis' => !empty($precomputedImageAnalysis),
                ]);

                // Pass user message ID to exclude it from chat history (prevents context extraction from current message)
                $result = $processor->processMainFlow($session, $messageContent, $imagePaths, $topicContext, $precomputedImageAnalysis, $userMessage->id);
                Log::debug('Main flow completed', [
                    'responseLength' => strlen($result['response'] ?? ''),
                    'hasMetadata' => !empty($result['metadata']),
                ]);

                // If we have image analysis, use it to enhance the response
                // NOTE: Do NOT show verbose image analysis to user - use it internally
                if ($imageAnalysisResult && $imageAnalysisResult['success'] && !empty($imageAnalysisResult['analysis'])) {
                    $mainResponse = $result['response'] ?? '';
                    $imageAnalysis = $imageAnalysisResult['analysis'];
                    $imageProvider = $imageAnalysisResult['provider'] ?? 'unknown';

                    // DEBUG: Log what we're working with
                    Log::info('=== IMAGE ANALYSIS ENHANCEMENT CHECK ===', [
                        'mainResponseLength' => strlen($mainResponse),
                        'mainResponsePreview' => substr($mainResponse, 0, 300),
                        'imageAnalysisProvider' => $imageProvider,
                    ]);

                    // SKIP generic response check for certain providers
                    // These are NOT actual image analysis - they're pre-built context instructions
                    // The AI already generated a proper response following our instructions
                    $skipGenericCheck = in_array($imageProvider, [
                        'variety_specs_comparison',
                        'crop_status_context',
                        'reference_variety_specs',
                    ]);

                    if ($skipGenericCheck) {
                        Log::info('=== SKIPPING GENERIC CHECK - Provider is context-based ===', [
                            'provider' => $imageProvider,
                            'responseLength' => strlen($mainResponse),
                        ]);
                        $result['response'] = $mainResponse;

                        // Add analysis metadata (for debugging in flow log, not shown to user)
                        $result['metadata']['imageAnalysis'] = [
                            'success' => true,
                            'imageCount' => $imageAnalysisResult['imageCount'] ?? count($imagePaths),
                            'summary' => 'Context-based response (no image analysis synthesis)',
                            'fullAnalysis' => $imageAnalysis,
                            'provider' => $imageProvider,
                        ];
                        // CRITICAL: Store detected crop type for follow-up context
                        if (!empty($detectedCropType)) {
                            $result['metadata']['detectedCropType'] = $detectedCropType;
                        }
                    } else {

                    // Check if main response is minimal or generic (AI couldn't answer without image context)
                    // NOTE: Only trigger if AI is ASKING for images, not just MENTIONING images
                    // Pattern matches: "magpadala ng larawan", "send me a photo", "upload image", "please send image"
                    // Does NOT match: "Batay sa mga larawan" (AI talking about uploaded images)
                    // Does NOT match: "I need to analyze the image" (AI using existing image)
                    $lengthCheck = strlen($mainResponse) < 100;

                    // Pattern 1: AI asking user to SEND/UPLOAD images (request pattern)
                    // These must be REQUEST phrases, not mentions of existing images
                    $pattern1Match = preg_match('/\b(magpadala|mag-send|mag-upload|paki-?send|please\s+send|pls\s+send|send\s+me|upload)\s+(ng\s+)?(larawan|image|picture|photo|litrato)\b/i', $mainResponse);

                    // Pattern 2: AI saying it CANNOT answer without images (inability pattern)
                    // Must be explicit "cannot answer" or "need image to answer" phrases
                    // EXCLUDE: "I need to analyze the image" (AI using existing image)
                    // EXCLUDE: "crop needs" + later mention of image (unrelated usage)
                    $pattern2Match = preg_match('/\b(hindi\s+(po\s+)?ako\s+maka(ka)?sagot|cannot\s+answer|can\'t\s+answer|kailangan\s+(ko\s+)?(ng\s+)?larawan|walang\s+larawan)\b/i', $mainResponse);

                    // Pattern 3: Short responses asking for image upload (last resort check)
                    $pattern3Match = strlen($mainResponse) < 300 && preg_match('/\b(upload|send|magpadala).*(larawan|image|photo)\b/i', $mainResponse);

                    $isGenericResponse = $lengthCheck || $pattern1Match || $pattern2Match || $pattern3Match;

                    // DEBUG: Log the condition evaluation
                    Log::info('=== GENERIC RESPONSE CHECK ===', [
                        'lengthCheck' => $lengthCheck,
                        'pattern1Match' => (bool)$pattern1Match,
                        'pattern2Match' => (bool)$pattern2Match,
                        'pattern3Match' => (bool)$pattern3Match,
                        'isGenericResponse' => $isGenericResponse,
                    ]);

                    if ($isGenericResponse) {
                        // Main response is generic - synthesize a direct answer from image analysis
                        // Use OpenAI to create a concise, direct response
                        $reason = $lengthCheck ? 'length < 100' : ($pattern1Match ? 'pattern1 (upload request)' : ($pattern2Match ? 'pattern2 (cannot answer)' : 'pattern3 (short + upload mention)'));
                        Log::warning('=== SYNTHESIZING RESPONSE (GENERIC DETECTED) ===', [
                            'reason' => $reason,
                            'responseLength' => strlen($mainResponse),
                        ]);
                        $result['response'] = $processor->synthesizeImageResponse($imageAnalysis, $messageContent);
                    } else {
                        // Main response already has good content
                        // Don't prepend verbose image analysis - the main flow already used image context
                        // Just use the main response as-is (it was already informed by image analysis in Step 1-4)
                        Log::info('=== KEEPING ORIGINAL RESPONSE (NOT GENERIC) ===');
                        $result['response'] = $mainResponse;
                    }

                    // Add analysis metadata (for debugging in flow log, not shown to user)
                    $result['metadata']['imageAnalysis'] = [
                        'success' => true,
                        'imageCount' => $imageAnalysisResult['imageCount'] ?? count($imagePaths),
                        'summary' => $imageAnalysisResult['summary'] ?? '',
                        // Store full analysis for debugging (shown in flow log only)
                        'fullAnalysis' => $imageAnalysis,
                    ];
                    // CRITICAL: Store detected crop type for follow-up context
                    if (!empty($detectedCropType)) {
                        $result['metadata']['detectedCropType'] = $detectedCropType;
                    }

                    Log::debug('Image analysis used internally (not shown to user)', [
                        'mainResponseLength' => strlen($mainResponse),
                        'imageAnalysisLength' => strlen($imageAnalysis),
                        'finalResponseLength' => strlen($result['response']),
                        'wasGeneric' => $isGenericResponse,
                    ]);
                    } // Close else block (non-context-based providers)
                } // Close if ($imageAnalysisResult ...)

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

                // Check for image request OR visual reference need (what does X look like)
                $needsImages = $processor->isImageRequest($messageContent) || $processor->needsVisualReference();

                // SKIP IMAGE GENERATION if AI is asking for clarification (no actual content to illustrate)
                $aiResponse = $result['response'] ?? '';
                $isAskingForClarification = preg_match('/\b(anong tanim|ilang araw|paki-?send ng larawan|mag-?send ng larawan|kailangan ko ng.*detalye|gusto.*i-?check|pwede.*mag-?send|ano po ang gusto)\b/i', $aiResponse)
                    && !preg_match('/\b(nakikita ko|batay sa larawan|ayon sa larawan|sa inyong larawan)\b/i', $aiResponse);

                if ($isAskingForClarification) {
                    Log::debug('AI is asking for clarification, skipping image generation');
                    $needsImages = false;
                }

                if (!$userUploadedImages && $needsImages) {
                    Log::debug('Image request detected, generating educational images...', [
                        'isImageRequest' => $processor->isImageRequest($messageContent),
                        'needsVisualReference' => $processor->needsVisualReference(),
                    ]);

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

                Log::debug('Assistant message saved successfully', ['messageId' => $assistantMessage->id]);

                // ================================================================
                // GENERATE AI TITLE DURING FIRST MESSAGE PROCESSING
                // ================================================================
                // Instead of checking on page load, generate the title NOW
                // This saves API calls and provides instant title updates
                // ================================================================
                $generatedTitle = null;

                // Refresh session from database to get current state
                $session->refresh();

                Log::debug('Title generation check', [
                    'sessionId' => $session->id,
                    'messageCount' => $session->messageCount,
                    'isTitleGenerated' => $session->isTitleGenerated(),
                    'currentSessionName' => $session->sessionName,
                ]);

                // Generate title if this is the first exchange (messageCount = 2 means 1 user + 1 assistant)
                // and title hasn't been AI-generated yet
                if ($session->messageCount <= 2 && !$session->isTitleGenerated()) {
                    Log::info('First message - generating AI title inline', ['sessionId' => $session->id]);

                    try {
                        // Build context from the first exchange
                        $titleContext = "User: " . Str::limit($messageContent, 200) . "\n";
                        $titleContext .= "AI: " . Str::limit($result['response'], 200);

                        // Generate title using AI
                        $generatedTitle = $processor->generateChatTitle($titleContext);

                        if (!empty($generatedTitle)) {
                            // Clean up the title
                            $generatedTitle = trim($generatedTitle);
                            $generatedTitle = str_replace(['"', "'", "\n", "\r"], '', $generatedTitle);
                            $generatedTitle = Str::limit($generatedTitle, 60);

                            // Update session name
                            $session->update(['sessionName' => $generatedTitle]);

                            // Mark title as AI-generated to prevent regeneration
                            $session->markTitleGenerated();

                            Log::info('AI title generated during first message', [
                                'sessionId' => $session->id,
                                'title' => $generatedTitle,
                            ]);
                        }
                    } catch (\Exception $titleException) {
                        Log::warning('Failed to generate AI title during message processing', [
                            'error' => $titleException->getMessage(),
                            'sessionId' => $session->id,
                        ]);
                        // Non-critical - continue without title
                    }
                }

                // Check if client is still connected
                if (connection_aborted()) {
                    Log::warning('Client disconnected before response could be sent - message saved for fallback polling', [
                        'messageId' => $assistantMessage->id,
                        'sessionId' => $session->id,
                    ]);
                    return; // Exit cleanly - message is saved, fallback polling will find it
                }

                // Send final response with flow log and images
                Log::debug('Sending response event...');

                // Use the generated title if available, otherwise use the current display name
                $responseSessionName = !empty($generatedTitle) ? $generatedTitle : $session->display_name;

                $this->sendSSE('response', [
                    'id' => $assistantMessage->id,
                    'role' => $assistantMessage->role,
                    'content' => $assistantMessage->content,
                    'formattedTime' => $assistantMessage->formatted_time,
                    'processingTime' => $assistantMessage->processing_time_formatted,
                    'sessionName' => $responseSessionName,
                    'generatedTitle' => $generatedTitle, // AI-generated title (null if not generated)
                    'titleGenerated' => !empty($generatedTitle), // Flag to mark session as titled
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
            'Content-Type' => 'text/event-stream; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate, private',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
            'Content-Encoding' => 'none', // Prevent compression that causes buffering
        ]);
    }

    /**
     * Helper to send Server-Sent Event.
     * Includes aggressive flushing for cross-network/XAMPP compatibility.
     */
    public function sendSSE(string $event, array $data): void
    {
        // Format the SSE message
        $output = "event: {$event}\n";
        $output .= "data: " . json_encode($data) . "\n\n";

        // Echo the output
        echo $output;

        // AGGRESSIVE FLUSHING for Windows/XAMPP/cross-network compatibility
        // This ensures the response is sent immediately over the network

        // 1. Flush all PHP output buffers
        $levels = ob_get_level();
        for ($i = 0; $i < $levels; $i++) {
            @ob_end_flush();
        }

        // 2. Force PHP to flush its internal buffers
        @ob_flush();

        // 3. Force the web server to send the data
        @flush();

        // 4. Additional flush call for Apache mod_php
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }

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

    /**
     * Clean up empty chat sessions (sessions with no messages).
     * This removes abandoned chats that were created but never used.
     *
     * @param int $userId
     * @return int Number of sessions cleaned up
     */
    private function cleanupEmptySessions($userId)
    {
        try {
            // Find sessions with no active messages
            // The messages() relationship already filters for delete_status = 'active'
            $emptySessions = AiChatSession::where('usersId', $userId)
                ->where('delete_status', 'active')
                ->whereDoesntHave('messages')
                ->get();

            $count = $emptySessions->count();

            if ($count > 0) {
                // Soft delete the empty sessions
                foreach ($emptySessions as $session) {
                    $session->update(['delete_status' => 'deleted']);
                }

                Log::info('Cleaned up empty chat sessions', [
                    'userId' => $userId,
                    'count' => $count,
                ]);
            }

            return $count;
        } catch (\Exception $e) {
            Log::error('Failed to cleanup empty sessions: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Check if a message is gibberish/unintelligible.
     * Detects random character sequences that don't form meaningful words.
     *
     * @param string $message
     * @return bool
     */
    private function isGibberishMessage(string $message): bool
    {
        // Clean the message
        $cleaned = trim(strtolower($message));

        // If empty or very short, not gibberish (could be punctuation, emoji, etc.)
        if (strlen($cleaned) < 5) {
            return false;
        }

        // Remove common punctuation and spaces for analysis
        $textOnly = preg_replace('/[^a-z0-9\s]/i', '', $cleaned);
        $textOnly = preg_replace('/\s+/', ' ', trim($textOnly));

        // If nothing left after cleaning, not gibberish
        if (empty($textOnly)) {
            return false;
        }

        // Split into words
        $words = explode(' ', $textOnly);

        // Common Filipino and English words/phrases used in farming context
        $validWords = [
            // Filipino common words
            'po', 'ko', 'mo', 'na', 'ng', 'ang', 'sa', 'ay', 'at', 'ito', 'yan', 'yung', 'kung',
            'ano', 'paano', 'bakit', 'saan', 'kailan', 'sino', 'ilan', 'magkano', 'pwede', 'puwede',
            'oo', 'hindi', 'opo', 'wala', 'meron', 'may', 'ba', 'pa', 'lang', 'din', 'rin', 'naman',
            'salamat', 'sige', 'okay', 'ok', 'yes', 'no', 'thank', 'thanks', 'hello', 'hi', 'hey',
            // Farming terms
            'tanim', 'palay', 'mais', 'corn', 'rice', 'gulay', 'halaman', 'pananim', 'bukid',
            'dahon', 'bunga', 'ugat', 'uhay', 'butil', 'ani', 'anihan', 'harvest',
            'tubig', 'pataba', 'fertilizer', 'pesticide', 'spray', 'gamot', 'abono',
            'dap', 'dat', 'araw', 'days', 'day', 'linggo', 'week', 'buwan', 'month',
            'problema', 'sakit', 'dilaw', 'yellow', 'berde', 'green', 'puti', 'itim',
            'check', 'tingnan', 'tignan', 'ayos', 'maayos', 'malusog', 'healthy',
            'larawan', 'picture', 'photo', 'image', 'upload', 'send', 'ipadala',
            // Question words
            'kumusta', 'kamusta', 'anong', 'ilang', 'magandang', 'umaga', 'hapon', 'gabi',
            // Numbers as words
            'isa', 'dalawa', 'tatlo', 'apat', 'lima', 'anim', 'pito', 'walo', 'siyam', 'sampu',
            'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten',
        ];

        // Count valid words
        $validWordCount = 0;
        $totalWords = count($words);

        foreach ($words as $word) {
            $word = trim($word);
            if (empty($word)) continue;

            // Check if it's a valid word
            if (in_array($word, $validWords)) {
                $validWordCount++;
                continue;
            }

            // Check if it's a number (like DAP number: 100, 75, etc.)
            if (is_numeric($word)) {
                $validWordCount++;
                continue;
            }

            // Check if it looks like a valid word (has vowels, reasonable length)
            // Valid words typically have vowels and are 2-15 characters
            if (strlen($word) >= 2 && strlen($word) <= 15) {
                // Check for vowel presence (words usually have vowels)
                if (preg_match('/[aeiou]/i', $word)) {
                    // Check consonant-to-vowel ratio (gibberish often has too many consonants)
                    $vowelCount = preg_match_all('/[aeiou]/i', $word);
                    $consonantCount = preg_match_all('/[bcdfghjklmnpqrstvwxyz]/i', $word);

                    // If reasonable ratio (not too many consonants per vowel)
                    if ($consonantCount <= $vowelCount * 4) {
                        $validWordCount++;
                        continue;
                    }
                }
            }
        }

        // If less than 30% of words are valid, it's likely gibberish
        $validRatio = $totalWords > 0 ? $validWordCount / $totalWords : 0;

        Log::debug('Gibberish check', [
            'message' => $message,
            'totalWords' => $totalWords,
            'validWords' => $validWordCount,
            'validRatio' => $validRatio,
            'isGibberish' => $validRatio < 0.3,
        ]);

        return $validRatio < 0.3;
    }
}
