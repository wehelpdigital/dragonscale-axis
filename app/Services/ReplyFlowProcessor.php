<?php

namespace App\Services;

use App\Models\AiApiSetting;
use App\Models\AiChatSession;
use App\Models\AiExternalProduct;
use App\Models\AiImageSearchSetting;
use App\Models\AiQueryRule;
use App\Models\AiRagSetting;
use App\Models\AiReplyFlow;
use App\Models\AiWebsiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReplyFlowProcessor
{
    protected $userId;
    protected $flow;
    protected $session;
    protected $userMessage;
    protected $images;
    protected $chatHistory;

    // Node outputs storage
    protected $nodeOutputs = [];

    // Personality context
    protected $personalityText = '';
    protected $sampleConversations = '';

    // Thinking reply (to send before processing)
    protected $thinkingReply = null;

    // Blocker status
    protected $isBlocked = false;
    protected $blockMessage = null;

    // Topic context for follow-up questions
    protected $topicContext = null;

    // Force web search flag (for meta-questions asking to verify/check online)
    protected $forceWebSearch = false;

    // Force RAG-first flag (for product recommendation questions)
    protected $forceRagFirst = false;

    // Detected agricultural need (grain_filling, zinc_deficiency, etc.)
    // Used to provide specific product recommendations when no RAG product matches
    protected $detectedAgriculturalNeed = null;

    // Extracted product images from RAG response
    protected $productImages = [];

    // Current node ID being processed (for token tracking)
    protected $currentNodeId = 'unknown';

    // Global API rate limiting (to prevent 429 errors)
    protected static $lastApiCallTime = 0;
    protected static $apiCallMinDelay = 1500000; // 1.5 seconds between API calls (microseconds)

    // Meta-question patterns (verification/confirmation requests)
    // IMPORTANT: These should only match VERIFICATION requests about the previous answer,
    // NOT new questions that happen to contain "check" or similar words.
    protected static $metaQuestionPatterns = [
        // English patterns - verification requests
        '/\b(are you sure|you sure|sure ka|sigurado ka)\b/i',
        '/\b(check online|search online|verify online|look it up)\b/i',
        // "can you check" only when followed by verification words (this/that/it/if/online) or end of sentence
        '/\b(can you check|could you check|please check)\s*(this|that|it|online|if|again|\?|$)/i',
        '/\bcheck this\b/i',
        '/\b(can you verify|could you verify|please verify)\s*(this|that|it|online|if|again|\?|$)/i',
        '/\b(is that correct|is that right|is this correct|is this right)\b/i',
        '/\b(double check|recheck|re-check|confirm this)\b/i',
        '/\b(are you certain|you certain)\b/i',
        // Tagalog/Filipino patterns
        '/\b(sigurado ka ba|sure ka ba|totoo ba)\b/i',
        '/\b(pwede mo.*i-check|puwede mo.*i-check|pwede bang.*i-check)\b/i',
        '/\b(pwede mo.*verify|puwede mo.*verify)\b/i',
        '/\b(i-check mo|check mo nga|i-verify mo|verify mo)\b/i',
        '/\b(tama ba|correct ba|tama po ba)\b/i',
        '/\b(sigurado po ba|sure po ba)\b/i',
        '/\b(pakicheck|paki-check|paki check)\b/i',
        '/\b(online.*check|check.*online|search.*online)\b/i',
        // Short verification phrases (must be the whole message or end with ?)
        '/^(sigurado|sure|tama|correct)\?*$/i',
        '/^check\s*(this|that|it|online)?\s*\?*$/i',
    ];

    // Clarification patterns - asking for explanation/definition of terms
    // These are ALWAYS related to the previous answer
    protected static $clarificationPatterns = [
        // English patterns - asking for explanation
        '/\b(what is|what are|what does|what do)\b.*\?/i',
        '/\b(can you explain|could you explain|please explain|explain)\b/i',
        '/\b(what do you mean|what does that mean|meaning of)\b/i',
        '/\b(can you clarify|could you clarify|please clarify|clarify)\b/i',
        '/\b(can you specify|could you specify|please specify|specify)\b/i',
        '/\b(can you elaborate|could you elaborate|elaborate on)\b/i',
        '/\b(tell me more|more details|more info|more information)\b/i',
        '/\b(how does|how do|how is|how are)\b.*\?/i',
        '/\b(define|definition of)\b/i',
        // Tagalog/Filipino patterns - asking for explanation
        '/\b(ano ang|ano po ang|ano ba ang|anong)\b/i',
        '/\b(pwede mo.*explain|puwede mo.*explain|pwede.*ipaliwanag)\b/i',
        '/\b(pwede mo.*specify|puwede mo.*specify)\b/i',
        '/\b(ipaliwanag mo|pakipaliwanag|explain mo)\b/i',
        '/\b(ano.*ibig.*sabihin|ibig sabihin)\b/i',
        '/\b(mas.*detalye|dagdag.*info|dagdag.*detalye)\b/i',
        '/\b(paano|papaano|pano)\b.*\?/i',
        // Asking for visual/picture (clarification via image)
        '/\b(show me|can you show|picture|photo|image|pakita)\b/i',
        '/\b(pwede.*pakita|pwede.*magpakita|magpakita.*picture)\b/i',
        '/\b(litrato|larawan)\b/i',
    ];

    // Image request patterns - when user wants to see photos/pictures
    protected static $imageRequestPatterns = [
        // English patterns
        '/\b(show me|show.*picture|show.*photo|show.*image)\b/i',
        '/\b(can you show|could you show|please show)\b/i',
        '/\b(picture of|photo of|image of)\b/i',
        '/\b(what does.*look like|looks like)\b/i',
        '/\b(see.*picture|see.*photo|see.*image)\b/i',
        '/\b(give me.*picture|give me.*photo|give me.*image)\b/i',
        '/\b(display.*picture|display.*photo|display.*image)\b/i',
        // Tagalog/Filipino patterns
        '/\b(pakita|ipakita|magpakita|pakita mo|ipakita mo)\b/i',
        '/\b(pwede.*pakita|puwede.*pakita|pwede.*magpakita)\b/i',
        '/\b(makita|tingnan|tignan)\b/i',
        '/\b(litrato|larawan|picture|photo|imahe)\b/i',
        '/\b(itsura|hitsura|mukha)\b/i',
        '/\b(ano.*itsura|ano.*hitsura|paano.*itsura)\b/i',
        // Sample requests
        '/\b(sample.*picture|sample.*photo|sample.*image)\b/i',
        '/\b(halimbawa.*litrato|halimbawa.*larawan)\b/i',
    ];

    // Continuation patterns - follow-up questions about when/where/how much (same topic)
    // These are asking for more details about the same topic, not a new topic
    protected static $continuationPatterns = [
        // English patterns - timing questions
        '/^(when|when can|when should|when do|when is)\b/i',
        '/\b(how long|how many|how much|how often)\b.*\?/i',
        '/\b(what time|which time|at what|at which)\b.*\?/i',
        '/\b(and what about|what about|how about)\b/i',
        '/\b(is it|is that|can it|can I|should I)\b.*\?/i',
        '/\b(then what|so what|now what)\b/i',
        // Tagalog/Filipino patterns - timing and quantity
        '/^(kelan|kailan)\b/i',
        '/\b(kelan po|kailan po|kelan ba|kailan ba)\b/i',
        '/\b(pwede pa ba|pwede pa|puwede pa ba)\b/i',
        '/\b(hanggang kailan|hanggang kelan|until when)\b/i',
        '/\b(gaano katagal|gaano kahaba|ilang araw)\b/i',
        '/\b(magkano|ilan|ilang)\b/i',
        '/\b(saan|nasaan|saang)\b.*\?/i',
        '/\b(at ano|tapos ano|at paano)\b/i',
        '/\b(dapat ba|kailangan ba|need ba)\b/i',
        // Short continuation phrases
        '/^(tapos|then|at|and)\s*\?*$/i',
        '/^(e\s|eh\s|paano|so)\b/i',
    ];

    // Agricultural keyword patterns that REQUIRE web search for accurate, current information
    // These topics need up-to-date data about varieties, yields, prices, etc.
    protected static $agriculturalKeywords = [
        // Crops - English
        '/\b(corn|maize|rice|palay|wheat|soybean|vegetable|fruit|crop|seed|variety|hybrid)\b/i',
        // Crops - Filipino
        '/\b(mais|bigas|palay|gulay|prutas|pananim|binhi|halaman|tanim|ani)\b/i',
        // Agricultural terms - English
        '/\b(yield|harvest|plant|farm|fertilizer|pesticide|herbicide|irrigation|soil)\b/i',
        '/\b(planting|farming|agriculture|agri|cultivation|growing|production)\b/i',
        // Agricultural terms - Filipino
        '/\b(ani|aanihin|pataba|abono|gamot|tubig|lupa|taniman|bukid|sakahan)\b/i',
        '/\b(itanim|pagtatanim|magtanim|pagpapalago|palago|inani|inaani)\b/i',
        // Specific products/brands commonly asked about
        '/\b(dekalb|syngenta|pioneer|bioseed|nk\d+|bayer|monsanto)\b/i',
        // Yield/production questions
        '/\b(mataas.*ani|highest.*yield|best.*variety|maganda.*itanim|recommend.*tanim)\b/i',
        '/\b(MT\/ha|metric ton|tonelada|kilo.*hectare)\b/i',
        // Pest and disease
        '/\b(peste|sakit|virus|bacteria|fungi|insect|worm|borer|armyworm)\b/i',
        '/\b(pest|disease|blight|rust|rot|infestation)\b/i',
        // Growth stages
        '/\b(DAP|days after|flowering|vegetative|reproductive|maturity|germination)\b/i',
        '/\b(namumulaklak|tumutubo|hinog|binhi|supling|punla)\b/i',
        // Nutrients
        '/\b(nitrogen|phosphorus|potassium|NPK|urea|boron|zinc|calcium|magnesium)\b/i',
        // Seasons and weather - English
        '/\b(dry season|wet season|rainy|drought|summer|monsoon|flood|irrigation)\b/i',
        // Seasons and weather - Filipino
        '/\b(tag-init|tag-araw|tag-ulan|mainit|baha|tubig|patubig|irigasyon)\b/i',
        // Common question patterns in Tagalog
        '/\b(ano.*maganda|alin.*mas|pinaka.*mataas|pinaka.*maganda|anong.*pwede)\b/i',
        '/\b(pwede.*itanim|dapat.*itanim|mainam.*itanim|bagay.*itanim)\b/i',
    ];

    // Product recommendation patterns - questions seeking product/ingredient recommendations
    // These should prioritize RAG (local Philippine products) before web search
    protected static $productRecommendationPatterns = [
        // Product type requests - English
        '/\b(recommend|suggestion|suggest|what.*use|can you recommend)\b.*(pesticide|insecticide|fungicide|herbicide|fertilizer|spray|chemical|product)\b/i',
        '/\b(pesticide|insecticide|fungicide|herbicide|fertilizer)\b.*(recommend|suggestion|suggest|for|against)\b/i',
        '/\b(best|good|effective|maganda)\b.*(pesticide|insecticide|fungicide|herbicide|fertilizer|spray|product)\b/i',
        '/\b(what|which)\b.*(pesticide|insecticide|fungicide|herbicide|fertilizer|spray|chemical)\b.*(use|apply|spray|for)\b/i',
        // Product type requests - Filipino
        '/\b(ano.*gamot|anong.*gamot|may.*gamot)\b.*(peste|pest|insekto|sakit|damo|kulisap)\b/i',
        '/\b(recommend|suggest|maganda|mabisa)\b.*(gamot|spray|pataba|abono|chemical)\b/i',
        '/\b(ano.*spray|anong.*spray|may.*spray)\b/i',
        '/\b(ano.*pataba|anong.*pataba|may.*pataba)\b/i',
        // Specific active ingredients commonly searched
        '/\b(glyphosate|cypermethrin|lambda.?cyhalothrin|chlorpyrifos|imidacloprid|fipronil)\b/i',
        '/\b(mancozeb|carbendazim|propiconazole|azoxystrobin|tebuconazole|trifloxystrobin)\b/i',
        '/\b(butachlor|pretilachlor|bispyribac|cyhalofop|fenoxaprop|quinclorac)\b/i',
        '/\b(cartap|abamectin|emamectin|spinosad|chlorantraniliprole|flubendiamide)\b/i',
        '/\b(2,4-D|triclopyr|paraquat|diquat|glufosinate|oxyfluorfen)\b/i',
        '/\b(metaldehyde|niclosamide|bayluscide)\b/i', // Molluscicides
        '/\b(urea|complete|ammosul|ammonium sulfate|muriate of potash|MOP|solophos)\b/i', // Fertilizers (excluding DAP - ambiguous with Days After Planting)
        '/\bDAP\b.*(fertilizer|abono|pataba|gamit|bili|apply|lagay)/i', // DAP fertilizer only when in product context
        '/\b(fertilizer|abono|pataba)\b.*\bDAP\b/i', // DAP fertilizer only when in product context
        // Solution-seeking patterns - English
        '/\b(how to kill|how to control|how to eliminate|solution for|treatment for)\b.*(pest|insect|worm|disease|weed|fungus|bacteria)\b/i',
        '/\b(what kills|what controls|what eliminates)\b.*(pest|insect|worm|disease|weed|fungus|bacteria)\b/i',
        // Solution-seeking patterns - Filipino
        '/\b(paano.*patayin|paano.*kontrolin|paano.*mawala|paano.*maalis)\b.*(peste|insekto|uod|sakit|damo|kulisap)\b/i',
        '/\b(ano.*pang.*gamot|may.*gamot.*ba|gamot.*para.*sa)\b/i',
        '/\b(panlaban|pangkontrol|pampatay).*(peste|insekto|uod|sakit|damo|kulisap|kuhol|daga)\b/i',
        // Pest/disease-specific product queries
        '/\b(armyworm|fall armyworm|FAW|corn borer|stem borer|rice bug|brown planthopper|BPH)\b.*(gamot|spray|control|kill|treat)\b/i',
        '/\b(tungro|blast|bacterial leaf blight|BLB|sheath blight|downy mildew)\b.*(gamot|spray|control|treat|fungicide)\b/i',
        '/\b(golden snail|kuhol|apple snail|slug)\b.*(gamot|control|kill|molluscicide)\b/i',
        '/\b(daga|rat|rodent)\b.*(gamot|control|kill|poison)\b/i',
        // Looking for locally available products
        '/\b(available|meron|may|mabibili|nabibili)\b.*(pesticide|insecticide|fungicide|herbicide|fertilizer|gamot|spray)\b/i',
        '/\b(san.*makakabili|saan.*mabibili|where.*buy|where.*get)\b.*(pesticide|insecticide|fungicide|herbicide|fertilizer|gamot|spray)\b/i',
    ];

    // Processing metadata
    protected $metadata = [
        'nodesProcessed' => [],
        'tokensUsed' => 0,
        'errors' => [],
    ];

    // Detailed token usage tracking
    protected $tokenUsage = [
        'total' => [
            'inputTokens' => 0,
            'outputTokens' => 0,
            'totalTokens' => 0,
            'estimatedCost' => 0.0,
        ],
        'byNode' => [],      // Token usage per flow node
        'byProvider' => [],  // Token usage per AI provider
        'serper' => [        // Serper web search usage
            'searches' => 0,
            'credits' => 0,
            'queries' => [],
        ],
    ];

    // AI Provider pricing (per 1M tokens in USD)
    protected static $providerPricing = [
        'gemini' => [
            'input' => 0.075,    // Gemini 1.5 Flash input
            'output' => 0.30,   // Gemini 1.5 Flash output
            'name' => 'Google Gemini 1.5 Flash',
        ],
        'gemini-pro' => [
            'input' => 1.25,    // Gemini 1.5 Pro input
            'output' => 5.00,   // Gemini 1.5 Pro output
            'name' => 'Google Gemini 1.5 Pro',
        ],
        'openai' => [
            'input' => 2.50,    // GPT-4o input
            'output' => 10.00,  // GPT-4o output
            'name' => 'OpenAI GPT-4o',
        ],
        'openai-search' => [
            'input' => 2.50,    // GPT-4o search input
            'output' => 10.00,  // GPT-4o search output
            'name' => 'OpenAI GPT-4o Search',
        ],
        'claude' => [
            'input' => 3.00,    // Claude 3.5 Sonnet input
            'output' => 15.00,  // Claude 3.5 Sonnet output
            'name' => 'Anthropic Claude 3.5 Sonnet',
        ],
        'pinecone' => [
            'input' => 2.50,    // Pinecone uses GPT-4o internally (same pricing)
            'output' => 10.00,  // GPT-4o output pricing
            'name' => 'Pinecone RAG (GPT-4o)',
        ],
    ];

    // Flow log for debugging - stores search terms, prompts, and responses
    protected $flowLog = [
        'userMessage' => '',
        'questionType' => '',
        'aiProvider' => '',
        'searchQuery' => '',
        'aiPrompt' => '',
        'aiResponse' => '',
        'steps' => [],
        'processingTime' => 0,
        'tokenUsage' => [],  // Will be populated from $tokenUsage
    ];

    /**
     * Create a new processor instance.
     */
    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Track token usage for an AI call.
     *
     * @param string $provider AI provider (gemini, openai, claude, pinecone)
     * @param string $nodeId Flow node ID
     * @param int $inputTokens Number of input/prompt tokens
     * @param int $outputTokens Number of output/completion tokens
     * @param string $model Optional model name for more accurate pricing
     */
    protected function trackTokenUsage(string $provider, string $nodeId, int $inputTokens, int $outputTokens, string $model = ''): void
    {
        // Determine pricing key based on provider and model
        $pricingKey = $provider;
        if ($provider === 'openai' && strpos($model, 'search') !== false) {
            $pricingKey = 'openai-search';
        } elseif ($provider === 'gemini' && strpos($model, 'pro') !== false) {
            $pricingKey = 'gemini-pro';
        }

        // Get pricing (default to gemini if unknown)
        $pricing = self::$providerPricing[$pricingKey] ?? self::$providerPricing['gemini'];

        // Calculate cost (pricing is per 1M tokens)
        $inputCost = ($inputTokens / 1000000) * $pricing['input'];
        $outputCost = ($outputTokens / 1000000) * $pricing['output'];
        $totalCost = $inputCost + $outputCost;

        // Update totals
        $this->tokenUsage['total']['inputTokens'] += $inputTokens;
        $this->tokenUsage['total']['outputTokens'] += $outputTokens;
        $this->tokenUsage['total']['totalTokens'] += ($inputTokens + $outputTokens);
        $this->tokenUsage['total']['estimatedCost'] += $totalCost;

        // Track by node
        if (!isset($this->tokenUsage['byNode'][$nodeId])) {
            $this->tokenUsage['byNode'][$nodeId] = [
                'provider' => $pricing['name'],
                'model' => $model ?: $provider,
                'inputTokens' => 0,
                'outputTokens' => 0,
                'totalTokens' => 0,
                'estimatedCost' => 0.0,
            ];
        }
        $this->tokenUsage['byNode'][$nodeId]['inputTokens'] += $inputTokens;
        $this->tokenUsage['byNode'][$nodeId]['outputTokens'] += $outputTokens;
        $this->tokenUsage['byNode'][$nodeId]['totalTokens'] += ($inputTokens + $outputTokens);
        $this->tokenUsage['byNode'][$nodeId]['estimatedCost'] += $totalCost;

        // Track by provider
        if (!isset($this->tokenUsage['byProvider'][$pricingKey])) {
            $this->tokenUsage['byProvider'][$pricingKey] = [
                'name' => $pricing['name'],
                'inputTokens' => 0,
                'outputTokens' => 0,
                'totalTokens' => 0,
                'estimatedCost' => 0.0,
                'calls' => 0,
            ];
        }
        $this->tokenUsage['byProvider'][$pricingKey]['inputTokens'] += $inputTokens;
        $this->tokenUsage['byProvider'][$pricingKey]['outputTokens'] += $outputTokens;
        $this->tokenUsage['byProvider'][$pricingKey]['totalTokens'] += ($inputTokens + $outputTokens);
        $this->tokenUsage['byProvider'][$pricingKey]['estimatedCost'] += $totalCost;
        $this->tokenUsage['byProvider'][$pricingKey]['calls']++;

        // Update legacy metadata
        $this->metadata['tokensUsed'] = $this->tokenUsage['total']['totalTokens'];

        Log::debug('Token usage tracked', [
            'provider' => $pricingKey,
            'nodeId' => $nodeId,
            'inputTokens' => $inputTokens,
            'outputTokens' => $outputTokens,
            'cost' => number_format($totalCost, 6),
            'totalCost' => number_format($this->tokenUsage['total']['estimatedCost'], 6),
        ]);
    }

    /**
     * Get the current token usage summary.
     */
    public function getTokenUsage(): array
    {
        return $this->tokenUsage;
    }

    /**
     * Track Serper web/image search usage.
     * Serper pricing: ~$0.001 per search (1 credit = 1 search)
     */
    protected function trackSerperUsage(string $query, int $resultsCount = 0, int $credits = 1): void
    {
        $this->tokenUsage['serper']['searches']++;
        $this->tokenUsage['serper']['credits'] += $credits;
        $this->tokenUsage['serper']['queries'][] = [
            'query' => $query,
            'results' => $resultsCount,
            'credits' => $credits,
        ];

        Log::debug('Serper usage tracked', [
            'query' => $query,
            'results' => $resultsCount,
            'totalSearches' => $this->tokenUsage['serper']['searches'],
            'totalCredits' => $this->tokenUsage['serper']['credits'],
        ]);
    }

    /**
     * Process a user message through the reply flow.
     *
     * @param AiChatSession $session The chat session
     * @param string $userMessage The user's message
     * @param array $images Array of image paths (optional)
     * @return array Result with 'response', 'thinkingReply', 'metadata'
     */
    public function process(AiChatSession $session, string $userMessage, array $images = []): array
    {
        $this->session = $session;
        $this->userMessage = $userMessage;
        $this->images = $images;
        $this->chatHistory = $session->getChatHistoryText(10);

        // Get the user's reply flow
        $this->flow = AiReplyFlow::getOrCreateForUser($this->userId);

        if (!$this->flow || !$this->flow->isActive) {
            return $this->buildSimpleResponse();
        }

        $flowData = $this->flow->flowData;
        if (!$flowData || empty($flowData['nodes'])) {
            return $this->buildSimpleResponse();
        }

        try {
            // ========================================================
            // SPECIAL HANDLING: Gender-first greeting for new sessions
            // ========================================================
            $specialResponse = $this->handleSpecialConversationStates();
            if ($specialResponse !== null) {
                return $specialResponse;
            }

            // Find the start node
            $startNode = $this->findNodeByType($flowData['nodes'], 'start');
            if (!$startNode) {
                return $this->buildSimpleResponse();
            }

            // Initialize start node output
            $this->nodeOutputs[$startNode['id']] = $userMessage;

            // STEP 1: Process BLOCKER FIRST (gate keeper check)
            // If blocked, return block message immediately and skip everything else
            $this->processBlockerFirst($flowData);

            if ($this->isBlocked) {
                Log::debug('Message blocked by Blocker node', [
                    'userId' => $this->userId,
                    'blockMessage' => $this->blockMessage,
                ]);
                return [
                    'success' => true,
                    'response' => $this->blockMessage,
                    'thinkingReply' => null,
                    'blocked' => true,
                    'metadata' => $this->metadata,
                ];
            }

            // STEP 2: Process Thinking Reply (only if not blocked)
            // RANDOM: Sometimes show thinking reply, sometimes not (more natural)
            $this->processThinkingReplyFirst($flowData);

            // STEP 3: Pre-process Personality node to set context (before main flow)
            // This ensures personality context is available for all AI calls
            $this->preprocessPersonalityNode($flowData);

            // STEP 4: Process the main flow starting from start node
            $flowResult = $this->processFromNode($startNode['id'], $flowData);

            return [
                'success' => true,
                'response' => $flowResult['text'],
                'images' => $flowResult['images'] ?? [], // Extracted product images for lightbox
                'thinkingReply' => $this->thinkingReply,
                'blocked' => false,
                'metadata' => $this->metadata,
            ];
        } catch (\Exception $e) {
            Log::error('ReplyFlowProcessor error: ' . $e->getMessage(), [
                'userId' => $this->userId,
                'sessionId' => $session->id,
            ]);

            $this->metadata['errors'][] = $e->getMessage();
            // Log to flow modal instead of showing error in chat
            $this->logFlowStep('Error', 'Processing error: ' . $e->getMessage());

            return [
                'success' => false,
                'response' => '', // Empty response - error is in flow modal
                'thinkingReply' => $this->thinkingReply,
                'metadata' => $this->metadata,
                'flowLog' => $this->getFlowLog(), // Include flow log with error
            ];
        }
    }

    /**
     * Handle special conversation states.
     * Currently disabled - just process messages directly without gender greeting.
     * Returns null to proceed with normal flow.
     */
    protected function handleSpecialConversationStates(): ?array
    {
        // Mark session as normal if it's new (skip gender greeting)
        $state = $this->session->getConversationState();
        if ($state === 'new' || $state === 'awaiting_gender') {
            $this->session->setConversationState('normal');
        }

        // No special handling - proceed with normal flow
        return null;
    }

    /**
     * Check if a message is a meta-question (verification/confirmation request).
     * Meta-questions are things like "are you sure?", "can you check online?", etc.
     * These should be treated as related to the previous topic, not standalone questions.
     *
     * @param string $message The message to check
     * @return bool True if the message is a meta-question
     */
    protected function isMetaQuestion(string $message): bool
    {
        $message = trim($message);

        // Check against all meta-question patterns
        foreach (self::$metaQuestionPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                Log::debug('Meta-question detected', [
                    'message' => $message,
                    'pattern' => $pattern,
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a message is a clarification question (asking for explanation/definition).
     * Clarification questions are asking about the previous answer, so always related.
     *
     * @param string $message The user's message
     * @return bool True if asking for clarification
     */
    protected function isClarificationQuestion(string $message): bool
    {
        $message = trim($message);

        foreach (self::$clarificationPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                Log::debug('Clarification question detected', [
                    'message' => $message,
                    'pattern' => $pattern,
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a message is requesting images/photos.
     *
     * @param string $message The user's message
     * @return bool True if requesting images
     */
    public function isImageRequest(string $message): bool
    {
        $message = trim($message);

        foreach (self::$imageRequestPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                Log::debug('Image request detected', [
                    'message' => $message,
                    'pattern' => $pattern,
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a message is a continuation question (when/where/how much about same topic).
     * These are direct follow-ups, not new topics.
     *
     * @param string $message The user's message
     * @return bool True if asking a continuation question
     */
    protected function isContinuationQuestion(string $message): bool
    {
        $message = trim($message);

        foreach (self::$continuationPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                Log::debug('Continuation question detected', [
                    'message' => $message,
                    'pattern' => $pattern,
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a message is an agricultural question that requires web search.
     * Agricultural questions need current data about varieties, yields, prices, etc.
     *
     * @param string $message The user's message
     * @return bool True if the message is an agricultural question
     */
    protected function isAgriculturalQuestion(string $message): bool
    {
        $message = trim($message);

        // Check against all agricultural keyword patterns
        foreach (self::$agriculturalKeywords as $pattern) {
            if (preg_match($pattern, $message)) {
                Log::debug('Agricultural question detected - will force web search', [
                    'message' => substr($message, 0, 100),
                    'pattern' => $pattern,
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a message contains off-topic indicators that should NOT be auto-allowed.
     * These are topics that are clearly outside the agricultural scope even if
     * the message pattern looks like a meta-question.
     *
     * @param string $message The user's message
     * @return bool True if the message contains off-topic indicators
     */
    protected function containsOffTopicIndicators(string $message): bool
    {
        $message = strtolower(trim($message));

        // Off-topic patterns that indicate a new unrelated topic
        $offTopicPatterns = [
            // Cooking/Food preparation - NOT agriculture
            '/\b(cook|cooking|luto|lutuin|iluto|magluto|pagluto|recipe|recipes|resipe)\b/i',
            '/\b(fry|fried|pritong|prito|boil|boiled|nilaga|grill|grilled|inihaw)\b/i',
            '/\b(bake|baking|roast|steam|steamed|sinigang|adobo|pinakbet|kare.?kare)\b/i',
            '/\b(ingredient|ingredients|sangkap|sahog)\b.*(food|pagkain|dish|ulam)/i',
            // Entertainment/Media
            '/\b(movie|movies|pelikula|film|series|show|music|kanta|song)\b/i',
            '/\b(game|games|laro|play|sport|sports|basketball|volleyball)\b/i',
            // Technology (non-agricultural)
            '/\b(phone|cellphone|computer|laptop|internet|wifi|app|apps|software)\b/i',
            '/\b(programming|coding|website|facebook|tiktok|youtube|instagram)\b/i',
            // Personal/Social
            '/\b(relationship|love|dating|boyfriend|girlfriend|jowa|karelasyon)\b/i',
            '/\b(fashion|damit|clothes|makeup|beauty|parlor)\b/i',
            // Health (non-agricultural context)
            '/\b(hospital|doctor|doktor|gamot.*sakit|medicine.*sick|surgery)\b/i',
            // Travel/Places (non-agricultural context)
            '/\b(travel|biyahe|vacation|bakasyon|tourist|tour|hotel|beach)\b/i',
            // Education (non-agricultural)
            '/\b(school|eskwela|exam|test|assignment|homework|thesis)\b/i',
            // Finance (non-agricultural)
            '/\b(loan|utang|bank|bangko|invest|stock|crypto|bitcoin)\b/i',
        ];

        foreach ($offTopicPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                Log::debug('Off-topic indicator detected', [
                    'message' => substr($message, 0, 100),
                    'pattern' => $pattern,
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Get the default block message for off-topic questions.
     *
     * @return string The default block message in Taglish
     */
    protected function getDefaultBlockMessage(): string
    {
        return "Pasensya na po, pero ang inyong tanong ay mukhang hindi po sapat na malinaw o hindi po ito tungkol sa agriculture. 🌾 " .
               "Ako po ay isang AI Agricultural Technician para sa mga pananim - maaari ko lang po kayong tulungan sa mga tanong tungkol sa pagsasaka, " .
               "mga pananim, fertilizer, pesticide, at iba pang crop-related topics. " .
               "Pwede po ba ninyong i-clarify ang inyong tanong tungkol sa farming?";
    }

    /**
     * Check if a question needs multi-AI search for better data accuracy.
     * These are questions that ask for rankings, specific data, comparisons, or latest information.
     *
     * @param string $message The user's message
     * @return bool True if multi-AI search should be used
     */
    protected function isDataIntensiveQuestion(string $message): bool
    {
        $message = strtolower(trim($message));

        // Patterns that indicate need for accurate, current data from multiple sources
        $dataIntensivePatterns = [
            // Rankings/Top/Best questions
            '/\b(top|best|highest|pinaka|mataas|maganda|recommended|recommend|inirerekomenda)\b/i',
            // Yield/Production data
            '/\b(yield|ani|harvest|mt\/ha|metric ton|tonelada|production)\b/i',
            // Variety/Product recommendations
            '/\b(variety|varieties|barayti|hybrid|seed|binhi|brand)\b/i',
            // Comparison questions
            '/\b(which is better|alin.*mas|compare|comparison|vs|versus)\b/i',
            // Price/Cost questions
            '/\b(price|presyo|magkano|cost|halaga|how much)\b/i',
            // Specific data requests
            '/\b(data|statistics|latest|pinakabago|current|2024|2025)\b/i',
            // Agricultural products
            '/\b(corn|mais|rice|palay|fertilizer|pataba|pesticide)\b/i',
        ];

        foreach ($dataIntensivePatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                Log::debug('Data-intensive question detected - will use multi-AI search', [
                    'message' => substr($message, 0, 100),
                    'pattern' => $pattern,
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a question is asking for product recommendations or specific ingredients.
     * These questions should prioritize RAG search (local Philippine products) before web search.
     *
     * @param string $message The user's message
     * @return bool True if the message is asking for product recommendations
     */
    protected function isProductRecommendationQuestion(string $message): bool
    {
        $message = trim($message);

        // EXCLUSION PATTERNS: Questions about timing, scheduling, methods - NOT product recommendations
        // These should NOT trigger RAG product search
        $exclusionPatterns = [
            // DAP (Days After Planting) timing questions - handle various formats:
            // "dap100", "dap 100", "100 dap", "100dap", "DAP-100", etc.
            '/\bDAP\s*\d+.*(patubig|irrigate|irrigation|diligan|dilig|tubig|water)/i',
            '/\b\d+\s*DAP.*(patubig|irrigate|irrigation|diligan|dilig|tubig|water)/i',
            '/\b(patubig|diligan|dilig|tubig|irrigat).*\bDAP\s*\d+/i',
            '/\b(patubig|diligan|dilig|tubig|irrigat).*\b\d+\s*DAP/i',
            '/\bDAP\s*\d+.*(schedule|timing|kailan|kelan|kung kailan)/i',
            '/\banong?\s*(mga\s+)?DAP\s*\d*.*(patubig|diligan|mais|palay|rice|corn)/i',
            '/\b(ilang|ilan|how many)\s*DAP/i',
            // Also match "magpatubig ng dap100" style
            '/magpatubig.*(ng|sa)?\s*DAP\s*\d+/i',
            '/magpatubig.*(ng|sa)?\s*\d+\s*DAP/i',
            // Match standalone DAP with irrigation context
            '/\bDAP\b.*(patubig|irrigate|irrigation|diligan|dilig|tubig|water)/i',
            '/\b(patubig|diligan|dilig|tubig|irrigat)\b.*\bDAP\b/i',
            // General timing/method questions (not product-seeking)
            '/\b(kailan|kelan|when|what time|anong oras)\b.*(tanim|diligan|patubig|harvest|ani)/i',
            '/\b(paano|how to)\b.*(magtanim|mag-?diligan|mag-?patubig|mag-?harvest)/i',
            '/\b(schedule|timing|calendar)\b.*(tanim|planting|irrigation|patubig)/i',
            // Variety/seed questions (not product recommendations)
            '/\b(anong|ano|what)\b.*(variety|klase|uri|tipo)\b.*(mais|palay|rice|corn|gulay)/i',
            '/\b(magandang|best|magaling)\b.*(variety|klase|uri)\b/i',
        ];

        foreach ($exclusionPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                Log::debug('Timing/method question detected - NOT a product recommendation question', [
                    'message' => substr($message, 0, 100),
                    'excludedByPattern' => $pattern,
                ]);
                return false;
            }
        }

        // Check against product recommendation patterns
        foreach (self::$productRecommendationPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                Log::debug('Product recommendation question detected - will prioritize RAG', [
                    'message' => substr($message, 0, 100),
                    'pattern' => $pattern,
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Extract product image URLs from RAG response content.
     * Looks for URLs in the "PRODUCT IMAGES:" section of external product RAG content.
     *
     * @param string $ragContent The RAG response content
     * @return array Array of image data ['url' => string, 'type' => string, 'title' => string]
     */
    protected function extractProductImagesFromRag(string $ragContent): array
    {
        $images = [];

        Log::debug('Extracting product images from RAG content', [
            'contentLength' => strlen($ragContent),
            'contentPreview' => substr($ragContent, 0, 500),
        ]);

        // Pattern 1: Look for PRODUCT IMAGES section format
        // PRODUCT IMAGES:
        // - Image 1: http://... (Front Label)
        if (preg_match('/PRODUCT IMAGES?:?\s*\n((?:[-•]\s*Image\s*\d*:?\s*https?:\/\/[^\s]+[^\n]*\n?)+)/i', $ragContent, $matches)) {
            $imageSection = $matches[1];
            Log::debug('Found PRODUCT IMAGES section', ['section' => $imageSection]);

            preg_match_all('/[-•]\s*Image\s*\d*:?\s*(https?:\/\/[^\s\)]+)(?:\s*\(([^)]+)\))?/i', $imageSection, $imageMatches, PREG_SET_ORDER);

            foreach ($imageMatches as $match) {
                $url = trim($match[1]);
                // Clean URL - remove trailing punctuation
                $url = rtrim($url, '.,;:');
                $type = isset($match[2]) ? trim($match[2]) : 'Product Image';

                $images[] = [
                    'url' => $url,
                    'thumbnail' => $url,
                    'type' => $type,
                    'title' => $type,
                    'isProduct' => true,
                    'source' => 'rag',
                ];
                Log::debug('Extracted image from PRODUCT IMAGES section', ['url' => $url, 'type' => $type]);
            }
        }

        // Pattern 2: Look for any image URLs with /storage/ path (covers ai-products and other storage paths)
        preg_match_all('/(https?:\/\/[^\s"\'<>\)]+\/storage\/[^\s"\'<>\)]+\.(jpg|jpeg|png|gif|webp))/i', $ragContent, $storageMatches);
        foreach ($storageMatches[1] as $url) {
            $url = trim($url);
            $url = rtrim($url, '.,;:');
            // Avoid duplicates
            $exists = false;
            foreach ($images as $img) {
                if ($img['url'] === $url) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $images[] = [
                    'url' => $url,
                    'thumbnail' => $url,
                    'type' => 'Product Image',
                    'title' => 'Product Image',
                    'isProduct' => true,
                    'source' => 'rag',
                ];
                Log::debug('Extracted image from storage path', ['url' => $url]);
            }
        }

        // Pattern 3: Look for PRODUCT IMAGE: single image format
        if (preg_match('/PRODUCT IMAGE:\s*(https?:\/\/[^\s\)]+)/i', $ragContent, $match)) {
            $url = trim($match[1]);
            $url = rtrim($url, '.,;:');
            $exists = false;
            foreach ($images as $img) {
                if ($img['url'] === $url) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $images[] = [
                    'url' => $url,
                    'thumbnail' => $url,
                    'type' => 'Product Image',
                    'title' => 'Product Image',
                    'isProduct' => true,
                    'source' => 'rag',
                ];
                Log::debug('Extracted single product image', ['url' => $url]);
            }
        }

        // Pattern 4: Look for localhost URLs with storage path (for local development)
        preg_match_all('/(http:\/\/(?:localhost|127\.0\.0\.1)[^\s"\'<>\)]*\/storage\/[^\s"\'<>\)]+)/i', $ragContent, $localMatches);
        foreach ($localMatches[1] as $url) {
            $url = trim($url);
            $url = rtrim($url, '.,;:');
            $exists = false;
            foreach ($images as $img) {
                if ($img['url'] === $url) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists && preg_match('/\.(jpg|jpeg|png|gif|webp)/i', $url)) {
                $images[] = [
                    'url' => $url,
                    'thumbnail' => $url,
                    'type' => 'Product Image',
                    'title' => 'Product Image',
                    'isProduct' => true,
                    'source' => 'rag',
                ];
                Log::debug('Extracted localhost image', ['url' => $url]);
            }
        }

        Log::info('Product image extraction complete', [
            'count' => count($images),
            'urls' => array_column($images, 'url'),
        ]);

        return $images;
    }

    /**
     * Proactively look up product images from the database based on product names found in RAG response.
     * This ensures product images are shown even when Pinecone doesn't return the image URLs.
     *
     * @param string $ragContent The RAG response content
     * @return array Array of image data ['url' => string, 'type' => string, 'title' => string]
     */
    protected function lookupProductImagesFromDatabase(string $ragContent): array
    {
        $images = [];

        // Get all active external products for this user
        $products = AiExternalProduct::active()
            ->forUser($this->userId)
            ->where('ragStatus', AiExternalProduct::RAG_INDEXED)
            ->with(['images' => function ($query) {
                $query->where('delete_status', 'active')
                    ->orderBy('isPrimary', 'desc')
                    ->orderBy('sortOrder', 'asc');
            }])
            ->get();

        Log::debug('Looking up product images from database', [
            'userId' => $this->userId,
            'productCount' => $products->count(),
            'products' => $products->pluck('productName')->toArray(),
            'contentLength' => strlen($ragContent),
            'contentPreview' => substr($ragContent, 0, 500),
        ]);

        // Check if any product names are mentioned in the RAG content
        foreach ($products as $product) {
            $productName = $product->productName;
            $brandName = $product->brandName;

            // Check if product name or brand is mentioned (case-insensitive)
            $mentioned = false;

            // Check product name
            if (!empty($productName) && stripos($ragContent, $productName) !== false) {
                $mentioned = true;
                Log::debug('Found product name in RAG content', ['productName' => $productName]);
            }

            // Also check brand name if different from product name
            if (!$mentioned && !empty($brandName) && $brandName !== $productName && stripos($ragContent, $brandName) !== false) {
                $mentioned = true;
                Log::debug('Found brand name in RAG content', ['brandName' => $brandName]);
            }

            // If product is mentioned and has images, add them
            if ($mentioned && $product->images->count() > 0) {
                foreach ($product->images as $image) {
                    $imageUrl = $image->image_url;

                    // Avoid duplicates
                    $exists = false;
                    foreach ($images as $img) {
                        if ($img['url'] === $imageUrl) {
                            $exists = true;
                            break;
                        }
                    }

                    if (!$exists && $imageUrl) {
                        $imageType = $image->aiAnalysis['imageType'] ?? 'Product';
                        $images[] = [
                            'url' => $imageUrl,
                            'thumbnail' => $imageUrl,
                            'type' => $productName . ' - ' . $imageType,
                            'title' => $productName,
                            'isProduct' => true,
                            'source' => 'database',
                            'badgeClass' => 'bg-success text-white',
                            'badgeText' => 'Product',
                        ];
                        Log::debug('Added product image from database', [
                            'productName' => $productName,
                            'imageUrl' => $imageUrl,
                        ]);
                    }
                }
            }
        }

        Log::info('Product image lookup from database complete', [
            'count' => count($images),
            'urls' => array_column($images, 'url'),
        ]);

        return $images;
    }

    /**
     * Post-processing: Clean up redundant content when images already provide diagnosis.
     *
     * When user uploads images and asks multiple questions (e.g., "what's the problem?" + "what product to use?"):
     * 1. Image analysis provides diagnosis
     * 2. But separate query might ask for more details (redundant)
     * 3. This method removes the redundant "asking for details" section
     *
     * @param string $finalOutput The AI's response
     * @return string Cleaned response
     */
    protected function cleanupRedundantContent(string $finalOutput): string
    {
        // Only clean up if we have images (diagnosis from image analysis)
        if (empty($this->images)) {
            return $finalOutput;
        }

        // Check if response has both diagnosis AND "asking for details"
        $hasDiagnosis = preg_match('/\b(deficiency|kakulangan|diagnosis|diyagnosis|sintomas|problema)\b/i', $finalOutput);
        $hasAskingForDetails = preg_match('/\b(kailangan ko.*malaman|ano.*variety|ilang araw|saang lugar|maaari.*sabihin)\b/i', $finalOutput);

        if (!$hasDiagnosis || !$hasAskingForDetails) {
            return $finalOutput;
        }

        Log::info('Cleanup: Response has both diagnosis and "asking for details" - cleaning up');

        // Find and remove the "KARAGDAGANG IMPORMASYON" section or similar
        $patterns = [
            // Remove sections that ask for more details after diagnosis
            '/===\s*KARAGDAGANG IMPORMASYON\s*===.*$/is',
            '/🌾\s*Magandang araw.*Kapag nalaman ko ang mga detalyeng ito.*$/is',
            '/Para matulungan kita.*kailangan ko.*malaman.*Kapag nalaman ko.*$/is',
            // Remove duplicate generic advice when specific diagnosis exists
            '/🌽\s*Pero sa ngayon.*Mga Posibleng Problema at Solusyon:.*$/is',
        ];

        $cleaned = $finalOutput;
        foreach ($patterns as $pattern) {
            $cleaned = preg_replace($pattern, '', $cleaned);
        }

        // If we removed content, also remove trailing whitespace and separators
        $cleaned = preg_replace('/\s*---\s*$/', '', $cleaned);
        $cleaned = trim($cleaned);

        // If cleanup removed significant content, log it
        if (strlen($cleaned) < strlen($finalOutput) * 0.8) {
            Log::info('Cleanup: Removed redundant "asking for details" section', [
                'originalLength' => strlen($finalOutput),
                'cleanedLength' => strlen($cleaned),
            ]);
            $this->logFlowStep('Content Cleanup', 'Removed redundant sections when images provide diagnosis');
        }

        return $cleaned;
    }

    /**
     * Remove "Magandang araw po!" greeting from follow-up messages.
     * It's not natural to greet in the middle of a conversation.
     */
    protected function removeGreetingFromFollowUp(string $response): string
    {
        // Only remove greeting if this is a follow-up (has chat history)
        if (empty($this->chatHistory)) {
            return $response;
        }

        // Patterns to remove greetings at the start of response
        $patterns = [
            // Remove "Magandang araw po!" and similar greetings at the start
            '/^🌾\s*Magandang (araw|umaga|hapon|gabi) po!?\s*/iu',
            '/^🌽\s*Magandang (araw|umaga|hapon|gabi) po!?\s*/iu',
            '/^🌱\s*Magandang (araw|umaga|hapon|gabi) po!?\s*/iu',
            '/^💧\s*Magandang (araw|umaga|hapon|gabi) po!?\s*/iu',
            '/^Magandang (araw|umaga|hapon|gabi) po!?\s*/iu',
            // Also catch "Hello po!" style greetings
            '/^🌾\s*Hello po!?\s*/iu',
            '/^🌽\s*Hello po!?\s*/iu',
            '/^Hello po!?\s*/iu',
            '/^Kumusta po!?\s*/iu',
        ];

        $cleaned = $response;
        foreach ($patterns as $pattern) {
            $cleaned = preg_replace($pattern, '', $cleaned);
        }

        // If we removed something, log it
        if (strlen($cleaned) < strlen($response)) {
            Log::debug('Removed greeting from follow-up response', [
                'originalStart' => substr($response, 0, 50),
                'cleanedStart' => substr($cleaned, 0, 50),
            ]);
        }

        return trim($cleaned);
    }

    /**
     * Remove "Larawan ng Produkto" placeholder text from response.
     * Product images are displayed by the frontend from the images array, not in the text.
     */
    protected function removeImagePlaceholders(string $response): string
    {
        $patterns = [
            // Remove standalone "Larawan ng Produkto" lines
            '/\n*Larawan ng Produkto\s*\n*/i',
            '/\n*\[Larawan ng Produkto\]\s*\n*/i',
            // Remove markdown image links with empty or missing URLs
            '/\[Larawan ng Produkto\]\(\s*\)/i',
            '/\[Larawan ng Produkto\]\([^)]*\)/i',
            '/\[Larawan\]\(\s*\)/i',
            // Remove "Product Image" variations
            '/\n*Product Image\s*\n*/i',
            '/\n*\[Product Image\]\s*\n*/i',
        ];

        $cleaned = $response;
        foreach ($patterns as $pattern) {
            $cleaned = preg_replace($pattern, "\n", $cleaned);
        }

        // Clean up excessive newlines
        $cleaned = preg_replace('/\n{3,}/', "\n\n", $cleaned);

        if (strlen($cleaned) < strlen($response)) {
            Log::debug('Removed image placeholder text from response', [
                'originalLength' => strlen($response),
                'cleanedLength' => strlen($cleaned),
            ]);
        }

        return trim($cleaned);
    }

    /**
     * Repair CJK (Chinese, Japanese, Korean) characters in AI response.
     *
     * AI models sometimes mix in characters from other languages when the training
     * data bleeds through (e.g., "穂軸" appearing instead of "cob" in Tagalog text).
     * This method detects these characters and uses AI to translate/replace them
     * with the correct Filipino/English equivalents so the text makes sense.
     *
     * @param string $response The AI's response
     * @return string Repaired response with CJK characters translated
     */
    protected function removeCjkCharacters(string $response): string
    {
        // CJK Unicode ranges:
        // - Chinese: \x{4E00}-\x{9FFF} (CJK Unified Ideographs)
        // - Japanese Hiragana: \x{3040}-\x{309F}
        // - Japanese Katakana: \x{30A0}-\x{30FF}
        // - Korean Hangul: \x{AC00}-\x{D7AF}
        // - CJK Compatibility: \x{3300}-\x{33FF}
        // - CJK Unified Ideographs Extension A: \x{3400}-\x{4DBF}

        $cjkPattern = '/[\x{4E00}-\x{9FFF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{AC00}-\x{D7AF}\x{3300}-\x{33FF}\x{3400}-\x{4DBF}]+/u';

        $hasCjk = preg_match($cjkPattern, $response);

        if (!$hasCjk) {
            return $response;
        }

        // Extract the CJK characters found
        preg_match_all($cjkPattern, $response, $matches);
        $cjkFound = array_unique($matches[0]);

        Log::warning('CJK characters detected in AI response, attempting AI repair...', [
            'responsePreview' => substr($response, 0, 300),
            'cjkFound' => $cjkFound,
        ]);

        // Try to use AI to repair the text intelligently
        try {
            $repaired = $this->repairCjkWithAI($response, $cjkFound);
            if ($repaired && $repaired !== $response) {
                Log::info('CJK characters repaired using AI', [
                    'originalLength' => strlen($response),
                    'repairedLength' => strlen($repaired),
                    'cjkFound' => $cjkFound,
                ]);
                $this->logFlowStep('CJK Repair', 'AI translated ' . count($cjkFound) . ' CJK term(s) to Filipino/English');
                return $repaired;
            }
        } catch (\Exception $e) {
            Log::error('Failed to repair CJK with AI, falling back to removal', [
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback: Simple removal with cleanup
        Log::info('Falling back to simple CJK removal');
        $cleaned = preg_replace($cjkPattern, '', $response);

        // Clean up orphaned punctuation and spacing issues that may result
        $cleaned = preg_replace('/\s+,/', ',', $cleaned);
        $cleaned = preg_replace('/\s+\./', '.', $cleaned);
        $cleaned = preg_replace('/\(\s*\)/', '', $cleaned); // Remove empty parentheses
        $cleaned = preg_replace('/\s{2,}/', ' ', $cleaned); // Multiple spaces to single
        $cleaned = preg_replace('/\n\s*\n\s*\n/', "\n\n", $cleaned); // Multiple newlines to double

        $this->logFlowStep('CJK Filter', 'Removed ' . count($cjkFound) . ' CJK term(s) (AI repair unavailable)');

        return trim($cleaned);
    }

    /**
     * Use AI to repair text containing CJK characters.
     *
     * This method asks the AI to translate/replace CJK characters with
     * the appropriate Filipino or English words based on context.
     *
     * @param string $response The response containing CJK characters
     * @param array $cjkFound Array of CJK terms found
     * @return string|null Repaired text or null if failed
     */
    protected function repairCjkWithAI(string $response, array $cjkFound): ?string
    {
        // Get Gemini API setting
        $geminiSetting = AiApiSetting::where('usersId', $this->userId)
            ->where('provider', 'gemini')
            ->where('isEnabled', true)
            ->where('delete_status', 'active')
            ->first();

        if (!$geminiSetting || !$geminiSetting->apiKey) {
            Log::debug('No Gemini API key available for CJK repair');
            return null;
        }

        // Build the repair prompt
        $cjkList = implode(', ', $cjkFound);
        $prompt = <<<PROMPT
You are a text repair assistant. The following text contains Chinese/Japanese/Korean characters that should not be there. These characters appeared because the AI model mixed languages.

Your task:
1. Identify what each CJK character/word means
2. Replace them with the appropriate FILIPINO or ENGLISH word based on context
3. Return the COMPLETE repaired text
4. Do NOT add any explanations - just return the fixed text

CJK characters found: {$cjkList}

Text to repair:
{$response}

IMPORTANT:
- The text is about AGRICULTURE (farming, crops, plants)
- Common terms that might be replaced: cob (corn cob), leaf, stem, root, grain, rice, fertilizer, etc.
- Keep the rest of the text EXACTLY the same
- Only replace the CJK characters
- Return ONLY the repaired text, nothing else
PROMPT;

        // Use a fast model for this simple task
        $model = 'gemini-2.0-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $geminiSetting->apiKey;

        $requestData = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'maxOutputTokens' => 8192, // Allow long responses
                'temperature' => 0.1, // Low temp for consistent output
            ],
        ];

        $httpResponse = Http::timeout(30)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $requestData);

        if ($httpResponse->successful()) {
            $data = $httpResponse->json();
            $repairedText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if ($repairedText) {
                // Verify CJK was actually removed
                $stillHasCjk = preg_match('/[\x{4E00}-\x{9FFF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{AC00}-\x{D7AF}]/u', $repairedText);
                if (!$stillHasCjk) {
                    return trim($repairedText);
                } else {
                    Log::warning('AI repair still contains CJK characters', [
                        'preview' => substr($repairedText, 0, 200),
                    ]);
                }
            }
        } else {
            Log::error('Gemini API error during CJK repair', [
                'status' => $httpResponse->status(),
                'error' => $httpResponse->json('error.message'),
            ]);
        }

        return null;
    }

    /**
     * Public method to run post-processing on a combined response.
     * This should be called AFTER image analysis is combined with the main response.
     *
     * The method:
     * 1. Cleans up redundant content (e.g., "asking for details" when images provide diagnosis)
     * 2. Enhances with product recommendation if needed
     * 3. Re-extracts product images from the combined response
     *
     * @param string $combinedResponse The response after image analysis is combined
     * @return array ['response' => string, 'productImages' => array]
     */
    public function postProcessCombinedResponse(string $combinedResponse): array
    {
        Log::info('Post-processing combined response', [
            'responseLength' => strlen($combinedResponse),
            'hasImages' => !empty($this->images),
            'userMessage' => substr($this->userMessage ?? '', 0, 100),
            'hasChatHistory' => !empty($this->chatHistory),
        ]);

        // Step 0: Remove "Magandang araw po!" greeting from follow-up messages (not natural)
        $processed = $this->removeGreetingFromFollowUp($combinedResponse);

        // Step 1: Clean up redundant content
        $processed = $this->cleanupRedundantContent($processed);

        // Step 2: Enhance with product recommendation if needed
        $processed = $this->enhanceWithProductRecommendationIfNeeded($processed);

        // Step 3: Remove "Larawan ng Produkto" placeholder text (images are shown by frontend)
        $processed = $this->removeImagePlaceholders($processed);

        // Step 4: Remove CJK (Chinese/Japanese/Korean) characters that may leak from AI model
        $processed = $this->removeCjkCharacters($processed);

        // Step 5: Re-extract product images from the combined (and possibly enhanced) response
        $additionalImages = [];

        // Extract from patterns
        $patternImages = $this->extractProductImagesFromRag($processed);
        if (!empty($patternImages)) {
            $additionalImages = array_merge($additionalImages, $patternImages);
            Log::debug('Post-process: Found product images from patterns', ['count' => count($patternImages)]);
        }

        // Extract from database lookup
        $dbImages = $this->lookupProductImagesFromDatabase($processed);
        if (!empty($dbImages)) {
            foreach ($dbImages as $dbImg) {
                $exists = false;
                foreach ($additionalImages as $existingImg) {
                    if (($existingImg['url'] ?? '') === ($dbImg['url'] ?? '')) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $additionalImages[] = $dbImg;
                }
            }
            Log::debug('Post-process: Found product images from database', ['count' => count($dbImages)]);
        }

        // Merge with existing product images (avoid duplicates)
        foreach ($additionalImages as $newImg) {
            $exists = false;
            foreach ($this->productImages as $existingImg) {
                if (($existingImg['url'] ?? '') === ($newImg['url'] ?? '')) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $this->productImages[] = $newImg;
            }
        }

        Log::info('Post-processing complete', [
            'originalLength' => strlen($combinedResponse),
            'processedLength' => strlen($processed),
            'totalProductImages' => count($this->productImages),
        ]);

        return [
            'response' => $processed,
            'productImages' => $this->productImages,
        ];
    }

    /**
     * Post-processing: Enhance response with product recommendation if needed.
     *
     * This method checks if:
     * 1. User asked for a product recommendation (gamot, recommend, etc.)
     * 2. AI diagnosed a problem (deficiency, pest, disease)
     * 3. AI did NOT recommend a specific product from our database
     *
     * If all conditions are met, query RAG for matching products and append recommendation.
     *
     * @param string $finalOutput The AI's final response
     * @return string Enhanced response with product recommendation, or original if not needed
     */
    protected function enhanceWithProductRecommendationIfNeeded(string $finalOutput): string
    {
        Log::info('Product enhancement: Starting enhancement check', [
            'responseLength' => strlen($finalOutput),
            'responsePreview' => substr($finalOutput, 0, 200),
            'userMessage' => substr($this->userMessage ?? '', 0, 100),
        ]);

        // Step 0: Check if user actually asked for a product recommendation
        // Don't add products to follow-up questions that don't ask for them
        if (!$this->userAskedForProductRecommendation()) {
            Log::debug('Product enhancement: User did not ask for product recommendation, skipping', [
                'userMessage' => $this->userMessage,
            ]);
            return $finalOutput;
        }
        Log::debug('Product enhancement: User asked for product, continuing...');

        // Step 1: Check if response already mentions a specific product from our database
        if ($this->responseContainsProductFromDatabase($finalOutput)) {
            Log::debug('Product enhancement: Response already contains product recommendation');
            return $finalOutput;
        }
        Log::debug('Product enhancement: No existing product in response, continuing...');

        // Step 2: Get all available products from RAG database
        $availableProducts = $this->getAvailableProductsForEnhancement();
        if (empty($availableProducts)) {
            Log::debug('Product enhancement: No products available in RAG database');
            return $finalOutput;
        }

        Log::info('Product enhancement: Checking if AI response needs product recommendation', [
            'availableProducts' => count($availableProducts),
        ]);

        // Step 3: First try pattern-based detection (fast and reliable)
        $problem = $this->detectDiagnosedProblem($finalOutput);
        if (!empty($problem)) {
            Log::info('Product enhancement: Pattern detected problem', ['problem' => $problem]);

            // Find matching product using pattern-based matching
            $matchingProduct = $this->findProductForProblemFromList($problem, $availableProducts);

            if (!empty($matchingProduct)) {
                $enhancedOutput = $this->appendAiProductRecommendation($finalOutput, $matchingProduct);
                $this->logFlowStep('Product Enhancement (Pattern)', 'Added product for: ' . $problem . ' - ' . $matchingProduct['productName']);
                Log::info('Product enhancement: Pattern-based product added', [
                    'problem' => $problem,
                    'product' => $matchingProduct['productName'],
                ]);
                return $enhancedOutput;
            }
        }

        // Step 4: Fall back to AI analysis if pattern-based didn't find a match
        $productRecommendation = $this->aiAnalyzeForProductRecommendation($finalOutput, $availableProducts);

        if (empty($productRecommendation)) {
            Log::debug('Product enhancement: Neither pattern nor AI found a product recommendation');
            return $finalOutput;
        }

        // Step 5: Append AI-generated product recommendation seamlessly
        $enhancedOutput = $this->appendAiProductRecommendation($finalOutput, $productRecommendation);

        $this->logFlowStep('AI Product Enhancement', 'AI added product recommendation: ' . ($productRecommendation['productName'] ?? 'Unknown'));

        Log::info('Product enhancement: Response enhanced with AI product recommendation', [
            'product' => $productRecommendation['productName'] ?? 'Unknown',
        ]);

        return $enhancedOutput;
    }

    /**
     * Find a product for a diagnosed problem from the available products list.
     */
    protected function findProductForProblemFromList(string $problem, array $availableProducts): ?array
    {
        // Map problem types to keywords that should match in product descriptions
        $problemKeywords = [
            'zinc deficiency' => ['zinc', 'Zn', 'zintrac', 'micronutrient'],
            'nitrogen deficiency' => ['nitrogen', 'N', 'urea', 'ammoni'],
            'phosphorus deficiency' => ['phosphorus', 'P', 'phosphate'],
            'potassium deficiency' => ['potassium', 'K', 'potash', 'muriate'],
            'magnesium deficiency' => ['magnesium', 'Mg'],
            'iron deficiency' => ['iron', 'Fe', 'ferrous'],
            'boron deficiency' => ['boron', 'B'],
            'fall armyworm' => ['armyworm', 'insecticide', 'chlorantraniliprole', 'emamectin', 'spinetoram'],
            'corn borer' => ['borer', 'insecticide', 'chlorantraniliprole', 'fipronil'],
            'rice bug' => ['rice bug', 'insecticide', 'cypermethrin', 'lambda'],
            'brown planthopper' => ['planthopper', 'BPH', 'insecticide', 'imidacloprid'],
            'golden snail' => ['snail', 'kuhol', 'molluscicide', 'metaldehyde', 'niclosamide'],
            'aphids' => ['aphid', 'insecticide', 'imidacloprid', 'thiamethoxam'],
            'tungro' => ['tungro', 'insecticide', 'imidacloprid'],
            'rice blast' => ['blast', 'fungicide', 'tricyclazole', 'azoxystrobin'],
            'bacterial leaf blight' => ['blight', 'bactericide', 'copper'],
            'sheath blight' => ['sheath', 'fungicide', 'hexaconazole', 'propiconazole'],
            'downy mildew' => ['mildew', 'fungicide', 'metalaxyl', 'mancozeb'],
        ];

        $keywords = $problemKeywords[$problem] ?? [];
        if (empty($keywords)) {
            return null;
        }

        foreach ($availableProducts as $product) {
            // Build searchable text from product (handle both string and array fields)
            $searchText = strtolower(
                $this->fieldToString($product['productName'] ?? '') . ' ' .
                $this->fieldToString($product['brandName'] ?? '') . ' ' .
                $this->fieldToString($product['description'] ?? '') . ' ' .
                $this->fieldToString($product['activeIngredients'] ?? '') . ' ' .
                $this->fieldToString($product['productType'] ?? '') . ' ' .
                $this->fieldToString($product['targetPests'] ?? '') . ' ' .
                $this->fieldToString($product['targetDiseases'] ?? '')
            );

            // Check if any keyword matches
            foreach ($keywords as $keyword) {
                if (stripos($searchText, strtolower($keyword)) !== false) {
                    Log::debug('Product match found for problem', [
                        'problem' => $problem,
                        'keyword' => $keyword,
                        'product' => $product['productName'],
                    ]);
                    return $product;
                }
            }
        }

        return null;
    }

    /**
     * Get all available products from RAG database for enhancement analysis.
     */
    protected function getAvailableProductsForEnhancement(): array
    {
        $products = AiExternalProduct::active()
            ->forUser($this->userId)
            ->where('ragStatus', AiExternalProduct::RAG_INDEXED)
            ->with(['images' => function ($query) {
                $query->where('delete_status', 'active')
                    ->orderBy('isPrimary', 'desc')
                    ->orderBy('sortOrder', 'asc')
                    ->limit(1);
            }])
            ->get();

        Log::info('Product enhancement: Retrieved RAG products', [
            'userId' => $this->userId,
            'productCount' => $products->count(),
            'productNames' => $products->pluck('productName')->toArray(),
        ]);

        $productList = [];
        foreach ($products as $product) {
            $hasImages = $product->images->count() > 0;
            $imageUrl = $product->images->first()?->image_url;

            $productList[] = [
                'id' => $product->id,
                'productName' => $product->productName,
                'brandName' => $product->brandName,
                'productType' => $product->productType,
                'activeIngredients' => $product->activeIngredients,
                'recommendedDosage' => $product->recommendedDosage,
                'applicationMethod' => $product->applicationMethod,
                'targetPests' => $product->targetPests,
                'targetDiseases' => $product->targetDiseases,
                'description' => $product->description,
                'imageUrl' => $imageUrl,
            ];

            Log::debug('Product enhancement: Product details', [
                'name' => $product->productName,
                'hasImages' => $hasImages,
                'imageUrl' => $imageUrl,
            ]);
        }

        return $productList;
    }

    /**
     * Use AI to analyze if product recommendation is needed and find matching product.
     */
    protected function aiAnalyzeForProductRecommendation(string $aiResponse, array $availableProducts): ?array
    {
        // Build product list for AI (convert arrays to strings)
        $productListText = "";
        foreach ($availableProducts as $index => $product) {
            $productListText .= ($index + 1) . ". **{$product['productName']}**";
            if ($product['brandName'] && $product['brandName'] !== $product['productName']) {
                $productListText .= " ({$product['brandName']})";
            }
            $productListText .= "\n";
            if (!empty($product['productType'])) {
                $productListText .= "   - Type: " . $this->fieldToString($product['productType']) . "\n";
            }
            if (!empty($product['activeIngredients'])) {
                $productListText .= "   - Active Ingredients: " . $this->fieldToString($product['activeIngredients']) . "\n";
            }
            if (!empty($product['targetPests'])) {
                $productListText .= "   - Target Pests: " . $this->fieldToString($product['targetPests']) . "\n";
            }
            if (!empty($product['targetDiseases'])) {
                $productListText .= "   - Target Diseases: " . $this->fieldToString($product['targetDiseases']) . "\n";
            }
            $productListText .= "\n";
        }

        // Include original user question for better matching
        $userQuestion = $this->userMessage ?? '';

        $analysisPrompt = <<<PROMPT
You are a STRICT product recommendation assistant. You must only recommend products that DIRECTLY address the user's specific need.

## USER'S ORIGINAL QUESTION:
{$userQuestion}

## AI RESPONSE TO ANALYZE:
{$aiResponse}

## AVAILABLE PRODUCTS:
{$productListText}

## AGRICULTURAL KNOWLEDGE - CRITICAL:
- GRAIN FILLING / MABIGAT NA BUNGA / MALAKI ANG BUNGA = needs POTASSIUM (K) fertilizers
- VEGETATIVE GROWTH / GREEN LEAVES = needs NITROGEN (N) fertilizers
- ZINC DEFICIENCY (bronzing, small leaves) = needs ZINC fertilizers

## KNOWN PRODUCT CLASSIFICATIONS:
- Innosolve 40-5 = NITROGEN EFFICIENCY enhancer (NOT for grain filling!)
- Zintrac 700 = ZINC fertilizer (NOT for grain filling!)
- MOP, 0-0-60, Potash = POTASSIUM fertilizers (for grain filling)

## STRICT MATCHING RULES:
1. ONLY recommend a product if it DIRECTLY solves what the user asked for
2. GRAIN FILLING / MABIGAT NA BUNGA / MALAKI ANG BUNGA:
   - ONLY recommend Potassium/K fertilizers
   - NEVER recommend Innosolve (it's nitrogen efficiency, NOT potassium!)
   - NEVER recommend Zintrac (it's zinc, NOT potassium!)
3. VEGETATIVE GROWTH: ONLY recommend nitrogen products
4. ZINC DEFICIENCY: ONLY recommend zinc products like Zintrac
5. DO NOT recommend nitrogen products for grain filling
6. DO NOT recommend zinc products unless clear zinc deficiency symptoms

## WHEN TO RETURN needsRecommendation: false (BE STRICT!)
- User asks about grain weight/size/bunga but no POTASSIUM products available
- User asks about grain filling but only N/Zn products available → FALSE
- No product directly matches the user's specific need
- User is asking general questions (not seeking treatment)
- The available products would only be "somewhat helpful" not "directly addressing"

## RESPONSE FORMAT (must be valid JSON):
If a product DIRECTLY matches the user's need:
{
    "needsRecommendation": true,
    "productName": "exact product name from list",
    "reason": "specific explanation of how this product directly addresses the user's need",
    "dosageNote": "relevant dosage info",
    "applicationNote": "relevant application info"
}

If NO product directly matches (BE STRICT - this is often the correct answer):
{
    "needsRecommendation": false,
    "reason": "explanation of why available products don't match the specific need"
}

IMPORTANT: Only respond with valid JSON, no other text. Be STRICT - only recommend if there's a DIRECT match.
PROMPT;

        try {
            // Use GPT for quick analysis (it's faster than Gemini for this task)
            $apiSettings = \App\Models\AiApiSetting::where('usersId', $this->userId)
                ->where('delete_status', 'active')
                ->first();

            if (!$apiSettings || empty($apiSettings->openaiApiKey)) {
                Log::warning('Product enhancement: No OpenAI API key configured');
                return null;
            }

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiSettings->openaiApiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful assistant that analyzes agricultural advice and recommends relevant products. Always respond with valid JSON only.'],
                    ['role' => 'user', 'content' => $analysisPrompt],
                ],
                'temperature' => 0.3,
                'max_tokens' => 500,
            ]);

            if (!$response->successful()) {
                Log::error('Product enhancement: OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $result = $response->json();
            $content = $result['choices'][0]['message']['content'] ?? '';

            // Parse JSON response
            $content = trim($content);
            // Remove markdown code blocks if present
            $content = preg_replace('/^```json\s*/', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);

            $analysis = json_decode($content, true);

            if (!$analysis || !isset($analysis['needsRecommendation'])) {
                Log::warning('Product enhancement: Failed to parse AI analysis', [
                    'content' => $content,
                ]);
                return null;
            }

            if (!$analysis['needsRecommendation']) {
                Log::debug('Product enhancement: AI says no recommendation needed', [
                    'reason' => $analysis['reason'] ?? 'No reason provided',
                ]);
                return null;
            }

            // Find the matching product from our list
            $matchedProduct = null;
            foreach ($availableProducts as $product) {
                if (stripos($product['productName'], $analysis['productName']) !== false ||
                    stripos($analysis['productName'], $product['productName']) !== false) {
                    $matchedProduct = $product;
                    break;
                }
                if ($product['brandName'] &&
                    (stripos($product['brandName'], $analysis['productName']) !== false ||
                     stripos($analysis['productName'], $product['brandName']) !== false)) {
                    $matchedProduct = $product;
                    break;
                }
            }

            if (!$matchedProduct) {
                Log::warning('Product enhancement: AI recommended product not found in list', [
                    'recommended' => $analysis['productName'],
                ]);
                return null;
            }

            return [
                'productName' => $matchedProduct['productName'],
                'brandName' => $matchedProduct['brandName'],
                'productType' => $matchedProduct['productType'],
                'activeIngredients' => $matchedProduct['activeIngredients'],
                'recommendedDosage' => $matchedProduct['recommendedDosage'] ?? $analysis['dosageNote'] ?? null,
                'applicationMethod' => $matchedProduct['applicationMethod'] ?? $analysis['applicationNote'] ?? null,
                'imageUrl' => $matchedProduct['imageUrl'],
                'reason' => $analysis['reason'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Product enhancement: AI analysis failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Append AI-analyzed product recommendation seamlessly to the response.
     * Uses AI to format the product details into clean, readable Tagalog.
     */
    protected function appendAiProductRecommendation(string $response, array $product): string
    {
        // Try to use AI to format the product recommendation nicely
        $formattedRecommendation = $this->formatProductRecommendationWithAI($product);

        if (!empty($formattedRecommendation)) {
            return $response . "\n\n---\n\n" . $formattedRecommendation;
        }

        // Fallback to simple formatting if AI fails
        return $this->appendSimpleProductRecommendation($response, $product);
    }

    /**
     * Use AI (Gemini Flash - cheap model) to format product recommendation nicely.
     */
    protected function formatProductRecommendationWithAI(array $product): ?string
    {
        // Get Gemini API setting
        $geminiSetting = AiApiSetting::where('usersId', $this->userId)
            ->where('provider', AiApiSetting::PROVIDER_GEMINI)
            ->where('isActive', true)
            ->where('delete_status', 'active')
            ->first();

        if (!$geminiSetting || !$geminiSetting->apiKey) {
            Log::debug('Gemini not available for product formatting, using fallback');
            return null;
        }

        // Build product data as readable text for AI
        $productData = "Product Name: " . ($product['productName'] ?? 'Unknown') . "\n";
        if (!empty($product['brandName'])) {
            $productData .= "Brand: " . $product['brandName'] . "\n";
        }
        if (!empty($product['productType'])) {
            $productData .= "Type: " . $this->fieldToString($product['productType']) . "\n";
        }
        if (!empty($product['activeIngredients'])) {
            $ingredients = $product['activeIngredients'];
            if (is_array($ingredients)) {
                $productData .= "Active Ingredients: " . json_encode($ingredients, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            } else {
                $productData .= "Active Ingredients: " . $ingredients . "\n";
            }
        }
        if (!empty($product['recommendedDosage'])) {
            $productData .= "Dosage: " . $this->fieldToString($product['recommendedDosage']) . "\n";
        }
        if (!empty($product['applicationMethod'])) {
            $productData .= "Application Method: " . $this->fieldToString($product['applicationMethod']) . "\n";
        }
        if (!empty($product['reason'])) {
            $productData .= "Reason for recommendation: " . $this->fieldToString($product['reason']) . "\n";
        }

        $prompt = <<<PROMPT
Ikaw ay isang agricultural product formatter. I-format ang product recommendation na ito para sa mga magsasakang Pilipino.

PRODUCT DATA:
{$productData}

INSTRUCTIONS:
1. Isulat sa TAGALOG (English para sa technical terms lang)
2. Gamitin ang format na ito:

🎯 INIREREKOMENDANG PRODUKTO

[Product Name] ([Brand])

[Isang maikling paliwanag kung bakit ito ang rekomendasyon - 1-2 sentences]

Uri: [type in readable form, e.g., "Foliar Fertilizer" not "fertilizer_foliar"]
Aktibong Sangkap: [name] - [concentration]
  Gamit: [purpose in simple Tagalog]
Dosis: [if available]
Paraan ng Pag-apply: [if available]

⚠️ Paalala: Sundin po ang tamang dosage at paraan ng pag-apply na nakasaad sa label ng produkto.

3. HUWAG gumamit ng markdown formatting tulad ng ** o _ o `
4. HUWAG gumamit ng JSON format
5. Gawing simple at madaling basahin
6. Kung walang data para sa isang field, huwag isama
7. Maximum 150 words lang

I-format ang product recommendation:
PROMPT;

        try {
            // Use gemini-2.0-flash-lite (cheapest and fastest)
            $model = 'gemini-2.0-flash-lite';
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $geminiSetting->apiKey;

            $response = Http::timeout(15)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'maxOutputTokens' => 300,
                        'temperature' => 0.3,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $formatted = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                // Track token usage
                $usageMetadata = $data['usageMetadata'] ?? [];
                $inputTokens = $usageMetadata['promptTokenCount'] ?? 0;
                $outputTokens = $usageMetadata['candidatesTokenCount'] ?? 0;
                $this->trackTokenUsage('gemini', 'product_format', $inputTokens, $outputTokens, $model);

                if (!empty($formatted)) {
                    $this->logFlowStep('Product Format', 'AI formatted product recommendation');
                    return trim($formatted);
                }
            }
        } catch (\Exception $e) {
            Log::warning('AI product formatting failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Simple fallback formatting for product recommendation (no AI).
     */
    protected function appendSimpleProductRecommendation(string $response, array $product): string
    {
        $recommendation = "\n\n---\n\n";
        $recommendation .= "🎯 INIREREKOMENDANG PRODUKTO\n\n";

        $recommendation .= $product['productName'];
        if (!empty($product['brandName']) && $product['brandName'] !== $product['productName']) {
            $recommendation .= " (" . $product['brandName'] . ")";
        }
        $recommendation .= "\n\n";

        if (!empty($product['reason'])) {
            $recommendation .= $this->fieldToString($product['reason']) . "\n\n";
        }

        if (!empty($product['productType'])) {
            $recommendation .= "Uri: " . $this->formatProductType($product['productType']) . "\n";
        }
        if (!empty($product['activeIngredients'])) {
            $recommendation .= "Aktibong Sangkap: " . $this->formatActiveIngredientsSimple($product['activeIngredients']) . "\n";
        }
        if (!empty($product['recommendedDosage'])) {
            $recommendation .= "Dosis: " . $this->fieldToString($product['recommendedDosage']) . "\n";
        }
        if (!empty($product['applicationMethod'])) {
            $recommendation .= "Paraan ng Pag-apply: " . $this->fieldToString($product['applicationMethod']) . "\n";
        }

        $recommendation .= "\n⚠️ Paalala: Sundin po ang tamang dosage at paraan ng pag-apply na nakasaad sa label ng produkto.\n";

        return $response . $recommendation;
    }

    /**
     * Simple formatting for active ingredients (no markdown).
     */
    protected function formatActiveIngredientsSimple($activeIngredients): string
    {
        if (is_string($activeIngredients)) {
            $decoded = json_decode($activeIngredients, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $activeIngredients = $decoded;
            } else {
                return $activeIngredients;
            }
        }

        if (!is_array($activeIngredients)) {
            return (string) $activeIngredients;
        }

        // Single ingredient object
        if (isset($activeIngredients['name'])) {
            $result = $activeIngredients['name'];
            if (!empty($activeIngredients['concentration'])) {
                $result .= " (" . $activeIngredients['concentration'] . ")";
            }
            return $result;
        }

        // Array of ingredients
        $parts = [];
        foreach ($activeIngredients as $ingredient) {
            if (is_array($ingredient) && isset($ingredient['name'])) {
                $part = $ingredient['name'];
                if (!empty($ingredient['concentration'])) {
                    $part .= " (" . $ingredient['concentration'] . ")";
                }
                $parts[] = $part;
            } else {
                $parts[] = $this->fieldToString($ingredient);
            }
        }

        return implode(', ', $parts);
    }

    /**
     * Format product type for display.
     * Converts "fertilizer_foliar" to "Foliar Fertilizer"
     */
    protected function formatProductType($productType): string
    {
        $type = $this->fieldToString($productType);

        // Map common product types to readable Tagalog/English
        $typeMap = [
            'fertilizer_foliar' => 'Foliar Fertilizer (Spray sa Dahon)',
            'fertilizer_granular' => 'Granular Fertilizer (Abunog Butil)',
            'fertilizer_liquid' => 'Liquid Fertilizer (Patabang Likido)',
            'pesticide' => 'Pesticide (Pamuksa ng Peste)',
            'insecticide' => 'Insecticide (Pamuksa ng Insekto)',
            'fungicide' => 'Fungicide (Pamuksa ng Fungus)',
            'herbicide' => 'Herbicide (Pamuksa ng Damo)',
            'growth_regulator' => 'Growth Regulator (Pantulong sa Paglaki)',
            'soil_conditioner' => 'Soil Conditioner (Pangkondisyon ng Lupa)',
        ];

        // Check if we have a mapping
        $lowerType = strtolower($type);
        if (isset($typeMap[$lowerType])) {
            return $typeMap[$lowerType];
        }

        // Fallback: Convert snake_case to Title Case
        return ucwords(str_replace('_', ' ', $type));
    }

    /**
     * Check if user asked for product recommendation in their message.
     */
    protected function userAskedForProductRecommendation(): bool
    {
        $patterns = [
            '/\b(recommend|suggest|rekomenda|irerekomenda|mairekomenda|marerekomenda)\b/i',
            '/\b(gamot|spray|pataba|abono|fertilizer|pesticide|insecticide|fungicide)\b/i',
            '/\b(ano.*gamit|anong.*gamit|pwede.*gamit|puwede.*gamit)\b/i',
            '/\b(may.*maganda|meron.*maganda|ano.*maganda)\b.*(gamot|spray|pataba)/i',
            '/\b(paano.*gamutin|paano.*solusyon|ano.*solusyon)\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $this->userMessage)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if response already contains a product name from our database.
     */
    protected function responseContainsProductFromDatabase(string $response): bool
    {
        $products = AiExternalProduct::active()
            ->forUser($this->userId)
            ->where('ragStatus', AiExternalProduct::RAG_INDEXED)
            ->get(['productName', 'brandName']);

        foreach ($products as $product) {
            if (!empty($product->productName) && stripos($response, $product->productName) !== false) {
                return true;
            }
            if (!empty($product->brandName) && $product->brandName !== $product->productName
                && stripos($response, $product->brandName) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect the diagnosed problem from the AI response.
     * Returns the problem type (e.g., 'zinc deficiency', 'nitrogen deficiency', 'fall armyworm', etc.)
     */
    protected function detectDiagnosedProblem(string $response): ?string
    {
        // Deficiency patterns
        $deficiencyPatterns = [
            '/\b(zinc|Zn)\s*(deficiency|kakulangan)/i' => 'zinc deficiency',
            '/\bkakulangan\s*(sa|ng)\s*(zinc|Zn)\b/i' => 'zinc deficiency',
            '/\b(nitrogen|N)\s*(deficiency|kakulangan)/i' => 'nitrogen deficiency',
            '/\bkakulangan\s*(sa|ng)\s*(nitrogen|N)\b/i' => 'nitrogen deficiency',
            '/\b(phosphorus|P)\s*(deficiency|kakulangan)/i' => 'phosphorus deficiency',
            '/\b(potassium|K)\s*(deficiency|kakulangan)/i' => 'potassium deficiency',
            '/\b(magnesium|Mg)\s*(deficiency|kakulangan)/i' => 'magnesium deficiency',
            '/\b(iron|Fe)\s*(deficiency|kakulangan)/i' => 'iron deficiency',
            '/\b(boron|B)\s*(deficiency|kakulangan)/i' => 'boron deficiency',
        ];

        foreach ($deficiencyPatterns as $pattern => $problem) {
            if (preg_match($pattern, $response)) {
                return $problem;
            }
        }

        // Pest patterns
        $pestPatterns = [
            '/\b(fall armyworm|FAW|armyworm)\b/i' => 'fall armyworm',
            '/\b(corn borer|stem borer)\b/i' => 'corn borer',
            '/\b(rice bug|atangya)\b/i' => 'rice bug',
            '/\b(brown planthopper|BPH)\b/i' => 'brown planthopper',
            '/\b(golden snail|kuhol|apple snail)\b/i' => 'golden snail',
            '/\b(aphid|aphis)\b/i' => 'aphids',
        ];

        foreach ($pestPatterns as $pattern => $problem) {
            if (preg_match($pattern, $response)) {
                return $problem;
            }
        }

        // Disease patterns
        $diseasePatterns = [
            '/\b(tungro)\b/i' => 'tungro',
            '/\b(blast|rice blast)\b/i' => 'rice blast',
            '/\b(bacterial leaf blight|BLB)\b/i' => 'bacterial leaf blight',
            '/\b(sheath blight)\b/i' => 'sheath blight',
            '/\b(downy mildew)\b/i' => 'downy mildew',
        ];

        foreach ($diseasePatterns as $pattern => $problem) {
            if (preg_match($pattern, $response)) {
                return $problem;
            }
        }

        return null;
    }

    /**
     * Find products from our database that can solve the diagnosed problem.
     */
    protected function findProductsForProblem(string $problem): array
    {
        $products = AiExternalProduct::active()
            ->forUser($this->userId)
            ->where('ragStatus', AiExternalProduct::RAG_INDEXED)
            ->with(['images' => function ($query) {
                $query->where('delete_status', 'active')
                    ->orderBy('isPrimary', 'desc')
                    ->orderBy('sortOrder', 'asc')
                    ->limit(1);
            }])
            ->get();

        $matchingProducts = [];

        // Map problem types to keywords that should match in product descriptions
        $problemKeywords = [
            'zinc deficiency' => ['zinc', 'Zn', 'zintrac', 'micronutrient'],
            'nitrogen deficiency' => ['nitrogen', 'N', 'urea', 'ammoni'],
            'phosphorus deficiency' => ['phosphorus', 'P', 'phosphate'],
            'potassium deficiency' => ['potassium', 'K', 'potash', 'muriate'],
            'magnesium deficiency' => ['magnesium', 'Mg'],
            'iron deficiency' => ['iron', 'Fe', 'ferrous'],
            'boron deficiency' => ['boron', 'B'],
            'fall armyworm' => ['armyworm', 'insecticide', 'chlorantraniliprole', 'emamectin', 'spinetoram'],
            'corn borer' => ['borer', 'insecticide', 'chlorantraniliprole', 'fipronil'],
            'rice bug' => ['rice bug', 'insecticide', 'cypermethrin', 'lambda'],
            'brown planthopper' => ['planthopper', 'BPH', 'insecticide', 'imidacloprid'],
            'golden snail' => ['snail', 'kuhol', 'molluscicide', 'metaldehyde', 'niclosamide'],
            'aphids' => ['aphid', 'insecticide', 'imidacloprid', 'thiamethoxam'],
            'tungro' => ['tungro', 'insecticide', 'imidacloprid'], // Controlled by vector control
            'rice blast' => ['blast', 'fungicide', 'tricyclazole', 'azoxystrobin'],
            'bacterial leaf blight' => ['blight', 'bactericide', 'copper'],
            'sheath blight' => ['sheath', 'fungicide', 'hexaconazole', 'propiconazole'],
            'downy mildew' => ['mildew', 'fungicide', 'metalaxyl', 'mancozeb'],
        ];

        $keywords = $problemKeywords[$problem] ?? [];

        if (empty($keywords)) {
            return [];
        }

        foreach ($products as $product) {
            // Build searchable text from product
            $searchText = strtolower(
                ($product->productName ?? '') . ' ' .
                ($product->brandName ?? '') . ' ' .
                ($product->description ?? '') . ' ' .
                ($product->activeIngredients ?? '') . ' ' .
                ($product->productType ?? '') . ' ' .
                ($product->targetPests ?? '') . ' ' .
                ($product->targetDiseases ?? '')
            );

            // Check if any keyword matches
            foreach ($keywords as $keyword) {
                if (stripos($searchText, strtolower($keyword)) !== false) {
                    $matchingProducts[] = $product;
                    break; // Only add once per product
                }
            }
        }

        // Limit to top 2 products
        return array_slice($matchingProducts, 0, 2);
    }

    /**
     * Append product recommendation to the response.
     * Uses AI formatting for clean output.
     */
    protected function appendProductRecommendation(string $response, array $products, string $problem): string
    {
        $recommendation = "\n\n---\n\n";
        $recommendation .= "🎯 INIREREKOMENDANG PRODUKTO PARA SA " . strtoupper($problem) . "\n\n";

        foreach ($products as $index => $product) {
            $num = $index + 1;

            // Try to use AI formatting for each product
            $productArray = [
                'productName' => $product->productName,
                'brandName' => $product->brandName,
                'productType' => $product->productType,
                'activeIngredients' => $product->activeIngredients,
                'recommendedDosage' => $product->recommendedDosage,
                'applicationMethod' => $product->applicationMethod,
                'reason' => "Para sa {$problem}",
            ];

            $aiFormatted = $this->formatProductRecommendationWithAI($productArray);

            if (!empty($aiFormatted)) {
                $recommendation .= $aiFormatted . "\n\n";
            } else {
                // Fallback to simple formatting
                $recommendation .= "{$num}. {$product->productName}";
                if ($product->brandName && $product->brandName !== $product->productName) {
                    $recommendation .= " ({$product->brandName})";
                }
                $recommendation .= "\n";

                if ($product->productType) {
                    $recommendation .= "   Uri: " . $this->formatProductType($product->productType) . "\n";
                }
                if ($product->activeIngredients) {
                    $recommendation .= "   Aktibong Sangkap: " . $this->formatActiveIngredientsSimple($product->activeIngredients) . "\n";
                }
                if ($product->recommendedDosage) {
                    $recommendation .= "   Dosis: {$product->recommendedDosage}\n";
                }
                if ($product->applicationMethod) {
                    $recommendation .= "   Paraan ng Pag-apply: {$product->applicationMethod}\n";
                }
                $recommendation .= "\n";
            }
        }

        $recommendation .= "💡 Paalala: Sundin po ang tamang dosage at paraan ng pag-apply na nakasaad sa label ng produkto. Kung hindi sigurado, kumonsulta sa agricultural technician.\n";

        return $response . $recommendation;
    }

    /**
     * Perform multi-AI search using both Gemini (Google Search) and GPT (web search).
     * Combines results from both for more comprehensive, accurate data.
     *
     * @param string $prompt The search query/question
     * @param string $systemPrompt The system prompt
     * @return string Combined and formatted response
     */
    protected function performMultiAISearch(string $prompt, string $systemPrompt = ''): string
    {
        Log::info('=== STARTING GOOGLE SEARCH (Gemini) ===', [
            'promptPreview' => substr($prompt, 0, 200),
        ]);

        // Log flow step
        $this->logFlowStep('Starting Google Search', 'Using Gemini with Google Search grounding');

        // Get Gemini API setting for Google Search
        $geminiSetting = AiApiSetting::where('usersId', $this->userId)
            ->where('provider', AiApiSetting::PROVIDER_GEMINI)
            ->where('isActive', true)
            ->where('delete_status', 'active')
            ->first();

        if (!$geminiSetting) {
            Log::warning('No Gemini API configured');
            $this->logFlowStep('Error', 'No Gemini API configured');
            return 'Hindi ko po nakuha ang impormasyon. Walang Google Search API na naka-configure.';
        }

        // Detect crop type and build search instructions
        $cropInfo = $this->detectCropAndBrands($prompt);
        $searchPrompt = $this->buildTargetedSearchInstructions($cropInfo, $prompt);

        // Log the user's original question and the full AI prompt
        $this->logSearchQuery($prompt); // User's actual question
        $this->logAiPrompt($searchPrompt); // Full prompt sent to AI
        $this->logAiProvider('Gemini (Google Search)');

        // Log all detected crops (supports multiple)
        $cropsDetected = !empty($cropInfo['crops']) ? implode(', ', $cropInfo['crops']) : 'general';
        $this->logFlowStep('Built search instructions', 'Crop type(s): ' . $cropsDetected);

        Log::info('Calling Gemini with Google Search', [
            'crops' => $cropInfo['crops'] ?? [],
            'cropType' => $cropsDetected,
            'searchPromptLength' => strlen($searchPrompt),
        ]);

        try {
            $geminiResponse = $this->callGeminiAPI($geminiSetting, $searchPrompt, [], $systemPrompt);

            if (!empty($geminiResponse)) {
                Log::info('Gemini Google Search response received', [
                    'length' => strlen($geminiResponse),
                    'preview' => substr($geminiResponse, 0, 500),
                ]);

                // Log the AI response
                $this->logAiResponse($geminiResponse);
                $this->logFlowStep('Received Gemini response', 'Length: ' . strlen($geminiResponse) . ' chars');

                return $this->stripMarkdownFormatting($geminiResponse);
            }
        } catch (\Exception $e) {
            Log::warning('Gemini Google Search failed: ' . $e->getMessage());
            $this->logFlowStep('Error', 'Gemini failed: ' . $e->getMessage());
        }

        // Fallback
        Log::warning('No response received from Google Search');
        $this->logFlowStep('Error', 'No response received from Google Search');
        return 'Hindi ko po nakuha ang impormasyon. Subukan po ulit mamaya.';
    }

    /**
     * Analyze the user's question to determine the appropriate sorting criteria.
     * This ensures combined results are ordered in the most useful way.
     *
     * @param string $question The user's question
     * @return array Contains 'criteria' (what to sort by) and 'instruction' (detailed sorting instructions)
     */
    protected function analyzeQuestionForSorting(string $question): array
    {
        $question = strtolower($question);

        // Check for yield/harvest/production-related questions (most common for agriculture)
        if (preg_match('/\b(yield|ani|harvest|mataas.*ani|high.*yield|pinakamataas|highest|top|best|maganda|pinakamag|production|produkto|mt\/ha|metric ton|tonelada)\b/i', $question)) {
            return [
                'criteria' => 'yield potential (highest to lowest)',
                'instruction' => "SORT BY YIELD POTENTIAL - Highest to Lowest:\n" .
                    "- List items in DESCENDING order by yield (MT/ha or kg/ha)\n" .
                    "- Highest yielding variety/product should be #1\n" .
                    "- Include the yield value for each item (e.g., '15 MT/ha')\n" .
                    "- If yield is not specified, place those items at the end"
            ];
        }

        // Check for price/cost-related questions (handles Filipino prefixes like "pinaka-")
        if (preg_match('/(price|presyo|mura|cheap|affordable|mahal|expensive|cost|halaga|magkano|budget|tipid|pinakamura|pinakamahal)/i', $question)) {
            // Determine if they want cheapest or most expensive
            if (preg_match('/(mura|cheap|affordable|budget|tipid|lowest|pinakamura|murang|mas\s*mura)/i', $question)) {
                return [
                    'criteria' => 'price (lowest to highest)',
                    'instruction' => "SORT BY PRICE - Lowest to Highest:\n" .
                        "- List items in ASCENDING order by price\n" .
                        "- Most affordable option should be #1\n" .
                        "- Include the price for each item when available\n" .
                        "- If price is not specified, place those items at the end"
                ];
            } else {
                return [
                    'criteria' => 'price (highest to lowest)',
                    'instruction' => "SORT BY PRICE - Highest to Lowest:\n" .
                        "- List items in DESCENDING order by price\n" .
                        "- Premium/most expensive option should be #1\n" .
                        "- Include the price for each item when available\n" .
                        "- If price is not specified, place those items at the end"
                ];
            }
        }

        // Check for maturity/harvest time questions
        if (preg_match('/\b(maturity|mature|maaga|early|late|days|araw|duration|tagal|mabilis|fast|quick)\b/i', $question)) {
            if (preg_match('/\b(maaga|early|mabilis|fast|quick|shortest|pinakamabilis)\b/i', $question)) {
                return [
                    'criteria' => 'maturity period (shortest to longest)',
                    'instruction' => "SORT BY MATURITY PERIOD - Shortest to Longest:\n" .
                        "- List items in ASCENDING order by days to maturity\n" .
                        "- Earliest maturing variety should be #1\n" .
                        "- Include maturity period (e.g., '95-100 days')\n" .
                        "- If maturity is not specified, place those items at the end"
                ];
            } else {
                return [
                    'criteria' => 'maturity period (longest to shortest)',
                    'instruction' => "SORT BY MATURITY PERIOD - Longest to Shortest:\n" .
                        "- List items in DESCENDING order by days to maturity\n" .
                        "- Latest maturing variety should be #1\n" .
                        "- Include maturity period (e.g., '120-125 days')"
                ];
            }
        }

        // Check for resistance/tolerance questions
        if (preg_match('/\b(resistant|resistance|tolerant|tolerance|immune|lumalaban|matibay|matatag|pest|sakit|disease|drought|baha|flood|bagyo|storm)\b/i', $question)) {
            return [
                'criteria' => 'resistance/tolerance level',
                'instruction' => "SORT BY RESISTANCE/TOLERANCE:\n" .
                    "- List items with the MOST resistances/tolerances first\n" .
                    "- Prioritize varieties with multiple resistance traits\n" .
                    "- Specify what each variety is resistant to (e.g., 'Fall Armyworm, drought')\n" .
                    "- Group by resistance type if relevant to the question"
            ];
        }

        // Check for popularity/recommendation questions (handles Filipino prefixes)
        if (preg_match('/(popular|sikat|pinakasikat|uso|common|karaniwan|recommended|inirerekomenda|trusted|paborito|favorite|kilala|pinakakilala|bantog|tanyag)/i', $question)) {
            return [
                'criteria' => 'popularity/recommendation level',
                'instruction' => "SORT BY POPULARITY/RECOMMENDATION:\n" .
                    "- List most recommended/popular items first\n" .
                    "- Consider: farmer preferences, sales volume, expert recommendations\n" .
                    "- Mention why each is popular/recommended\n" .
                    "- Include any awards, derby wins, or endorsements"
            ];
        }

        // Check for quality-related questions
        if (preg_match('/\b(quality|kalidad|grade|premium|maganda.*klase|best.*quality)\b/i', $question)) {
            return [
                'criteria' => 'quality rating (highest to lowest)',
                'instruction' => "SORT BY QUALITY:\n" .
                    "- List highest quality items first\n" .
                    "- Consider: grain quality, taste, appearance, market value\n" .
                    "- Mention quality characteristics for each item"
            ];
        }

        // Default: Sort by overall recommendation/relevance (general usefulness)
        return [
            'criteria' => 'overall recommendation (best to good)',
            'instruction' => "SORT BY OVERALL RECOMMENDATION:\n" .
                "- List the BEST overall options first based on the question context\n" .
                "- Consider multiple factors: yield, cost-effectiveness, availability, reliability\n" .
                "- Most highly recommended item should be #1\n" .
                "- Explain why each item is recommended"
        ];
    }

    /**
     * Detect crop type and relevant brands from the user's question.
     * This helps build targeted search queries for official sources.
     *
     * @param string $prompt The user's question
     * @return array Crop info with type and brand list
     */
    protected function detectCropAndBrands(string $prompt): array
    {
        $prompt = strtolower($prompt);

        $info = [
            'crop' => null,        // Primary crop (for backward compatibility)
            'crops' => [],         // Array of all detected crops (supports multiple)
            'brands' => [],
            'searchTerms' => [],
        ];

        // Detect corn/mais
        if (preg_match('/\b(corn|mais|maize)\b/i', $prompt)) {
            $info['crops'][] = 'corn';
            $info['brands'] = array_merge($info['brands'], [
                ['name' => 'NK', 'varieties' => 'NK6414, NK6410, NK7676, NK8840', 'company' => 'Syngenta', 'site' => 'syngenta.com.ph'],
                ['name' => 'DEKALB', 'varieties' => 'DK8282S, DK9108, DK8118S, DK6919S', 'company' => 'Bayer', 'site' => 'cropscience.bayer.com.ph'],
                ['name' => 'Pioneer', 'varieties' => 'P3396, P4546, 30T80', 'company' => 'Corteva/Pioneer', 'site' => 'pioneer.com'],
                ['name' => 'Bioseed', 'varieties' => '9909, 9818', 'company' => 'Bioseed', 'site' => 'bioseed.com.ph'],
            ]);
            $info['searchTerms'] = array_merge($info['searchTerms'], [
                'NK6414 Syngenta corn yield Philippines MT/ha',
                'DEKALB corn varieties Philippines highest yield',
                'Pioneer corn hybrid Philippines',
                'top corn varieties Philippines 2024 2025',
            ]);
        }

        // Detect rice/palay
        if (preg_match('/\b(rice|palay|bigas)\b/i', $prompt)) {
            $info['crops'][] = 'rice';
            $info['brands'] = array_merge($info['brands'], [
                ['name' => 'SL', 'varieties' => 'SL-8H, SL-18H, SL-19H, SL-20H', 'company' => 'SL Agritech', 'site' => 'slagritech.com'],
                ['name' => 'Arize', 'varieties' => 'Arize Bigante, Arize Prima', 'company' => 'Bayer', 'site' => 'cropscience.bayer.com.ph'],
                ['name' => 'Mestizo', 'varieties' => 'Mestizo 1, Mestizo 2', 'company' => 'Syngenta', 'site' => 'syngenta.com.ph'],
                ['name' => 'NSIC', 'varieties' => 'NSIC Rc222, NSIC Rc216, NSIC Rc160', 'company' => 'PhilRice', 'site' => 'philrice.gov.ph'],
            ]);
            $info['searchTerms'] = array_merge($info['searchTerms'], [
                'SL-8H hybrid rice yield Philippines',
                'highest yield rice variety Philippines 2024',
                'PhilRice recommended varieties',
            ]);
        }

        // Set primary crop (first detected) for backward compatibility
        if (!empty($info['crops'])) {
            $info['crop'] = $info['crops'][0];
        }

        return $info;
    }

    /**
     * @deprecated No longer used in new 6-step flow. Kept for backwards compatibility.
     * Detect the specific agricultural need from user's question.
     * This is used to validate if RAG products are actually relevant.
     *
     * @param string $message The user's question
     * @return array ['need' => string, 'requiredNutrient' => string|null, 'productTypes' => array]
     */
    protected function detectAgriculturalNeed(string $message): array
    {
        $message = strtolower($message);

        $need = [
            'type' => 'general',           // Type of need (grain_filling, zinc_deficiency, pest_control, etc.)
            'requiredNutrient' => null,    // Required nutrient (K, N, Zn, etc.)
            'productTypes' => [],          // Types of products that can address this need
            'excludeTypes' => [],          // Types of products that should NOT be recommended
        ];

        // GRAIN FILLING / HEAVY GRAINS - needs POTASSIUM (K)
        $grainFillingPatterns = [
            '/\b(mabigat|malaki|heavy|large|big)\b.*(butil|bunga|grain|seed|palay|mais)/i',
            '/\b(butil|bunga|grain|seed)\b.*(mabigat|malaki|heavy|large|big)/i',
            '/\b(grain.?fill|pagbuo ng butil|pampabunga|pampabigat)/i',
            '/\b(ani|harvest|yield)\b.*(mabigat|malaki|increase|dagdagan)/i',
            '/\b(flowering|reproductive|panicle|tassel).*(stage|yugto)/i',
        ];

        foreach ($grainFillingPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $need['type'] = 'grain_filling';
                $need['requiredNutrient'] = 'K';  // Potassium
                $need['productTypes'] = ['potassium', 'potash', 'K fertilizer', 'MOP', '0-0-60', 'grain filling'];
                $need['excludeTypes'] = ['nitrogen', 'urea', 'zinc', 'N fertilizer', 'Zn fertilizer', 'nitrification inhibitor'];

                Log::info('Agricultural need detected: GRAIN FILLING', [
                    'message' => substr($message, 0, 100),
                    'requiredNutrient' => 'K (Potassium)',
                ]);
                return $need;
            }
        }

        // VEGETATIVE GROWTH - needs NITROGEN (N)
        $vegetativePatterns = [
            '/\b(vegetative|tumubo|lumaki|growth|pag-unlad)\b.*(dahon|leaf|tanim|plant)/i',
            '/\b(dahon|leaf)\b.*(dilaw|yellow|chlor|kulang)/i',
            '/\b(nitrogen|N).*(deficien|kulang|kakulangan)/i',
            '/\b(seedling|binhi|transplant|bagong tanim)/i',
        ];

        foreach ($vegetativePatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $need['type'] = 'vegetative_growth';
                $need['requiredNutrient'] = 'N';  // Nitrogen
                $need['productTypes'] = ['nitrogen', 'urea', 'ammonium', 'N fertilizer', 'complete fertilizer'];
                $need['excludeTypes'] = ['potassium', 'zinc', 'insecticide', 'fungicide'];

                Log::info('Agricultural need detected: VEGETATIVE GROWTH', [
                    'message' => substr($message, 0, 100),
                    'requiredNutrient' => 'N (Nitrogen)',
                ]);
                return $need;
            }
        }

        // ZINC DEFICIENCY - needs ZINC (Zn)
        $zincPatterns = [
            '/\b(zinc|Zn).*(deficien|kulang|kakulangan)/i',
            '/\b(bronz|kulay tanso|brown spot).*(dahon|leaf)/i',
            '/\b(dahon|leaf).*(maliit|stunted|dwarf|bansot)/i',
            '/\b(kink|baluktot).*(dahon|leaf)/i',
        ];

        foreach ($zincPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $need['type'] = 'zinc_deficiency';
                $need['requiredNutrient'] = 'Zn';  // Zinc
                $need['productTypes'] = ['zinc', 'Zn fertilizer', 'zinc sulfate', 'Zintrac'];
                $need['excludeTypes'] = ['nitrogen', 'potassium', 'urea', 'insecticide'];

                Log::info('Agricultural need detected: ZINC DEFICIENCY', [
                    'message' => substr($message, 0, 100),
                    'requiredNutrient' => 'Zn (Zinc)',
                ]);
                return $need;
            }
        }

        // PEST CONTROL - needs INSECTICIDE
        $pestPatterns = [
            '/\b(pest|insect|uod|worm|kulisap|armyworm|borer|planthopper|aphid|thrip)/i',
            '/\b(insecticid|pesticide|gamot sa peste|panlaban sa peste)/i',
        ];

        foreach ($pestPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $need['type'] = 'pest_control';
                $need['requiredNutrient'] = null;
                $need['productTypes'] = ['insecticide', 'pesticide', 'pest control'];
                $need['excludeTypes'] = ['fertilizer', 'nitrogen', 'zinc', 'potassium', 'fungicide'];

                Log::info('Agricultural need detected: PEST CONTROL', [
                    'message' => substr($message, 0, 100),
                ]);
                return $need;
            }
        }

        // DISEASE CONTROL - needs FUNGICIDE
        $diseasePatterns = [
            '/\b(disease|sakit|fungus|fungal|blast|blight|rust|mildew)/i',
            '/\b(fungicid|gamot sa sakit|panlaban sa sakit)/i',
        ];

        foreach ($diseasePatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $need['type'] = 'disease_control';
                $need['requiredNutrient'] = null;
                $need['productTypes'] = ['fungicide', 'disease control'];
                $need['excludeTypes'] = ['fertilizer', 'nitrogen', 'zinc', 'potassium', 'insecticide'];

                Log::info('Agricultural need detected: DISEASE CONTROL', [
                    'message' => substr($message, 0, 100),
                ]);
                return $need;
            }
        }

        // WEED CONTROL - needs HERBICIDE
        $weedPatterns = [
            '/\b(weed|damo|grass|unwanted plant|herbicid)/i',
            '/\b(gamot sa damo|panlaban sa damo)/i',
        ];

        foreach ($weedPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $need['type'] = 'weed_control';
                $need['requiredNutrient'] = null;
                $need['productTypes'] = ['herbicide', 'weed killer'];
                $need['excludeTypes'] = ['fertilizer', 'insecticide', 'fungicide'];

                Log::info('Agricultural need detected: WEED CONTROL', [
                    'message' => substr($message, 0, 100),
                ]);
                return $need;
            }
        }

        // Default: general question - no specific validation
        Log::debug('Agricultural need: GENERAL (no specific need detected)', [
            'message' => substr($message, 0, 100),
        ]);
        return $need;
    }

    /**
     * @deprecated No longer used in new 6-step flow. Kept for backwards compatibility.
     * Validate if RAG products are relevant to the user's specific agricultural need.
     * Returns filtered RAG content with only relevant products, or null if no match.
     *
     * @param string $ragResult The RAG result containing products
     * @param string $userMessage The user's original question
     * @return string|null Filtered RAG content or null if no relevant products
     */
    protected function validateRagProductRelevance(string $ragResult, string $userMessage): ?string
    {
        // Detect what the user actually needs
        $need = $this->detectAgriculturalNeed($userMessage);

        // If it's a general question, allow RAG products through (AI will format them)
        if ($need['type'] === 'general') {
            Log::debug('RAG validation: General question - allowing all products');
            return $ragResult;
        }

        // Check if RAG contains products that match the specific need
        $hasRelevantProduct = false;
        $irrelevantProducts = [];

        // List of known products and their types
        $productClassifications = [
            // Nitrogen products - for vegetative growth
            'innosolve' => ['type' => 'nitrogen', 'nutrient' => 'N', 'use' => 'nitrogen efficiency'],
            'urea' => ['type' => 'nitrogen', 'nutrient' => 'N', 'use' => 'vegetative growth'],
            'ammonium' => ['type' => 'nitrogen', 'nutrient' => 'N', 'use' => 'vegetative growth'],
            // Zinc products - for zinc deficiency
            'zintrac' => ['type' => 'zinc', 'nutrient' => 'Zn', 'use' => 'zinc deficiency'],
            'zinc sulfate' => ['type' => 'zinc', 'nutrient' => 'Zn', 'use' => 'zinc deficiency'],
            // Potassium products - for grain filling
            'mop' => ['type' => 'potassium', 'nutrient' => 'K', 'use' => 'grain filling'],
            'muriate of potash' => ['type' => 'potassium', 'nutrient' => 'K', 'use' => 'grain filling'],
            'potash' => ['type' => 'potassium', 'nutrient' => 'K', 'use' => 'grain filling'],
            '0-0-60' => ['type' => 'potassium', 'nutrient' => 'K', 'use' => 'grain filling'],
            'sop' => ['type' => 'potassium', 'nutrient' => 'K', 'use' => 'grain filling'],
        ];

        $ragLower = strtolower($ragResult);

        foreach ($productClassifications as $productName => $productInfo) {
            if (strpos($ragLower, $productName) !== false) {
                // Check if this product type is relevant to the user's need
                $isRelevant = false;

                foreach ($need['productTypes'] as $neededType) {
                    if (stripos($productInfo['type'], $neededType) !== false ||
                        stripos($productInfo['use'], $neededType) !== false ||
                        ($need['requiredNutrient'] && $productInfo['nutrient'] === $need['requiredNutrient'])) {
                        $isRelevant = true;
                        break;
                    }
                }

                // Check if this product should be excluded
                foreach ($need['excludeTypes'] as $excludeType) {
                    if (stripos($productInfo['type'], $excludeType) !== false ||
                        stripos($productInfo['use'], $excludeType) !== false) {
                        $isRelevant = false;
                        $irrelevantProducts[] = $productName;
                        break;
                    }
                }

                if ($isRelevant) {
                    $hasRelevantProduct = true;
                    Log::info('RAG validation: Found RELEVANT product', [
                        'product' => $productName,
                        'productType' => $productInfo['type'],
                        'userNeed' => $need['type'],
                    ]);
                }
            }
        }

        // If no relevant products found, store the need for specific recommendations and return null
        if (!$hasRelevantProduct) {
            // Store the detected need so output can recommend specific products
            $this->detectedAgriculturalNeed = $need;

            Log::warning('RAG validation: NO RELEVANT PRODUCTS for user need', [
                'userNeed' => $need['type'],
                'requiredNutrient' => $need['requiredNutrient'],
                'irrelevantProductsFound' => $irrelevantProducts,
                'recommendation' => 'Excluding RAG products - will provide specific product recommendations',
                'storedNeed' => $need['type'],
            ]);
            return null;
        }

        Log::info('RAG validation: Relevant products found - including in response');
        return $ragResult;
    }

    /**
     * Build targeted search instructions based on crop type and brands.
     * This tells the AI exactly WHICH websites to search and WHAT to look for.
     *
     * @param array $cropInfo Detected crop and brand information
     * @param string $originalPrompt The original user question
     * @return string Detailed search instructions
     */
    protected function buildTargetedSearchInstructions(array $cropInfo, string $originalPrompt): string
    {
        $currentYear = date('Y');

        // ULTRATHINK MODE: Enhanced reasoning with comprehensive analysis
        $instructions = "=== ULTRATHINK MODE: COMPREHENSIVE AGRICULTURAL RESEARCH ===\n\n";
        $instructions .= "TASK: Thoroughly research and analyze this agricultural question using Google Search.\n";
        $instructions .= "APPROACH: Think deeply, search multiple sources, verify data, then synthesize.\n\n";
        $instructions .= "QUESTION: {$originalPrompt}\n\n";

        // Check for season-specific questions
        $isDrySeasonQuestion = preg_match('/\b(dry season|tag-init|tag-araw|summer|mainit|drought|tubig|irrigat)\b/i', $originalPrompt);
        $isWetSeasonQuestion = preg_match('/\b(wet season|tag-ulan|rainy|monsoon|baha|flood)\b/i', $originalPrompt);

        // Check for irrigation/DAP timing questions
        $isDapQuestion = preg_match('/\b(DAP|days?\s*after\s*plant)/i', $originalPrompt);
        $isIrrigationQuestion = preg_match('/\b(patubig|magpatubig|diligan|irrigat|tubig|water)\b/i', $originalPrompt);
        $isIrrigationTimingQuestion = $isDapQuestion && $isIrrigationQuestion;

        // Get array of crops (supports multiple crops)
        $crops = $cropInfo['crops'] ?? [];
        $hasCorn = in_array('corn', $crops);
        $hasRice = in_array('rice', $crops);
        $hasMultipleCrops = count($crops) > 1;

        // Handle irrigation timing questions FIRST (they need specific search instructions)
        if ($isIrrigationTimingQuestion) {
            $instructions .= "=== IRRIGATION TIMING RESEARCH ===\n\n";
            $instructions .= "This question is about IRRIGATION TIMING at a specific DAP (Days After Planting).\n\n";

            if ($hasMultipleCrops) {
                $instructions .= "IMPORTANT: Multiple crops mentioned: " . implode(' at ', $crops) . "\n";
                $instructions .= "You MUST provide irrigation recommendations for EACH crop separately!\n\n";
            }

            if ($hasCorn) {
                $instructions .= "CORN/MAIS IRRIGATION RESEARCH:\n";
                $instructions .= "Search: \"corn irrigation schedule Philippines DAP\" \"mais pagdidilig\"\n";
                $instructions .= "Search: \"corn water requirements Philippines\" \"mais growth stages irrigation\"\n";
                $instructions .= "Key stages: vegetative (early DAP), tasseling (critical), grain fill\n";
                $instructions .= "Critical: Corn needs most water during tasseling and silking (around DAP 50-70)\n\n";
            }

            if ($hasRice) {
                $instructions .= "RICE/PALAY IRRIGATION RESEARCH:\n";
                $instructions .= "Search: \"rice irrigation schedule Philippines DAP\" \"palay pagdidilig\"\n";
                $instructions .= "Search: \"rice water management Philippines\" \"alternate wetting drying AWD\"\n";
                $instructions .= "Key stages: tillering, panicle initiation, flowering, grain fill\n";
                $instructions .= "Consider: AWD (Alternate Wetting and Drying) for water efficiency\n\n";
            }

            $instructions .= "PROVIDE SPECIFIC ADVICE:\n";
            $instructions .= "- Is the mentioned DAP a good time to irrigate?\n";
            $instructions .= "- What is the crop stage at that DAP?\n";
            $instructions .= "- What are the water requirements at that stage?\n";
            $instructions .= "- Any recommendations based on crop stage and conditions?\n\n";

            if ($hasMultipleCrops) {
                $instructions .= "CRITICAL: Give SEPARATE answers for each crop since they have different water needs!\n\n";
            }
        }
        // Handle variety/yield questions (not irrigation timing)
        else {
            if ($hasMultipleCrops) {
                $instructions .= "=== MULTI-CROP RESEARCH STRATEGY ===\n\n";
                $instructions .= "IMPORTANT: This question mentions MULTIPLE CROPS: " . implode(' at ', $crops) . "\n";
                $instructions .= "You MUST provide information for EACH crop separately.\n\n";
            }
        }

        // Add variety/yield research instructions only if NOT an irrigation timing question
        if (!$isIrrigationTimingQuestion && $hasCorn) {
            // Enhanced search for corn with seasonal considerations
            $instructions .= "=== CORN/MAIS RESEARCH ===\n\n";
            $instructions .= "STEP 1 - SEARCH CORN SOURCES:\n";
            $instructions .= "Search: \"corn variety Philippines {$currentYear} yield\" from:\n";
            $instructions .= "   - syngenta.com.ph (NK varieties: NK6414, NK6410, NK8840)\n";
            $instructions .= "   - cropscience.bayer.com.ph (DEKALB varieties)\n";
            $instructions .= "   - pioneer.com (Pioneer varieties)\n";
            $instructions .= "   - bioseed.com.ph (Bioseed varieties)\n\n";

            if ($isDrySeasonQuestion) {
                $instructions .= "CORN DRY SEASON SPECIFIC RESEARCH:\n";
                $instructions .= "Search: \"corn dry season Philippines\" \"drought tolerant corn\"\n";
                $instructions .= "Focus on: varieties with drought tolerance, heat tolerance, water efficiency\n";
                $instructions .= "Consider: shorter maturity = less water needed\n\n";
            } elseif ($isWetSeasonQuestion) {
                $instructions .= "CORN WET SEASON SPECIFIC RESEARCH:\n";
                $instructions .= "Search: \"corn wet season Philippines\" \"disease resistant corn\"\n";
                $instructions .= "Focus on: varieties with disease resistance, lodging resistance\n\n";
            }

            $instructions .= "VERIFY CORN DATA:\n";
            $instructions .= "   - Cross-reference yield data from multiple sources\n";
            $instructions .= "   - Look for YIELD POTENTIAL (maximum), not just derby results\n";
            $instructions .= "   - Consider: Drought tolerance, disease resistance, maturity period\n\n";
        }

        // Add variety/yield research instructions for rice only if NOT an irrigation timing question
        if (!$isIrrigationTimingQuestion && $hasRice) {
            $instructions .= "=== RICE/PALAY RESEARCH ===\n\n";
            $instructions .= "STEP 1 - SEARCH RICE SOURCES:\n";
            $instructions .= "Search: \"rice variety Philippines {$currentYear} yield\" from:\n";
            $instructions .= "   - philrice.gov.ph (official recommendations)\n";
            $instructions .= "   - slagritech.com (SL hybrid varieties)\n";
            $instructions .= "   - cropscience.bayer.com.ph (Arize varieties)\n\n";

            if ($isDrySeasonQuestion) {
                $instructions .= "RICE DRY SEASON SPECIFIC:\n";
                $instructions .= "Focus on: drought tolerant varieties, short maturity, water-efficient\n\n";
            }

            $instructions .= "VERIFY RICE DATA:\n";
            $instructions .= "   - Cross-reference data from multiple sources\n";
            $instructions .= "   - Consider hybrid vs inbred varieties\n\n";
        }

        // Add synthesis instructions for variety questions (not irrigation timing)
        if (!$isIrrigationTimingQuestion) {
            if ($hasMultipleCrops) {
                $instructions .= "=== SYNTHESIZE FOR BOTH CROPS ===\n\n";
                $instructions .= "CRITICAL: Provide SEPARATE recommendations for EACH crop!\n";
                $instructions .= "Format: First discuss MAIS/CORN, then discuss PALAY/RICE\n";
                $instructions .= "Clearly label each section so the farmer knows which advice applies to which crop.\n\n";
            } elseif ($hasCorn || $hasRice) {
                $instructions .= "SYNTHESIZE RECOMMENDATION:\n";
                $instructions .= "   - Rank varieties by yield potential (highest first)\n";
                $instructions .= "   - Explain WHY each variety is recommended\n";
                $instructions .= "   - Include practical growing tips\n\n";

                $instructions .= "DATA TO INCLUDE FOR EACH VARIETY:\n";
                $instructions .= "   - Brand name (company)\n";
                $instructions .= "   - Yield potential in MT/ha\n";
                $instructions .= "   - Maturity period (days)\n";
                $instructions .= "   - Special features (drought tolerance, pest resistance)\n";
                $instructions .= "   - Best season to plant\n\n";
            }
        }

        if (!$hasCorn && !$hasRice && !$isIrrigationTimingQuestion) {
            // General agricultural question
            $instructions .= "=== COMPREHENSIVE RESEARCH ===\n\n";
            $instructions .= "STEP 1: Search for latest {$currentYear} information from official sources\n";
            $instructions .= "STEP 2: Cross-reference data from multiple reliable sources\n";
            $instructions .= "STEP 3: Analyze and synthesize findings\n";
            $instructions .= "STEP 4: Provide clear, actionable recommendations\n\n";
            $instructions .= "Prefer: official government/company websites over news articles\n\n";
        }

        $instructions .= "=== RESPONSE FORMAT ===\n";
        $instructions .= "- Write in Taglish (Filipino-English mix) with 'po' for politeness\n";
        $instructions .= "- Start with your TOP recommendation clearly stated\n";
        $instructions .= "- Use numbered list (1. 2. 3.) for varieties\n";
        $instructions .= "- Include for each: Variety name, yield (MT/ha), key features, best conditions\n";
        $instructions .= "- Add 'Bakit ito ang pinakamataas:' section explaining your top choice\n";
        $instructions .= "- Add 'Mga Tips sa Pagtatanim:' section with practical advice\n";
        $instructions .= "- Do NOT use markdown formatting like ** or __\n";
        $instructions .= "- Do NOT include URLs in the response\n";

        return $instructions;
    }

    /**
     * Combine and format responses from multiple AIs into a single cohesive answer.
     *
     * @param array $responses Array of responses keyed by AI name
     * @param string $originalQuestion The original user question
     * @param AiApiSetting $formatterSetting AI setting to use for formatting
     * @return string Combined and formatted response
     */
    /**
     * Pre-filter AI response to remove derby/trial data before combining.
     * This provides an additional layer of filtering beyond prompt instructions.
     *
     * @param string $response The AI response text
     * @return string Filtered response
     */
    protected function filterDerbyData(string $response): string
    {
        // Log the FULL response content for debugging
        Log::info('=== RAW AI RESPONSE FOR FILTERING ===', [
            'responseContent' => $response,
        ]);

        // Remove lines that mention banned sources or derby data patterns
        $lines = explode("\n", $response);
        $filteredLines = [];

        foreach ($lines as $line) {
            $lineLower = strtolower($line);
            $shouldRemove = false;
            $removalReason = '';

            // Skip lines from banned sources
            if (strpos($lineLower, 'cotabatoprov') !== false) {
                $shouldRemove = true;
                $removalReason = 'cotabatoprov source';
            }
            elseif (strpos($lineLower, 'pia.gov') !== false) {
                $shouldRemove = true;
                $removalReason = 'pia.gov source';
            }
            // Skip lines with derby ranking language
            elseif (preg_match('/\b(nanguna|pumangalawa|pumangatlo|1st place|2nd place|3rd place|first place|second place|third place|winning entry|derby winner|varietal trial|regional.*trial|corn derby)\b/i', $line)) {
                $shouldRemove = true;
                $removalReason = 'derby ranking language';
            }
            // Skip lines mentioning known derby varieties with their derby yields
            // P3582 PW at 11.69 is a known derby result
            elseif (preg_match('/P3582.*11\.6/i', $line) || preg_match('/11\.69.*MT/i', $line)) {
                $shouldRemove = true;
                $removalReason = 'P3582 derby result (11.69 MT/ha)';
            }
            // DK88995 at 10.52 is a known derby result
            elseif (preg_match('/DK88995.*10\.5/i', $line) || preg_match('/10\.52.*MT/i', $line)) {
                $shouldRemove = true;
                $removalReason = 'DK88995 derby result (10.52 MT/ha)';
            }
            // H102G at 9.25 is a known derby result
            elseif (preg_match('/H102G.*9\.2/i', $line) || preg_match('/9\.25.*MT/i', $line)) {
                $shouldRemove = true;
                $removalReason = 'H102G derby result (9.25 MT/ha)';
            }
            // Skip lines with "Yield Potential" that have low values (likely derby data)
            elseif (preg_match('/yield.*potential.*:\s*(8|9|10|11)\.\d+\s*MT/i', $line)) {
                $shouldRemove = true;
                $removalReason = 'low yield potential (likely derby data)';
            }
            // Skip lines that cite lowland/trial locations
            elseif (preg_match('/\b(lowland area|brgy\.|barangay|mlang|dangcagan|bukidnon.*derby)\b/i', $line)) {
                $shouldRemove = true;
                $removalReason = 'trial location reference';
            }

            if ($shouldRemove) {
                Log::info('FILTERED OUT LINE', [
                    'reason' => $removalReason,
                    'line' => substr($line, 0, 150),
                ]);
                continue;
            }

            $filteredLines[] = $line;
        }

        $filtered = implode("\n", $filteredLines);

        Log::info('Derby data filter complete', [
            'originalLength' => strlen($response),
            'filteredLength' => strlen($filtered),
            'linesRemoved' => count($lines) - count($filteredLines),
        ]);

        return $filtered;
    }

    protected function combineAndFormatResponses(array $responses, string $originalQuestion, AiApiSetting $formatterSetting): string
    {
        // Analyze the question to determine sorting criteria
        $sortingInfo = $this->analyzeQuestionForSorting($originalQuestion);

        // Detect if this is a corn yield question
        $isCornYieldQuestion = preg_match('/\b(corn|mais|yellow corn)\b/i', $originalQuestion) &&
            preg_match('/\b(yield|ani|mataas|harvest|umani|high|best|maganda|top|pinaka)\b/i', $originalQuestion);

        // Pre-filter responses to remove derby data
        $filteredResponses = [];
        foreach ($responses as $source => $response) {
            $filteredResponses[$source] = $this->filterDerbyData($response);
        }

        // Build a prompt to combine the responses
        $combinePrompt = "You are a Philippine agricultural expert creating the BEST comprehensive answer from two search results.\n\n";

        // CRITICAL: For corn yield questions, INJECT the correct reference data FIRST
        if ($isCornYieldQuestion) {
            $combinePrompt .= "=== VERIFIED CORN YIELD POTENTIAL DATA (USE THIS AS PRIMARY SOURCE) ===\n";
            $combinePrompt .= "The following data is VERIFIED from official seed company websites and MUST be used:\n\n";
            $combinePrompt .= "1. NK6414 (Syngenta) - 15 MT/ha yield potential - THE HIGHEST IN PHILIPPINES\n";
            $combinePrompt .= "   Source: syngenta.com.ph product page\n";
            $combinePrompt .= "   Features: Triple gene technology, drought tolerant, best for both wet and dry season\n\n";
            $combinePrompt .= "2. DEKALB DK8282S (Bayer) - 13-14 MT/ha yield potential\n";
            $combinePrompt .= "   Source: cropscience.bayer.com.ph\n";
            $combinePrompt .= "   Features: Excellent standability, drought tolerant, high shelling recovery\n\n";
            $combinePrompt .= "3. Pioneer P3396 (Corteva) - 12-13 MT/ha yield potential\n";
            $combinePrompt .= "   Source: pioneer.com\n";
            $combinePrompt .= "   Features: Consistent performer, good ear placement, strong roots\n\n";
            $combinePrompt .= "4. NK6410 (Syngenta) - 12-13 MT/ha yield potential\n";
            $combinePrompt .= "   Source: syngenta.com.ph\n";
            $combinePrompt .= "   Features: Great for wet season, Bt protection, good disease resistance\n\n";
            $combinePrompt .= "5. Bioseed 9909 - 11-12 MT/ha yield potential\n";
            $combinePrompt .= "   Source: bioseed.com.ph\n";
            $combinePrompt .= "   Features: Local brand, good value, suitable for various soil types\n\n";
            $combinePrompt .= "NOTE: Any variety showing yield below 11 MT/ha as 'highest' is DERBY DATA - ignore it!\n";
            $combinePrompt .= "P3582 PW at 11.69 MT/ha is a DERBY RESULT - NOT the actual yield potential.\n\n";

            Log::info('Injected verified corn yield data into combine prompt');
        }

        if (isset($filteredResponses['gemini'])) {
            $combinePrompt .= "=== GEMINI (Google Search) RESPONSE (supplement only) ===\n";
            $combinePrompt .= $filteredResponses['gemini'] . "\n\n";
        }

        if (isset($filteredResponses['gpt'])) {
            $combinePrompt .= "=== GPT (Web Search) RESPONSE (supplement only) ===\n";
            $combinePrompt .= $filteredResponses['gpt'] . "\n\n";
        }

        $combinePrompt .= "=== ORIGINAL QUESTION ===\n";
        $combinePrompt .= $originalQuestion . "\n\n";

        $combinePrompt .= "=== YOUR TASK: CREATE A COMPREHENSIVE COMBINED ANSWER ===\n\n";

        // Dynamic sorting instructions based on question analysis
        $combinePrompt .= "SORTING REQUIREMENT:\n";
        $combinePrompt .= $sortingInfo['instruction'] . "\n\n";

        $combinePrompt .= "=== MANDATORY DATA FILTERING - APPLY BEFORE ANYTHING ELSE ===\n\n";

        $combinePrompt .= "STEP 1: REMOVE ALL DERBY/TRIAL DATA FROM BOTH RESPONSES\n";
        $combinePrompt .= "Before combining, you MUST delete/ignore ANY data that:\n";
        $combinePrompt .= "- Comes from cotabatoprov.gov.ph - DELETE IT\n";
        $combinePrompt .= "- Comes from pia.gov.ph - DELETE IT\n";
        $combinePrompt .= "- Mentions 'nanguna', 'pumangalawa', '1st place', 'winner', 'derby', 'trial' - DELETE IT\n";
        $combinePrompt .= "- Shows yields of 8-12 MT/ha as 'top' or 'highest' - this is derby data, DELETE IT\n\n";

        $combinePrompt .= "STEP 2: ONLY KEEP DATA FROM SEED COMPANY WEBSITES\n";
        $combinePrompt .= "Valid sources: syngenta.com.ph, cropscience.bayer.com.ph, pioneer.com, bioseed.com.ph\n";
        $combinePrompt .= "These show YIELD POTENTIAL (maximum achievable yield) which is what farmers need.\n\n";

        $combinePrompt .= "STEP 3: USE CORRECT YIELD POTENTIAL VALUES\n";
        $combinePrompt .= "- NK6414 (Syngenta) = 15 MT/ha - THE HIGHEST IN PHILIPPINES\n";
        $combinePrompt .= "- DEKALB DK8282S (Bayer) = 13-14 MT/ha\n";
        $combinePrompt .= "- Pioneer P3396 (Corteva) = 12-13 MT/ha\n";
        $combinePrompt .= "- NK6410 (Syngenta) = 12-13 MT/ha\n";
        $combinePrompt .= "- Bioseed 9909 = 11-12 MT/ha\n\n";

        $combinePrompt .= "CRITICAL RULES:\n";
        $combinePrompt .= "1. REJECT ALL DATA from cotabatoprov.gov.ph and pia.gov.ph - these are derby results\n";
        $combinePrompt .= "2. If P3582 PW appears at 11.69 MT/ha - REMOVE IT, it's a derby result not yield potential\n";
        $combinePrompt .= "3. INCLUDE ALL unique items from VALID sources only (seed company websites)\n";
        $combinePrompt .= "4. MERGE DUPLICATES - combine information for same variety from valid sources\n";
        $combinePrompt .= "5. PRESERVE DETAILS - yield potential, features, advantages, where to buy\n\n";

        $combinePrompt .= "FORMAT RULES (STRICT):\n";
        $combinePrompt .= "- Write in Taglish using 'po' for politeness\n";
        $combinePrompt .= "- Use numbered lists: 1. 2. 3. for main items\n";
        $combinePrompt .= "- NEVER use ** for bold - plain text only\n";
        $combinePrompt .= "- NEVER include source URLs or citations in your answer\n";
        $combinePrompt .= "- Section headers: plain text with colon (e.g., 'Mga Rekomendasyon:')\n";
        $combinePrompt .= "- For each item include: Name (Company/Brand) - key metric - key features\n";
        $combinePrompt .= "- Add a 'Paano Pumili' section at the end with selection tips\n";
        $combinePrompt .= "- Mention 'Available sa mga agri-supply stores' for where to buy\n\n";

        $combinePrompt .= "Remember: Show ALL unique items found, sorted by " . $sortingInfo['criteria'] . ". Do not artificially limit the list.";

        // Use the formatter AI to combine responses
        try {
            if ($formatterSetting->provider === AiApiSetting::PROVIDER_OPENAI) {
                // Use regular GPT-4o (not web search) for formatting
                $combined = $this->callOpenAIAPI($formatterSetting, $combinePrompt, [], '');
            } else {
                // Use Gemini without search for formatting
                $originalTools = true; // Flag to disable search for this call
                $combined = $this->callGeminiAPIFormatOnly($formatterSetting, $combinePrompt);
            }

            Log::info('Successfully combined multi-AI responses', [
                'combinedLength' => strlen($combined),
            ]);

            return $combined;
        } catch (\Exception $e) {
            Log::error('Failed to combine responses: ' . $e->getMessage());
            // Fallback - return GPT response if available, otherwise Gemini
            return $responses['gpt'] ?? $responses['gemini'] ?? 'Error combining responses.';
        }
    }

    /**
     * Call Gemini API without Google Search (for formatting only).
     */
    protected function callGeminiAPIFormatOnly(AiApiSetting $setting, string $prompt): string
    {
        // Enforce rate limiting to prevent 429 errors
        $this->enforceRateLimit('gemini-format');

        $model = $setting->defaultModel ?: 'gemini-2.0-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $setting->apiKey;

        $requestData = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'maxOutputTokens' => $setting->maxTokens ?: 4096,
                'temperature' => 0.3, // Lower temp for more consistent formatting
            ],
            // No tools - don't use Google Search for formatting
        ];

        $response = Http::timeout(120)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $requestData);

        if ($response->successful()) {
            $data = $response->json();

            // Extract token usage from Gemini response
            $usageMetadata = $data['usageMetadata'] ?? [];
            $inputTokens = $usageMetadata['promptTokenCount'] ?? 0;
            $outputTokens = $usageMetadata['candidatesTokenCount'] ?? 0;

            // Track token usage with proper provider and node (format-only)
            $this->trackTokenUsage('gemini', $this->currentNodeId . '_format', $inputTokens, $outputTokens, $model);

            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            return $this->stripMarkdownFormatting($text);
        }

        throw new \Exception($response->json('error.message') ?? 'Gemini API error');
    }

    /**
     * Detect gender from user's message.
     */
    protected function detectGenderFromMessage(string $message): ?string
    {
        $message = strtolower(trim($message));

        // Male indicators
        $maleKeywords = ['sir', 'kuya', 'lalaki', 'male', 'boy', 'man', 'po sir', 'opo sir', 'lalake'];
        foreach ($maleKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return 'male';
            }
        }

        // Female indicators
        $femaleKeywords = ['maam', 'ma\'am', "ma'am", 'ate', 'babae', 'female', 'girl', 'woman', 'po maam', 'opo maam', 'miss'];
        foreach ($femaleKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return 'female';
            }
        }

        return null;
    }

    /**
     * Get honorific based on gender.
     */
    protected function getHonorific(?string $gender): string
    {
        if ($gender === 'male') {
            return 'Sir';
        } elseif ($gender === 'female') {
            return "Ma'am";
        }
        return 'po';
    }

    /**
     * Check blocker and get thinking reply (for streaming).
     * Returns array with 'blocked', 'blockMessage', 'thinkingReply', and 'specialState'.
     *
     * @param AiChatSession $session The chat session
     * @param string $userMessage The user's message
     * @param array $images Array of image paths
     * @param string|null $topicContext The original question for follow-up context
     */
    public function checkBlockerAndGetThinkingReply(AiChatSession $session, string $userMessage, array $images = [], ?string $topicContext = null): array
    {
        $this->session = $session;
        $this->userMessage = $userMessage;
        $this->images = $images;
        $this->chatHistory = $session->getChatHistoryText(10);
        $this->topicContext = $topicContext;

        // Initialize flow log with user message
        $this->logUserMessage($userMessage);
        $this->logFlowStep('Received user message', substr($userMessage, 0, 100));

        // Get the user's reply flow
        $this->flow = AiReplyFlow::getOrCreateForUser($this->userId);

        if (!$this->flow || !$this->flow->isActive) {
            return ['blocked' => false, 'blockMessage' => null, 'thinkingReply' => null, 'specialState' => null];
        }

        $flowData = $this->flow->flowData;
        if (!$flowData || empty($flowData['nodes'])) {
            return ['blocked' => false, 'blockMessage' => null, 'thinkingReply' => null, 'specialState' => null];
        }

        // Mark session as normal if it's new (no more gender greeting)
        $state = $session->getConversationState();
        if ($state === 'new' || $state === 'awaiting_gender') {
            $session->setConversationState('normal');
        }

        // AGRICULTURAL QUESTIONS: ALWAYS ALLOW - This is an agricultural AI assistant
        // Skip blocker entirely for farming/crop questions
        if ($this->isAgriculturalQuestion($userMessage)) {
            Log::info('Agricultural question detected - skipping blocker, enabling web search', [
                'userMessage' => substr($userMessage, 0, 100),
            ]);

            // Log the question type
            $this->logQuestionType('Agricultural Question');
            $this->logFlowStep('Detected agricultural question', 'Blocker bypassed, web search enabled');

            // Force web search for agricultural questions - they need current data
            $this->forceWebSearch = true;

            // Process thinking reply
            $this->processThinkingReplyFirst($flowData);
            $this->preprocessPersonalityNode($flowData);

            return [
                'blocked' => false,
                'blockMessage' => null,
                'thinkingReply' => $this->thinkingReply,
                'specialState' => 'agricultural',
            ];
        }

        // SPECIAL: If this is a meta-question (verification request) and we have topic context,
        // skip the blocker check entirely - treat it as a valid follow-up
        if ($this->topicContext && $this->isMetaQuestion($userMessage)) {
            Log::debug('Meta-question detected with topic context - skipping blocker', [
                'userMessage' => $userMessage,
                'topicContext' => substr($this->topicContext, 0, 100),
            ]);

            // Process thinking reply
            $this->processThinkingReplyFirst($flowData);
            $this->preprocessPersonalityNode($flowData);

            return [
                'blocked' => false,
                'blockMessage' => null,
                'thinkingReply' => $this->thinkingReply,
                'specialState' => 'meta_question',
            ];
        }

        // STEP 1: Process BLOCKER FIRST (with topic context if available)
        $this->processBlockerFirst($flowData);

        if ($this->isBlocked) {
            return [
                'blocked' => true,
                'blockMessage' => $this->blockMessage,
                'thinkingReply' => null,
                'specialState' => null,
            ];
        }

        // STEP 2: Process thinking reply (only if not blocked)
        // RANDOM: 50% chance of showing thinking reply for more natural feel
        $this->processThinkingReplyFirst($flowData);

        // Also pre-load personality for later use
        $this->preprocessPersonalityNode($flowData);

        return [
            'blocked' => false,
            'blockMessage' => null,
            'thinkingReply' => $this->thinkingReply,
            'specialState' => null,
        ];
    }

    /**
     * Get ONLY the thinking reply (fast operation for streaming).
     * This is called first so it can be sent to client immediately.
     * @deprecated Use checkBlockerAndGetThinkingReply instead
     */
    public function getThinkingReplyOnly(AiChatSession $session, string $userMessage, array $images = []): ?string
    {
        $result = $this->checkBlockerAndGetThinkingReply($session, $userMessage, $images);
        return $result['thinkingReply'];
    }

    /**
     * Process the main flow (after thinking reply was already sent).
     * This is the slow operation with RAG queries and AI processing.
     *
     * @param AiChatSession $session The chat session
     * @param string $userMessage The user's message
     * @param array $images Array of image paths
     * @param string|null $topicContext The original question for follow-up context
     */
    public function processMainFlow(AiChatSession $session, string $userMessage, array $images = [], ?string $topicContext = null): array
    {
        // Store topic context for follow-up questions
        $this->topicContext = $topicContext;

        // Normalize user message for common abbreviations/patterns
        $userMessage = $this->normalizeUserMessage($userMessage);

        // Restore state if not already set (in case called independently)
        if (!$this->session) {
            $this->session = $session;
            $this->userMessage = $userMessage;
            $this->images = $images;
            $this->chatHistory = $session->getChatHistoryText(10);

            $this->flow = AiReplyFlow::getOrCreateForUser($this->userId);
        } else {
            // Update userMessage with normalized version
            $this->userMessage = $userMessage;
        }

        // IMPORTANT: For ALL follow-up questions with topic context, expand the message
        // to include the original question so the AI understands the context
        if ($this->topicContext) {
            $isMetaQ = $this->isMetaQuestion($userMessage);

            Log::debug('Follow-up question detected - expanding with topic context', [
                'originalMessage' => $userMessage,
                'topicContext' => substr($this->topicContext, 0, 100),
                'isMetaQuestion' => $isMetaQ,
            ]);

            if ($isMetaQ) {
                // Meta-question: Force web search and frame as verification
                $this->forceWebSearch = true;
                $this->userMessage = "Search online and verify: " . $this->topicContext .
                                     "\n\nUser is asking: " . $userMessage .
                                     "\n\nIMPORTANT: You MUST search the web for current information. Do NOT rely on your training data.";
            } else {
                // Regular follow-up: Expand with context so AI understands what the follow-up is about
                // This is CRITICAL for short follow-ups like "e and boron at zinc?" which need context
                $this->userMessage = "CONTEXT: This is a follow-up question about: \"" . $this->topicContext . "\"\n\n" .
                                     "FOLLOW-UP QUESTION: " . $userMessage . "\n\n" .
                                     "IMPORTANT: Answer the follow-up question IN THE CONTEXT of the original question above. " .
                                     "Do NOT answer about unrelated topics.";
            }

            Log::debug('Expanded follow-up message', [
                'expandedMessage' => substr($this->userMessage, 0, 300),
                'forceWebSearch' => $this->forceWebSearch,
            ]);

            // CRITICAL: For follow-ups, limit chat history to only the last 2 exchanges
            // This prevents the AI from getting confused by older, unrelated topics in the conversation
            // (e.g., Q1 about "corn varieties in Pangasinan" confusing a follow-up to Q3 about "flowering mais")
            $this->chatHistory = $this->session->getChatHistoryText(4); // Only last 4 messages (2 Q&A pairs)

            Log::debug('Limited chat history for follow-up', [
                'chatHistoryLength' => strlen($this->chatHistory),
                'reason' => 'Follow-up question detected, limiting history to prevent topic confusion',
            ]);
        }

        // PRIORITY CHECK: For product recommendation questions, prioritize RAG FIRST
        // These questions are looking for locally available Philippine products (pesticides, fertilizers, etc.)
        // RAG contains our external products knowledge base which is more relevant than web search
        if ($this->isProductRecommendationQuestion($userMessage)) {
            $this->forceRagFirst = true;
            Log::info('=== PRODUCT RECOMMENDATION DETECTED - RAG FIRST PRIORITY ===', [
                'message' => substr($userMessage, 0, 100),
            ]);
            $this->logFlowStep('Product Question Detected', 'Prioritizing RAG for local product recommendations');
            $this->logQuestionType('Product Recommendation (RAG-First)');
        }

        // CRITICAL: For agricultural questions, ALWAYS force web search
        // These topics need current, accurate data (varieties, yields, prices, recommendations)
        // Without web search, the AI may give outdated or inaccurate information
        // NOTE: If forceRagFirst is set, RAG will be searched first, then web search will supplement
        if (!$this->forceWebSearch && $this->isAgriculturalQuestion($userMessage)) {
            $this->forceWebSearch = true;
            Log::info('Agricultural question detected - forcing web search for accurate data', [
                'message' => substr($userMessage, 0, 100),
            ]);
        }

        if (!$this->flow || !$this->flow->isActive) {
            return $this->buildSimpleResponse();
        }

        $flowData = $this->flow->flowData;
        if (!$flowData || empty($flowData['nodes'])) {
            return $this->buildSimpleResponse();
        }

        try {
            // ========================================================
            // SPECIAL HANDLING: Gender-first greeting for new sessions
            // ========================================================
            $specialResponse = $this->handleSpecialConversationStates();
            if ($specialResponse !== null) {
                return $specialResponse;
            }

            // Find the start node
            $startNode = $this->findNodeByType($flowData['nodes'], 'start');
            if (!$startNode) {
                return $this->buildSimpleResponse();
            }

            // Initialize start node output
            $this->nodeOutputs[$startNode['id']] = $userMessage;

            // Ensure personality is loaded (may already be done in getThinkingReplyOnly)
            if (empty($this->personalityText)) {
                $this->preprocessPersonalityNode($flowData);
            }

            // PRIORITY: If forceRagFirst is set, execute RAG query BEFORE the main flow
            // This ensures local product recommendations are found first
            if ($this->forceRagFirst) {
                $this->logFlowStep('RAG-First Priority', 'Executing RAG query before main flow for product recommendations');

                $ragResult = $this->executeRagFirstQuery($userMessage);
                if (!empty($ragResult)) {
                    // ALWAYS include RAG content - filtering happens at combination stage
                    $this->nodeOutputs['node_rag_priority'] = $ragResult;

                    // Detect agricultural need for filtering at combination stage
                    // This stores the need but doesn't exclude RAG content
                    $this->validateRagProductRelevance($ragResult, $userMessage);

                    Log::info('RAG-First query completed', [
                        'resultLength' => strlen($ragResult),
                        'detectedNeed' => $this->detectedAgriculturalNeed['type'] ?? 'general',
                    ]);
                    $this->logFlowStep('RAG Query Done', 'RAG content retrieved, will filter at combination');
                }
            }

            // Process the main flow
            $flowResult = $this->processFromNode($startNode['id'], $flowData);
            $finalOutput = $flowResult['text'];
            $extractedImages = $flowResult['images'] ?? [];

            // POST-PROCESSING: Only run cleanup and enhancement here if NO images are present
            // When images ARE present, post-processing will run AFTER image analysis is combined
            // in the controller (via postProcessCombinedResponse method)
            if (empty($this->images)) {
                // POST-PROCESSING STEP 0: Remove greeting from follow-up messages
                $finalOutput = $this->removeGreetingFromFollowUp($finalOutput);

                // POST-PROCESSING STEP 1: Clean up redundant content
                $finalOutput = $this->cleanupRedundantContent($finalOutput);

                // POST-PROCESSING STEP 2: Check if product recommendation is needed but missing
                $finalOutput = $this->enhanceWithProductRecommendationIfNeeded($finalOutput);

                // POST-PROCESSING STEP 3: Remove image placeholders
                $finalOutput = $this->removeImagePlaceholders($finalOutput);

                // POST-PROCESSING STEP 4: Remove CJK (Chinese/Japanese/Korean) characters from AI
                $finalOutput = $this->removeCjkCharacters($finalOutput);

                Log::debug('Post-processing completed in processMainFlow (no images)');
            } else {
                Log::debug('Skipping post-processing in processMainFlow - will run after image analysis combination', [
                    'imageCount' => count($this->images),
                ]);
            }

            // AFTER AI generates response: Look up product images based on FINAL response
            // ALWAYS look up product images - not just when forceRagFirst is set
            if (!empty($finalOutput)) {
                // Extract product images from patterns in final response
                $productImages = $this->extractProductImagesFromRag($finalOutput);
                if (!empty($productImages)) {
                    $this->productImages = array_merge($this->productImages, $productImages);
                    $this->logFlowStep('Product Images Found', count($productImages) . ' image(s) extracted from response patterns');
                }

                // Look up product images from database based on products mentioned in FINAL response
                $dbImages = $this->lookupProductImagesFromDatabase($finalOutput);
                if (!empty($dbImages)) {
                    // Add database images, avoiding duplicates
                    foreach ($dbImages as $dbImg) {
                        $exists = false;
                        foreach ($this->productImages as $existingImg) {
                            if ($existingImg['url'] === $dbImg['url']) {
                                $exists = true;
                                break;
                            }
                        }
                        if (!$exists) {
                            $this->productImages[] = $dbImg;
                        }
                    }
                    $this->logFlowStep('Product Images (DB)', count($dbImages) . ' image(s) found from database lookup');
                }

                Log::info('Product images extracted from FINAL response', [
                    'productImagesFromPatterns' => count($productImages ?? []),
                    'productImagesFromDatabase' => count($dbImages ?? []),
                    'totalProductImages' => count($this->productImages),
                ]);
            }

            // Merge extracted images from AI response with product images
            $allProductImages = array_merge($extractedImages, $this->productImages);

            // Add product images to metadata if any were extracted
            if (!empty($allProductImages)) {
                $this->metadata['productImages'] = $allProductImages;
                Log::info('Adding product images to response metadata', [
                    'extractedFromResponse' => count($extractedImages),
                    'fromDatabase' => count($this->productImages),
                    'total' => count($allProductImages),
                ]);
            }

            return [
                'success' => true,
                'response' => $finalOutput,
                'images' => $allProductImages, // All images for lightbox
                'metadata' => $this->metadata,
                'productImages' => $allProductImages,
            ];
        } catch (\Exception $e) {
            Log::error('ReplyFlowProcessor main flow error: ' . $e->getMessage(), [
                'userId' => $this->userId,
                'sessionId' => $session->id,
            ]);

            // Log to flow modal instead of showing error in chat
            $this->logFlowStep('Error', 'Main flow error: ' . $e->getMessage());
            $this->metadata['errors'][] = $e->getMessage();

            return [
                'success' => false,
                'response' => '', // Empty response - error is in flow modal
                'metadata' => $this->metadata,
                'flowLog' => $this->getFlowLog(), // Include flow log with error
            ];
        }
    }

    /**
     * Check if a follow-up question is related to the original question.
     * Uses AI to determine relevance.
     *
     * IMPORTANT: Meta-questions (verification/confirmation requests) are ALWAYS
     * considered related to the previous topic since they're asking about the
     * previous answer, not starting a new topic.
     *
     * @param string $originalQuestion The previous/original question
     * @param string $followupQuestion The current follow-up question
     * @return bool True if related, false if not related
     */
    public function checkFollowupRelevance(string $originalQuestion, string $followupQuestion, ?string $previousResponse = null): bool
    {
        // FIRST: Check if this is a meta-question (verification/confirmation request)
        // Meta-questions are ALWAYS related to the previous topic
        if ($this->isMetaQuestion($followupQuestion)) {
            Log::debug('Follow-up is a meta-question - automatically considered related', [
                'originalQuestion' => substr($originalQuestion, 0, 100),
                'followupQuestion' => $followupQuestion,
            ]);
            return true;
        }

        // SECOND: Check if this is a clarification question (asking for explanation/definition)
        // Clarification questions are ALWAYS related because they ask about the answer
        if ($this->isClarificationQuestion($followupQuestion)) {
            Log::debug('Follow-up is a clarification question - automatically considered related', [
                'originalQuestion' => substr($originalQuestion, 0, 100),
                'followupQuestion' => $followupQuestion,
            ]);
            return true;
        }

        // THIRD: Check if this is an image request
        // Image requests about the current topic are always related
        if ($this->isImageRequest($followupQuestion)) {
            Log::debug('Follow-up is an image request - automatically considered related', [
                'originalQuestion' => substr($originalQuestion, 0, 100),
                'followupQuestion' => $followupQuestion,
            ]);
            return true;
        }

        // FOURTH: Check if this is a continuation question (when/where/how much)
        // Continuation questions are direct follow-ups about timing, quantity, etc.
        if ($this->isContinuationQuestion($followupQuestion)) {
            Log::debug('Follow-up is a continuation question - automatically considered related', [
                'originalQuestion' => substr($originalQuestion, 0, 100),
                'followupQuestion' => $followupQuestion,
            ]);
            return true;
        }

        // Get default AI API for relevance check
        $apiSetting = $this->getDefaultApiSetting();
        if (!$apiSetting) {
            // If no API configured, allow by default
            Log::warning('No AI API configured for follow-up relevance check');
            return true;
        }

        // Build prompt for relevance check - include previous response context if available
        // IMPORTANT: Prompt is designed to be LENIENT and default to YES when uncertain
        $prompt = "You are checking if a follow-up question is related to an ongoing conversation about agriculture/farming.\n\n";
        $prompt .= "Original Question: {$originalQuestion}\n\n";
        if ($previousResponse) {
            // Truncate long responses
            $truncatedResponse = strlen($previousResponse) > 500 ? substr($previousResponse, 0, 500) . '...' : $previousResponse;
            $prompt .= "AI's Previous Response: {$truncatedResponse}\n\n";
        }
        $prompt .= "Follow-up Question: {$followupQuestion}\n\n";
        $prompt .= "ANSWER YES (related) if ANY of these apply:\n";
        $prompt .= "- It asks about ANYTHING mentioned in the conversation OR the AI's response\n";
        $prompt .= "- It asks about timing (when, how long), quantity (how many, how much), location (where)\n";
        $prompt .= "- It asks to CLARIFY, EXPLAIN, or DEFINE any term\n";
        $prompt .= "- It asks for more details, pictures, or examples\n";
        $prompt .= "- It's about the same general subject (same crop, same practice, same topic)\n";
        $prompt .= "- It asks for verification or confirmation\n";
        $prompt .= "- It's a continuation like 'then what?', 'and?', 'what about?'\n\n";
        $prompt .= "ANSWER NO (not related) ONLY if:\n";
        $prompt .= "- It's about a COMPLETELY DIFFERENT topic with NO connection\n";
        $prompt .= "- Example: Original was about corn irrigation, follow-up is about pig raising\n\n";
        $prompt .= "VERY IMPORTANT: When in doubt, answer YES. Be LENIENT. The conversation is about farming, so related farming questions should be YES.\n\n";
        $prompt .= "Examples:\n";
        $prompt .= "- Original: 'pwede pa kaya magpatubig ng mais sa dap100?' Follow-up: 'ano ang milk line?' = YES (milk line was mentioned in response)\n";
        $prompt .= "- Original: 'pwede pa kaya magpatubig ng mais?' Follow-up: 'kelan pwede magpatubig?' = YES (same topic: irrigation)\n";
        $prompt .= "- Original: 'paano magtanim ng mais?' Follow-up: 'what is corn?' = YES (related to corn topic)\n\n";
        $prompt .= "Respond with only one word: YES or NO";

        Log::debug('Checking follow-up relevance', [
            'originalQuestion' => substr($originalQuestion, 0, 100),
            'followupQuestion' => substr($followupQuestion, 0, 100),
        ]);

        try {
            $response = $this->callAI($apiSetting, $prompt);
            $isRelated = stripos(trim($response), 'yes') !== false;

            Log::debug('Follow-up relevance check result', [
                'response' => $response,
                'isRelated' => $isRelated,
            ]);

            return $isRelated;
        } catch (\Exception $e) {
            Log::error('Follow-up relevance check failed: ' . $e->getMessage());
            // On error, allow the message through (fail-open)
            return true;
        }
    }

    /**
     * Build a simple response when no flow is configured.
     */
    protected function buildSimpleResponse(): array
    {
        // Get default AI API
        $apiSetting = $this->getDefaultApiSetting();
        if (!$apiSetting) {
            return [
                'success' => false,
                'response' => 'No AI provider is configured. Please configure an API in AI Settings.',
                'thinkingReply' => null,
                'metadata' => $this->metadata,
            ];
        }

        // Simple query to AI
        $response = $this->callAI($apiSetting, $this->userMessage, $this->images);

        return [
            'success' => true,
            'response' => $response,
            'thinkingReply' => null,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Process the flow starting from a given node.
     * Follows connections and respects conditional branching (if/else nodes).
     * Only processes nodes along the actual flow path.
     *
     * IMPORTANT: Skips pre-processing nodes (personality, thinking_reply, blocker)
     * in the main flow since these are handled separately before the main flow.
     *
     * @return array{text: string, images: array} Array with 'text' and 'images' keys
     */
    protected function processFromNode(string $nodeId, array $flowData): array
    {
        Log::debug('Starting flow processing', [
            'startNodeId' => $nodeId,
            'totalNodes' => count($flowData['nodes']),
            'totalConnections' => count($flowData['connections'] ?? []),
        ]);

        // Pre-processing node types that are DATA SOURCES only (no main flow traversal)
        // NOTE: Blocker is pre-processed BUT IS part of main flow (pass-through)
        $skipInMainFlowTypes = ['personality', 'thinking_reply'];

        $currentNodeId = $nodeId;
        $maxIterations = 50; // Prevent infinite loops
        $iterations = 0;
        $processedPath = [];

        while ($currentNodeId && $iterations < $maxIterations) {
            $iterations++;
            $node = $this->findNodeById($flowData['nodes'], $currentNodeId);

            if (!$node) {
                Log::warning('Node not found in flow', ['nodeId' => $currentNodeId]);
                break;
            }

            $processedPath[] = $node['id'] . ' (' . $node['type'] . ')';
            $this->metadata['nodesProcessed'][] = $node['type'];

            // Process the node
            $result = $this->processNode($node, $flowData);

            // Store output for merge field replacement
            $this->nodeOutputs[$node['id']] = $result['output'];

            Log::debug('Node processed', [
                'nodeId' => $node['id'],
                'nodeType' => $node['type'],
                'outputLength' => strlen($result['output']),
                'nextConnector' => $result['nextConnector'] ?? 'output',
            ]);

            // Check if this is an output node (end of flow)
            if ($node['type'] === 'output') {
                $extractedImages = $result['images'] ?? [];
                Log::debug('Flow completed at output node', [
                    'outputNodeId' => $node['id'],
                    'path' => $processedPath,
                    'finalOutputLength' => strlen($result['output']),
                    'extractedImages' => count($extractedImages),
                ]);
                // Return both output text and images
                return [
                    'text' => $result['output'],
                    'images' => $extractedImages,
                ];
            }

            // Determine next node based on connector type
            $nextConnector = $result['nextConnector'] ?? 'output';

            // For start node, find the MAIN flow path (skip data-source-only nodes)
            if ($node['type'] === 'start') {
                $currentNodeId = $this->getMainFlowNodeId(
                    $flowData['connections'],
                    $flowData['nodes'],
                    $node['id'],
                    $skipInMainFlowTypes
                );
            } else {
                // Check for parallel connections (multiple nodes from same connector)
                $parallelNodeIds = $this->getAllConnectedNodeIds(
                    $flowData['connections'],
                    $node['id'],
                    $nextConnector
                );

                if (count($parallelNodeIds) > 1) {
                    // Process all parallel nodes SEQUENTIALLY and find their common downstream
                    // Sequential processing avoids API rate limiting (Gemini, Pinecone, etc.)
                    Log::debug('Processing parallel nodes SEQUENTIALLY to avoid rate limiting', [
                        'fromNode' => $node['id'],
                        'parallelNodes' => $parallelNodeIds,
                    ]);

                    // ALL node types that call external APIs - need delays between them
                    $apiCallingNodeTypes = [
                        'rag_query',            // Pinecone API (unified)
                        'rag_docs_query',       // Pinecone API (legacy)
                        'rag_websites_query',   // Pinecone API (legacy)
                        'rag_images_query',     // Pinecone API (legacy)
                        'query',                // Gemini/OpenAI/Claude API
                        'online_query',         // Gemini Google Search API
                        'api_query',            // External API calls
                        'output',               // Final AI processing (if has AI API)
                        'blocker',              // AI scope checking
                        'thinking_reply',       // AI thinking
                        'if_else_query',        // AI query classification
                    ];
                    $lastApiCallTime = 0;
                    $apiCallDelay = 2000000; // 2 seconds delay between API calls (in microseconds)

                    foreach ($parallelNodeIds as $parallelNodeId) {
                        // Skip if already processed
                        if (isset($this->nodeOutputs[$parallelNodeId])) {
                            continue;
                        }

                        $parallelNode = $this->findNodeById($flowData['nodes'], $parallelNodeId);
                        if ($parallelNode) {
                            $processedPath[] = $parallelNode['id'] . ' (' . $parallelNode['type'] . ') [sequential]';
                            $this->metadata['nodesProcessed'][] = $parallelNode['type'];

                            // Add delay between API calls to avoid rate limiting (HTTP 429)
                            if (in_array($parallelNode['type'], $apiCallingNodeTypes)) {
                                if ($lastApiCallTime > 0) {
                                    $elapsed = microtime(true) * 1000000 - $lastApiCallTime;
                                    if ($elapsed < $apiCallDelay) {
                                        $sleepTime = (int)($apiCallDelay - $elapsed);
                                        Log::debug('API rate limit protection: waiting before next call', [
                                            'nodeType' => $parallelNode['type'],
                                            'sleepMs' => $sleepTime / 1000,
                                        ]);
                                        usleep($sleepTime);
                                    }
                                }
                                $lastApiCallTime = microtime(true) * 1000000;
                            }

                            $parallelResult = $this->processNode($parallelNode, $flowData);
                            $this->nodeOutputs[$parallelNode['id']] = $parallelResult['output'];

                            Log::debug('Sequential node processed', [
                                'nodeId' => $parallelNode['id'],
                                'nodeType' => $parallelNode['type'],
                                'outputLength' => strlen($parallelResult['output']),
                            ]);
                        }
                    }

                    // Find the common downstream node (where all parallel paths merge)
                    $currentNodeId = $this->findCommonDownstreamNode(
                        $flowData['connections'],
                        $parallelNodeIds
                    );

                    Log::debug('Sequential processing complete, continuing to', [
                        'nextNode' => $currentNodeId,
                    ]);
                } else {
                    // Single connection, follow normally
                    $currentNodeId = $this->getConnectedNodeId(
                        $flowData['connections'],
                        $node['id'],
                        $nextConnector
                    );
                }
            }

            if (!$currentNodeId) {
                Log::debug('No next node found, flow ended', [
                    'lastNodeId' => $node['id'],
                    'path' => $processedPath,
                ]);
            }
        }

        if ($iterations >= $maxIterations) {
            Log::warning('Flow processing reached max iterations', [
                'path' => $processedPath,
            ]);
        }

        // If no output node was reached, return the last output
        $lastOutput = end($this->nodeOutputs) ?: $this->userMessage;
        Log::debug('Flow ended without output node', [
            'path' => $processedPath,
            'lastOutputLength' => strlen($lastOutput),
        ]);

        return [
            'text' => $lastOutput,
            'images' => $this->extractedImages,
        ];
    }

    /**
     * Process a single node.
     */
    protected function processNode(array $node, array $flowData): array
    {
        $data = $node['data'] ?? [];

        // Track current node ID for token usage tracking
        $this->currentNodeId = $node['id'] ?? 'unknown';

        switch ($node['type']) {
            case 'start':
                return ['output' => $this->userMessage];

            case 'personality':
                return $this->processPersonalityNode($data);

            case 'thinking_reply':
                return $this->processThinkingReplyNode($data);

            case 'blocker':
                // Blocker is already processed first, this is just a pass-through
                return $this->processBlockerNode($data);

            case 'query':
                return $this->processQueryNode($data);

            case 'rag_query':
                // New unified RAG query node
                return $this->processRagQueryNode($data);

            case 'rag_docs_query':
                // Legacy: redirect to unified RAG query
                return $this->processRagQueryNode($data);

            case 'rag_websites_query':
                // Legacy: redirect to unified RAG query (will be combined)
                return $this->processRagQueryNode($data);

            case 'rag_images_query':
                // Legacy: redirect to unified RAG query (will be combined)
                return $this->processRagQueryNode($data);

            case 'online_query':
                return $this->processOnlineQueryNode($data);

            case 'api_query':
                return $this->processApiQueryNode($data);

            case 'if_else_image':
                return $this->processIfElseImageNode($data);

            case 'if_else_query':
                return $this->processIfElseQueryNode($data);

            case 'output':
                return $this->processOutputNode($data);

            default:
                return ['output' => $this->getLastOutput()];
        }
    }

    /**
     * Process BLOCKER node FIRST (before thinking reply).
     * This acts as a gate keeper to check if the question is in scope.
     * If blocked, the entire flow stops and returns the block message.
     *
     * IMPORTANT: Agricultural questions are ALWAYS allowed since this is
     * primarily an agricultural assistant. This prevents false blocking.
     */
    protected function processBlockerFirst(array $flowData): void
    {
        // AUTO-ALLOW: Agricultural questions should NEVER be blocked
        // This is an agricultural AI assistant, so all farming/crop questions are in scope
        if ($this->isAgriculturalQuestion($this->userMessage)) {
            Log::info('Blocker: AUTO-ALLOWING agricultural question', [
                'userMessage' => substr($this->userMessage, 0, 100),
            ]);
            return; // Allow without calling AI blocker
        }

        // AUTO-ALLOW: Meta-questions (verification requests) about previous topic
        // BUT NOT if the message contains off-topic indicators (cooking, recipes, etc.)
        if ($this->isMetaQuestion($this->userMessage) && !empty($this->topicContext)) {
            // Check if the message contains off-topic keywords that indicate a new unrelated topic
            if (!$this->containsOffTopicIndicators($this->userMessage)) {
                Log::info('Blocker: AUTO-ALLOWING meta-question about previous topic', [
                    'userMessage' => substr($this->userMessage, 0, 100),
                    'topicContext' => substr($this->topicContext, 0, 50),
                ]);
                return; // Allow without calling AI blocker
            } else {
                Log::info('Blocker: Meta-question contains off-topic indicators, will check with AI', [
                    'userMessage' => substr($this->userMessage, 0, 100),
                ]);
            }
        }

        // DEFAULT BLOCK: If message contains clear off-topic indicators, block immediately
        // This works even without a configured blocker node
        if ($this->containsOffTopicIndicators($this->userMessage)) {
            Log::info('Blocker: BLOCKING off-topic question (default blocker)', [
                'userMessage' => substr($this->userMessage, 0, 100),
            ]);
            $this->isBlocked = true;
            $this->blockMessage = $this->getDefaultBlockMessage();
            return;
        }

        // Check if there's a blocker node with custom configuration
        $blockerNode = $this->findNodeByType($flowData['nodes'], 'blocker');
        if (!$blockerNode) {
            return; // No blocker node and passed default checks - allow
        }

        $data = $blockerNode['data'] ?? [];
        $scopeQuery = $data['scopeQuery'] ?? '';
        $blockMessage = $data['blockMessage'] ?? $this->getDefaultBlockMessage();
        $aiApiId = $data['aiApiId'] ?? null;

        Log::debug('Processing Blocker FIRST', [
            'nodeId' => $blockerNode['id'],
            'hasScopeQuery' => !empty($scopeQuery),
            'aiApiId' => $aiApiId,
        ]);

        if (empty($scopeQuery) || empty($aiApiId)) {
            // No blocker configuration - allow (already passed default off-topic check)
            return;
        }

        $apiSetting = $this->getApiSettingById($aiApiId);
        if (!$apiSetting) {
            Log::warning('Blocker: AI API not found', ['aiApiId' => $aiApiId]);
            return;
        }

        // Build a simple, clear prompt for the blocker
        // Replace merge fields in scope query
        $prompt = str_replace(
            ['@{{user_message}}', '{{user_message}}'],
            $this->userMessage,
            $scopeQuery
        );

        // Replace chat history merge field
        $prompt = str_replace(
            ['@{{chat_history}}', '{{chat_history}}'],
            $this->chatHistory ?? '(No previous messages)',
            $prompt
        );

        // IMPORTANT: If there's topic context (follow-up question), include it
        // This helps the blocker understand that meta-questions like "are you sure?"
        // or "check online" are related to the previous topic
        if (!empty($this->topicContext)) {
            $prompt .= "\n\nIMPORTANT CONTEXT: This is a follow-up to a previous question about: \"" . $this->topicContext . "\"\n";
            $prompt .= "If the current message is asking for verification, confirmation, or additional information about the previous topic, consider it IN SCOPE.";
        }

        // Add simple final instruction
        $prompt .= "\n\nRespond with only one word: YES or NO";

        // Log the blocker prompt for debugging
        Log::debug('Blocker prompt being sent', [
            'prompt' => $prompt,
            'userMessage' => $this->userMessage,
        ]);

        try {
            $response = $this->callAI($apiSetting, $prompt);
            $isAllowed = stripos(trim($response), 'yes') !== false;

            Log::debug('Blocker AI response', [
                'response' => $response,
                'isAllowed' => $isAllowed,
            ]);

            if (!$isAllowed) {
                $this->isBlocked = true;
                $this->blockMessage = $blockMessage;
            }
        } catch (\Exception $e) {
            Log::error('Blocker check failed: ' . $e->getMessage());
            // On error, allow the message through (fail-open)
        }
    }

    /**
     * Pre-process thinking reply node FIRST before main flow.
     * This ensures the thinking reply is available immediately.
     *
     * Thinking reply shows while the AI is processing.
     * Always show it so user knows the AI is working.
     */
    protected function processThinkingReplyFirst(array $flowData): void
    {
        $thinkingNode = $this->findNodeByType($flowData['nodes'], 'thinking_reply');

        // If no thinking node configured, still show default thinking reply
        if (!$thinkingNode) {
            $defaultReplies = $this->getDefaultThinkingReplies();
            $this->thinkingReply = $defaultReplies[array_rand($defaultReplies)];
            Log::debug('Default thinking reply (no node)', ['reply' => $this->thinkingReply]);
            return;
        }

        // Always show thinking reply so user knows AI is working

        $data = $thinkingNode['data'] ?? [];
        $queryText = $data['queryText'] ?? '';
        $staticReplies = $data['staticReplies'] ?? [];

        Log::debug('Processing Thinking Reply FIRST', [
            'nodeId' => $thinkingNode['id'],
            'hasQueryText' => !empty($queryText),
            'hasStaticReplies' => !empty($staticReplies),
            'aiApiId' => $data['aiApiId'] ?? null,
        ]);

        // Try AI-generated thinking reply first
        if (!empty($queryText) && !empty($data['aiApiId'])) {
            $apiSetting = $this->getApiSettingById($data['aiApiId']);
            if ($apiSetting) {
                // For thinking reply, we just use the user message directly
                $prompt = str_replace(
                    ['@{{user_message}}', '{{user_message}}'],
                    $this->userMessage,
                    $queryText
                );

                // Add instructions for natural Taglish thinking replies
                $prompt .= "\n\nIMPORTANT INSTRUCTIONS FOR THINKING REPLY:";
                $prompt .= "\n- Use natural casual Taglish phrases like: 'Wait lang po...', 'Tignan ko lang po...', 'Sandali lang po...', 'Sige po, sandali lang...', 'Saglit lang po...', 'Sige po...'";
                $prompt .= "\n- Do NOT say 'iniisip', 'iisipin ko lang', 'mag-iisip' - these sound unnatural and robotic";
                $prompt .= "\n- Do NOT say 'alamin natin', 'tingnan natin', 'Let's see', 'Let me check' - these imply searching";
                $prompt .= "\n- Keep it very short (1-5 words max) and casual like how Filipinos naturally speak";
                $prompt .= "\n- You are an expert who KNOWS the answer, just acknowledging the question briefly";
                $prompt .= "\n- Add '...' at the end to indicate you're about to give the full answer";

                $this->thinkingReply = $this->callAI($apiSetting, $prompt);
                Log::debug('AI Thinking reply generated', ['length' => strlen($this->thinkingReply)]);
            }
        }

        // Fallback to static replies if AI didn't generate one
        if (!$this->thinkingReply && !empty($staticReplies)) {
            $this->thinkingReply = $staticReplies[array_rand($staticReplies)];
            Log::debug('Static thinking reply selected', ['reply' => $this->thinkingReply]);
        }

        // Final fallback: Use default natural Taglish variations
        if (!$this->thinkingReply) {
            $defaultReplies = $this->getDefaultThinkingReplies();
            $this->thinkingReply = $defaultReplies[array_rand($defaultReplies)];
            Log::debug('Default thinking reply selected', ['reply' => $this->thinkingReply]);
        }
    }

    /**
     * Get default natural Taglish thinking replies.
     * These are casual, natural-sounding phrases that Filipinos commonly use.
     */
    protected function getDefaultThinkingReplies(): array
    {
        return [
            'Wait lang po...',
            'Tignan ko lang po...',
            'Sandali lang po...',
            'Sige po, sandali lang...',
            'Saglit lang po...',
            'Sige po...',
            'Ah sige po...',
            'Opo, sandali lang...',
            'Wait lang ha...',
            'Sandali lang ha...',
        ];
    }

    /**
     * Pre-process personality node to set context before main flow.
     * This ensures personality is available for all subsequent AI calls.
     */
    protected function preprocessPersonalityNode(array $flowData): void
    {
        $personalityNode = $this->findNodeByType($flowData['nodes'], 'personality');
        if (!$personalityNode) {
            return;
        }

        $data = $personalityNode['data'] ?? [];
        $this->personalityText = $data['personalityText'] ?? '';
        $this->sampleConversations = $data['sampleConversations'] ?? '';

        // IMPORTANT: Store personality text in nodeOutputs so {{output_node_X}} can be resolved
        // This allows templates to reference personality using {{output_node_2}}
        $this->nodeOutputs[$personalityNode['id']] = $this->personalityText;

        Log::debug('Personality context pre-loaded', [
            'nodeId' => $personalityNode['id'],
            'hasPersonality' => !empty($this->personalityText),
            'hasSampleConversations' => !empty($this->sampleConversations),
        ]);
    }

    /**
     * Process personality node - stores personality context for later use.
     * IMPORTANT: This node is a CONFIGURATION node, not a content node.
     * It stores personality/sample conversations as CONTEXT for AI calls,
     * but does NOT return them as output (they are NOT the response).
     */
    protected function processPersonalityNode(array $data): array
    {
        // Store personality context (may already be pre-loaded, but ensure it's set)
        $this->personalityText = $data['personalityText'] ?? '';
        $this->sampleConversations = $data['sampleConversations'] ?? '';

        Log::debug('Personality node processed (pass-through)', [
            'hasPersonality' => !empty($this->personalityText),
            'hasSampleConversations' => !empty($this->sampleConversations),
        ]);

        // IMPORTANT: Return the LAST OUTPUT (pass-through), NOT the personality text
        // The personality/sample conversations are CONTEXT for AI, not the response
        return ['output' => $this->getLastOutput()];
    }

    /**
     * Process thinking reply node during main flow traversal.
     * NOTE: Thinking reply is already processed FIRST before the main flow,
     * so this just acts as a pass-through during normal flow traversal.
     */
    protected function processThinkingReplyNode(array $data): array
    {
        // Thinking reply was already processed in processThinkingReplyFirst()
        // This node just passes through to the next node
        Log::debug('Thinking reply node (pass-through in main flow)');
        return ['output' => $this->getLastOutput()];
    }

    /**
     * Process blocker node during main flow traversal.
     * NOTE: Blocker is already processed FIRST before the main flow,
     * so this just acts as a pass-through.
     */
    protected function processBlockerNode(array $data): array
    {
        // Blocker was already processed in processBlockerFirst()
        // This node just passes through to the next node
        Log::debug('Blocker node (pass-through in main flow)');
        return ['output' => $this->getLastOutput()];
    }

    /**
     * Process query node - sends query to AI.
     * Supports web search mode via 'useWebSearch' flag in node data.
     * When topic context is available (follow-up questions), includes it in search.
     * When forceWebSearch is set (meta-questions), always uses web search.
     */
    protected function processQueryNode(array $data): array
    {
        $queryText = $data['queryText'] ?? '';
        $aiApiId = $data['aiApiId'] ?? null;
        // Use web search if node specifies OR if forceWebSearch is set (meta-questions)
        $useWebSearch = ($data['useWebSearch'] ?? false) || $this->forceWebSearch;

        Log::debug('Processing Query node', [
            'hasQueryText' => !empty($queryText),
            'aiApiId' => $aiApiId,
            'useWebSearch' => $useWebSearch,
            'forceWebSearch' => $this->forceWebSearch,
            'webSearchReason' => $this->forceWebSearch ? 'Meta-question detected (user asked to check/verify online)' : 'Node configuration',
            'hasTopicContext' => !empty($this->topicContext),
            'availableOutputs' => array_keys($this->nodeOutputs),
        ]);

        if (empty($queryText)) {
            Log::warning('Query node has no query text, returning last output');
            return ['output' => $this->getLastOutput()];
        }

        $apiSetting = $aiApiId ? $this->getApiSettingById($aiApiId) : $this->getDefaultApiSetting();
        if (!$apiSetting) {
            Log::error('Query node: No AI provider configured', ['aiApiId' => $aiApiId]);
            return ['output' => '[Error: No AI provider configured. Please configure an AI API in Settings.]'];
        }

        // Replace merge fields in the prompt
        $prompt = $this->replaceMergeFields($queryText);

        // IMPORTANT: For follow-up questions with web search, include topic context
        // This ensures the search maintains the conversation topic (e.g., mais vs palay)
        if ($useWebSearch && !empty($this->topicContext)) {
            // Extract key topic terms from the original question
            $topicHint = "Topic context from original question: " . $this->topicContext . "\n\n";
            $topicHint .= "The user is asking a follow-up question about the same topic. ";
            $topicHint .= "Make sure your answer is specifically about this topic, not a different but similar topic.\n\n";
            $topicHint .= "Current follow-up question: " . $prompt;
            $prompt = $topicHint;

            Log::debug('Query node: Added topic context for follow-up', [
                'originalTopic' => substr($this->topicContext, 0, 100),
            ]);
        }

        // Query nodes CAN use general AI knowledge (not restricted to RAG only)
        $systemPrompt = $this->buildSystemPrompt('query');

        Log::debug('Query node calling AI', [
            'provider' => $apiSetting->provider,
            'model' => $apiSetting->defaultModel,
            'promptLength' => strlen($prompt),
            'hasSystemPrompt' => !empty($systemPrompt),
            'useWebSearch' => $useWebSearch,
        ]);

        // Log the actual prompt for debugging (truncated)
        Log::debug('Query node PROMPT CONTENT', [
            'promptPreview' => substr($prompt, 0, 500) . (strlen($prompt) > 500 ? '...' : ''),
        ]);

        // CHECK: Is this an agricultural or data-intensive question?
        // Agricultural questions ALWAYS use Gemini with Google Search for latest data
        $originalUserMessage = $this->userMessage ?? $prompt;
        $isAgriculturalQ = $this->isAgriculturalQuestion($originalUserMessage);
        $isDataIntensive = $this->isDataIntensiveQuestion($originalUserMessage);

        // CRITICAL: Agricultural questions ALWAYS use Google Search (Gemini) regardless of flow config
        // This ensures farmers get current, accurate information
        if ($isAgriculturalQ || ($isDataIntensive && $useWebSearch)) {
            Log::info('=== USING GEMINI GOOGLE SEARCH (PRIMARY) ===', [
                'reason' => $isAgriculturalQ ? 'Agricultural question detected' : 'Data-intensive question detected',
                'userMessage' => substr($originalUserMessage, 0, 100),
                'isAgricultural' => $isAgriculturalQ,
                'isDataIntensive' => $isDataIntensive,
            ]);

            // Use Gemini with Google Search as PRIMARY search tool
            // This gives us real-time search results from Google
            $response = $this->performMultiAISearch($prompt, $systemPrompt);
        } else {
            // Regular single-AI call for non-agricultural questions
            $response = $this->callAI($apiSetting, $prompt, [], $systemPrompt, $useWebSearch);
        }

        Log::debug('Query node AI response received', [
            'responseLength' => strlen($response),
            'responsePreview' => substr($response, 0, 300) . (strlen($response) > 300 ? '...' : ''),
            'usedMultiAI' => $isDataIntensive && $useWebSearch,
        ]);

        return ['output' => $response];
    }

    /**
     * Process RAG Docs query node - queries Documents knowledge base.
     * Returns raw Pinecone results for use by subsequent nodes.
     */
    /**
     * Execute RAG query FIRST for product recommendation questions.
     * This runs before the main flow to prioritize local product knowledge.
     *
     * @param string $userMessage The user's question
     * @return string|null The RAG response or null if not found
     */
    protected function executeRagFirstQuery(string $userMessage): ?string
    {
        // Get RAG settings
        $ragSettings = AiRagSetting::active()->forUser($this->userId)->first();

        if (!$ragSettings || empty($ragSettings->apiKey) || empty($ragSettings->indexName)) {
            Log::warning('RAG-First: RAG not configured for user', ['userId' => $this->userId]);
            return null;
        }

        // Build a focused product search query
        $productQuery = $this->buildProductSearchQuery($userMessage);

        Log::info('=== RAG-FIRST QUERY FOR PRODUCTS ===', [
            'originalMessage' => substr($userMessage, 0, 100),
            'productQuery' => substr($productQuery, 0, 200),
        ]);

        // Enforce rate limiting
        $this->enforceRateLimit('pinecone');

        // Query Pinecone
        $result = $this->queryPineconeAssistantRaw($ragSettings->apiKey, $ragSettings->indexName, $productQuery);

        if (!empty($result['content'])) {
            $content = $result['content'];

            // Track token usage
            if ($result['inputTokens'] > 0 || $result['outputTokens'] > 0) {
                $this->trackTokenUsage('pinecone', 'node_rag_priority', $result['inputTokens'], $result['outputTokens'], 'gpt-4o (via Pinecone)');
            }

            Log::info('RAG-First query returned results', [
                'contentLength' => strlen($content),
                'hasProductImages' => strpos($content, 'PRODUCT IMAGE') !== false || strpos($content, 'storage/ai-products') !== false,
            ]);

            return $content;
        }

        Log::debug('RAG-First query: No results found');
        return null;
    }

    /**
     * @deprecated No longer used in new 6-step flow. Kept for backwards compatibility.
     * Build a focused query for product search in RAG.
     * Enhances the user's question to better match product knowledge base entries.
     *
     * @param string $userMessage The user's question
     * @return string Enhanced query for product search
     */
    protected function buildProductSearchQuery(string $userMessage): string
    {
        // First, detect the specific agricultural need to make the search targeted
        $need = $this->detectAgriculturalNeed($userMessage);

        // Build a SPECIFIC query based on the detected need
        if ($need['type'] === 'grain_filling') {
            // User wants heavy/large grains - need POTASSIUM products
            $query = "Find POTASSIUM fertilizers, K fertilizers, MOP, Muriate of Potash, SOP, Sulfate of Potash, 0-0-60, potash products for grain filling, heavy grains, large kernels in rice or corn.\n\n";
            $query .= "User question: " . $userMessage . "\n\n";
            $query .= "IMPORTANT: Only return POTASSIUM-based products. Do NOT return nitrogen products or zinc products.";
        } elseif ($need['type'] === 'vegetative_growth') {
            // User wants leaf/plant growth - need NITROGEN products
            $query = "Find NITROGEN fertilizers, urea, ammonium sulfate, 46-0-0, 21-0-0, complete fertilizer for vegetative growth, leaf development.\n\n";
            $query .= "User question: " . $userMessage . "\n\n";
            $query .= "IMPORTANT: Only return NITROGEN-based products for vegetative growth.";
        } elseif ($need['type'] === 'zinc_deficiency') {
            // User has zinc deficiency symptoms - need ZINC products
            $query = "Find ZINC fertilizers, zinc sulfate, Zintrac, chelated zinc, foliar zinc for zinc deficiency in rice or corn.\n\n";
            $query .= "User question: " . $userMessage . "\n\n";
            $query .= "IMPORTANT: Only return ZINC-based products for zinc deficiency.";
        } elseif ($need['type'] === 'pest_control') {
            $query = "Find insecticides, pesticides for pest control in rice or corn.\n\n";
            $query .= "User question: " . $userMessage;
        } elseif ($need['type'] === 'disease_control') {
            $query = "Find fungicides for disease control in rice or corn.\n\n";
            $query .= "User question: " . $userMessage;
        } else {
            // General query
            $query = "Find agricultural products that can help with: " . $userMessage;
        }

        // If the question mentions specific ingredients, add that context
        $ingredients = ['glyphosate', 'cypermethrin', 'lambda-cyhalothrin', 'chlorpyrifos', 'imidacloprid',
                       'fipronil', 'mancozeb', 'carbendazim', 'propiconazole', 'azoxystrobin',
                       'butachlor', 'pretilachlor', 'bispyribac', 'cartap', 'abamectin',
                       'emamectin', 'spinosad', 'chlorantraniliprole', 'metaldehyde', 'niclosamide'];

        foreach ($ingredients as $ingredient) {
            if (stripos($userMessage, $ingredient) !== false) {
                $query .= "\n\nSpecifically looking for products containing: " . $ingredient;
                break;
            }
        }

        // If asking about specific pests/diseases, add that context
        $pests = ['armyworm', 'fall armyworm', 'FAW', 'corn borer', 'stem borer', 'rice bug',
                  'brown planthopper', 'BPH', 'tungro', 'blast', 'bacterial leaf blight',
                  'sheath blight', 'downy mildew', 'golden snail', 'kuhol', 'apple snail', 'daga', 'rat'];

        foreach ($pests as $pest) {
            if (stripos($userMessage, $pest) !== false) {
                $query .= "\n\nTarget pest/disease: " . $pest;
                break;
            }
        }

        $query .= "\n\nInclude product name, brand, active ingredients, recommended dosage, and application method if available.";

        Log::info('Built specific RAG query', [
            'detectedNeed' => $need['type'],
            'queryPreview' => substr($query, 0, 200),
        ]);

        return $query;
    }

    /**
     * Unified RAG Query Node - queries ALL configured knowledge bases.
     * Combines results from:
     * All content types (documents, websites, images) are stored in a single Pinecone Assistant.
     * Uses AiRagSetting as the single source of truth for API settings.
     */
    protected function processRagQueryNode(array $data): array
    {
        $queryText = $data['queryText'] ?? '';

        if (empty($queryText)) {
            return ['output' => $this->getLastOutput()];
        }

        // Enforce rate limiting to prevent 429 errors from Pinecone
        $this->enforceRateLimit('pinecone');

        // Log flow step
        $this->logFlowStep('RAG Knowledge Base', 'Querying unified knowledge base...');

        // Get RAG settings (Pinecone) - single source of truth
        $ragSettings = AiRagSetting::active()->forUser($this->userId)->first();

        if (!$ragSettings || empty($ragSettings->apiKey) || empty($ragSettings->indexName)) {
            Log::warning('RAG not configured for user: ' . $this->userId);
            $this->logFlowStep('RAG Error', 'Knowledge base not configured');
            return ['output' => '[RAG Error: Knowledge base is not configured. Please configure RAG settings.]'];
        }

        // Prepare the query with merge fields replaced
        $query = $this->replaceMergeFields($queryText);

        Log::debug('RAG query', [
            'query' => substr($query, 0, 200),
            'index' => $ragSettings->indexName,
        ]);

        // Query the single unified knowledge base
        $this->logFlowStep('RAG Query', 'Searching knowledge base...');
        $result = $this->queryPineconeAssistantRaw($ragSettings->apiKey, $ragSettings->indexName, $query);

        // Track token usage
        $inputTokens = $result['inputTokens'] ?? 0;
        $outputTokens = $result['outputTokens'] ?? 0;

        if ($inputTokens > 0 || $outputTokens > 0) {
            $this->trackTokenUsage('pinecone', $this->currentNodeId, $inputTokens, $outputTokens, 'gpt-4o (via Pinecone)');
        }

        // Check results
        if (empty($result['content'])) {
            Log::debug('RAG: No results found');
            $this->logFlowStep('RAG Result', 'No matching content found');
            return ['output' => '[RAG: No relevant information found in knowledge base.]'];
        }

        $output = $result['content'];

        Log::debug('RAG result', [
            'contentLength' => strlen($output),
            'inputTokens' => $inputTokens,
            'outputTokens' => $outputTokens,
        ]);

        $this->logFlowStep('RAG Complete', strlen($output) . ' chars retrieved');

        return ['output' => trim($output)];
    }

    /**
     * Query Pinecone Assistant and return raw results with token estimates.
     * Does NOT track tokens itself - caller should aggregate and track.
     *
     * @param string $apiKey Pinecone API key
     * @param string $indexName Pinecone assistant/index name
     * @param string $query The query to search for
     * @param array $filter Optional metadata filter
     * @return array ['content' => string|null, 'inputTokens' => int, 'outputTokens' => int]
     */
    protected function queryPineconeAssistantRaw(string $apiKey, string $indexName, string $query, array $filter = []): array
    {
        // If Pinecone is already rate limited in this request, skip immediately
        if ($this->pineconeRateLimited) {
            Log::debug('Pinecone skipped - already rate limited in this request', [
                'indexName' => $indexName,
            ]);
            return ['content' => null, 'inputTokens' => 0, 'outputTokens' => 0];
        }

        try {
            $requestData = [
                'messages' => [
                    ['role' => 'user', 'content' => $query]
                ],
                'stream' => false,
                'model' => 'gpt-4o'
            ];

            if (!empty($filter)) {
                $requestData['filter'] = $filter;
            }

            $response = Http::timeout(30)
                ->withHeaders([
                    'Api-Key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("https://prod-1-data.ke.pinecone.io/assistant/chat/{$indexName}", $requestData);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['message']['content'])) {
                    $content = $data['message']['content'];

                    // Extract citations if available
                    $citations = '';
                    if (!empty($data['citations'])) {
                        $citations = "\n\nSources:\n";
                        foreach ($data['citations'] as $citation) {
                            $citations .= "- " . ($citation['reference']['file']['name'] ?? 'Unknown source') . "\n";
                        }
                    }

                    // Estimate tokens (~4 chars per token)
                    $inputTokens = (int) ceil(strlen($query) / 4);
                    $outputTokens = (int) ceil(strlen($content) / 4);

                    Log::debug('Pinecone query successful (raw)', [
                        'indexName' => $indexName,
                        'contentLength' => strlen($content),
                        'inputTokens' => $inputTokens,
                        'outputTokens' => $outputTokens,
                    ]);

                    return [
                        'content' => $content . $citations,
                        'inputTokens' => $inputTokens,
                        'outputTokens' => $outputTokens,
                    ];
                }

                return ['content' => null, 'inputTokens' => 0, 'outputTokens' => 0];
            }

            // FAIL-FAST on rate limiting (HTTP 429)
            if ($response->status() === 429) {
                $this->pineconeRateLimited = true;
                Log::warning('Pinecone rate limited (429) - FAIL FAST', [
                    'indexName' => $indexName,
                ]);
                return ['content' => null, 'inputTokens' => 0, 'outputTokens' => 0];
            }

            Log::warning('Pinecone query failed', [
                'status' => $response->status(),
                'indexName' => $indexName,
            ]);

            return ['content' => null, 'inputTokens' => 0, 'outputTokens' => 0];

        } catch (\Exception $e) {
            Log::error('Pinecone query exception: ' . $e->getMessage(), [
                'indexName' => $indexName,
            ]);
            return ['content' => null, 'inputTokens' => 0, 'outputTokens' => 0];
        }
    }

    // ===== LEGACY RAG METHODS (kept for backwards compatibility) =====
    // These now redirect to the unified processRagQueryNode() via the switch statement

    /**
     * @deprecated Use processRagQueryNode() instead
     */
    protected function processRagDocsQueryNode(array $data): array
    {
        return $this->processRagQueryNode($data);
    }

    /**
     * @deprecated Use processRagQueryNode() instead
     */
    protected function processRagWebsitesQueryNode(array $data): array
    {
        return $this->processRagQueryNode($data);
    }

    /**
     * @deprecated Use processRagQueryNode() instead
     */
    protected function processRagImagesQueryNode(array $data): array
    {
        return $this->processRagQueryNode($data);
    }

    /**
     * Track if Pinecone is rate limited for this request.
     * When true, skip subsequent RAG queries to avoid delays.
     */
    protected $pineconeRateLimited = false;

    /**
     * Query Pinecone Assistant for context.
     * FAIL-FAST on rate limiting (429) - don't retry, skip remaining RAG queries.
     *
     * @param string $apiKey Pinecone API key
     * @param string $indexName Pinecone assistant/index name
     * @param string $query The query to search for
     * @param array $filter Optional metadata filter
     * @return string|null The context retrieved from Pinecone
     */
    protected function queryPineconeAssistant(string $apiKey, string $indexName, string $query, array $filter = []): ?string
    {
        // If Pinecone is already rate limited in this request, skip immediately
        if ($this->pineconeRateLimited) {
            Log::debug('Pinecone skipped - already rate limited in this request', [
                'indexName' => $indexName,
            ]);
            return null;
        }

        try {
            $requestData = [
                'messages' => [
                    ['role' => 'user', 'content' => $query]
                ],
                'stream' => false,
                'model' => 'gpt-4o'  // Pinecone uses OpenAI models for chat
            ];

            // Add filter if provided
            if (!empty($filter)) {
                $requestData['filter'] = $filter;
            }

            $response = Http::timeout(30) // Reduced timeout
                ->withHeaders([
                    'Api-Key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("https://prod-1-data.ke.pinecone.io/assistant/chat/{$indexName}", $requestData);

            if ($response->successful()) {
                $data = $response->json();

                // Extract the response content
                if (isset($data['message']['content'])) {
                    $content = $data['message']['content'];

                    // Also extract citations if available
                    $citations = '';
                    if (!empty($data['citations'])) {
                        $citations = "\n\nSources:\n";
                        foreach ($data['citations'] as $citation) {
                            $citations .= "- " . ($citation['reference']['file']['name'] ?? 'Unknown source') . "\n";
                        }
                    }

                    // Estimate token usage for Pinecone (uses GPT-4o internally)
                    // Pinecone API doesn't return usage, so we estimate: ~4 chars per token
                    $estimatedInputTokens = (int) ceil(strlen($query) / 4);
                    $estimatedOutputTokens = (int) ceil(strlen($content) / 4);

                    // Track token usage for RAG (using OpenAI pricing since Pinecone uses GPT-4o)
                    $this->trackTokenUsage('pinecone', $this->currentNodeId, $estimatedInputTokens, $estimatedOutputTokens, 'gpt-4o (via Pinecone)');

                    Log::debug('Pinecone query successful', [
                        'indexName' => $indexName,
                        'contentLength' => strlen($content),
                        'estimatedInputTokens' => $estimatedInputTokens,
                        'estimatedOutputTokens' => $estimatedOutputTokens,
                    ]);

                    return $content . $citations;
                }

                return null;
            }

            // FAIL-FAST on rate limiting (HTTP 429) - mark as rate limited and skip
            if ($response->status() === 429) {
                $this->pineconeRateLimited = true;
                Log::warning('Pinecone rate limited (429) - FAIL FAST, skipping remaining RAG queries', [
                    'indexName' => $indexName,
                ]);
                return null;
            }

            Log::warning('Pinecone query failed', [
                'status' => $response->status(),
                'indexName' => $indexName,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Pinecone query exception: ' . $e->getMessage(), [
                'indexName' => $indexName,
            ]);
            return null;
        }
    }

    /**
     * Process online query node - web search.
     * This node always enables web search to get real-time online information.
     */
    protected function processOnlineQueryNode(array $data): array
    {
        // Enable web search for online query node
        $data['useWebSearch'] = true;

        Log::debug('Processing Online Query node (web search enabled)', [
            'hasQueryText' => !empty($data['queryText']),
            'aiApiId' => $data['aiApiId'] ?? null,
            'hasTopicContext' => !empty($this->topicContext),
        ]);

        return $this->processQueryNode($data);
    }

    /**
     * Process API query node - calls external API.
     */
    protected function processApiQueryNode(array $data): array
    {
        $apiEndpoint = $data['apiEndpoint'] ?? '';
        $method = $data['method'] ?? 'GET';
        $queryText = $data['queryText'] ?? '';

        if (empty($apiEndpoint)) {
            return ['output' => 'No API endpoint configured.'];
        }

        try {
            // Call external API
            $response = match (strtoupper($method)) {
                'POST' => Http::timeout(30)->post($apiEndpoint),
                'PUT' => Http::timeout(30)->put($apiEndpoint),
                default => Http::timeout(30)->get($apiEndpoint),
            };

            $apiResult = $response->body();

            // Process API result with AI if query text provided
            if (!empty($queryText) && !empty($data['aiApiId'])) {
                $apiSetting = $this->getApiSettingById($data['aiApiId']);
                if ($apiSetting) {
                    $prompt = $this->replaceMergeFields($queryText) . "\n\nAPI Response:\n" . $apiResult;
                    $systemPrompt = $this->buildSystemPrompt('query');
                    return ['output' => $this->callAI($apiSetting, $prompt, [], $systemPrompt)];
                }
            }

            return ['output' => $apiResult];
        } catch (\Exception $e) {
            return ['output' => 'API call failed: ' . $e->getMessage()];
        }
    }

    /**
     * Process if/else image node - checks for images.
     */
    protected function processIfElseImageNode(array $data): array
    {
        $hasImages = !empty($this->images);

        if ($hasImages) {
            // Analyze images if query text is provided
            $queryText = $data['queryText'] ?? '';
            $aiApiId = $data['aiApiId'] ?? null;

            if (!empty($queryText) && $aiApiId) {
                $apiSetting = $this->getApiSettingById($aiApiId);
                if ($apiSetting) {
                    $prompt = $this->replaceMergeFields($queryText);
                    $systemPrompt = $this->buildSystemPrompt('query');
                    $imageAnalysis = $this->callAI($apiSetting, $prompt, $this->images, $systemPrompt);
                    return ['output' => $imageAnalysis, 'nextConnector' => 'output-yes'];
                }
            }

            return ['output' => 'Images detected', 'nextConnector' => 'output-yes'];
        }

        return ['output' => $this->getLastOutput(), 'nextConnector' => 'output-no'];
    }

    /**
     * Process if/else query node - AI decides yes/no.
     * For follow-up questions, includes topic context to make better decisions.
     */
    protected function processIfElseQueryNode(array $data): array
    {
        $queryText = $data['queryText'] ?? '';
        $aiApiId = $data['aiApiId'] ?? null;

        if (empty($queryText)) {
            return ['output' => $this->getLastOutput(), 'nextConnector' => 'output-no'];
        }

        $apiSetting = $aiApiId ? $this->getApiSettingById($aiApiId) : $this->getDefaultApiSetting();
        if (!$apiSetting) {
            return ['output' => $this->getLastOutput(), 'nextConnector' => 'output-no'];
        }

        $prompt = $this->replaceMergeFields($queryText);

        // IMPORTANT: For follow-up questions, include topic context so the AI knows
        // this is a continuation about a specific topic (e.g., NK6414 corn variety)
        // and will more likely require current/online information
        if (!empty($this->topicContext)) {
            $prompt = "CONTEXT: This is a follow-up question. The original question was: \"{$this->topicContext}\"\n\n" .
                      "CURRENT FOLLOW-UP QUESTION being evaluated:\n" . $prompt . "\n\n" .
                      "NOTE: Since this is a follow-up about the same topic, if the original topic " .
                      "would benefit from online/current information, this follow-up likely would too.";

            Log::debug('If/Else Query: Added topic context for follow-up', [
                'originalTopic' => substr($this->topicContext, 0, 100),
            ]);
        }

        $prompt .= "\n\nRespond with only 'YES' or 'NO'.";
        $response = $this->callAI($apiSetting, $prompt);

        $isYes = stripos(trim($response), 'yes') !== false;

        Log::debug('If/Else Query decision', [
            'hasTopicContext' => !empty($this->topicContext),
            'decision' => $isYes ? 'YES' : 'NO',
        ]);

        return [
            'output' => $this->getLastOutput(),
            'nextConnector' => $isYes ? 'output-yes' : 'output-no'
        ];
    }

    /**
     * Process output node - final response.
     *
     * SIMPLE 5-STEP FLOW:
     * 1. Image Analysis - If images uploaded, analyze deeply
     * 2. Combine Image + Message - Full context
     * 3. Get outputs from: RAG, Web Search, AI Knowledge
     * 4. Combine all with AI using young Filipino technician personality
     * 5. Return final response
     */
    protected function processOutputNode(array $data): array
    {
        $outputType = $data['outputType'] ?? 'response';
        $aiApiId = $data['aiApiId'] ?? null;

        Log::info('Processing Output node - SIMPLE 5-STEP FLOW', [
            'outputType' => $outputType,
            'hasImages' => !empty($this->images),
        ]);

        if ($outputType === 'silent') {
            return ['output' => ''];
        }

        // Get API settings
        $apiSetting = $aiApiId ? $this->getApiSettingById($aiApiId) : $this->getDefaultApiSetting();
        if (!$apiSetting) {
            Log::warning('Output node: No AI API configured');
            return ['output' => $this->getLastOutput()];
        }

        // Get all API settings for different providers
        $geminiSetting = AiApiSetting::active()->forUser($this->userId)->forProvider('gemini')->enabled()->first();
        $openaiSetting = AiApiSetting::active()->forUser($this->userId)->forProvider('openai')->enabled()->first();

        // ================================================================
        // STEP 1: IMAGE ANALYSIS (if images uploaded)
        // ================================================================
        $imageAnalysis = '';
        $imageQuery = '';
        if (!empty($this->images)) {
            $imageQuery = "Suriin ang larawang ito nang mabuti - uri ng halaman, kulay ng dahon, kondisyon, problema";
            $this->logFlowStep('Step 1: Image Analysis', $imageQuery);
            $imageAnalysis = $this->analyzeImagesSimple($apiSetting);
            Log::info('Step 1: Image analysis done', ['length' => strlen($imageAnalysis)]);
        } else {
            $this->logFlowStep('Step 1: Image Analysis', 'No images uploaded - skipped');
        }

        // ================================================================
        // STEP 2: COMBINE IMAGE ANALYSIS + MESSAGE
        // ================================================================
        $fullRequest = $this->userMessage;
        if (!empty($imageAnalysis)) {
            $fullRequest = "USER MESSAGE: " . $this->userMessage . "\n\nIMAGE ANALYSIS:\n" . $imageAnalysis;
            $this->logFlowStep('Step 2: Combined Request', "Message + Image Analysis combined");
        } else {
            $this->logFlowStep('Step 2: User Message', $this->userMessage);
        }
        Log::info('Step 2: Full request prepared', ['length' => strlen($fullRequest)]);

        // ================================================================
        // STEP 3: GET OUTPUTS FROM SOURCES
        // a. RAG Pinecone Assistant
        // b. Web Search (Google via Gemini)
        // c. AI Knowledge (Gemini or GPT)
        // ================================================================

        // 3a. RAG Assistant - Use EXACT user message only
        $ragQuery = $this->userMessage;
        $this->logFlowStep('Step 3a: RAG Query', $ragQuery);
        $ragResult = $this->getSimpleRagResult($ragQuery);
        Log::info('Step 3a: RAG result', ['hasContent' => !empty($ragResult)]);

        // 3b. Web Search via Gemini - Philippines context
        $webQuery = "Philippines agriculture: " . $this->userMessage . " - products available in Philippines, dosage, timing";
        $webResult = '';
        if ($geminiSetting && !empty($geminiSetting->apiKey)) {
            $this->logFlowStep('Step 3b: Web Search Query', $webQuery);
            $webResult = $this->getSimpleWebSearch($geminiSetting->apiKey, $webQuery);
        } else {
            $this->logFlowStep('Step 3b: Web Search', 'No Gemini API key - skipped');
        }
        Log::info('Step 3b: Web search result', ['hasContent' => !empty($webResult)]);

        // 3c. AI Knowledge (try GPT first, then Gemini) - Philippines context
        $aiQuery = "[Philippines context] " . $this->userMessage;
        $aiKnowledge = '';
        $aiProvider = '';
        if ($openaiSetting && !empty($openaiSetting->apiKey)) {
            $aiProvider = 'GPT-4o-mini';
            $this->logFlowStep('Step 3c: AI Knowledge Query (' . $aiProvider . ')', $aiQuery);
            $aiKnowledge = $this->getSimpleAiKnowledge('openai', $openaiSetting->apiKey, $aiQuery);
        } elseif ($geminiSetting && !empty($geminiSetting->apiKey)) {
            $aiProvider = 'Gemini';
            $this->logFlowStep('Step 3c: AI Knowledge Query (' . $aiProvider . ')', $aiQuery);
            $aiKnowledge = $this->getSimpleAiKnowledge('gemini', $geminiSetting->apiKey, $aiQuery);
        } else {
            $this->logFlowStep('Step 3c: AI Knowledge', 'No API key available - skipped');
        }
        Log::info('Step 3c: AI knowledge result', ['hasContent' => !empty($aiKnowledge)]);

        // ================================================================
        // STEP 4: COMBINE ALL SOURCES WITH AI
        // ================================================================
        $combineInfo = "Combining: " .
            (!empty($ragResult) ? "RAG ✓ " : "RAG ✗ ") .
            (!empty($webResult) ? "Web ✓ " : "Web ✗ ") .
            (!empty($aiKnowledge) ? "AI ✓ " : "AI ✗ ") .
            (!empty($imageAnalysis) ? "Image ✓" : "");
        $this->logFlowStep('Step 4: Combine Sources', $combineInfo);

        // Reset extracted images before combining
        $this->extractedImages = [];

        $finalResponse = $this->combineSourcesSimple(
            $apiSetting,
            $openaiSetting,
            $geminiSetting,
            $ragResult,
            $webResult,
            $aiKnowledge,
            $imageAnalysis
        );

        // Get extracted images for lightbox display
        $extractedImages = $this->getExtractedImages();
        Log::info('Step 4: Final response ready', [
            'length' => strlen($finalResponse),
            'extractedImages' => count($extractedImages),
        ]);

        return [
            'output' => $finalResponse,
            'images' => $extractedImages,
        ];
    }

    /**
     * Simple image analysis - deeply analyze what's in the image.
     */
    protected function analyzeImagesSimple(AiApiSetting $apiSetting): string
    {
        if (empty($this->images)) {
            return '';
        }

        $prompt = "Suriin ang larawang ito nang mabuti. Ilarawan ang LAHAT ng nakikita mo:\n";
        $prompt .= "- Uri ng halaman/pananim\n";
        $prompt .= "- Kulay ng mga dahon\n";
        $prompt .= "- Kondisyon ng halaman\n";
        $prompt .= "- Kung may sakit, peste, o problema na nakikita\n";
        $prompt .= "- Anumang iba pang detalye\n\n";
        $prompt .= "Magbigay ng detalyadong obserbasyon.";

        try {
            return $this->callAI($apiSetting, $prompt, $this->images);
        } catch (\Exception $e) {
            Log::error('Image analysis failed: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Simple RAG search.
     */
    protected function getSimpleRagResult(string $query): string
    {
        $ragSettings = AiRagSetting::active()->forUser($this->userId)->first();
        if (!$ragSettings || empty($ragSettings->apiKey)) {
            return '';
        }

        $result = $this->queryPineconeAssistant(
            $ragSettings->apiKey,
            $ragSettings->indexName,
            $query
        );

        return $result ?? '';
    }

    /**
     * Simple web search using Gemini - Philippines focused.
     */
    protected function getSimpleWebSearch(string $geminiApiKey, string $query): string
    {
        $this->enforceRateLimit('gemini-web');

        $prompt = "Search for PHILIPPINES-SPECIFIC agricultural information about: " . $query . "\n\n";
        $prompt .= "IMPORTANT RULES:\n";
        $prompt .= "- Search for information relevant to PHILIPPINES agriculture ONLY\n";
        $prompt .= "- Use BRAND NAMES for fertilizers (e.g., 'Urea' not just '46-0-0', 'Complete 14-14-14' not just 'NPK')\n";
        $prompt .= "- Default land area is 1 HECTARE (ha) - NEVER use 'mu' or other units\n\n";
        $prompt .= "Return ONLY:\n";
        $prompt .= "- Specific product BRAND names sold in Philippine agri-stores (Urea, Complete, Ammosul, MOP, etc.)\n";
        $prompt .= "- Exact dosages per HECTARE (kg/ha) or per liter of water (ml/L)\n";
        $prompt .= "- Application timing for Philippine climate/seasons\n";
        $prompt .= "- Philippine Department of Agriculture recommendations if available\n";
        $prompt .= "- Do NOT return general education about nutrients - just the specific answer for Philippines";

        try {
            $response = Http::timeout(30)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$geminiApiKey}",
                [
                    'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                    'tools' => [['google_search' => new \stdClass()]],
                    'generationConfig' => ['temperature' => 0.5, 'maxOutputTokens' => 1500],
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            }
            return '';
        } catch (\Exception $e) {
            Log::error('Web search failed: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Simple AI knowledge query - Philippines focused.
     */
    protected function getSimpleAiKnowledge(string $provider, string $apiKey, string $query): string
    {
        $this->enforceRateLimit('ai-knowledge');

        $prompt = "Question (PHILIPPINES CONTEXT): " . $query . "\n\n";
        $prompt .= "IMPORTANT RULES:\n";
        $prompt .= "- Answer for PHILIPPINES agriculture ONLY\n";
        $prompt .= "- Use BRAND NAMES for fertilizers (e.g., 'Urea' not '46-0-0', 'Complete' not just 'NPK', 'Ammosul' not '21-0-0')\n";
        $prompt .= "- Default land area is 1 HECTARE (ha) - NEVER use 'mu' or other confusing units\n\n";
        $prompt .= "Provide a DIRECT, SPECIFIC answer:\n";
        $prompt .= "- Name the SINGLE BEST product BRAND available in Philippine agri-stores\n";
        $prompt .= "- Include exact dosage per HECTARE (kg/ha) or per liter (ml/L)\n";
        $prompt .= "- Include timing (when to apply)\n";
        $prompt .= "- Keep it brief and focused - no general education\n";

        $systemPrompt = "You are an expert agricultural technician based in the PHILIPPINES. You ONLY recommend products available in Philippine agricultural stores. " .
            "ALWAYS use fertilizer BRAND NAMES that Filipino farmers know: Urea (46-0-0), Complete 14-14-14, Ammosul/Ammonium Sulfate (21-0-0), MOP/Muriate of Potash (0-0-60), Solophos (0-18-0). " .
            "ALWAYS use HECTARE (ha) as the land unit - NEVER use 'mu' or other units. " .
            "Example: If asked 'what fertilizer for heavier rice grains' - answer with 'MOP (Muriate of Potash), 50kg per hectare at flowering stage' - not a lesson about N, P, K. " .
            "Always consider Philippine climate, soil conditions, and locally available products.";

        try {
            if ($provider === 'openai') {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => 1500,
                ]);

                if ($response->successful()) {
                    $result = $response->json();
                    return $result['choices'][0]['message']['content'] ?? '';
                }
            } else {
                $geminiPrompt = $systemPrompt . "\n\n" . $prompt;
                $response = Http::timeout(30)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}",
                    [
                        'contents' => [['role' => 'user', 'parts' => [['text' => $geminiPrompt]]]],
                        'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 1500],
                    ]
                );

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                }
            }
            return '';
        } catch (\Exception $e) {
            Log::error('AI knowledge query failed: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Combine all sources into final response using AI.
     */
    protected function combineSourcesSimple(
        AiApiSetting $apiSetting,
        ?AiApiSetting $openaiSetting,
        ?AiApiSetting $geminiSetting,
        string $ragResult,
        string $webResult,
        string $aiKnowledge,
        string $imageAnalysis
    ): string {
        // Build combination prompt - FOCUSED on user's MAIN concern
        $prompt = "Ikaw ay isang BATANG FILIPINO agricultural technician na DIREKTA at SPECIFIC sumagot.\n\n";
        $prompt .= "TANONG NG USER: " . $this->userMessage . "\n\n";

        if (!empty($imageAnalysis)) {
            $prompt .= "=== IMAGE ANALYSIS ===\n" . $imageAnalysis . "\n\n";
        }

        if (!empty($ragResult)) {
            $prompt .= "=== KNOWLEDGE BASE ===\n" . $ragResult . "\n\n";
        }

        if (!empty($webResult)) {
            $prompt .= "=== WEB SEARCH ===\n" . $webResult . "\n\n";
        }

        if (!empty($aiKnowledge)) {
            $prompt .= "=== AI KNOWLEDGE ===\n" . $aiKnowledge . "\n\n";
        }

        $prompt .= "=== CRITICAL INSTRUCTIONS - SUNDIN ITO NANG EKSAKTO ===\n\n";

        $prompt .= "**FERTILIZER NAMING RULES:**\n";
        $prompt .= "- ALWAYS use BRAND NAMES na kilala ng Filipino farmers:\n";
        $prompt .= "  • Urea (hindi lang '46-0-0')\n";
        $prompt .= "  • Complete 14-14-14 (hindi lang 'NPK')\n";
        $prompt .= "  • Ammosul/Ammonium Sulfate (hindi lang '21-0-0')\n";
        $prompt .= "  • MOP/Muriate of Potash (hindi lang '0-0-60')\n";
        $prompt .= "  • Solophos (hindi lang '0-18-0')\n";
        $prompt .= "- Pwedeng isama ang grade sa parenthesis: 'Urea (46-0-0)'\n\n";

        $prompt .= "**LAND AREA RULES:**\n";
        $prompt .= "- Default land area is 1 HECTARE (ha)\n";
        $prompt .= "- NEVER use 'mu' or other confusing units\n";
        $prompt .= "- Dosage format: '50 kg per hectare' or '50 kg/ha'\n\n";

        $prompt .= "**PINAKA-IMPORTANTE: DIREKTANG SAGOT MUNA!**\n";
        $prompt .= "- Sagutin AGAD ang tanong sa UNANG paragraph\n";
        $prompt .= "- Hindi kailangan ng introduction - DIRETSO sa sagot\n";
        $prompt .= "- Kung tinatanong ang SPECIFIC na abono, sabihin AGAD kung ANO ang pinaka-angkop\n\n";

        $prompt .= "**EXAMPLE NG TAMANG FORMAT:**\n";
        $prompt .= "Kung tanong: 'ano ang abono para mabigat ang butil ng palay?'\n";
        $prompt .= "TAMANG SAGOT: 'Para sa mas mabigat na butil ng palay, ang **MOP (Muriate of Potash)** ang pinaka-epektibo. I-apply ang 50 kg per hectare sa flowering stage...'\n";
        $prompt .= "MALING SAGOT: 'Ang mga abono ay may tatlong uri... N, P, at K... [tapos hinaluan lahat]'\n\n";

        $prompt .= "**MGA RULES:**\n";
        $prompt .= "1. ISANG PANGUNAHING REKOMENDASYON lang - ang pinaka-angkop sa tanong\n";
        $prompt .= "2. Kung may alternatives, ilagay sa 'Pwede ring gamitin:' section sa dulo\n";
        $prompt .= "3. Specific BRAND name, dosage per HECTARE (kg/ha o ml/L), at timing\n";
        $prompt .= "4. HUWAG ilagay lahat ng nutrients/fertilizer - yung KAILANGAN lang sa tanong\n";
        $prompt .= "5. Use emojis at formatting (bold, bullets)\n";
        $prompt .= "6. Maging friendly pero DIREKTA - parang kaibigan na expert\n\n";

        $prompt .= "**IMAGE URL RULES (IMPORTANTE!):**\n";
        $prompt .= "Kung may IMAGE URL sa knowledge base (http://...webp o .jpg), ilagay ito sa SARILING LINYA:\n";
        $prompt .= "- TAMANG FORMAT:\n";
        $prompt .= "  [text ng sagot]\n";
        $prompt .= "  \n";
        $prompt .= "  http://btc-check.test/storage/ai-products/image.webp\n";
        $prompt .= "  \n";
        $prompt .= "  [continuation ng text]\n";
        $prompt .= "- HUWAG ilagay ang URL sa gitna ng sentence\n";
        $prompt .= "- HUWAG lagyan ng 'Larawan 1:' prefix - URL lang mismo\n";
        $prompt .= "- Ang system ay automatic na magco-convert ng URL sa actual image\n\n";

        $prompt .= $this->getFormattingInstructions();

        $systemPrompt = $this->buildCombinationSystemPrompt();

        // Try GPT first (more reliable), then Gemini
        try {
            if ($openaiSetting && !empty($openaiSetting->apiKey)) {
                $this->enforceRateLimit('gpt-combine');
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $openaiSetting->apiKey,
                ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => 2000,
                ]);

                if ($response->successful()) {
                    $result = $response->json();
                    $content = $result['choices'][0]['message']['content'] ?? '';
                    if (!empty($content)) {
                        return $this->filterInternalAnalysis($content);
                    }
                }
            }

            // Fallback to Gemini
            if ($geminiSetting && !empty($geminiSetting->apiKey)) {
                $this->enforceRateLimit('gemini-combine');
                $response = Http::timeout(60)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$geminiSetting->apiKey}",
                    [
                        'contents' => [['role' => 'user', 'parts' => [['text' => $systemPrompt . "\n\n" . $prompt]]]],
                        'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 2000],
                    ]
                );

                if ($response->successful()) {
                    $data = $response->json();
                    $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    if (!empty($content)) {
                        return $this->filterInternalAnalysis($content);
                    }
                }
            }

            // Last fallback - use default AI
            return $this->callAI($apiSetting, $prompt, [], $systemPrompt);

        } catch (\Exception $e) {
            Log::error('Combine sources failed: ' . $e->getMessage());
            $this->logFlowStep('Combine Error', $e->getMessage());
            return ''; // Empty - error logged to flow modal
        }
    }

    /**
     * @deprecated Replaced by getAiDefaultKnowledge() in new 6-step flow. Kept for backwards compatibility.
     * Query AI (GPT) for base knowledge about the user's question.
     * This provides the AI's inherent knowledge as a third source.
     *
     * @param AiApiSetting $apiSetting The API settings
     * @return string AI's base knowledge response
     */
    protected function queryAiForBaseKnowledge(AiApiSetting $apiSetting): string
    {
        // Only query if we have OpenAI API key
        if (empty($apiSetting->openaiApiKey)) {
            Log::debug('AI Knowledge query skipped - no OpenAI API key');
            return '';
        }

        // Enforce rate limiting
        $this->enforceRateLimit('gpt-knowledge');

        $this->logFlowStep('AI Knowledge Query', 'Querying GPT for base agricultural knowledge...');

        // Build a focused prompt for AI base knowledge
        $prompt = "You are an expert agricultural technician in the Philippines.\n\n";
        $prompt .= "User's Question: {$this->userMessage}\n\n";

        $prompt .= "As an expert agricultural technician, provide:\n";
        $prompt .= "1. Analysis of the specific problem or need\n";
        $prompt .= "2. SPECIFIC product recommendations with exact names (not generic terms)\n";
        $prompt .= "3. Dosages per hectare or per liter of water\n";
        $prompt .= "4. Application timing and method\n";
        $prompt .= "5. Scientific explanation of why this works\n\n";
        $prompt .= "Be specific and practical. Filipino farmers need exact product names and dosages they can use.";

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiSetting->openaiApiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert agricultural technician specializing in Philippine crops like rice (palay) and corn (mais). Provide practical, specific advice with product names and dosages.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ]);

            if (!$response->successful()) {
                Log::error('AI Knowledge query failed', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 200),
                ]);
                return '';
            }

            $result = $response->json();
            $content = $result['choices'][0]['message']['content'] ?? '';

            // Track token usage
            $inputTokens = $result['usage']['prompt_tokens'] ?? 0;
            $outputTokens = $result['usage']['completion_tokens'] ?? 0;
            $this->trackTokenUsage('openai', 'ai_knowledge', $inputTokens, $outputTokens, 'gpt-4o-mini');

            Log::info('AI Knowledge query completed', [
                'contentLength' => strlen($content),
                'tokens' => $inputTokens + $outputTokens,
            ]);

            $this->logFlowStep('AI Knowledge Done', 'Retrieved AI base knowledge');

            return $content;

        } catch (\Exception $e) {
            Log::error('AI Knowledge query exception', ['error' => $e->getMessage()]);
            return '';
        }
    }

    /**
     * Extract RAG result from node outputs.
     * Includes RAG-first priority results for product recommendations.
     */
    protected function extractRagResult(): string
    {
        $results = [];

        // PRIORITY: Check for RAG-first results (product recommendations)
        if (isset($this->nodeOutputs['node_rag_priority']) && !empty($this->nodeOutputs['node_rag_priority'])) {
            $results[] = $this->nodeOutputs['node_rag_priority'];
            Log::debug('Including RAG-First priority result in RAG extraction');
        }

        foreach ($this->nodeOutputs as $nodeId => $output) {
            // Check for RAG node outputs (but not the priority one we already added)
            if ($nodeId !== 'node_rag_priority' && (strpos($nodeId, 'rag') !== false || strpos($nodeId, 'RAG') !== false)) {
                $results[] = $output;
            }
        }

        // Also check for outputs that look like RAG results
        foreach ($this->nodeOutputs as $nodeId => $output) {
            if ($nodeId !== 'node_rag_priority' && strpos($nodeId, 'rag') === false && strpos($nodeId, 'RAG') === false) {
                if (strpos($output, '[RAG:') !== false || strpos($output, 'Sources:') !== false) {
                    $results[] = $output;
                }
            }
        }

        // Combine all RAG results
        return implode("\n\n", array_filter($results));
    }

    /**
     * Extract Web Search result from node outputs.
     */
    protected function extractWebSearchResult(): string
    {
        foreach ($this->nodeOutputs as $nodeId => $output) {
            // Check for query/search node outputs (excluding RAG)
            if ((strpos($nodeId, 'query') !== false || strpos($nodeId, 'search') !== false)
                && strpos($nodeId, 'rag') === false) {
                return $output;
            }
        }

        // Get the last non-RAG output as fallback
        $lastOutput = '';
        foreach ($this->nodeOutputs as $nodeId => $output) {
            if (strpos($nodeId, 'rag') === false && strpos($output, '[RAG:') === false) {
                $lastOutput = $output;
            }
        }

        return $lastOutput;
    }

    /**
     * Check if content is valid (not empty or error message).
     */
    protected function hasValidContent(string $content): bool
    {
        if (empty(trim($content))) {
            return false;
        }

        // Check for "no results" indicators
        $noResultIndicators = [
            '[RAG: No relevant information',
            'No relevant information found',
            'No matching content found',
            'Hindi ko po nakuha',
            'Walang nahanap',
            '[Error:',
        ];

        foreach ($noResultIndicators as $indicator) {
            if (strpos($content, $indicator) !== false) {
                return false;
            }
        }

        return strlen(trim($content)) > 50; // Must have substantial content
    }

    /**
     * @deprecated Replaced by combineAllSources() in new 6-step flow. Kept for backwards compatibility.
     * Intelligently combine RAG and Web Search results.
     *
     * Logic:
     * 1. If both have content - check if they complement, then combine appropriately
     * 2. If only RAG has content - use RAG
     * 3. If only Web Search has content - use Web Search
     * 4. If neither has content - generate fallback response
     */
    protected function combineThreeSources(
        AiApiSetting $apiSetting,
        string $ragResult,
        string $webSearchResult,
        string $aiKnowledgeResult,
        bool $hasRagContent,
        bool $hasWebContent,
        bool $hasAiKnowledge
    ): string {
        $this->logFlowStep('Combining Three Sources', sprintf(
            'RAG: %s, Web: %s, AI: %s',
            $hasRagContent ? 'Yes' : 'No',
            $hasWebContent ? 'Yes' : 'No',
            $hasAiKnowledge ? 'Yes' : 'No'
        ));

        // If no sources have content, generate fallback
        if (!$hasRagContent && !$hasWebContent && !$hasAiKnowledge) {
            Log::warning('No content from any source - generating fallback');
            return $this->generateFallbackResponse($apiSetting);
        }

        // Enforce rate limiting for combination
        $this->enforceRateLimit('gemini-combine-three');

        // Build the comprehensive combination prompt
        $prompt = "=== TASK: Create Comprehensive Agricultural Report ===\n\n";
        $prompt .= "You have THREE sources of information. Combine them into a COMPLETE, WELL-FORMATTED report.\n\n";

        // Add product filtering instructions if agricultural need was detected
        if (!empty($this->detectedAgriculturalNeed) && $this->detectedAgriculturalNeed['type'] !== 'general') {
            $need = $this->detectedAgriculturalNeed;
            $prompt .= "=== CRITICAL: PRODUCT FILTERING RULES ===\n\n";
            $prompt .= "Detected Agricultural Need: **{$need['type']}**\n";
            if (!empty($need['requiredNutrient'])) {
                $prompt .= "Required Nutrient: **{$need['requiredNutrient']}**\n\n";
            }

            $prompt .= "PRODUCT FILTERING:\n";
            $prompt .= "✅ INCLUDE products that match: " . implode(', ', $need['productTypes']) . "\n";
            $prompt .= "❌ EXCLUDE products of these types: " . implode(', ', $need['excludeTypes']) . "\n\n";

            if ($need['type'] === 'grain_filling') {
                $prompt .= "⚠️ SPECIFIC RULE FOR GRAIN FILLING:\n";
                $prompt .= "- Grain filling/mabigat na bunga needs POTASSIUM (K) fertilizers\n";
                $prompt .= "- ❌ DO NOT recommend: Innosolve (nitrogen), Zintrac (zinc)\n";
                $prompt .= "- ✅ RECOMMEND: MOP (0-0-60), SOP (0-0-50), Potash fertilizers\n\n";
            }
        }

        // SOURCE 1: RAG (Knowledge Base)
        $prompt .= "=== SOURCE 1: KNOWLEDGE BASE (RAG) ===\n";
        if ($hasRagContent) {
            $prompt .= $ragResult . "\n\n";
        } else {
            $prompt .= "(No relevant content from knowledge base)\n\n";
        }

        // SOURCE 2: Web Search
        $prompt .= "=== SOURCE 2: WEB SEARCH (Online) ===\n";
        if ($hasWebContent) {
            $prompt .= $webSearchResult . "\n\n";
        } else {
            $prompt .= "(No relevant content from web search)\n\n";
        }

        // SOURCE 3: AI Knowledge (GPT)
        $prompt .= "=== SOURCE 3: AI EXPERT KNOWLEDGE (GPT) ===\n";
        if ($hasAiKnowledge) {
            $prompt .= $aiKnowledgeResult . "\n\n";
        } else {
            $prompt .= "(No AI knowledge available)\n\n";
        }

        $prompt .= "=== USER'S QUESTION ===\n";
        $prompt .= $this->userMessage . "\n\n";

        $prompt .= "=== YOUR TASK ===\n";
        $prompt .= "Create a COMPREHENSIVE, WELL-FORMATTED agricultural report by combining ALL three sources.\n\n";

        $prompt .= "REQUIREMENTS:\n";
        $prompt .= "1. Combine and synthesize information from all available sources\n";
        $prompt .= "2. Provide SPECIFIC product recommendations (exact names, not generic terms)\n";
        $prompt .= "3. Include DOSAGES (per hectare or per liter)\n";
        $prompt .= "4. Include APPLICATION TIMING (when to apply)\n";
        $prompt .= "5. Explain WHY these recommendations work\n\n";

        $prompt .= "FORMAT YOUR RESPONSE WITH:\n";
        $prompt .= "- Clear sections with headers\n";
        $prompt .= "- A '🎯 RECOMMENDED PRODUCTS' section listing specific products with dosages\n";
        $prompt .= "- Practical advice that Filipino farmers can immediately use\n\n";

        $prompt .= "=== OUTPUT FORMAT REQUIREMENTS ===\n";
        $prompt .= $this->getFormattingInstructions();

        $systemPrompt = $this->buildCombinationSystemPrompt();

        Log::info('Combining three sources', [
            'hasRag' => $hasRagContent,
            'hasWeb' => $hasWebContent,
            'hasAi' => $hasAiKnowledge,
            'detectedNeed' => $this->detectedAgriculturalNeed['type'] ?? 'general',
        ]);

        // Use GPT for combination to avoid Gemini rate limits
        $response = $this->callGptForCombination($apiSetting, $prompt, $systemPrompt);

        // Filter any leaked internal analysis
        $response = $this->filterInternalAnalysis($response);

        return $response;
    }

    /**
     * Call GPT specifically for combining sources.
     * This reduces load on Gemini and avoids rate limiting.
     *
     * @param AiApiSetting $apiSetting API settings
     * @param string $prompt The combination prompt
     * @param string $systemPrompt The system prompt
     * @return string The combined response
     */
    protected function callGptForCombination(AiApiSetting $apiSetting, string $prompt, string $systemPrompt): string
    {
        // Check if OpenAI API key is available
        if (empty($apiSetting->openaiApiKey)) {
            Log::warning('GPT combination: No OpenAI API key, falling back to default AI');
            return $this->callAI($apiSetting, $prompt, [], $systemPrompt);
        }

        $this->enforceRateLimit('gpt-combine');

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiSetting->openaiApiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 2000,
            ]);

            if (!$response->successful()) {
                Log::error('GPT combination failed', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 200),
                ]);
                // Fallback to default AI
                return $this->callAI($apiSetting, $prompt, [], $systemPrompt);
            }

            $result = $response->json();
            $content = $result['choices'][0]['message']['content'] ?? '';

            // Track token usage
            $inputTokens = $result['usage']['prompt_tokens'] ?? 0;
            $outputTokens = $result['usage']['completion_tokens'] ?? 0;
            $this->trackTokenUsage('openai', 'gpt_combination', $inputTokens, $outputTokens, 'gpt-4o-mini');

            Log::info('GPT combination completed', [
                'contentLength' => strlen($content),
                'tokens' => $inputTokens + $outputTokens,
            ]);

            return $content;

        } catch (\Exception $e) {
            Log::error('GPT combination exception', ['error' => $e->getMessage()]);
            // Fallback to default AI
            return $this->callAI($apiSetting, $prompt, [], $systemPrompt);
        }
    }

    /**
     * Intelligently combine RAG and Web Search results (legacy method).
     *
     * Logic:
     * 1. If both have content - check if they complement, then combine appropriately
     * 2. If only RAG has content - use RAG
     * 3. If only Web Search has content - use Web Search
     * 4. If neither has content - generate fallback response
     */
    protected function combineSourcesIntelligently(
        AiApiSetting $apiSetting,
        string $ragResult,
        string $webSearchResult,
        bool $hasRagContent,
        bool $hasWebContent
    ): string {
        $this->logFlowStep('Combining Sources', sprintf(
            'RAG: %s, Web: %s',
            $hasRagContent ? 'Yes' : 'No',
            $hasWebContent ? 'Yes' : 'No'
        ));

        // Case 1: Both sources have content
        if ($hasRagContent && $hasWebContent) {
            Log::info('Both RAG and Web Search have results - analyzing complementarity');
            return $this->combineBothSources($apiSetting, $ragResult, $webSearchResult);
        }

        // Case 2: Only RAG has content
        if ($hasRagContent && !$hasWebContent) {
            Log::info('Only RAG has results - formatting RAG response');
            return $this->formatSingleSource($apiSetting, $ragResult, 'knowledge_base');
        }

        // Case 3: Only Web Search has content
        if (!$hasRagContent && $hasWebContent) {
            Log::info('Only Web Search has results - formatting Web response');
            return $this->formatSingleSource($apiSetting, $webSearchResult, 'web_search');
        }

        // Case 4: Neither has content - generate helpful fallback
        Log::warning('No content from RAG or Web Search - generating fallback');
        return $this->generateFallbackResponse($apiSetting);
    }

    /**
     * Combine both RAG and Web Search results intelligently.
     * For product recommendations, RAG (local products) is prioritized.
     */
    protected function combineBothSources(AiApiSetting $apiSetting, string $ragResult, string $webSearchResult): string
    {
        // Enforce rate limiting
        $this->enforceRateLimit('gemini-combine');

        // Check if this is a product recommendation question - RAG should be prioritized
        $isProductQuestion = $this->forceRagFirst;

        $prompt = "=== TASK: Intelligently Combine Two Information Sources ===\n\n";
        $prompt .= "You have TWO sources of information about the user's question.\n\n";

        if ($isProductQuestion) {
            // For product questions, RAG (local products) comes first
            $prompt .= "SOURCE 1 - LOCAL PRODUCT DATABASE (PRIORITY - These are locally available products in Philippines):\n";
            $prompt .= "----------------------------------------\n";
            $prompt .= $ragResult . "\n\n";

            $prompt .= "SOURCE 2 - WEB SEARCH (Supplementary information):\n";
            $prompt .= "----------------------------------------\n";
            $prompt .= $webSearchResult . "\n\n";

            $prompt .= "=== MAHALAGANG PATAKARAN SA PRODUCT RECOMMENDATIONS ===\n\n";

            $prompt .= "🚨 PINAKA-KRITIKAL NA PATAKARAN - RELEVANCE FILTER:\n";
            $prompt .= "⚠️ HUWAG irekomenda ang LAHAT ng produkto mula sa database!\n";
            $prompt .= "⚠️ Irekomenda LAMANG ang mga produkto na DIREKTANG SOLUSYON sa problema!\n";
            $prompt .= "⚠️ KUNG WALANG DIREKTANG MATCH → HUWAG MAG-RECOMMEND NG KAHIT ANONG PRODUKTO!\n\n";

            $prompt .= "=== AGRICULTURAL KNOWLEDGE - NUTRIENT REQUIREMENTS ===\n";
            $prompt .= "MAHALAGANG KAALAMAN sa crop nutrition:\n\n";
            $prompt .= "🌾 GRAIN FILLING / MABIGAT NA BUNGA / MALAKI ANG BUNGA:\n";
            $prompt .= "   - Kailangan: POTASSIUM (K) / POTASH fertilizers\n";
            $prompt .= "   - Mga halimbawa: MOP (Muriate of Potash), SOP, 0-0-60, K fertilizers\n";
            $prompt .= "   - HINDI para dito: Nitrogen products (N), Zinc (Zn), Urea protectors\n";
            $prompt .= "   - Innosolve = nitrogen efficiency → HINDI para sa grain filling\n";
            $prompt .= "   - Zintrac = zinc → HINDI para sa grain filling\n\n";

            $prompt .= "🌿 VEGETATIVE GROWTH / PAG-UNLAD NG DAHON:\n";
            $prompt .= "   - Kailangan: NITROGEN (N) fertilizers\n";
            $prompt .= "   - Mga halimbawa: Urea, Ammonium sulfate, Complete 14-14-14\n\n";

            $prompt .= "🍃 ZINC DEFICIENCY (maliit na dahon, bronzing):\n";
            $prompt .= "   - Kailangan: ZINC fertilizers\n";
            $prompt .= "   - Mga halimbawa: Zintrac, Zinc sulfate\n\n";

            $prompt .= "=== KAPAG WALANG MATCHING PRODUCT ===\n";
            $prompt .= "KUNG wala sa database ang tamang produkto para sa problema:\n";
            $prompt .= "1. HUWAG mag-recommend ng hindi related na produkto\n";
            $prompt .= "2. Sabihin: 'Walang available na produkto sa database para sa [problema]'\n";
            $prompt .= "3. Magbigay ng GENERAL ADVICE kung ano ang dapat hanapin\n";
            $prompt .= "   Halimbawa: 'Para sa mabigat na bunga, maghanap ng Potassium (K) fertilizers tulad ng MOP o 0-0-60'\n\n";

            $prompt .= "=== HALIMBAWA NG TAMANG PAG-FILTER ===\n";
            $prompt .= "User: 'ano magandang ilagay para mabigat ang bunga?'\n";
            $prompt .= "Available products: Innosolve (N), Zintrac (Zn)\n";
            $prompt .= "TAMANG SAGOT: 'Walang Potassium fertilizer sa database. Para mabigat ang bunga, kailangan ng K fertilizers.'\n";
            $prompt .= "MALING SAGOT: Recommend Innosolve o Zintrac (dahil hindi sila para sa grain filling!)\n\n";

            $prompt .= "=== PAANO MAG-FILTER ===\n";
            $prompt .= "1. Alamin muna ang EKSAKTONG PROBLEMA (grain filling, vegetative, zinc deficiency, pest, disease, etc.)\n";
            $prompt .= "2. Alamin kung ANONG NUTRIENT/SOLUTION ang kailangan gamit ang AGRICULTURAL KNOWLEDGE sa itaas\n";
            $prompt .= "3. Tingnan ang bawat produkto sa SOURCE 1\n";
            $prompt .= "4. Tanungin: 'Ang produktong ito ba ay DIREKTANG SOLUSYON sa problema?'\n";
            $prompt .= "5. Kung WALA kahit isang direktang match → HUWAG mag-recommend, sabihin lang ang dapat hanapin\n";
            $prompt .= "6. Kung MAY direktang match → isama sa rekomendasyon\n\n";

            $prompt .= "PRODUCT FORMAT - BAWAT PRODUKTO AY HIWALAY:\n";
            $prompt .= "Para sa bawat recommended product, gamitin ang format na ito:\n\n";
            $prompt .= "---\n";
            $prompt .= "**🎯 [Product Name] ([Brand])**\n";
            $prompt .= "- **Uri:** [Type]\n";
            $prompt .= "- **Aktibong Sangkap:** [Active Ingredients]\n";
            $prompt .= "- **Layunin:** [Purpose - bakit ito ang tamang solusyon]\n";
            $prompt .= "- **Dosis:** [Recommended dosage]\n";
            $prompt .= "- **Paraan:** [Application method]\n";
            $prompt .= "---\n\n";

            $prompt .= "IMPORTANTE:\n";
            $prompt .= "- Maglagay ng '---' (horizontal line) BAGO at PAGKATAPOS ng bawat produkto\n";
            $prompt .= "- HUWAG isama ang mga image URL o 'Larawan ng Produkto' sa text - ang mga larawan ay ipapakita ng system nang hiwalay\n";
            $prompt .= "- Kung isa lang ang relevant na produkto, isa lang ang irekomenda - HUWAG dagdagan ng hindi relevant\n\n";
        } else {
            $prompt .= "SOURCE 1 - KNOWLEDGE BASE (RAG):\n";
            $prompt .= "----------------------------------------\n";
            $prompt .= $ragResult . "\n\n";

            $prompt .= "SOURCE 2 - WEB SEARCH:\n";
            $prompt .= "----------------------------------------\n";
            $prompt .= $webSearchResult . "\n\n";

            $prompt .= "=== HOW TO USE THESE SOURCES (INTERNAL - DO NOT OUTPUT) ===\n";
            $prompt .= "Internally decide which source(s) to use:\n";
            $prompt .= "- If both are useful, combine them seamlessly\n";
            $prompt .= "- If they contradict, use the more relevant one\n";
            $prompt .= "- If one is empty or irrelevant, use the other\n\n";
        }

        $prompt .= "=== USER'S QUESTION ===\n";
        $prompt .= $this->userMessage . "\n\n";

        $prompt .= "=== OUTPUT FORMAT REQUIREMENTS ===\n";
        $prompt .= $this->getFormattingInstructions();

        $prompt .= "\n=== CRITICAL: OUTPUT ONLY THE FINAL ANSWER ===\n";
        $prompt .= "DO NOT output any of the following:\n";
        $prompt .= "- 'COMPLEMENTARY' or 'NOT COMPLEMENTARY'\n";
        $prompt .= "- 'Source 1 is more relevant' or similar analysis\n";
        $prompt .= "- Any internal reasoning or decision-making process\n";
        $prompt .= "- Any meta-commentary about the sources\n\n";
        $prompt .= "ONLY output the final formatted answer as if you are an expert technician.\n";
        $prompt .= "Start your response directly with a greeting and the answer.\n";

        $systemPrompt = $this->buildCombinationSystemPrompt();

        Log::info('Combining sources', [
            'isProductQuestion' => $isProductQuestion,
            'ragLength' => strlen($ragResult),
            'webLength' => strlen($webSearchResult),
        ]);

        $response = $this->callAI($apiSetting, $prompt, [], $systemPrompt);

        // Safety filter: Remove any leaked internal analysis from the response
        $response = $this->filterInternalAnalysis($response);

        return $response;
    }

    /**
     * Extracted images from response - populated by filterInternalAnalysis
     */
    protected array $extractedImages = [];

    /**
     * Filter out any internal analysis that may have leaked into the response.
     * This is a safety net in case the AI outputs internal reasoning.
     * Also extracts image URLs for lightbox display.
     */
    protected function filterInternalAnalysis(string $response): string
    {
        // Patterns that indicate leaked internal analysis
        $leakedPatterns = [
            '/^(NOT |)COMPLEMENTARY[\.:].*$/mi',
            '/^Source \d+ is (more |less |)relevant.*$/mi',
            '/^(I will |Let me )?(use|prioritize|choose) Source \d+.*$/mi',
            '/^(The |Both )sources? (are |is |contradict|complement).*$/mi',
            '/^(Based on |According to )my analysis.*$/mi',
            '/^(Analyzing|Comparing) the (two |)sources?.*$/mi',
        ];

        foreach ($leakedPatterns as $pattern) {
            $response = preg_replace($pattern, '', $response);
        }

        // Clean up any resulting empty lines at the start
        $response = preg_replace('/^\s*\n+/', '', $response);

        // Extract image URLs for lightbox display (removes from text, stores in $this->extractedImages)
        $result = $this->extractImagesFromResponse($response);
        $this->extractedImages = array_merge($this->extractedImages, $result['images']);

        return trim($result['text']);
    }

    /**
     * Get extracted images from the response.
     */
    public function getExtractedImages(): array
    {
        return $this->extractedImages;
    }

    /**
     * Extract image URLs from response and return them separately for lightbox display.
     * Returns array with 'text' (cleaned response) and 'images' (array of image objects).
     */
    protected function extractImagesFromResponse(string $response): array
    {
        $images = [];
        $imageIndex = 1;

        // Pattern 1: "Larawan X: URL" or "Larawan X:\nURL"
        $response = preg_replace_callback(
            '/Larawan\s*\d*\s*:?\s*(https?:\/\/[^\s\n]+\.(?:webp|jpg|jpeg|png|gif))/i',
            function ($matches) use (&$images, &$imageIndex) {
                $url = trim($matches[1]);
                $images[] = [
                    'url' => $url,
                    'thumbnail' => $url,
                    'title' => 'Product Image ' . $imageIndex,
                    'sourceUrl' => $url,
                    'isProduct' => true,
                    'badgeClass' => 'product-badge',
                    'badgeText' => 'Product',
                ];
                $imageIndex++;
                return ''; // Remove from text
            },
            $response
        );

        // Pattern 2: Standalone image URLs on their own line
        $response = preg_replace_callback(
            '/^(https?:\/\/[^\s\n]+\.(?:webp|jpg|jpeg|png|gif))$/mi',
            function ($matches) use (&$images, &$imageIndex) {
                $url = trim($matches[1]);
                // Check if already added
                foreach ($images as $img) {
                    if ($img['url'] === $url) return '';
                }
                $images[] = [
                    'url' => $url,
                    'thumbnail' => $url,
                    'title' => 'Product Image ' . $imageIndex,
                    'sourceUrl' => $url,
                    'isProduct' => true,
                    'badgeClass' => 'product-badge',
                    'badgeText' => 'Product',
                ];
                $imageIndex++;
                return ''; // Remove from text
            },
            $response
        );

        // Pattern 3: Image URLs in the middle of text (storage/ai-products paths)
        $response = preg_replace_callback(
            '/(?<!\[|\()(https?:\/\/[^\s\n\)\]<]+\/storage\/ai-products\/[^\s\n\)\]<]+\.(?:webp|jpg|jpeg|png|gif))(?!\))/i',
            function ($matches) use (&$images, &$imageIndex) {
                $url = trim($matches[1]);
                // Check if already added
                foreach ($images as $img) {
                    if ($img['url'] === $url) return '';
                }
                $images[] = [
                    'url' => $url,
                    'thumbnail' => $url,
                    'title' => 'Product Image ' . $imageIndex,
                    'sourceUrl' => $url,
                    'isProduct' => true,
                    'badgeClass' => 'product-badge',
                    'badgeText' => 'Product',
                ];
                $imageIndex++;
                return ''; // Remove from text
            },
            $response
        );

        // Pattern 4: Any remaining image URLs (general web images)
        $response = preg_replace_callback(
            '/(https?:\/\/[^\s\n\)\]<]+\.(?:webp|jpg|jpeg|png|gif))(?!["\'\)])/i',
            function ($matches) use (&$images, &$imageIndex) {
                $url = trim($matches[1]);
                // Check if already added
                foreach ($images as $img) {
                    if ($img['url'] === $url) return '';
                }
                $isProduct = strpos($url, 'ai-products') !== false || strpos($url, 'storage') !== false;
                $images[] = [
                    'url' => $url,
                    'thumbnail' => $url,
                    'title' => ($isProduct ? 'Product' : 'Reference') . ' Image ' . $imageIndex,
                    'sourceUrl' => $url,
                    'isProduct' => $isProduct,
                    'badgeClass' => $isProduct ? 'product-badge' : 'web-badge',
                    'badgeText' => $isProduct ? 'Product' : 'Web',
                ];
                $imageIndex++;
                return ''; // Remove from text
            },
            $response
        );

        // Clean up multiple consecutive newlines and empty lines
        $response = preg_replace('/\n{3,}/', "\n\n", $response);
        $response = trim($response);

        return [
            'text' => $response,
            'images' => $images,
        ];
    }

    /**
     * @deprecated Use extractImagesFromResponse instead
     */
    protected function convertImageUrlsToEmbedded(string $response): string
    {
        $result = $this->extractImagesFromResponse($response);
        return $result['text'];
    }

    /**
     * Format a single source response with proper styling.
     */
    protected function formatSingleSource(AiApiSetting $apiSetting, string $content, string $sourceType): string
    {
        // Enforce rate limiting
        $this->enforceRateLimit('gemini-format-single');

        $prompt = "=== TASK: Format and Present Information ===\n\n";

        if ($sourceType === 'knowledge_base') {
            $prompt .= "SOURCE: Internal Knowledge Base\n";
            $prompt .= "(This is verified information from our curated database)\n\n";

            // Add relevance filtering for product recommendations
            if ($this->forceRagFirst) {
                $prompt .= "=== MAHALAGANG PATAKARAN SA PRODUCT RECOMMENDATIONS ===\n\n";

                $prompt .= "🚨 PINAKA-KRITIKAL NA PATAKARAN:\n";
                $prompt .= "⚠️ HUWAG irekomenda ang LAHAT ng produkto mula sa database!\n";
                $prompt .= "⚠️ Irekomenda LAMANG ang mga produkto na DIREKTANG SOLUSYON sa problema!\n";
                $prompt .= "⚠️ KUNG WALANG DIREKTANG MATCH → HUWAG MAG-RECOMMEND NG KAHIT ANONG PRODUKTO!\n\n";

                $prompt .= "=== AGRICULTURAL KNOWLEDGE - NUTRIENT REQUIREMENTS ===\n";
                $prompt .= "🌾 GRAIN FILLING / MABIGAT NA BUNGA / MALAKI ANG BUNGA:\n";
                $prompt .= "   - Kailangan: POTASSIUM (K) / POTASH fertilizers\n";
                $prompt .= "   - HINDI para dito: Nitrogen (N), Zinc (Zn), Urea protectors\n";
                $prompt .= "   - Innosolve = nitrogen efficiency → HINDI para sa grain filling\n";
                $prompt .= "   - Zintrac = zinc → HINDI para sa grain filling\n\n";

                $prompt .= "🌿 VEGETATIVE GROWTH: Kailangan NITROGEN (N)\n";
                $prompt .= "🍃 ZINC DEFICIENCY: Kailangan ZINC fertilizers\n\n";

                $prompt .= "=== KAPAG WALANG MATCHING PRODUCT ===\n";
                $prompt .= "KUNG wala sa database ang tamang produkto:\n";
                $prompt .= "1. HUWAG mag-recommend ng hindi related na produkto\n";
                $prompt .= "2. Sabihin: 'Walang available na produkto sa database para sa [problema]'\n";
                $prompt .= "3. Magbigay ng GENERAL ADVICE kung ano ang dapat hanapin\n\n";

                $prompt .= "PRODUCT FORMAT (kung may match):\n";
                $prompt .= "---\n";
                $prompt .= "**🎯 [Product Name] ([Brand])**\n";
                $prompt .= "- **Uri:** [Type]\n";
                $prompt .= "- **Aktibong Sangkap:** [Active Ingredients]\n";
                $prompt .= "- **Layunin:** [Purpose]\n";
                $prompt .= "- **Dosis:** [Recommended dosage]\n";
                $prompt .= "- **Paraan:** [Application method]\n";
                $prompt .= "---\n\n";
            }
        } else {
            $prompt .= "SOURCE: Web Search Results\n";
            $prompt .= "(This is current information from online sources)\n\n";
        }

        $prompt .= "RAW CONTENT:\n";
        $prompt .= "----------------------------------------\n";
        $prompt .= $content . "\n\n";

        $prompt .= "USER'S QUESTION:\n";
        $prompt .= $this->userMessage . "\n\n";

        $prompt .= "=== YOUR TASK ===\n";
        $prompt .= "Present this information in a well-formatted, easy-to-read response.\n";
        $prompt .= "Include SPECIFIC product names with dosages and timing when applicable.\n\n";

        $prompt .= "=== OUTPUT FORMAT REQUIREMENTS ===\n";
        $prompt .= $this->getFormattingInstructions();

        $systemPrompt = $this->buildCombinationSystemPrompt();

        return $this->callAI($apiSetting, $prompt, [], $systemPrompt);
    }

    /**
     * Generate a helpful fallback response when no sources have content.
     */
    protected function generateFallbackResponse(AiApiSetting $apiSetting): string
    {
        // Enforce rate limiting
        $this->enforceRateLimit('gemini-fallback');

        $prompt = "=== TASK: Provide Helpful Response ===\n\n";
        $prompt .= "The user asked a question but our knowledge base and web search didn't return specific results.\n\n";

        $prompt .= "USER'S QUESTION:\n";
        $prompt .= $this->userMessage . "\n\n";
        $prompt .= "Please provide a helpful response using your expert agricultural knowledge.\n";
        $prompt .= "Include SPECIFIC product names with dosages and application timing.\n";
        $prompt .= "Be honest if you're not certain - suggest they verify with local agricultural experts.\n\n";
        $prompt .= "=== OUTPUT FORMAT REQUIREMENTS ===\n";
        $prompt .= $this->getFormattingInstructions();

        $systemPrompt = $this->buildCombinationSystemPrompt();

        return $this->callAI($apiSetting, $prompt, [], $systemPrompt);
    }

    /**
     * Get specific product recommendations based on detected agricultural need.
     * This provides detailed, specific product info when no RAG product matches.
     *
     * @return string Specific product recommendation instructions for the AI
     */
    protected function getSpecificProductRecommendations(): string
    {
        if (empty($this->detectedAgriculturalNeed)) {
            return '';
        }

        $need = $this->detectedAgriculturalNeed;
        $recommendations = "\n=== SPECIFIC PRODUCT RECOMMENDATIONS (MANDATORY) ===\n\n";
        $recommendations .= "⚠️ WALANG PRODUKTO SA DATABASE para sa specific need ng user.\n";
        $recommendations .= "DAPAT MONG IREKOMENDA ang mga SPECIFIC PRODUCTS na ito:\n\n";

        switch ($need['type']) {
            case 'grain_filling':
                $recommendations .= "🌾 PARA SA MABIGAT/MALAKI NA BUTIL (GRAIN FILLING):\n\n";
                $recommendations .= "IREKOMENDA ANG MGA SUMUSUNOD NA POTASSIUM (K) FERTILIZERS:\n\n";
                $recommendations .= "**1. MOP (Muriate of Potash) / 0-0-60**\n";
                $recommendations .= "   - Aktibong Sangkap: Potassium Chloride (KCl) - 60% K2O\n";
                $recommendations .= "   - Dosis: 2-3 bags (100-150 kg) per hectare\n";
                $recommendations .= "   - Paggamit: Ilagay sa lupa bago mag-flowering stage\n";
                $recommendations .= "   - Para sa: Mais at Palay - pampabunga, pampabigat ng butil\n\n";
                $recommendations .= "**2. SOP (Sulfate of Potash) / 0-0-50**\n";
                $recommendations .= "   - Aktibong Sangkap: Potassium Sulfate (K2SO4) - 50% K2O\n";
                $recommendations .= "   - Dosis: 2-4 bags (100-200 kg) per hectare\n";
                $recommendations .= "   - Paggamit: Mas maganda para sa sensitive crops\n";
                $recommendations .= "   - Para sa: Mais at Palay - mas mahal pero mas safe\n\n";
                $recommendations .= "**3. Foliar Potassium (0-0-30 liquid)**\n";
                $recommendations .= "   - Dosis: 2-3 tablespoons per 16L na tubig\n";
                $recommendations .= "   - Paggamit: I-spray sa dahon tuwing reproductive stage\n";
                $recommendations .= "   - Para sa: Quick potassium boost sa panahon ng pamumulaklak\n\n";
                $recommendations .= "TIMING: Ilagay ang K fertilizer BAGO o HABANG nag-fflowering (tasseling sa mais, panicle initiation sa palay)\n\n";
                $recommendations .= "IMPORTANTE: Dapat ISAMA ang mga SPECIFIC na produkto sa itaas SA IYONG SAGOT!\n";
                $recommendations .= "HUWAG generic advice lang - magbigay ng PANGALAN ng produkto, DOSIS, at TIMING!\n";
                break;

            case 'vegetative_growth':
                $recommendations .= "🌿 PARA SA VEGETATIVE GROWTH / PAG-UNLAD NG DAHON:\n\n";
                $recommendations .= "IREKOMENDA ANG MGA SUMUSUNOD NA NITROGEN (N) FERTILIZERS:\n\n";
                $recommendations .= "**1. Urea (46-0-0)**\n";
                $recommendations .= "   - Aktibong Sangkap: 46% Nitrogen\n";
                $recommendations .= "   - Dosis: 2-4 bags (100-200 kg) per hectare\n";
                $recommendations .= "   - Paggamit: Ilagay sa lupa, takpan ng kaunting lupa\n\n";
                $recommendations .= "**2. Ammonium Sulfate (21-0-0)**\n";
                $recommendations .= "   - Aktibong Sangkap: 21% Nitrogen + 24% Sulfur\n";
                $recommendations .= "   - Dosis: 4-6 bags per hectare\n";
                $recommendations .= "   - Paggamit: Mas maganda sa acidic soil\n\n";
                $recommendations .= "**3. Complete Fertilizer (14-14-14)**\n";
                $recommendations .= "   - Dosis: 4-6 bags per hectare\n";
                $recommendations .= "   - Paggamit: Basal application sa pagtatanim\n\n";
                break;

            case 'zinc_deficiency':
                $recommendations .= "🍃 PARA SA ZINC DEFICIENCY:\n\n";
                $recommendations .= "IREKOMENDA ANG MGA SUMUSUNOD NA ZINC FERTILIZERS:\n\n";
                $recommendations .= "**1. Zinc Sulfate (ZnSO4)**\n";
                $recommendations .= "   - Dosis: 10-15 kg per hectare (soil application)\n";
                $recommendations .= "   - Foliar: 2-3 tablespoons per 16L na tubig\n\n";
                $recommendations .= "**2. Chelated Zinc (EDTA Zn)**\n";
                $recommendations .= "   - Dosis: 1-2 tablespoons per 16L na tubig\n";
                $recommendations .= "   - Paggamit: Foliar spray, mas mabilis na absorption\n\n";
                break;

            case 'pest_control':
                $recommendations .= "🐛 PARA SA PEST CONTROL:\n\n";
                $recommendations .= "Maghanap ng INSECTICIDE na angkop sa specific pest.\n";
                $recommendations .= "Common insecticides sa Philippines:\n";
                $recommendations .= "- Cypermethrin - general insecticide\n";
                $recommendations .= "- Chlorantraniliprole - para sa armyworm, stem borer\n";
                $recommendations .= "- Imidacloprid - para sa sucking insects\n\n";
                break;

            case 'disease_control':
                $recommendations .= "🦠 PARA SA DISEASE CONTROL:\n\n";
                $recommendations .= "Maghanap ng FUNGICIDE na angkop sa specific disease.\n";
                $recommendations .= "Common fungicides sa Philippines:\n";
                $recommendations .= "- Mancozeb - general fungicide\n";
                $recommendations .= "- Propiconazole - para sa rice blast, sheath blight\n";
                $recommendations .= "- Carbendazim - systemic fungicide\n\n";
                break;

            default:
                return '';
        }

        return $recommendations;
    }

    /**
     * Get formatting instructions for AI responses.
     * Uses line separation with bold text instead of indentation.
     */
    protected function getFormattingInstructions(): string
    {
        $instructions = "PINAKAMAHALAGANG PATAKARAN SA WIKA (SUNDIN NANG MAHIGPIT):\n\n";

        $instructions .= "=== TAGALOG/FILIPINO ANG PANGUNAHING WIKA ===\n";
        $instructions .= "Ikaw ay isang TAGALOG-SPEAKING agricultural expert. Ang natural na wika mo ay TAGALOG.\n";
        $instructions .= "English LANG para sa TEKNIKAL NA TERMS na walang Filipino equivalent:\n";
        $instructions .= "- Chemicals: nitrogen deficiency, zinc deficiency, NPK, foliar spray\n";
        $instructions .= "- Products: insecticide, fungicide, fertilizer (kung walang Tagalog)\n";
        $instructions .= "- Scientific: hybrid seed, photosynthesis, chlorosis\n\n";

        $instructions .= "=== BAWAL NA ENGLISH WORDS (MAY TAGALOG ITO!) ===\n";
        $instructions .= "COMMON WORDS - LAGING TAGALOG:\n";
        $instructions .= "- 'young' → 'bata pa' o 'maliit pa'\n";
        $instructions .= "- 'plant/plants' → 'halaman' o 'pananim'\n";
        $instructions .= "- 'leaves/leaf' → 'dahon'\n";
        $instructions .= "- 'soil' → 'lupa'\n";
        $instructions .= "- 'water/watering' → 'tubig' o 'pagdidilig'\n";
        $instructions .= "- 'dry' → 'tuyo'\n";
        $instructions .= "- 'healthy' → 'malusog'\n";
        $instructions .= "- 'problem' → 'problema' o 'suliranin'\n";
        $instructions .= "- 'structure' → 'anyo' o 'hugis'\n";
        $instructions .= "- 'observation' → 'obserbasyon' o 'napansin'\n";
        $instructions .= "- 'indication' → 'palatandaan' o 'senyales'\n";
        $instructions .= "- 'signs' → 'palatandaan' o 'sintomas'\n";
        $instructions .= "- 'growth stage' → 'yugto ng paglaki'\n";
        $instructions .= "- 'image' → 'larawan'\n";
        $instructions .= "- 'close-up' → 'malapit na kuha'\n\n";

        $instructions .= "SENTENCE HEADERS - LAGING TAGALOG:\n";
        $instructions .= "- 'Here's my analysis' ❌ → 'Narito po ang aking pagsusuri' ✅\n";
        $instructions .= "- 'General Impression' ❌ → 'Pangkalahatang Tingin' ✅\n";
        $instructions .= "- 'Detailed Analysis' ❌ → 'Detalyadong Pagsusuri' ✅\n";
        $instructions .= "- 'Assessment' ❌ → 'Pagsusuri' ✅\n";
        $instructions .= "- 'Diagnosis' ❌ → 'Diyagnosis' o 'Pagtukoy sa Problema' ✅\n";
        $instructions .= "- 'Recommendations' ❌ → 'Mga Rekomendasyon' ✅\n";
        $instructions .= "- 'Important Notes' ❌ → 'Mahahalagang Paalala' ✅\n";
        $instructions .= "- 'Safety Precautions' ❌ → 'Mga Pag-iingat' o 'Paalala sa Kaligtasan' ✅\n";
        $instructions .= "- 'Based on' ❌ → 'Batay sa' ✅\n";
        $instructions .= "- 'Overall' ❌ → 'Sa kabuuan' ✅\n";
        $instructions .= "- 'However' ❌ → 'Gayunpaman' o 'Pero' ✅\n";
        $instructions .= "- 'In conclusion' ❌ → 'Bilang pangwakas' ✅\n";
        $instructions .= "- 'I noticed' ❌ → 'Napansin ko po' ✅\n";
        $instructions .= "- 'Specifically' ❌ → 'Partikular' ✅\n";
        $instructions .= "- 'Image 1/2/3' ❌ → 'Larawan 1/2/3' ✅\n";
        $instructions .= "- 'What is this' ❌ → 'Ano ito' ✅\n\n";

        $instructions .= "BAWAL NA SENTENCES:\n";
        $instructions .= "- 'Mukhang young pa ang...' ❌ → 'Mukhang bata pa ang...' ✅\n";
        $instructions .= "- 'nasa early growth stage' ❌ → 'nasa unang yugto ng paglaki' ✅\n";
        $instructions .= "- 'signs ng potential problem' ❌ → 'palatandaan ng posibleng problema' ✅\n";
        $instructions .= "- 'healthy pa naman ang overall structure' ❌ → 'malusog pa naman ang kabuuang anyo' ✅\n\n";

        $instructions .= "KAILAN PWEDE ANG ENGLISH (TEKNIKAL TERMS LANG):\n";
        $instructions .= "- 'Zinc deficiency' - OK (teknikal na sakit)\n";
        $instructions .= "- 'Interveinal chlorosis' - OK (teknikal na sintomas)\n";
        $instructions .= "- 'YaraVita Zintrac 700' - OK (pangalan ng produkto)\n";
        $instructions .= "- 'Foliar spray' - OK (teknikal na paraan)\n";
        $instructions .= "- 'NPK fertilizer' - OK (teknikal na abono)\n\n";

        $instructions .= "2. STRUCTURE (WALANG INDENTATION - LINE BREAKS LANG):\n";
        $instructions .= "- Magsimula sa friendly emoji at maikling intro\n";
        $instructions .= "- Bawat punto ay nasa SARILING LINYA (walang indentation)\n";
        $instructions .= "- Gumamit ng **bold** para sa headers at importanteng terms\n";
        $instructions .= "- Maglagay ng BLANKONG LINYA sa pagitan ng sections\n";
        $instructions .= "- Gumamit ng - para sa bullet points (bawat isa sa bagong linya)\n";
        $instructions .= "- Para sa FOLLOW-UP replies (may chat history na), HUWAG sabihin 'Magandang araw po' - diretso na sa sagot\n";
        $instructions .= "- 'Magandang araw po' sa UNANG mensahe lang ng conversation\n\n";

        $instructions .= "3. EMOJIS (GAMITIN PARA FRIENDLY TONE):\n";
        $instructions .= "- Magsimula sa relevant emoji: 🌾 🌽 🌱 💧\n";
        $instructions .= "- Gumamit ng ✅ para sa benepisyo/pros\n";
        $instructions .= "- Gumamit ng ⚠️ para sa babala/cautions\n";
        $instructions .= "- Gumamit ng 💡 para sa tips\n";
        $instructions .= "- Gumamit ng 🎯 para sa rekomendasyon\n";
        $instructions .= "- 3-5 emojis kada sagot (huwag sobra)\n\n";

        $instructions .= "4. PAGPRESENTA NG DATA:\n";
        $instructions .= "- Isama ang eksaktong numero (ani: **12-15 MT/ha**)\n";
        $instructions .= "- I-bold ang brand names: **Pioneer P3482**\n";
        $instructions .= "- Isama ang presyo kung relevant\n\n";

        $instructions .= "5. MAGSALITA BILANG EKSPERTO:\n";
        $instructions .= "- Ikaw ANG expert technician - ALAM mo ang sagot\n";
        $instructions .= "- HUWAG sabihin 'base sa impormasyon na nakuha ko' o katulad\n";
        $instructions .= "- HUWAG sabihin 'tingnan natin kung makakahanap' o 'ayon sa aking research'\n";
        $instructions .= "- HUWAG banggitin ang paghahanap o pagkuha ng impormasyon\n";
        $instructions .= "- Ipresenta ang impormasyon ng may tiwala bilang IYONG expertise\n\n";

        $instructions .= "6. PAGTATAPOS:\n";
        $instructions .= "- Magtapos ng may nakakatulong na pangwakas\n";
        $instructions .= "- Mag-alok na sagutin ang mga karagdagang tanong\n\n";

        $instructions .= "HALIMBAWANG FORMAT:\n";
        $instructions .= "----------------------------------------\n";
        $instructions .= "🌽 Magandang araw po! Narito po ang mga sagot sa tanong ninyo.\n\n";
        $instructions .= "**🎯 MGA REKOMENDASYON:**\n\n";
        $instructions .= "**1. Pioneer P3482** - Hybrid na mais\n";
        $instructions .= "- Potensyal na ani: **12-15 MT/ha**\n";
        $instructions .= "- Pagkahinog: 110-115 araw\n";
        $instructions .= "- Angkop sa: Mababang lugar\n\n";
        $instructions .= "**2. Dekalb DK9919** - Isa pang magandang klase\n";
        $instructions .= "- Potensyal na ani: **11-14 MT/ha**\n";
        $instructions .= "- ✅ Lumalaban sa sakit\n\n";
        $instructions .= "**💡 DAGDAG NA PAYO:**\n\n";
        $instructions .= "- Mahalaga po ang tamang pagitan ng tanim\n";
        $instructions .= "- Maglagay ng pataba sa vegetative stage\n";
        $instructions .= "- ⚠️ Mag-ingat sa fall armyworm\n\n";
        $instructions .= "Sana po nakatulong ito! May tanong pa po ba kayo? 😊\n";
        $instructions .= "----------------------------------------\n";

        return $instructions;
    }

    /**
     * Build system prompt for source combination.
     */
    protected function buildCombinationSystemPrompt(): string
    {
        $systemPrompt = "Ikaw ay isang TAGALOG-SPEAKING agricultural expert para sa mga magsasakang Pilipino.\n";
        $systemPrompt .= "Ang NATURAL na wika mo ay TAGALOG - hindi ka nagsasalita ng Taglish o English-heavy.\n\n";

        $systemPrompt .= "IYONG TUNGKULIN:\n";
        $systemPrompt .= "- Magbigay ng tumpak at nakakatulong na payo sa pagsasaka\n";
        $systemPrompt .= "- Ipresenta ang impormasyon ng malinaw at propesyonal\n";
        $systemPrompt .= "- Magsalita sa TAGALOG na natural at madaling maintindihan ng mga farmer\n";
        $systemPrompt .= "- Maging magalang gamit ang 'po' at 'opo'\n\n";

        $systemPrompt .= "MAHIGPIT NA PATAKARAN SA WIKA:\n\n";

        $systemPrompt .= "1. TAGALOG ANG PRIMARY LANGUAGE - Hindi English, Hindi Taglish\n";
        $systemPrompt .= "   - Dapat 90%+ Tagalog ang bawat pangungusap\n";
        $systemPrompt .= "   - English PARA SA TEKNIKAL NA TERMS LANG na walang Tagalog equivalent\n\n";

        $systemPrompt .= "2. MGA TEKNIKAL TERMS NA PWEDENG ENGLISH:\n";
        $systemPrompt .= "   - Chemicals/nutrients: nitrogen, zinc, phosphorus, NPK\n";
        $systemPrompt .= "   - Scientific terms: chlorosis, deficiency, photosynthesis\n";
        $systemPrompt .= "   - Product names: YaraVita Zintrac, Pioneer P3482\n";
        $systemPrompt .= "   - Application methods: foliar spray (pero 'pagspray sa dahon' mas mabuti)\n\n";

        $systemPrompt .= "3. BAWAL NA ENGLISH WORDS (MAY TAGALOG ITO!):\n";
        $systemPrompt .= "   - 'young' → 'bata pa' o 'maliit pa'\n";
        $systemPrompt .= "   - 'plant/plants' → 'halaman' o 'pananim'\n";
        $systemPrompt .= "   - 'leaves' → 'dahon'\n";
        $systemPrompt .= "   - 'soil' → 'lupa'\n";
        $systemPrompt .= "   - 'water' → 'tubig'\n";
        $systemPrompt .= "   - 'healthy' → 'malusog'\n";
        $systemPrompt .= "   - 'problem' → 'problema'\n";
        $systemPrompt .= "   - 'observation' → 'napansin'\n";
        $systemPrompt .= "   - 'signs' → 'palatandaan'\n";
        $systemPrompt .= "   - 'image' → 'larawan'\n";
        $systemPrompt .= "   - 'analysis' → 'pagsusuri'\n\n";

        $systemPrompt .= "4. BAWAL NA SENTENCE PATTERNS:\n";
        $systemPrompt .= "   ❌ 'Mukhang young pa ang mga mais ninyo' → ✅ 'Mukhang bata pa ang mga mais ninyo'\n";
        $systemPrompt .= "   ❌ 'nasa early growth stage' → ✅ 'nasa unang yugto ng paglaki'\n";
        $systemPrompt .= "   ❌ 'signs ng potential problem' → ✅ 'palatandaan ng posibleng problema'\n";
        $systemPrompt .= "   ❌ 'Here's my analysis' → ✅ 'Narito po ang aking pagsusuri'\n";
        $systemPrompt .= "   ❌ 'Image 1/2/3' → ✅ 'Larawan 1/2/3'\n\n";

        $systemPrompt .= "MAHAHALAGANG PATAKARAN:\n";
        $systemPrompt .= "1. HUWAG sabihin 'kumonsulta sa local agricultural office' bilang pangunahing sagot\n";
        $systemPrompt .= "2. PALAGING magbigay ng tiyak at maaksyunang impormasyon\n";
        $systemPrompt .= "3. Isama ang tunay na pangalan ng produkto, numero, at data kung available\n";
        $systemPrompt .= "4. I-format ang sagot para madaling basahin (tamang spacing, bullets, bold)\n";
        $systemPrompt .= "5. PANATILIHIN ang lahat ng teknikal na detalye mula sa source content\n";
        $systemPrompt .= "6. Kung pinagsasama ang sources, seamlessly na isama - huwag banggitin 'Source 1' o 'Source 2'\n";
        $systemPrompt .= "7. HUWAG banggitin na ikaw ay 'naghanap', 'nakahanap', o 'nakalap' ng impormasyon\n";
        $systemPrompt .= "8. HUWAG sabihin ang 'base sa impormasyon na nakuha ko', 'ayon sa aking research', 'tingnan natin kung makakahanap'\n";
        $systemPrompt .= "9. Magsalita bilang EKSPERTO na ALAM na ang sagot - hindi bilang naghahanap pa\n";
        $systemPrompt .= "10. Ipresenta ang impormasyon ng may tiwala bilang sariling expertise\n";
        $systemPrompt .= "11. HUWAG i-output ang internal reasoning tulad ng 'COMPLEMENTARY', 'NOT COMPLEMENTARY', o analysis ng sources\n";
        $systemPrompt .= "12. FINAL ANSWER LANG ang i-output sa tamang format - WALANG meta-commentary tungkol sa ginagawa mo\n\n";

        // Include query rules if available
        $queryRules = AiQueryRule::getCompiledRulesForUser($this->userId);
        if (!empty($queryRules)) {
            $systemPrompt .= "ADDITIONAL RULES FROM USER:\n";
            $systemPrompt .= $queryRules . "\n";
        }

        return $systemPrompt;
    }

    /**
     * Replace merge fields in text.
     * Supports both @{{...}} and {{...}} formats for flexibility.
     *
     * Available merge fields:
     * - {{user_message}} - The user's message
     * - {{chat_history}} - The conversation history
     * - {{personality}} - The stored personality text
     * - {{output_nodeId}} - Output from a specific node
     */
    protected function replaceMergeFields(string $text): string
    {
        // Replace basic merge fields (support both formats)
        $text = str_replace(['@{{user_message}}', '{{user_message}}'], $this->userMessage, $text);
        $text = str_replace(['@{{chat_history}}', '{{chat_history}}'], $this->chatHistory, $text);

        // Replace personality merge field
        $text = str_replace(['@{{personality}}', '{{personality}}'], $this->personalityText, $text);

        // Replace node output merge fields (support both formats)
        foreach ($this->nodeOutputs as $nodeId => $output) {
            $text = str_replace(
                ["@{{output_{$nodeId}}}", "{{output_{$nodeId}}}"],
                $output,
                $text
            );
        }

        Log::debug('ReplyFlowProcessor merge fields replaced', [
            'availableOutputs' => array_keys($this->nodeOutputs),
            'hasPersonality' => !empty($this->personalityText),
            'resultLength' => strlen($text),
            'unresolvedFields' => $this->findUnresolvedMergeFields($text),
        ]);

        return $text;
    }

    /**
     * Find any unresolved merge fields in text (for debugging).
     */
    protected function findUnresolvedMergeFields(string $text): array
    {
        preg_match_all('/\{\{[^}]+\}\}/', $text, $matches);
        return $matches[0] ?? [];
    }

    /**
     * Build system prompt with personality context.
     *
     * IMPORTANT: For query nodes, we use a minimal system prompt to guide behavior.
     * For output nodes, we format and present the information properly.
     *
     * @param string $nodeType The type of node making the AI call
     *                         - 'query' = Minimal guidance for direct answers
     *                         - 'output' = Combine and format results
     */
    protected function buildSystemPrompt(string $nodeType = 'output'): string
    {
        // Load user's query rules (compiled)
        $queryRules = AiQueryRule::getCompiledRulesForUser($this->userId);

        // ================================================================
        // QUERY NODE = Comprehensive, detailed expert guidance
        // ================================================================
        if ($nodeType === 'query') {
            $prompt = "You are an elite agricultural expert AI assistant with deep knowledge of Philippine farming.\n\n";

            $prompt .= "=== RESPONSE QUALITY STANDARDS ===\n";
            $prompt .= "You MUST provide responses that are AS GOOD OR BETTER than ChatGPT. This means:\n\n";

            $prompt .= "1. COMPREHENSIVE COVERAGE:\n";
            $prompt .= "   - Provide 5-8 specific recommendations when listing options (varieties, products, methods)\n";
            $prompt .= "   - Include SPECIFIC data for each: yield (MT/ha), brand name, key features, advantages\n";
            $prompt .= "   - Cover different price points and accessibility levels\n";
            $prompt .= "   - Mention both popular mainstream options AND lesser-known quality alternatives\n\n";

            $prompt .= "2. STRUCTURED FORMAT:\n";
            $prompt .= "   - Use bullet points (-) for lists, each on its own line\n";
            $prompt .= "   - Use **bold** for important terms, product names, and section headers\n";
            $prompt .= "   - Section headers should be bold: '**Mga Rekomendasyon:**'\n";
            $prompt .= "   - For each item: **Name (Brand/Company)** - Key stats - Why it's good\n";
            $prompt .= "   - Add a '**Paano Pumili:**' section with criteria\n";
            $prompt .= "   - Leave blank lines between sections for readability\n\n";

            $prompt .= "3. USE EMOJIS FOR FRIENDLIER TONE:\n";
            $prompt .= "   - Add relevant emojis to make responses engaging: 🌾 🌽 🌱 💧 🐛 🦠 💪 ✅ ⚠️ 📋 💡 🎯\n";
            $prompt .= "   - Start response with a friendly emoji\n";
            $prompt .= "   - Use ✅ for benefits, ⚠️ for warnings, 💡 for tips\n";
            $prompt .= "   - Don't overdo it - 3-5 emojis per response is enough\n\n";

            $prompt .= "4. SPECIFIC DATA REQUIREMENTS:\n";
            $prompt .= "   - Always include yield potential in metric tons per hectare (MT/ha)\n";
            $prompt .= "   - Mention the seed company/manufacturer for each variety\n";
            $prompt .= "   - Include resistance/tolerance information (pests, diseases, drought)\n";
            $prompt .= "   - Reference actual field trial results or farmer testimonies when available\n";
            $prompt .= "   - Include price range or cost considerations when relevant\n\n";

            $prompt .= "5. ACTIONABLE GUIDANCE:\n";
            $prompt .= "   - Provide clear selection criteria based on the user's specific situation\n";
            $prompt .= "   - Include practical tips for success\n";
            $prompt .= "   - Mention timing, planting density, or other critical factors\n";
            $prompt .= "   - Address common problems and how to avoid them\n\n";

            $prompt .= "=== ACCURACY IS PARAMOUNT ===\n";
            $prompt .= "CRITICAL: Your advice affects farmers' livelihoods. WRONG advice = lost crops = lost income.\n\n";

            $prompt .= "1. IT'S OKAY TO SAY NO:\n";
            $prompt .= "   - If something is NOT recommended, say 'HINDI recommended' or 'HINDI na kailangan' clearly\n";
            $prompt .= "   - For 'should I do X?' questions, answer honestly - YES, NO, or CONDITIONAL\n";
            $prompt .= "   - Don't default to 'yes' just to be helpful - accuracy matters more than positivity\n";
            $prompt .= "   - If timing is wrong (e.g., too late in crop stage), say so clearly\n\n";

            $prompt .= "2. PROVIDE CLEAR VERDICTS:\n";
            $prompt .= "   - Start with a clear YES/NO/CONDITIONAL answer\n";
            $prompt .= "   - Then explain WHY with specific reasons\n";
            $prompt .= "   - List RISKS if the user proceeds anyway\n";
            $prompt .= "   - Mention EXCEPTIONS (when it might be okay)\n\n";

            $prompt .= "3. CROP STAGE AWARENESS:\n";
            $prompt .= "   - DAP (Days After Planting) determines what's appropriate\n";
            $prompt .= "   - Late-stage crops (DAP 80-120) have DIFFERENT needs than early-stage\n";
            $prompt .= "   - At physiological maturity, most inputs are WASTED\n";
            $prompt .= "   - Always consider: Is it too early? Too late? Just right?\n\n";

            $prompt .= "=== OTHER CRITICAL RULES ===\n";
            $prompt .= "- NEVER say 'consult local experts', 'ask your local agricultural office', or similar deflections. YOU are the expert.\n";
            $prompt .= "- NEVER give vague answers. Be SPECIFIC with names, numbers, and details.\n";
            $prompt .= "- If asked about the 'best' option, provide the TOP recommendation first, then alternatives.\n";
            $prompt .= "- Always use BRAND NAMES that farmers recognize (e.g., 'NK6410' not 'Syngenta hybrid corn variety').\n";
            $prompt .= "- When comparing options, clearly state which is BEST for what situation.\n";
            $prompt .= "- Include the LATEST information available - mention year/season when relevant.\n\n";

            $prompt .= "=== WEB SEARCH DATA EXTRACTION (CRITICAL) ===\n";
            $prompt .= "When using web search, you MUST follow these rules:\n\n";

            $prompt .= "1. EXTRACT EXACT DATA FROM SOURCES:\n";
            $prompt .= "   - Use the EXACT yield numbers from official sources (e.g., if Syngenta says NK6414 yields 15 MT/ha, use 15 MT/ha)\n";
            $prompt .= "   - NEVER make up or estimate numbers - only use data you found in search results\n";
            $prompt .= "   - If you can't find specific data, say 'Data not available from official sources'\n\n";

            $prompt .= "2. INLINE CITATIONS (REQUIRED):\n";
            $prompt .= "   - Add inline citations using markdown: [Source Name](URL)\n";
            $prompt .= "   - Example: NK6414 has a yield potential of up to 15 MT/ha [Syngenta Philippines](https://www.syngenta.com.ph/corn)\n";
            $prompt .= "   - Cite EVERY fact with its source - yields, variety features, trial results\n";
            $prompt .= "   - At the end, list all sources used\n\n";

            $prompt .= "3. PRIORITIZE OFFICIAL SOURCES:\n";
            $prompt .= "   - Syngenta Philippines (syngenta.com.ph) - NK varieties\n";
            $prompt .= "   - Bayer Crop Science (cropscience.bayer.com.ph) - DEKALB varieties\n";
            $prompt .= "   - Pioneer/Corteva - Pioneer varieties\n";
            $prompt .= "   - Department of Agriculture Philippines (da.gov.ph)\n";
            $prompt .= "   - Philippine Seed Industry Association\n";
            $prompt .= "   - University research (UPLB, PhilRice)\n\n";

            $prompt .= "4. SEARCH STRATEGY:\n";
            $prompt .= "   - Search for specific variety names + 'yield Philippines' (e.g., 'NK6414 yield Philippines')\n";
            $prompt .= "   - Search for 'high yield corn varieties Philippines 2024/2025'\n";
            $prompt .= "   - Search for 'corn derby Philippines' for actual field trial results\n";
            $prompt .= "   - Cross-reference multiple sources for accuracy\n\n";

            $prompt .= "5. DATA QUALITY CHECK:\n";
            $prompt .= "   - If your data differs from ChatGPT, your data is likely wrong - search again\n";
            $prompt .= "   - Official seed company websites have the most accurate yield potential data\n";
            $prompt .= "   - Field trial results and 'corn derby' results show real-world performance\n\n";

            // Include query rules if available
            if (!empty($queryRules)) {
                $prompt .= $queryRules . "\n";
            }

            return $prompt;
        }

        // ================================================================
        // OUTPUT NODE = Present in Taglish with PROPER FORMATTING
        // ================================================================
        $systemPrompt = "You are presenting comprehensive agricultural information to Filipino farmers.\n\n";

        $systemPrompt .= "=== FORMATTING (CRITICAL - MUST FOLLOW) ===\n";
        $systemPrompt .= "- USE **bold** for important terms, brand names, headers, and key data\n";
        $systemPrompt .= "- USE numbered lists (1. 2. 3.) for main recommendations\n";
        $systemPrompt .= "- USE bullet points (•) for sub-items\n";
        $systemPrompt .= "- ADD blank lines between sections for readability\n";
        $systemPrompt .= "- Section headers should be **bold**: **MGA REKOMENDASYON:**\n\n";

        $systemPrompt .= "=== INDENTATION RULES ===\n";
        $systemPrompt .= "- Main points: No indent, numbered (1. 2. 3.)\n";
        $systemPrompt .= "- Sub-points: 3 spaces + bullet (   •)\n";
        $systemPrompt .= "- Sub-sub-points: 6 spaces + dash (      -)\n\n";

        $systemPrompt .= "=== PRESENTATION RULES ===\n";
        $systemPrompt .= "1. Use conversational Taglish (Filipino-English mix) with 'po' for politeness\n";
        $systemPrompt .= "2. PRESERVE ALL technical details, numbers, brand names, and data\n";
        $systemPrompt .= "3. Start with a brief friendly greeting\n";
        $systemPrompt .= "4. End with a helpful closing and offer to answer more questions\n";
        $systemPrompt .= "5. Make it friendly but professional and informative\n\n";

        $systemPrompt .= "=== CRITICAL ===\n";
        $systemPrompt .= "- NEVER say 'kumonsulta sa local agricultural office' as main answer\n";
        $systemPrompt .= "- ALWAYS provide specific, actionable information\n";
        $systemPrompt .= "- Preserve all yield data, brand names, and specific details\n";
        $systemPrompt .= "- If source has 6 recommendations, output should have 6 recommendations\n";

        // Include query rules if available
        if (!empty($queryRules)) {
            $systemPrompt .= "\n" . $queryRules;
        }

        return $systemPrompt;
    }

    /**
     * Call AI API.
     * @param bool $useWebSearch If true, forces web search mode for supported providers
     */
    protected function callAI(AiApiSetting $setting, string $prompt, array $images = [], string $systemPrompt = '', bool $useWebSearch = false): string
    {
        try {
            // PRIORITY: Use Gemini with Google Search as PRIMARY AI
            // Gemini provides better real-time search results for agricultural questions
            $geminiSetting = AiApiSetting::where('usersId', $this->userId)
                ->where('provider', AiApiSetting::PROVIDER_GEMINI)
                ->where('isActive', true)
                ->where('delete_status', 'active')
                ->first();

            if ($geminiSetting) {
                Log::info('Using Gemini as PRIMARY AI (with Google Search)', [
                    'originalProvider' => $setting->provider,
                    'promptLength' => strlen($prompt),
                ]);
                return $this->callGeminiAPI($geminiSetting, $prompt, $images, $systemPrompt);
            }

            // Fallback to configured provider if Gemini not available
            Log::debug('Gemini not available, using configured provider', [
                'provider' => $setting->provider,
            ]);

            switch ($setting->provider) {
                case AiApiSetting::PROVIDER_CLAUDE:
                    return $this->callClaudeAPI($setting, $prompt, $images, $systemPrompt);
                case AiApiSetting::PROVIDER_OPENAI:
                    return $this->callOpenAIAPI($setting, $prompt, $images, $systemPrompt);
                case AiApiSetting::PROVIDER_GEMINI:
                    return $this->callGeminiAPI($setting, $prompt, $images, $systemPrompt);
                default:
                    return 'Unsupported AI provider.';
            }
        } catch (\Exception $e) {
            Log::error('AI API call failed: ' . $e->getMessage());
            $this->metadata['errors'][] = $e->getMessage();
            // Log to flow modal instead of showing in chat
            $this->logFlowStep('AI Error', 'API call failed: ' . $e->getMessage());
            return ''; // Return empty so chat doesn't show error message
        }
    }

    /**
     * Call OpenAI API with web search using Chat Completions API.
     * Uses gpt-4o with web_search tool for grounded responses.
     */
    protected function callOpenAIWithWebSearch(AiApiSetting $setting, string $prompt, string $systemPrompt = ''): string
    {
        // Enforce rate limiting to prevent 429 errors
        $this->enforceRateLimit('openai-websearch');

        $headers = [
            'Authorization' => 'Bearer ' . $setting->apiKey,
            'Content-Type' => 'application/json',
        ];

        if ($setting->organizationId) {
            $headers['OpenAI-Organization'] = $setting->organizationId;
        }

        $messages = [];

        if (!empty($systemPrompt)) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        // User message - instructions are now in Query Rules (system prompt)
        $messages[] = ['role' => 'user', 'content' => $prompt];

        // Use gpt-4o-search-preview for web search with high context for comprehensive answers
        // For agricultural questions, we need sufficient tokens to provide detailed recommendations
        // Minimum 4096 tokens to prevent truncation of comprehensive variety lists
        $maxTokens = max($setting->maxTokens ?: 4096, 4096);

        $requestData = [
            'model' => 'gpt-4o-search-preview',
            'messages' => $messages,
            'max_completion_tokens' => $maxTokens, // Ensure sufficient tokens for comprehensive responses
            'web_search_options' => [
                'search_context_size' => 'high', // Use high for more comprehensive search results
            ],
        ];

        Log::debug('Web search using max_completion_tokens: ' . $maxTokens);

        Log::info('=== CALLING OPENAI WEB SEARCH API ===', [
            'model' => 'gpt-4o-search-preview',
            'promptLength' => strlen($prompt),
            'promptPreview' => substr($prompt, 0, 200),
            'maxTokens' => $maxTokens,
            'searchContextSize' => 'high',
        ]);

        $response = Http::timeout(120)
            ->withHeaders($headers)
            ->post('https://api.openai.com/v1/chat/completions', $requestData);

        Log::info('OpenAI Web Search API response status: ' . $response->status());

        if ($response->successful()) {
            $data = $response->json();

            // Extract token usage from OpenAI response
            $usage = $data['usage'] ?? [];
            $inputTokens = $usage['prompt_tokens'] ?? 0;
            $outputTokens = $usage['completion_tokens'] ?? 0;

            // Track token usage with proper provider and node
            $this->trackTokenUsage('openai-search', $this->currentNodeId, $inputTokens, $outputTokens, 'gpt-4o-search-preview');

            $content = $data['choices'][0]['message']['content'] ?? '';
            $annotations = $data['choices'][0]['message']['annotations'] ?? [];

            Log::info('=== OPENAI WEB SEARCH SUCCESS ===', [
                'hasAnnotations' => !empty($annotations),
                'annotationCount' => count($annotations),
                'inputTokens' => $inputTokens,
                'outputTokens' => $outputTokens,
                'responsePreview' => substr($content, 0, 200),
            ]);

            // Process annotations to add INLINE citations like ChatGPT does
            // Annotations contain url_citation with start_index and end_index indicating
            // where in the response text the citation applies
            if (!empty($annotations)) {
                $content = $this->processWebSearchAnnotations($content, $annotations);
            }

            // Strip markdown formatting (AI sometimes ignores instructions)
            $content = $this->stripMarkdownFormatting($content);

            return $content;
        }

        $errorMessage = $response->json('error.message') ?? 'OpenAI Web Search API error';
        $errorBody = $response->body();
        Log::error('=== OPENAI WEB SEARCH FAILED ===', [
            'status' => $response->status(),
            'error' => $errorMessage,
            'body' => substr($errorBody, 0, 500),
        ]);

        // Fallback to regular GPT-4o if web search fails
        Log::warning('Falling back to regular GPT-4o because web search failed');
        return $this->callOpenAIAPI($setting, $prompt, [], $systemPrompt);
    }

    /**
     * Process OpenAI web search annotations.
     * Removes inline citation markers but does NOT append sources at the end.
     * Sources are hidden from the user for cleaner responses.
     *
     * @param string $content The response content
     * @param array $annotations The annotations array from OpenAI response
     * @return string Content without source citations
     */
    protected function processWebSearchAnnotations(string $content, array $annotations): string
    {
        Log::debug('Processing web search annotations (sources hidden)', [
            'totalAnnotations' => count($annotations),
        ]);

        // Remove any inline citation markers like [1], [2], etc.
        $content = preg_replace('/\[\d+\]/', '', $content);

        // Remove any markdown-style citation links that might be in the content
        // Pattern: [text](url?utm_source=openai) - remove the entire link and keep just the text
        $content = preg_replace('/\[([^\]]+)\]\([^)]*utm_source=openai[^)]*\)/', '$1', $content);

        // Remove any "Sources:" or "Mga Sanggunian:" sections that the AI might have added
        $content = preg_replace('/\n*---\n*(Sources|Mga Sanggunian|References):?\n.*$/is', '', $content);

        // Remove any trailing source URLs
        $content = preg_replace('/\n+[-•]\s*(https?:\/\/[^\s]+)\s*$/m', '', $content);

        return trim($content);
    }

    /**
     * Strip markdown formatting from AI response.
     * AI models often ignore instructions to not use markdown, so we clean it up here.
     *
     * @param string $content The AI response content
     * @return string Clean content without markdown formatting
     */
    protected function stripMarkdownFormatting(string $content): string
    {
        // Remove ** bold markers
        $content = preg_replace('/\*\*([^*]+)\*\*/u', '$1', $content);

        // Remove __ bold markers
        $content = preg_replace('/__([^_]+)__/u', '$1', $content);

        // Remove single * italic markers (but not * used in lists)
        $content = preg_replace('/(?<!\*)\*([^*\n]+)\*(?!\*)/u', '$1', $content);

        // Remove single _ italic markers
        $content = preg_replace('/(?<!_)_([^_\n]+)_(?!_)/u', '$1', $content);

        // Remove # headers (convert to plain text)
        $content = preg_replace('/^#{1,6}\s+(.*)$/mu', '$1:', $content);

        // Remove source/reference sections (various formats)
        $content = preg_replace('/\n*---\n*(Sources|Mga Sanggunian|References|Pinagkunan):?\n.*$/isu', '', $content);

        // Remove lines that are just URLs or start with URL
        $content = preg_replace('/^\s*[-•*]?\s*(https?:\/\/[^\s]+)\s*$/mu', '', $content);

        // Remove markdown link format but keep the text: [text](url) -> text
        $content = preg_replace('/\[([^\]]+)\]\([^)]+\)/u', '$1', $content);

        // Remove CJK (Chinese, Japanese, Korean) characters that sometimes appear in AI responses
        // This includes: Chinese (4E00-9FFF), Japanese Hiragana (3040-309F), Katakana (30A0-30FF),
        // Japanese punctuation (3000-303F), CJK Symbols (31C0-31EF), and extended ranges
        $content = preg_replace('/[\x{4E00}-\x{9FFF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{3000}-\x{303F}\x{31C0}-\x{31EF}\x{AC00}-\x{D7AF}]+/u', '', $content);

        // Clean up multiple blank lines
        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        // Clean up any leftover orphaned parentheses from removed CJK text
        // e.g., "mula sa (cob)" becomes cleaner after CJK removal
        $content = preg_replace('/\s+\(/', ' (', $content);
        $content = preg_replace('/\(\s+/', '(', $content);

        return trim($content);
    }

    /**
     * Call Claude API.
     */
    protected function callClaudeAPI(AiApiSetting $setting, string $prompt, array $images = [], string $systemPrompt = ''): string
    {
        // Enforce rate limiting to prevent 429 errors
        $this->enforceRateLimit('claude');

        $model = $setting->defaultModel ?: 'claude-sonnet-4-20250514';

        $messages = [];

        // Build message content
        $content = [];

        // Add images if present
        foreach ($images as $imagePath) {
            $fullPath = Storage::disk('public')->path($imagePath);
            if (file_exists($fullPath)) {
                $imageData = base64_encode(file_get_contents($fullPath));
                $mimeType = mime_content_type($fullPath);
                $content[] = [
                    'type' => 'image',
                    'source' => [
                        'type' => 'base64',
                        'media_type' => $mimeType,
                        'data' => $imageData,
                    ],
                ];
            }
        }

        // Add text
        $content[] = ['type' => 'text', 'text' => $prompt];

        $messages[] = ['role' => 'user', 'content' => $content];

        $requestData = [
            'model' => $model,
            'max_tokens' => $setting->maxTokens ?: 4096,
            'messages' => $messages,
        ];

        if (!empty($systemPrompt)) {
            $requestData['system'] = $systemPrompt;
        }

        $response = Http::timeout(120)
            ->withHeaders([
                'x-api-key' => $setting->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', $requestData);

        if ($response->successful()) {
            $data = $response->json();

            // Extract token usage from Claude response
            $usage = $data['usage'] ?? [];
            $inputTokens = $usage['input_tokens'] ?? 0;
            $outputTokens = $usage['output_tokens'] ?? 0;

            // Track token usage with proper provider and node
            $this->trackTokenUsage('claude', $this->currentNodeId, $inputTokens, $outputTokens, $model);

            return $data['content'][0]['text'] ?? '';
        }

        throw new \Exception($response->json('error.message') ?? 'Claude API error');
    }

    /**
     * Call OpenAI API.
     */
    protected function callOpenAIAPI(AiApiSetting $setting, string $prompt, array $images = [], string $systemPrompt = ''): string
    {
        // Enforce rate limiting to prevent 429 errors
        $this->enforceRateLimit('openai');

        $model = $setting->defaultModel ?: 'gpt-4o';

        $messages = [];

        // Newer models (o1, o3, gpt-5 series) don't support system messages the same way
        // They use a different approach for instructions
        $newerModels = ['o1', 'o1-mini', 'o1-preview', 'o3', 'o3-mini', 'gpt-5', 'gpt-5.2'];
        $isNewerModel = false;
        foreach ($newerModels as $newerModel) {
            if (stripos($model, $newerModel) !== false) {
                $isNewerModel = true;
                break;
            }
        }

        if (!empty($systemPrompt)) {
            if ($isNewerModel) {
                // For newer models, prepend system prompt to user message
                $prompt = "[INSTRUCTIONS]\n{$systemPrompt}\n\n[USER MESSAGE]\n{$prompt}";
            } else {
                $messages[] = ['role' => 'system', 'content' => $systemPrompt];
            }
        }

        // Build user message content
        $content = [];
        $content[] = ['type' => 'text', 'text' => $prompt];

        // Add images
        foreach ($images as $imagePath) {
            $fullPath = Storage::disk('public')->path($imagePath);
            if (file_exists($fullPath)) {
                $imageData = base64_encode(file_get_contents($fullPath));
                $mimeType = mime_content_type($fullPath);
                $content[] = [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => "data:{$mimeType};base64,{$imageData}",
                    ],
                ];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $content];

        $headers = [
            'Authorization' => 'Bearer ' . $setting->apiKey,
            'Content-Type' => 'application/json',
        ];

        if ($setting->organizationId) {
            $headers['OpenAI-Organization'] = $setting->organizationId;
        }

        // Build request data - newer models use max_completion_tokens instead of max_tokens
        // Ensure minimum 4096 tokens to prevent truncation of comprehensive responses
        $maxTokens = max($setting->maxTokens ?: 4096, 4096);

        $requestData = [
            'model' => $model,
            'messages' => $messages,
        ];

        if ($isNewerModel) {
            $requestData['max_completion_tokens'] = $maxTokens;
        } else {
            $requestData['max_tokens'] = $maxTokens;
            // Add temperature for non-reasoning models (makes answers more confident)
            // Cast to float because OpenAI API requires numeric type
            $requestData['temperature'] = (float) ($setting->temperature ?: 0.7);
        }

        Log::debug('OpenAI API request', [
            'model' => $model,
            'isNewerModel' => $isNewerModel,
            'tokenParam' => $isNewerModel ? 'max_completion_tokens' : 'max_tokens',
            'temperature' => $requestData['temperature'] ?? 'N/A',
        ]);

        $response = Http::timeout(120)
            ->withHeaders($headers)
            ->post('https://api.openai.com/v1/chat/completions', $requestData);

        if ($response->successful()) {
            $data = $response->json();

            // Extract token usage from OpenAI response
            $usage = $data['usage'] ?? [];
            $inputTokens = $usage['prompt_tokens'] ?? 0;
            $outputTokens = $usage['completion_tokens'] ?? 0;

            // Track token usage with proper provider and node
            $this->trackTokenUsage('openai', $this->currentNodeId, $inputTokens, $outputTokens, $model);

            return $data['choices'][0]['message']['content'] ?? '';
        }

        throw new \Exception($response->json('error.message') ?? 'OpenAI API error');
    }

    /**
     * Call OpenAI Search API (Responses API with web search).
     * Used for models like gpt-4o-search-preview that have web search enabled.
     */
    protected function callOpenAISearchAPI(AiApiSetting $setting, string $prompt, array $images = [], string $systemPrompt = ''): string
    {
        $model = $setting->defaultModel ?: 'gpt-4o-search-preview';

        $headers = [
            'Authorization' => 'Bearer ' . $setting->apiKey,
            'Content-Type' => 'application/json',
        ];

        if ($setting->organizationId) {
            $headers['OpenAI-Organization'] = $setting->organizationId;
        }

        // Build the input with optional system instructions
        $input = $prompt;
        if (!empty($systemPrompt)) {
            $input = "[Context: {$systemPrompt}]\n\n{$prompt}";
        }

        // Use the Responses API with web search tool
        $requestData = [
            'model' => $model,
            'tools' => [
                [
                    'type' => 'web_search_preview',
                ]
            ],
            'input' => $input,
        ];

        Log::debug('OpenAI Search API request', [
            'model' => $model,
            'inputLength' => strlen($input),
            'hasWebSearch' => true,
        ]);

        $response = Http::timeout(180) // Longer timeout for web search
            ->withHeaders($headers)
            ->post('https://api.openai.com/v1/responses', $requestData);

        if ($response->successful()) {
            $data = $response->json();

            // Extract token usage from OpenAI Responses API
            $usage = $data['usage'] ?? [];
            $inputTokens = $usage['input_tokens'] ?? $usage['prompt_tokens'] ?? 0;
            $outputTokens = $usage['output_tokens'] ?? $usage['completion_tokens'] ?? 0;

            // Track token usage with proper provider and node
            $this->trackTokenUsage('openai-search', $this->currentNodeId, $inputTokens, $outputTokens, $model);

            Log::debug('OpenAI Search API response', [
                'hasOutput' => isset($data['output']),
                'outputCount' => count($data['output'] ?? []),
                'inputTokens' => $inputTokens,
                'outputTokens' => $outputTokens,
            ]);

            // Extract the text response from the output
            $output = $data['output'] ?? [];
            foreach ($output as $item) {
                if ($item['type'] === 'message' && isset($item['content'])) {
                    foreach ($item['content'] as $content) {
                        if ($content['type'] === 'output_text') {
                            return $content['text'] ?? '';
                        }
                    }
                }
            }

            // Fallback: try to get any text content
            return json_encode($data);
        }

        $errorMessage = $response->json('error.message') ?? 'OpenAI Search API error';
        Log::error('OpenAI Search API failed', [
            'status' => $response->status(),
            'error' => $errorMessage,
            'body' => $response->body(),
        ]);

        throw new \Exception($errorMessage);
    }

    /**
     * Call Gemini API with Google Search grounding.
     */
    protected function callGeminiAPI(AiApiSetting $setting, string $prompt, array $images = [], string $systemPrompt = ''): string
    {
        // Enforce rate limiting to prevent 429 errors
        $this->enforceRateLimit('gemini');

        $model = $setting->defaultModel ?: 'gemini-2.0-flash';

        $parts = [];

        // Add text prompt (system prompt will be in systemInstruction)
        $parts[] = ['text' => $prompt];

        // Add images
        foreach ($images as $imagePath) {
            $fullPath = Storage::disk('public')->path($imagePath);
            if (file_exists($fullPath)) {
                $imageData = base64_encode(file_get_contents($fullPath));
                $mimeType = mime_content_type($fullPath);
                $parts[] = [
                    'inline_data' => [
                        'mime_type' => $mimeType,
                        'data' => $imageData,
                    ],
                ];
            }
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $setting->apiKey;

        // Build system instruction with ULTRATHINK enhanced reasoning
        $baseSystemInstruction = "=== ULTRATHINK MODE: Agricultural Research Expert ===\n\n";
        $baseSystemInstruction .= "You are an expert agricultural research assistant for Filipino farmers.\n";
        $baseSystemInstruction .= "CRITICAL: ALWAYS use Google Search to find the LATEST information.\n";
        $baseSystemInstruction .= "NEVER rely on training data - search the web for every query.\n\n";

        $baseSystemInstruction .= "RESEARCH METHODOLOGY:\n";
        $baseSystemInstruction .= "1. Search multiple official sources (seed company websites, government)\n";
        $baseSystemInstruction .= "2. Cross-reference data to ensure accuracy\n";
        $baseSystemInstruction .= "3. Prioritize official product pages over news articles\n";
        $baseSystemInstruction .= "4. Look for yield POTENTIAL (maximum), not just derby/trial results\n";
        $baseSystemInstruction .= "5. Consider local Philippine conditions and availability\n\n";

        $baseSystemInstruction .= "RESPONSE STYLE:\n";
        $baseSystemInstruction .= "- Write in Taglish (Filipino-English) with 'po' for respect\n";
        $baseSystemInstruction .= "- Be specific: include exact numbers (yield in MT/ha, days to maturity)\n";
        $baseSystemInstruction .= "- Use bullet points (-) for lists, each on its own line\n";
        $baseSystemInstruction .= "- Use **bold** for important terms and section headers\n";
        $baseSystemInstruction .= "- Start with your TOP recommendation clearly\n";
        $baseSystemInstruction .= "- Explain WHY you recommend each option\n";
        $baseSystemInstruction .= "- Add emojis for friendly tone: 🌾 🌽 🌱 💧 ✅ ⚠️ 💡 (3-5 per response)\n";

        // Add user's system prompt if provided
        if (!empty($systemPrompt)) {
            $baseSystemInstruction .= "\n" . $systemPrompt;
        }

        // Build request with Google Search grounding enabled
        $requestData = [
            // System instruction - guides the model's overall behavior
            'systemInstruction' => [
                'parts' => [['text' => $baseSystemInstruction]]
            ],
            'contents' => [['parts' => $parts]],
            'generationConfig' => [
                'maxOutputTokens' => $setting->maxTokens ?: 4096,
                'temperature' => (float) ($setting->temperature ?: 0.7),
            ],
            // Enable Google Search grounding - Gemini will search the web
            'tools' => [
                [
                    'google_search' => new \stdClass()
                ]
            ],
        ];

        Log::debug('Gemini API request with Google Search', [
            'model' => $model,
            'hasGoogleSearch' => true,
            'promptLength' => strlen($prompt),
        ]);

        $response = Http::timeout(120)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $requestData);

        if ($response->successful()) {
            $data = $response->json();

            // Extract token usage from Gemini response
            $usageMetadata = $data['usageMetadata'] ?? [];
            $inputTokens = $usageMetadata['promptTokenCount'] ?? 0;
            $outputTokens = $usageMetadata['candidatesTokenCount'] ?? 0;

            // Track token usage with proper provider and node
            $this->trackTokenUsage('gemini', $this->currentNodeId, $inputTokens, $outputTokens, $model);

            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // Check if grounding was actually used
            $groundingMetadata = $data['candidates'][0]['groundingMetadata'] ?? null;
            $searchUsed = !empty($groundingMetadata);
            $searchQueries = $groundingMetadata['webSearchQueries'] ?? [];
            $searchSources = $groundingMetadata['groundingChunks'] ?? [];

            Log::info('Gemini response with grounding info', [
                'searchUsed' => $searchUsed,
                'searchQueries' => $searchQueries,
                'sourceCount' => count($searchSources),
                'inputTokens' => $inputTokens,
                'outputTokens' => $outputTokens,
            ]);

            // Log grounding details in flow log
            if ($searchUsed) {
                $this->logFlowStep('Google Search executed', 'Queries: ' . implode(', ', array_slice($searchQueries, 0, 3)));
                $this->logFlowStep('Sources found', count($searchSources) . ' web sources retrieved');
            } else {
                $this->logFlowStep('Info', 'Response generated (grounding metadata not available in response)');
            }

            return $this->stripMarkdownFormatting($text);
        }

        throw new \Exception($response->json('error.message') ?? 'Gemini API error');
    }

    // ==================== HELPER METHODS ====================

    /**
     * Enforce rate limiting before making an external API call.
     * This prevents 429 (Too Many Requests) errors from Gemini, OpenAI, etc.
     *
     * @param string $provider The API provider name for logging
     */
    protected function enforceRateLimit(string $provider = 'unknown'): void
    {
        if (self::$lastApiCallTime > 0) {
            $elapsed = microtime(true) * 1000000 - self::$lastApiCallTime;
            if ($elapsed < self::$apiCallMinDelay) {
                $sleepTime = (int)(self::$apiCallMinDelay - $elapsed);
                Log::debug('Rate limit: waiting before API call', [
                    'provider' => $provider,
                    'sleepMs' => round($sleepTime / 1000, 1),
                ]);
                usleep($sleepTime);
            }
        }
        self::$lastApiCallTime = microtime(true) * 1000000;
    }

    /**
     * Find a node by type.
     */
    protected function findNodeByType(array $nodes, string $type): ?array
    {
        foreach ($nodes as $node) {
            if ($node['type'] === $type) {
                return $node;
            }
        }
        return null;
    }

    /**
     * Find a node by ID.
     */
    protected function findNodeById(array $nodes, string $id): ?array
    {
        foreach ($nodes as $node) {
            if ($node['id'] === $id) {
                return $node;
            }
        }
        return null;
    }

    /**
     * Get the connected node ID from a connection.
     */
    protected function getConnectedNodeId(array $connections, string $fromNodeId, string $fromConnector): ?string
    {
        foreach ($connections as $conn) {
            if ($conn['from'] === $fromNodeId && $conn['fromConnector'] === $fromConnector) {
                return $conn['to'];
            }
        }
        return null;
    }

    /**
     * Get ALL connected node IDs from a connection (for parallel processing).
     */
    protected function getAllConnectedNodeIds(array $connections, string $fromNodeId, string $fromConnector): array
    {
        $nodeIds = [];
        foreach ($connections as $conn) {
            if ($conn['from'] === $fromNodeId && $conn['fromConnector'] === $fromConnector) {
                $nodeIds[] = $conn['to'];
            }
        }
        return $nodeIds;
    }

    /**
     * Find the common downstream node that receives input from multiple source nodes.
     */
    protected function findCommonDownstreamNode(array $connections, array $sourceNodeIds): ?string
    {
        // Get all downstream nodes from each source
        $downstreamMap = [];
        foreach ($sourceNodeIds as $sourceId) {
            foreach ($connections as $conn) {
                if ($conn['from'] === $sourceId && $conn['fromConnector'] === 'output') {
                    $downstreamMap[$conn['to']] = ($downstreamMap[$conn['to']] ?? 0) + 1;
                }
            }
        }

        // Find the node that receives from multiple sources
        foreach ($downstreamMap as $nodeId => $count) {
            if ($count > 1 || count($sourceNodeIds) === 1) {
                return $nodeId;
            }
        }

        return null;
    }

    /**
     * Get the main flow node ID from start node, skipping pre-processing nodes.
     * This ensures the main flow follows the path through blocker/if_else/rag/query/output
     * instead of following connections to personality/thinking_reply which are dead-ends.
     */
    protected function getMainFlowNodeId(array $connections, array $nodes, string $fromNodeId, array $skipTypes): ?string
    {
        // Get all connections from this node
        $connectedNodeIds = [];
        foreach ($connections as $conn) {
            if ($conn['from'] === $fromNodeId && $conn['fromConnector'] === 'output') {
                $connectedNodeIds[] = $conn['to'];
            }
        }

        Log::debug('Finding main flow path from start', [
            'fromNodeId' => $fromNodeId,
            'connectedNodes' => $connectedNodeIds,
            'skipTypes' => $skipTypes,
        ]);

        // Find the first connection that is NOT a pre-processing node
        foreach ($connectedNodeIds as $nodeId) {
            $node = $this->findNodeById($nodes, $nodeId);
            if ($node && !in_array($node['type'], $skipTypes)) {
                Log::debug('Main flow path found', [
                    'selectedNode' => $nodeId,
                    'nodeType' => $node['type'],
                ]);
                return $nodeId;
            }
        }

        // If all connections are pre-processing nodes, try to find a node that has
        // an output connection (not a dead-end)
        foreach ($connectedNodeIds as $nodeId) {
            $node = $this->findNodeById($nodes, $nodeId);
            if ($node) {
                // Check if this node has any output connections
                foreach ($connections as $conn) {
                    if ($conn['from'] === $nodeId) {
                        Log::debug('Following node with output connection', [
                            'nodeId' => $nodeId,
                            'nodeType' => $node['type'],
                        ]);
                        return $nodeId;
                    }
                }
            }
        }

        // Return the first connection as fallback
        return $connectedNodeIds[0] ?? null;
    }

    /**
     * Get the last output from processed nodes.
     */
    protected function getLastOutput(): string
    {
        return end($this->nodeOutputs) ?: $this->userMessage;
    }

    /**
     * Get API setting by ID.
     */
    protected function getApiSettingById($id): ?AiApiSetting
    {
        return AiApiSetting::active()
            ->forUser($this->userId)
            ->where('id', $id)
            ->first();
    }

    /**
     * Get default API setting.
     */
    protected function getDefaultApiSetting(): ?AiApiSetting
    {
        $setting = AiApiSetting::active()
            ->forUser($this->userId)
            ->enabled()
            ->default()
            ->first();

        if (!$setting) {
            $setting = AiApiSetting::active()
                ->forUser($this->userId)
                ->enabled()
                ->first();
        }

        return $setting;
    }

    // ==================== HELPER METHODS ====================

    /**
     * Normalize user message for common abbreviations and patterns.
     * This helps the AI understand variations like "dap100" → "100 DAP".
     */
    protected function normalizeUserMessage(string $message): string
    {
        $original = $message;

        // Normalize DAP (Days After Planting) patterns:
        // "dap100" → "100 DAP (Days After Planting)"
        // "dap 100" → "100 DAP (Days After Planting)"
        // "DAP100" → "100 DAP (Days After Planting)"
        $message = preg_replace_callback(
            '/\b[Dd][Aa][Pp]\s*(\d+)/i',
            function ($matches) {
                return $matches[1] . ' DAP (Days After Planting)';
            },
            $message
        );

        // Also handle "100dap" → "100 DAP"
        $message = preg_replace_callback(
            '/\b(\d+)\s*[Dd][Aa][Pp]\b/i',
            function ($matches) {
                return $matches[1] . ' DAP (Days After Planting)';
            },
            $message
        );

        // Normalize "magpatubig" variations to make it clearer
        // "magpatubig ng dap100" is actually asking about irrigation timing
        if (preg_match('/magpatubig.*DAP/i', $message)) {
            // Add context hint for the AI
            $message = preg_replace(
                '/magpatubig/i',
                'magpatubig (irrigation/pagdidilig)',
                $message,
                1 // Only replace first occurrence
            );
        }

        if ($message !== $original) {
            Log::debug('User message normalized', [
                'original' => $original,
                'normalized' => $message,
            ]);
        }

        return $message;
    }

    /**
     * Convert a field value to string (handles arrays).
     */
    protected function fieldToString($value): string
    {
        if (is_array($value)) {
            return implode(' ', array_map(function ($v) {
                return is_array($v) ? json_encode($v) : (string) $v;
            }, $value));
        }
        return (string) $value;
    }

    // ==================== FLOW LOGGING ====================

    /**
     * Log a step in the processing flow.
     */
    protected function logFlowStep(string $step, string $details = ''): void
    {
        $this->flowLog['steps'][] = [
            'time' => now()->format('H:i:s.v'),
            'step' => $step,
            'details' => $details,
        ];
    }

    /**
     * Get the flow log for debugging.
     */
    public function getFlowLog(): array
    {
        // Populate token usage from tracked data
        $this->flowLog['tokenUsage'] = $this->tokenUsage;

        return $this->flowLog;
    }

    /**
     * Set user message in flow log.
     */
    protected function logUserMessage(string $message): void
    {
        $this->flowLog['userMessage'] = $message;
    }

    /**
     * Set question type in flow log.
     */
    protected function logQuestionType(string $type): void
    {
        $this->flowLog['questionType'] = $type;
    }

    /**
     * Set AI provider in flow log.
     */
    protected function logAiProvider(string $provider): void
    {
        $this->flowLog['aiProvider'] = $provider;
    }

    /**
     * Set search query in flow log.
     */
    protected function logSearchQuery(string $query): void
    {
        $this->flowLog['searchQuery'] = $query;
    }

    /**
     * Set AI prompt in flow log.
     */
    protected function logAiPrompt(string $prompt): void
    {
        $this->flowLog['aiPrompt'] = $prompt;
    }

    /**
     * Set AI response in flow log.
     */
    protected function logAiResponse(string $response): void
    {
        $this->flowLog['aiResponse'] = $response;
    }

    /**
     * Set processing time in flow log.
     */
    protected function logProcessingTime(float $seconds): void
    {
        $this->flowLog['processingTime'] = round($seconds, 2);
    }

    /**
     * Perform deep analysis on uploaded images using Gemini Vision.
     * Analyzes up to 10 images together as a group for comprehensive understanding.
     *
     * @param array $imagePaths Array of image paths from storage
     * @param string $userMessage User's message/question about the images
     * @param string|null $topicContext Optional topic context for follow-up questions
     * @return array Analysis result with 'success', 'analysis', and 'summary' keys
     */
    public function analyzeUploadedImages(array $imagePaths, string $userMessage = '', ?string $topicContext = null): array
    {
        if (empty($imagePaths)) {
            return [
                'success' => false,
                'analysis' => '',
                'summary' => 'No images provided for analysis.',
            ];
        }

        $this->logFlowStep('Image Analysis', 'Analyzing ' . count($imagePaths) . ' uploaded image(s)');

        // Get Gemini API setting (required for Vision)
        $geminiSetting = AiApiSetting::where('usersId', $this->userId)
            ->where('provider', AiApiSetting::PROVIDER_GEMINI)
            ->where('isActive', true)
            ->where('delete_status', 'active')
            ->first();

        if (!$geminiSetting || !$geminiSetting->apiKey) {
            Log::warning('Gemini API not configured for image analysis', ['userId' => $this->userId]);
            return [
                'success' => false,
                'analysis' => '',
                'summary' => 'Image analysis is not available. Please configure Gemini API.',
            ];
        }

        // Enforce rate limiting to prevent 429 errors
        $this->enforceRateLimit('gemini-vision');

        // Build comprehensive analysis prompt
        $analysisPrompt = $this->buildImageAnalysisPrompt($imagePaths, $userMessage, $topicContext);

        try {
            // Use gemini-2.0-flash for vision (supports multimodal)
            $model = 'gemini-2.0-flash';
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $geminiSetting->apiKey;

            // Build parts array with images
            $parts = [];

            // Add the analysis prompt first
            $parts[] = ['text' => $analysisPrompt];

            // Add all images (up to 10)
            $imageCount = 0;
            foreach ($imagePaths as $imagePath) {
                if ($imageCount >= 10) break;

                $fullPath = Storage::disk('public')->path($imagePath);
                if (file_exists($fullPath)) {
                    $imageData = base64_encode(file_get_contents($fullPath));
                    $mimeType = mime_content_type($fullPath);

                    // Check file size (Gemini has limits)
                    $fileSize = filesize($fullPath);
                    if ($fileSize > 20 * 1024 * 1024) { // 20MB limit
                        Log::warning('Image too large for analysis, skipping', [
                            'path' => $imagePath,
                            'size' => $fileSize,
                        ]);
                        continue;
                    }

                    $parts[] = [
                        'inline_data' => [
                            'mime_type' => $mimeType,
                            'data' => $imageData,
                        ],
                    ];
                    $imageCount++;

                    Log::debug('Added image for analysis', [
                        'path' => $imagePath,
                        'mimeType' => $mimeType,
                        'size' => $fileSize,
                    ]);
                }
            }

            if ($imageCount === 0) {
                return [
                    'success' => false,
                    'analysis' => '',
                    'summary' => 'Could not load any images for analysis.',
                ];
            }

            $this->logFlowStep('Deep Analysis', "Processing {$imageCount} image(s) with Gemini Vision");

            // Build request with comprehensive system instruction
            $systemInstruction = $this->buildImageAnalysisSystemPrompt();

            $requestData = [
                'systemInstruction' => [
                    'parts' => [['text' => $systemInstruction]]
                ],
                'contents' => [['parts' => $parts]],
                'generationConfig' => [
                    'maxOutputTokens' => 8192, // Allow longer responses for detailed analysis
                    'temperature' => 0.4, // Lower temperature for more accurate analysis
                ],
            ];

            Log::debug('Calling Gemini Vision for deep image analysis', [
                'imageCount' => $imageCount,
                'hasUserMessage' => !empty($userMessage),
                'hasTopicContext' => !empty($topicContext),
            ]);

            $response = Http::timeout(120) // Longer timeout for multiple images
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $requestData);

            if ($response->successful()) {
                $data = $response->json();
                $analysis = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                // Extract token usage from Gemini response
                $usageMetadata = $data['usageMetadata'] ?? [];
                $inputTokens = $usageMetadata['promptTokenCount'] ?? 0;
                $outputTokens = $usageMetadata['candidatesTokenCount'] ?? 0;

                // Track token usage with proper provider and node
                $this->trackTokenUsage('gemini', $this->currentNodeId . '_vision', $inputTokens, $outputTokens, 'gemini-2.0-flash');

                Log::info('Image analysis completed successfully', [
                    'imageCount' => $imageCount,
                    'analysisLength' => strlen($analysis),
                    'inputTokens' => $inputTokens,
                    'outputTokens' => $outputTokens,
                ]);

                $this->logFlowStep('Analysis Complete', strlen($analysis) . ' characters generated');

                // Generate a brief summary for the flow log
                $summary = Str::limit($analysis, 200);

                return [
                    'success' => true,
                    'analysis' => $this->stripMarkdownFormatting($analysis),
                    'summary' => $summary,
                    'imageCount' => $imageCount,
                ];
            }

            $errorMessage = $response->json('error.message') ?? 'Gemini Vision API error';
            Log::error('Gemini Vision API failed', [
                'status' => $response->status(),
                'error' => $errorMessage,
            ]);

            return [
                'success' => false,
                'analysis' => '',
                'summary' => 'Image analysis failed: ' . $errorMessage,
            ];

        } catch (\Exception $e) {
            Log::error('Image analysis exception: ' . $e->getMessage());
            return [
                'success' => false,
                'analysis' => '',
                'summary' => 'Error analyzing images: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build the image analysis prompt based on context.
     */
    protected function buildImageAnalysisPrompt(array $imagePaths, string $userMessage, ?string $topicContext): string
    {
        $imageCount = count($imagePaths);

        $prompt = "=== ULTRATHINK: Deep Image Analysis Mode ===\n\n";

        // CRITICAL: Language instruction at the TOP - TAGALOG FIRST
        $prompt .= "MAHIGPIT NA PATAKARAN SA WIKA:\n";
        $prompt .= "TAGALOG/FILIPINO ang PANGUNAHING WIKA. English PARA SA TEKNIKAL NA TERMS LANG.\n\n";

        $prompt .= "BAWAL ANG MGA ENGLISH PHRASES NA ITO (GAMITIN ANG TAGALOG):\n";
        $prompt .= "- 'Here's my analysis' ❌ → 'Narito ang aking pagsusuri' ✅\n";
        $prompt .= "- 'General Impression' ❌ → 'Pangkalahatang Tingin' ✅\n";
        $prompt .= "- 'Detailed Analysis per Image' ❌ → 'Detalyadong Pagsusuri sa Bawat Larawan' ✅\n";
        $prompt .= "- 'Assessment' ❌ → 'Pagsusuri' ✅\n";
        $prompt .= "- 'Diagnosis' ❌ → 'Diyagnosis' o 'Pagsusuri ng Problema' ✅\n";
        $prompt .= "- 'Recommendations' ❌ → 'Mga Rekomendasyon' ✅\n";
        $prompt .= "- 'Important Notes' ❌ → 'Mahahalagang Paalala' ✅\n";
        $prompt .= "- 'Image 1/2/3' ❌ → 'Larawan 1/2/3' ✅\n";
        $prompt .= "- 'Overall' ❌ → 'Sa kabuuan' ✅\n";
        $prompt .= "- 'However' ❌ → 'Gayunpaman' o 'Pero' ✅\n";
        $prompt .= "- 'I can see' ❌ → 'Nakikita ko' ✅\n";
        $prompt .= "- 'Based on' ❌ → 'Batay sa' ✅\n";
        $prompt .= "- 'Specifically' ❌ → 'Partikular na' ✅\n\n";

        $prompt .= "PWEDENG ENGLISH LANG KUNG TEKNIKAL (mga halimbawa):\n";
        $prompt .= "- Pangalan ng sakit: bacterial leaf blight, tungro, blast\n";
        $prompt .= "- Pangalan ng peste: fall armyworm, stem borer, aphids\n";
        $prompt .= "- Kakulangan: nitrogen deficiency, potassium deficiency\n";
        $prompt .= "- Produkto: NPK fertilizer, hybrid seed\n";
        $prompt .= "- Iba pang teknikal: chlorosis, necrosis, lesions\n\n";

        $prompt .= "Halimbawa ng tamang sagot: 'Nakikita ko po sa larawan na may mga sintomas ng nitrogen deficiency ang inyong mais. Ang dahon po ay may pagdilaw na nagsisimula sa dulo.'\n";
        $prompt .= "Laging gumamit ng 'po' para magalang.\n\n";

        // Add topic context if this is a follow-up
        if (!empty($topicContext)) {
            $prompt .= "KONTEKSTO NG USAPAN: Ito ay follow-up sa nakaraang diskusyon tungkol sa:\n";
            $prompt .= "\"{$topicContext}\"\n\n";
        }

        // Add user's question/message
        if (!empty($userMessage) && $userMessage !== '[Image uploaded]' && $userMessage !== '[Images uploaded]') {
            $prompt .= "TANONG/MENSAHE NG USER:\n\"{$userMessage}\"\n\n";

            // Check if user is asking to COUNT something
            $isCountingRequest = preg_match('/\b(bilangin|bilang|count|ilan|ilang|how many|magkano|pila)\b/i', $userMessage);
            $isCountingTillers = preg_match('/\b(tillers?|suhi|sanga|shoots?)\b/i', $userMessage);
            $isCountingPlants = preg_match('/\b(halaman|puno|tanim|plants?|seedlings?|punla)\b/i', $userMessage);
            $isCountingLeaves = preg_match('/\b(dahon|leaves?|leaf)\b/i', $userMessage);
            $isCountingPests = preg_match('/\b(peste|pest|insect|uod|worm|bugs?)\b/i', $userMessage);

            if ($isCountingRequest) {
                $prompt .= "⚠️ MAHALAGANG GAWAIN - PAGBIBILANG:\n";
                $prompt .= "Ang user ay HUMIHILING na BILANGIN ang isang bagay sa larawan.\n";
                $prompt .= "DAPAT MONG SUBUKANG BILANGIN kahit hindi perpekto ang larawan!\n\n";

                $prompt .= "PARAAN NG PAGBIBILANG:\n";
                $prompt .= "1. SURIIN mabuti ang larawan at BILANGIN ang hinihingi\n";
                $prompt .= "2. Kung MALINAW ang larawan: Magbigay ng EKSAKTONG bilang\n";
                $prompt .= "3. Kung HINDI GANAP NA MALINAW: Magbigay ng ESTIMATE/RANGE (hal. '5-7 tillers')\n";
                $prompt .= "4. HUWAG TUMANGGI na bilangin - laging SUBUKAN at magbigay ng pinakamahusay na estimate\n";
                $prompt .= "5. Ipaliwanag kung paano mo binilang (hal. 'Nakikita ko 5 tillers mula sa base...')\n\n";

                if ($isCountingTillers) {
                    $prompt .= "PAGBIBILANG NG TILLERS:\n";
                    $prompt .= "- Ang tiller ay sanga na tumutubo mula sa BASE ng halaman\n";
                    $prompt .= "- BILANGIN ang lahat ng stems na nagmumula sa iisang puno\n";
                    $prompt .= "- Ang main stem ay BILANG DIN (kaya minimum 1 tiller)\n";
                    $prompt .= "- Kung mahirap makita lahat, ESTIMATE batay sa nakikita\n\n";
                }

                if ($isCountingPlants) {
                    $prompt .= "PAGBIBILANG NG HALAMAN/PUNO:\n";
                    $prompt .= "- BILANGIN ang bawat indibidwal na halaman\n";
                    $prompt .= "- Kung nakagrupo, ESTIMATE ang bilang sa bawat grupo\n\n";
                }

                if ($isCountingLeaves) {
                    $prompt .= "PAGBIBILANG NG DAHON:\n";
                    $prompt .= "- BILANGIN ang mga nakikitang dahon\n";
                    $prompt .= "- Kung marami, magbigay ng range o estimate\n\n";
                }

                if ($isCountingPests) {
                    $prompt .= "PAGBIBILANG NG PESTE:\n";
                    $prompt .= "- BILANGIN ang nakikitang mga peste o insekto\n";
                    $prompt .= "- Tukuyin kung ano ang uri kung posible\n\n";
                }

                $prompt .= "MAHALAGA: Ang UNANG bahagi ng iyong sagot ay dapat ang RESULTA NG PAGBIBILANG!\n";
                $prompt .= "Halimbawa: '📊 RESULTA NG PAGBIBILANG: Nakikita ko po ang humigit-kumulang 6-8 tillers sa larawan...'\n\n";
            }
        }

        $prompt .= "BILANG NG LARAWAN: {$imageCount}\n\n";

        $prompt .= "IYONG GAWAIN: Magsagawa ng KOMPREHENSIBO at MALALIM na pagsusuri sa mga na-upload na larawan.\n";
        $prompt .= "ISULAT SA TAGALOG - English para sa teknikal na terms lang.\n\n";

        $prompt .= "BALANGKAS NG PAGSUSURI (TAGALOG ANG HEADERS):\n";
        $prompt .= "1. **🔍 NAKIKITA KO** - Ano ang nakikita sa bawat larawan? Maging tiyak.\n";
        $prompt .= "2. **📋 DETALYADONG OBSERBASYON** - Ilarawan ang kulay, hugis, pattern, texture, kondisyon.\n";
        $prompt .= "3. **📊 PAGSUSURI** - Suriin ang kalidad, kalusugan, kondisyon, o kalagayan.\n";
        $prompt .= "4. **🔬 DIYAGNOSIS** (kung may problema) - Tukuyin ang mga problema, sakit, kakulangan, o isyu.\n";
        $prompt .= "5. **🎯 MGA REKOMENDASYON** - Magbigay ng maaksyunang payo batay sa pagsusuri.\n";
        $prompt .= "6. **⚖️ PAGHAHAMBING** (kung maraming larawan) - Pansinin ang pagkakaiba, pagsulong, o pattern sa mga larawan.\n\n";

        // Agriculture-specific analysis if context suggests it
        $prompt .= "FOCUS SA AGRIKULTURA (kung halaman/pananim ang larawan):\n";
        $prompt .= "- Kilalanin ang uri ng pananim, variety kung makikilala\n";
        $prompt .= "- Pagsusuri sa yugto ng paglaki\n";
        $prompt .= "- Kalagayan ng kalusugan (sintomas ng sakit, peste, kakulangan sa sustansya)\n";
        $prompt .= "- Kondisyon ng kapaligiran na nakikita\n";
        $prompt .= "- Tiyak na treatment o rekomendasyon sa aksyon\n\n";

        $prompt .= "MGA PAALALA:\n";
        $prompt .= "- TAGALOG ANG PANGUNAHIN - English para sa teknikal lang\n";
        $prompt .= "- Laging gumamit ng 'po' para magalang\n";
        $prompt .= "- Maging masusi at detalyado sa pagsusuri\n";
        $prompt .= "- Kung maraming larawan, SURIIN ANG BAWAT ISA at ihambing sila\n";
        $prompt .= "- Ituro ang anumang nakakabahala o kapansin-pansin\n";
        $prompt .= "- Magbigay ng antas ng kumpiyansa (tiyak, malamang, posible)\n";
        $prompt .= "- Gumamit ng bullet points (-), **bold** para sa importanteng terms\n";
        $prompt .= "- Maglagay ng emojis: 🔍 🌱 ⚠️ ✅ 💡 🦠 🐛 (3-5 kada sagot)\n\n";

        $prompt .= "MAHALAGANG FORMAT:\n";
        $prompt .= "- LAGING maglagay ng BAGONG LINYA (line break) PAGKATAPOS ng bawat section header\n";
        $prompt .= "- LAGING maglagay ng BAGONG LINYA BAGO magsimula ng bagong section\n";
        $prompt .= "- HUWAG isulat ang lahat sa iisang paragraph\n";
        $prompt .= "- Gamitin ang format na ito:\n\n";
        $prompt .= "🔍 NAKIKITA KO\n[content here]\n\n📋 DETALYADONG OBSERBASYON\n[content here]\n\n";
        $prompt .= "At ganito para sa bawat section.\n\n";

        $prompt .= "Ngayon po, suriin ang {$imageCount} na larawan:";

        return $prompt;
    }

    /**
     * Build system prompt for image analysis.
     */
    protected function buildImageAnalysisSystemPrompt(): string
    {
        $systemPrompt = "Ikaw ay isang expert visual analyst at agricultural specialist assistant para sa mga magsasakang Pilipino.\n\n";

        $systemPrompt .= "MAHIGPIT NA PATAKARAN SA WIKA:\n";
        $systemPrompt .= "TAGALOG/FILIPINO ang PANGUNAHING wika. English PARA SA TEKNIKAL NA TERMS LANG.\n\n";

        $systemPrompt .= "BAWAL ANG MGA ENGLISH PHRASES NA ITO:\n";
        $systemPrompt .= "- 'Here's my analysis' → Gamitin: 'Narito ang aking pagsusuri'\n";
        $systemPrompt .= "- 'General Impression' → Gamitin: 'Pangkalahatang Tingin'\n";
        $systemPrompt .= "- 'Detailed Analysis' → Gamitin: 'Detalyadong Pagsusuri'\n";
        $systemPrompt .= "- 'Assessment' → Gamitin: 'Pagsusuri'\n";
        $systemPrompt .= "- 'Recommendations' → Gamitin: 'Mga Rekomendasyon'\n";
        $systemPrompt .= "- 'Important Notes' → Gamitin: 'Mahahalagang Paalala'\n";
        $systemPrompt .= "- 'However' → Gamitin: 'Gayunpaman' o 'Pero'\n";
        $systemPrompt .= "- 'Overall' → Gamitin: 'Sa kabuuan'\n";
        $systemPrompt .= "- 'Image 1/2/3' → Gamitin: 'Larawan 1/2/3'\n\n";

        $systemPrompt .= "PWEDENG ENGLISH PARA SA TEKNIKAL NA TERMS:\n";
        $systemPrompt .= "- Pangalan ng sakit (bacterial leaf blight, blast, tungro)\n";
        $systemPrompt .= "- Pangalan ng peste (fall armyworm, stem borer, aphids)\n";
        $systemPrompt .= "- Kakulangan sa sustansya (nitrogen deficiency, potassium deficiency)\n";
        $systemPrompt .= "- Mga produkto (NPK fertilizer, hybrid seed)\n";
        $systemPrompt .= "- Teknikal na termino (chlorosis, necrosis, lesions)\n\n";

        $systemPrompt .= "Halimbawa ng tamang sagot: 'Nakikita ko po sa larawan na may mga sintomas ng nitrogen deficiency ang inyong mais. Ang dahon po ay may pagdilaw sa dulo na karaniwang senyales ng kakulangan sa nitrogen.'\n";
        $systemPrompt .= "PALAGING gumamit ng 'po' para magalang.\n\n";

        $systemPrompt .= "MGA LARANGAN NG EXPERTISE:\n";
        $systemPrompt .= "- Pagkilala at pagsusuri ng kalusugan ng pananim\n";
        $systemPrompt .= "- Pagkilala ng sakit at peste ng halaman\n";
        $systemPrompt .= "- Pagkilala ng kakulangan sa sustansya\n";
        $systemPrompt .= "- Pagsusuri ng kondisyon ng lupa\n";
        $systemPrompt .= "- Pagsusuri ng kagamitang pang-agrikultura\n";
        $systemPrompt .= "- Pangkalahatang pagsusuri at interpretasyon ng larawan\n\n";

        $systemPrompt .= "PARAAN NG PAGSUSURI:\n";
        $systemPrompt .= "1. Mag-obserba ng mabuti - Huwag palampasin ang kahit anong detalye\n";
        $systemPrompt .= "2. Mag-isip ng sistematiko - Suriin ang bawat aspeto\n";
        $systemPrompt .= "3. Maging tiyak - Pangalanan ang eksaktong isyu, huwag maging malabo\n";
        $systemPrompt .= "4. Magbigay ng maaksyunang payo - Malinaw na susunod na hakbang\n";
        $systemPrompt .= "5. Maging tapat - Kung hindi sigurado, sabihin ng malinaw\n\n";

        $systemPrompt .= "MAHALAGANG PATAKARAN - SUNDIN ANG HILING NG USER:\n";
        $systemPrompt .= "- Kung ang user ay nagtatanong ng TIYAK na bagay (hal. 'bilangin ang tillers'), GAWIN MO IYON!\n";
        $systemPrompt .= "- HUWAG magbigay ng generic analysis kung may TIYAK na tanong ang user\n";
        $systemPrompt .= "- Kung hinihingi na BILANGIN, BILANGIN MO - kahit estimate lang\n";
        $systemPrompt .= "- Kung hinihingi na TUKUYIN, TUKUYIN MO - kahit 'mukhang' o 'posibleng'\n";
        $systemPrompt .= "- HUWAG TUMANGGI sa kahilingan - SUBUKAN LAGI at magbigay ng best effort\n";
        $systemPrompt .= "- Ang UNANG bahagi ng sagot mo ay dapat DIREKTANG SAGOT sa tanong ng user\n\n";

        $systemPrompt .= "ISTILO NG KOMUNIKASYON:\n";
        $systemPrompt .= "- TAGALOG ANG PANGUNAHIN - English para sa teknikal lang\n";
        $systemPrompt .= "- PALAGING may 'po' para magalang sa magsasaka\n";
        $systemPrompt .= "- Palakaibigan pero propesyonal\n";
        $systemPrompt .= "- Ipaliwanag ang teknikal na termino sa simpleng salita\n";
        $systemPrompt .= "- Gumamit ng bullet points (-), **bold** para sa importanteng terms\n";
        $systemPrompt .= "- Maglagay ng emojis para friendly: 🔍 🌱 ⚠️ ✅ 💡 (3-5 kada sagot)\n\n";

        $systemPrompt .= "MAHALAGANG FORMAT:\n";
        $systemPrompt .= "- LAGING maglagay ng BAGONG LINYA pagkatapos ng bawat section header\n";
        $systemPrompt .= "- LAGING maglagay ng BAGONG LINYA bago magsimula ng bagong section\n";
        $systemPrompt .= "- HUWAG isulat ang lahat sa iisang mahabang paragraph\n";
        $systemPrompt .= "- Ihiwalay ang bawat idea sa sariling linya para madaling basahin\n";

        return $systemPrompt;
    }

    /**
     * Generate a short, descriptive title for a chat session based on its content.
     * Uses AI to create a meaningful title that summarizes the main concern.
     *
     * @param string $conversationContext The first few messages of the conversation
     * @return string|null The generated title or null on failure
     */
    public function generateChatTitle(string $conversationContext): ?string
    {
        // Get Gemini API setting
        $geminiSetting = AiApiSetting::where('usersId', $this->userId)
            ->where('provider', AiApiSetting::PROVIDER_GEMINI)
            ->where('isActive', true)
            ->where('delete_status', 'active')
            ->first();

        if (!$geminiSetting || !$geminiSetting->apiKey) {
            Log::warning('Gemini API not configured for title generation', ['userId' => $this->userId]);
            return null;
        }

        // Enforce rate limiting to prevent 429 errors
        $this->enforceRateLimit('gemini-title');

        try {
            $model = 'gemini-2.0-flash';
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $geminiSetting->apiKey;

            $prompt = "Based on this conversation, generate a SHORT TITLE (max 8 words) that captures the main topic or concern.\n\n";
            $prompt .= "CONVERSATION:\n{$conversationContext}\n\n";
            $prompt .= "RULES:\n";
            $prompt .= "- Title should be in TAGLISH (mix of Filipino and English)\n";
            $prompt .= "- Maximum 8 words, preferably 4-6 words\n";
            $prompt .= "- Focus on the MAIN CONCERN or TOPIC\n";
            $prompt .= "- Be specific, not generic\n";
            $prompt .= "- NO quotes, NO punctuation at the end\n";
            $prompt .= "- Examples: 'Yellowing ng Dahon sa Mais', 'Pest sa Palay', 'Fertilizer Schedule para sa Corn'\n\n";
            $prompt .= "Generate ONLY the title, nothing else:";

            $requestData = [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'maxOutputTokens' => 50,
                    'temperature' => 0.3,
                ],
            ];

            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $requestData);

            if ($response->successful()) {
                $data = $response->json();
                $title = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $title = trim($title);

                // Extract token usage from Gemini response (title generation uses minimal tokens)
                $usageMetadata = $data['usageMetadata'] ?? [];
                $inputTokens = $usageMetadata['promptTokenCount'] ?? 0;
                $outputTokens = $usageMetadata['candidatesTokenCount'] ?? 0;

                // Track token usage for title generation
                $this->trackTokenUsage('gemini', 'title_generation', $inputTokens, $outputTokens, 'gemini-2.0-flash');

                // Clean up the title
                $title = preg_replace('/^["\']+|["\']+$/', '', $title); // Remove quotes
                $title = preg_replace('/[.!?]+$/', '', $title); // Remove ending punctuation

                Log::debug('Generated chat title', ['title' => $title, 'inputTokens' => $inputTokens, 'outputTokens' => $outputTokens]);
                return $title;
            }

            Log::error('Gemini API failed for title generation', [
                'status' => $response->status(),
                'error' => $response->json('error.message'),
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('Exception generating chat title: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if user message indicates they want image analysis.
     * Used to determine if we should perform deep analysis on uploaded images.
     */
    public function isImageAnalysisRequest(string $message): bool
    {
        $message = strtolower($message);

        $analysisKeywords = [
            // Direct analysis requests
            'analyze', 'analyse', 'analysis', 'check', 'inspect', 'examine',
            'look at', 'tingnan', 'suriin', 'evaluate', 'assess',
            // Problem identification
            'what is this', 'ano ito', 'what\'s wrong', 'ano problema',
            'identify', 'diagnose', 'what disease', 'sakit', 'peste', 'pest',
            // Help requests with images
            'help', 'tulong', 'explain', 'ipaliwanag',
            // Questions about images
            'picture', 'image', 'photo', 'litrato', 'larawan',
            // Empty or generic (means they want analysis of uploaded images)
        ];

        foreach ($analysisKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }

        // If message is very short or empty with images, assume they want analysis
        if (strlen(trim($message)) < 20) {
            return true;
        }

        return false;
    }

    /**
     * Search for images based on the query and AI response.
     * Generates educational infographic-style images using AI.
     *
     * @param string $query User's question
     * @param int $maxImages Maximum number of total images (default 4: 2 AI + 2 web)
     * @param string|null $aiResponse The AI's response to use for better image prompts
     * @return array Array of image data
     */
    public function searchImages(string $query, int $maxImages = 4, ?string $aiResponse = null): array
    {
        $settings = AiImageSearchSetting::active()
            ->forUser($this->userId)
            ->first();

        if (!$settings || !$settings->isConfigured()) {
            Log::debug('Image search not configured', ['userId' => $this->userId]);
            return [];
        }

        $this->logFlowStep('Image Search', "Query: {$query}");

        $images = [];
        $webSearchQuery = $query; // Default to user query, will be replaced by AI-generated query
        $aiGenerationAttempted = false;
        $aiGenerationSuccess = false;

        // Check if Gemini is configured for AI image generation
        $geminiSetting = AiApiSetting::where('usersId', $this->userId)
            ->where('provider', AiApiSetting::PROVIDER_GEMINI)
            ->where('isActive', true)
            ->where('delete_status', 'active')
            ->first();

        $hasGeminiApi = $geminiSetting && $geminiSetting->apiKey;

        // Generate AI image prompts (1 infographic + 1 photo)
        try {
            $prompts = $this->generateImagePrompts($query, $aiResponse);

            // Get the smart web search query from AI (context-aware)
            if (!empty($prompts['webSearchQuery'])) {
                $webSearchQuery = $prompts['webSearchQuery'];
                Log::debug('Using AI-generated web search query', ['query' => $webSearchQuery]);
            }

            // 1. Generate AI Infographic (with labels)
            if (isset($prompts['infographic'])) {
                $aiGenerationAttempted = true;
                if (!$hasGeminiApi) {
                    $this->logFlowStep('AI Image Generation', "⚠️ Gemini API not configured - cannot generate AI images\nFallback: Using web search images only");
                } else {
                    $this->logFlowStep('AI Infographic', "Generating: {$prompts['infographic']['title']}\nPrompt: " . substr($prompts['infographic']['prompt'], 0, 150) . '...');

                    $infographic = $this->generateSingleImage(
                        $prompts['infographic']['prompt'],
                        $prompts['infographic']['title'] ?? 'Infographic',
                        'infographic'
                    );
                    if ($infographic) {
                        $images[] = $infographic;
                        $aiGenerationSuccess = true;
                        $this->logFlowStep('AI Infographic', "✓ Generated successfully: {$infographic['url']}");
                        Log::debug('AI infographic generated');
                    } else {
                        $this->logFlowStep('AI Infographic', "✗ Generation failed - Gemini API may have returned an error");
                    }
                }
            }

            // 2. Generate AI Photo (realistic, no text)
            if (isset($prompts['photo']) && $hasGeminiApi) {
                $this->logFlowStep('AI Photo', "Generating: {$prompts['photo']['title']}\nPrompt: " . substr($prompts['photo']['prompt'], 0, 150) . '...');

                $photo = $this->generateSingleImage(
                    $prompts['photo']['prompt'],
                    $prompts['photo']['title'] ?? 'Photo',
                    'photo'
                );
                if ($photo) {
                    $images[] = $photo;
                    $aiGenerationSuccess = true;
                    $this->logFlowStep('AI Photo', "✓ Generated successfully: {$photo['url']}");
                    Log::debug('AI photo generated');
                } else {
                    $this->logFlowStep('AI Photo', "✗ Generation failed - Gemini API may have returned an error");
                }
            }

        } catch (\Exception $e) {
            Log::error('AI image generation failed: ' . $e->getMessage());
            $this->logFlowStep('AI Image Generation', "✗ Error: " . $e->getMessage());
        }

        // 3 & 4. Fetch web images from Serper API (2 images) using the context-aware query
        try {
            if ($settings->isSerperConfigured()) {
                $this->logFlowStep('Web Image Search', "Query: {$webSearchQuery}");
                $webImages = $this->searchSerperImages($webSearchQuery, 2, $settings);
                if (!empty($webImages)) {
                    $images = array_merge($images, $webImages);
                    $this->logFlowStep('Web Image Search', "✓ Found " . count($webImages) . " web images");
                    Log::debug('Web images fetched from Serper', ['count' => count($webImages), 'query' => $webSearchQuery]);
                } else {
                    $this->logFlowStep('Web Image Search', "No images found for query");
                }
            } else {
                $this->logFlowStep('Web Image Search', "⚠️ Serper API not configured - skipping web images");
                Log::debug('Serper API not configured, skipping web images');
            }
        } catch (\Exception $e) {
            Log::error('Web image search failed: ' . $e->getMessage());
            $this->logFlowStep('Web Image Search', "✗ Error: " . $e->getMessage());
        }

        // Summary
        $summary = "Total: " . count($images) . " images";
        if ($aiGenerationAttempted) {
            $summary .= $aiGenerationSuccess ? " (AI-generated + web)" : " (web only - AI generation failed)";
        } else {
            $summary .= " (web only)";
        }
        $this->logFlowStep('Image Search Complete', $summary);
        Log::debug('Total images for response', ['count' => count($images)]);
        return array_slice($images, 0, $maxImages);
    }

    /**
     * Search for images using Serper API (Google Images).
     *
     * @param string $query Search query
     * @param int $maxImages Maximum images to return
     * @param AiImageSearchSetting $settings Image search settings
     * @return array Array of image data
     */
    protected function searchSerperImages(string $query, int $maxImages, AiImageSearchSetting $settings): array
    {
        // Check if Serper is configured
        if (!$settings->apiKey) {
            Log::debug('Serper API not configured');
            return [];
        }

        try {
            // Call Serper API
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-API-KEY' => $settings->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://google.serper.dev/images', [
                    'q' => $query,
                    'num' => min($maxImages + 3, 10), // Request extra in case some fail to download
                ]);

            if (!$response->successful()) {
                Log::warning('Serper API error', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                ]);
                return [];
            }

            $data = $response->json();
            $images = [];

            if (!empty($data['images'])) {
                foreach ($data['images'] as $item) {
                    $imageUrl = $item['imageUrl'] ?? '';
                    $sourceUrl = $item['link'] ?? '';
                    $title = $item['title'] ?? 'Web Image';

                    // Skip if no valid URL
                    if (empty($imageUrl)) {
                        continue;
                    }

                    // Download and save the image locally for persistence
                    $savedImage = $this->downloadAndSaveWebImage($imageUrl, $title, $sourceUrl);
                    if ($savedImage) {
                        $images[] = $savedImage;
                    }

                    if (count($images) >= $maxImages) {
                        break;
                    }
                }
            }

            // Track Serper usage (1 credit per image search)
            $this->trackSerperUsage($query, count($images), 1);

            Log::debug('Serper images fetched', ['count' => count($images), 'query' => $query]);
            return $images;

        } catch (\Exception $e) {
            Log::error('Serper API request failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Download a web image and save it locally for persistence.
     *
     * @param string $imageUrl URL of the image to download
     * @param string $title Image title
     * @param string $sourceUrl Source page URL
     * @return array|null Image data array or null on failure
     */
    protected function downloadAndSaveWebImage(string $imageUrl, string $title, string $sourceUrl): ?array
    {
        try {
            // Download the image with timeout
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get($imageUrl);

            if (!$response->successful()) {
                Log::debug('Failed to download web image', ['url' => $imageUrl, 'status' => $response->status()]);
                return null;
            }

            $imageContent = $response->body();
            $contentType = $response->header('Content-Type');

            // Validate it's actually an image
            if (!$contentType || strpos($contentType, 'image/') !== 0) {
                Log::debug('Not a valid image content type', ['url' => $imageUrl, 'type' => $contentType]);
                return null;
            }

            // Resize and compress the image
            $processedImage = $this->resizeAndCompressImage($imageContent, 800, 600, 75);
            if (!$processedImage) {
                return null;
            }

            // Generate unique filename
            $filename = 'web_' . time() . '_' . uniqid() . '.jpg';
            $directory = public_path('images/ai-generated');

            // Create directory if it doesn't exist
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $filePath = $directory . '/' . $filename;

            // Save the image
            if (!file_put_contents($filePath, $processedImage)) {
                Log::warning('Failed to save web image to disk');
                return null;
            }

            $localUrl = url('images/ai-generated/' . $filename);

            Log::debug('Web image saved locally', [
                'original_url' => $imageUrl,
                'local_url' => $localUrl,
                'size' => strlen($processedImage),
            ]);

            return [
                'url' => $localUrl,
                'thumbnail' => $localUrl,
                'title' => $title,
                'source' => parse_url($sourceUrl, PHP_URL_HOST) ?: '',
                'sourceUrl' => $sourceUrl,
                'photographer' => '',
                'photographerUrl' => '',
                'isGenerated' => false,
            ];

        } catch (\Exception $e) {
            Log::debug('Error downloading web image: ' . $e->getMessage(), ['url' => $imageUrl]);
            return null;
        }
    }

    /**
     * Generate smart, context-aware image prompts and web search query.
     * The AI analyzes the response to determine what visuals would best help the user.
     *
     * @param string $query Original user question
     * @param string|null $aiResponse The AI's response content
     * @return array Array with 'infographic', 'photo' prompts, and 'webSearchQuery'
     */
    protected function generateImagePrompts(string $query, ?string $aiResponse): array
    {
        $geminiSetting = AiApiSetting::where('usersId', $this->userId)
            ->where('provider', AiApiSetting::PROVIDER_GEMINI)
            ->where('isActive', true)
            ->where('delete_status', 'active')
            ->first();

        if (!$geminiSetting || !$geminiSetting->apiKey) {
            // Fallback to simple prompts with context - no rate limiting needed
            return [
                'infographic' => [
                    'prompt' => "Educational infographic diagram about {$query} in agriculture/farming context, with labeled parts, arrows pointing to key features, scientific illustration style, clean professional design",
                    'title' => "{$query} - Infographic",
                    'type' => 'infographic'
                ],
                'photo' => [
                    'prompt' => "Professional photograph showing {$query} in agriculture/farming context, realistic, detailed, high quality, natural lighting",
                    'title' => "{$query} - Photo",
                    'type' => 'photo'
                ],
                'webSearchQuery' => "{$query} agriculture farming",
            ];
        }

        // Enforce rate limiting to prevent 429 errors
        $this->enforceRateLimit('gemini-prompts');

        $model = 'gemini-2.0-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $geminiSetting->apiKey;

        // Use ULTRATHINK to generate smart, context-aware prompts
        $promptRequest = "=== ULTRATHINK: Generate Smart Educational Image Prompts ===\n\n";
        $promptRequest .= "USER QUESTION: {$query}\n\n";

        if ($aiResponse) {
            $truncatedResponse = strlen($aiResponse) > 2000 ? substr($aiResponse, 0, 2000) . '...' : $aiResponse;
            $promptRequest .= "AI ANSWER CONTENT:\n{$truncatedResponse}\n\n";
        }

        $promptRequest .= "YOUR TASK: Think like a teacher - what visuals would BEST help the user understand this concept?\n\n";

        $promptRequest .= "STEP 1 - ANALYZE THE CONCEPT:\n";
        $promptRequest .= "- What is the main subject? (e.g., 'milk line' = boundary in CORN kernel, NOT dairy milk)\n";
        $promptRequest .= "- What type of visual would be most educational? (cross-section? close-up? comparison? stages?)\n";
        $promptRequest .= "- What specific details from the AI answer should be shown?\n";
        $promptRequest .= "- What context is needed to avoid ambiguity? (e.g., add 'corn' to avoid confusion with dairy)\n\n";

        $promptRequest .= "STEP 2 - CREATE 3 OUTPUTS:\n\n";

        $promptRequest .= "OUTPUT 1 - INFOGRAPHIC (with labels):\n";
        $promptRequest .= "- Decide: Should it be a cross-section, cutaway, diagram, comparison, or stages?\n";
        $promptRequest .= "- Include specific labeled parts mentioned in the AI answer\n";
        $promptRequest .= "- Add arrows pointing to key features\n";
        $promptRequest .= "- Example for 'milk line': 'Cross-section diagram of a single corn kernel cut in half lengthwise, clearly showing the milk line - the visible boundary between the white/translucent liquid starch (top) and the solid yellow starch (bottom), with labeled arrows pointing to: pericarp, liquid endosperm above milk line, solid endosperm below milk line, and embryo'\n\n";

        $promptRequest .= "OUTPUT 2 - REALISTIC PHOTO (no text):\n";
        $promptRequest .= "- What real-world view would help? (macro shot? field view? hands holding it?)\n";
        $promptRequest .= "- Show the actual thing, not a generic image\n";
        $promptRequest .= "- NO text, labels, or annotations\n";
        $promptRequest .= "- Example for 'milk line': 'Macro photograph of a corn kernel sliced in half showing the internal structure, the distinct color boundary between milky white and solid yellow portions visible, agricultural close-up photography, natural lighting'\n\n";

        $promptRequest .= "OUTPUT 3 - WEB SEARCH QUERY:\n";
        $promptRequest .= "- Create a specific search query for Google Images\n";
        $promptRequest .= "- Include context to avoid wrong results (e.g., 'corn kernel milk line' not just 'milk line')\n";
        $promptRequest .= "- Think: what would someone search to find helpful reference images?\n";
        $promptRequest .= "- Example for 'milk line': 'corn kernel milk line cross section maturity'\n\n";

        $promptRequest .= "OUTPUT FORMAT (JSON only, no explanation):\n";
        $promptRequest .= "{\n";
        $promptRequest .= "  \"infographic\": {\"prompt\": \"detailed specific infographic prompt\", \"title\": \"short descriptive title\"},\n";
        $promptRequest .= "  \"photo\": {\"prompt\": \"specific photorealistic prompt, no text\", \"title\": \"short descriptive title\"},\n";
        $promptRequest .= "  \"webSearchQuery\": \"specific contextual search query for google images\"\n";
        $promptRequest .= "}\n";

        try {
            $response = Http::timeout(20)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, [
                    'contents' => [
                        ['parts' => [['text' => $promptRequest]]]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                // Try to parse JSON object
                if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
                    $prompts = json_decode($matches[0], true);
                    if (is_array($prompts) && isset($prompts['infographic']) && isset($prompts['photo'])) {
                        $prompts['infographic']['type'] = 'infographic';
                        $prompts['photo']['type'] = 'photo';
                        // Ensure webSearchQuery exists
                        if (empty($prompts['webSearchQuery'])) {
                            $prompts['webSearchQuery'] = $query . ' agriculture';
                        }
                        Log::debug('Generated smart image prompts', [
                            'webSearchQuery' => $prompts['webSearchQuery'],
                            'hasInfographic' => true,
                            'hasPhoto' => true
                        ]);
                        return $prompts;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to generate image prompts: ' . $e->getMessage());
        }

        // Fallback prompts with context - add agriculture/farming context to avoid ambiguity
        return [
            'infographic' => [
                'prompt' => "Educational cross-section infographic diagram explaining {$query} in agriculture/corn context, with labeled parts, arrows pointing to key features, annotations, scientific illustration style, clean professional design",
                'title' => "{$query} - Infographic",
                'type' => 'infographic'
            ],
            'photo' => [
                'prompt' => "Professional realistic photograph of {$query}, high quality, detailed, natural lighting, no text, no labels, documentary photography style",
                'title' => "{$query} - Photo",
                'type' => 'photo'
            ],
            'webSearchQuery' => "{$query} agriculture farming corn plant diagram",
        ];
    }

    /**
     * Generate a single image with the given prompt.
     *
     * @param string $prompt The image generation prompt
     * @param string $title Image title
     * @param string $type Image type: 'infographic' or 'photo'
     */
    protected function generateSingleImage(string $prompt, string $title, string $type = 'photo'): ?array
    {
        $geminiSetting = AiApiSetting::where('usersId', $this->userId)
            ->where('provider', AiApiSetting::PROVIDER_GEMINI)
            ->where('isActive', true)
            ->where('delete_status', 'active')
            ->first();

        if (!$geminiSetting || !$geminiSetting->apiKey) {
            Log::debug('AI image generation skipped - Gemini not configured', ['userId' => $this->userId]);
            return null;
        }

        // Enforce rate limiting to prevent 429 errors
        $this->enforceRateLimit('gemini-image');

        Log::debug('Calling Gemini image generation', [
            'type' => $type,
            'prompt_length' => strlen($prompt),
        ]);

        $image = $this->callGeminiImageGeneration($geminiSetting, $prompt, 0, $type);
        if ($image) {
            $image['title'] = $title;
            $image['imageType'] = $type; // Track the type for display
            $savedImage = $this->saveGeneratedImage($image);
            if ($savedImage) {
                Log::debug('AI image generated and saved successfully', [
                    'type' => $type,
                    'url' => $savedImage['url'] ?? 'no-url',
                ]);
                return $savedImage;
            } else {
                Log::warning('AI image generated but failed to save', ['type' => $type]);
            }
        } else {
            Log::warning('Gemini image generation returned null', [
                'type' => $type,
                'prompt' => substr($prompt, 0, 100),
            ]);
        }

        return null;
    }

    /**
     * Save a generated image to file and return URL-based image data.
     * This prevents large base64 data from being sent via SSE.
     * Images are resized and compressed to reduce file size.
     */
    protected function saveGeneratedImage(array $imageData): ?array
    {
        try {
            $url = $imageData['url'] ?? '';

            // Check if it's base64 data
            if (strpos($url, 'data:image/') !== 0) {
                return $imageData; // Already a URL, return as-is
            }

            // Extract base64 data and mime type
            if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $url, $matches)) {
                $originalExtension = $matches[1];
                $base64Data = $matches[2];

                // Generate unique filename (always save as jpg for compression)
                $filename = 'ai_gen_' . time() . '_' . uniqid() . '.jpg';
                $directory = public_path('images/ai-generated');

                // Create directory if it doesn't exist
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }

                $filePath = $directory . '/' . $filename;

                // Decode the image
                $imageContent = base64_decode($base64Data);
                if (!$imageContent) {
                    Log::warning('Failed to decode base64 image');
                    return null;
                }

                // Resize and compress the image using GD
                $resizedImage = $this->resizeAndCompressImage($imageContent, 800, 600, 70);

                if ($resizedImage && file_put_contents($filePath, $resizedImage)) {
                    $imageUrl = url('images/ai-generated/' . $filename);

                    Log::debug('Image saved and compressed', [
                        'original_size' => strlen($imageContent),
                        'compressed_size' => strlen($resizedImage),
                        'filename' => $filename,
                    ]);

                    return [
                        'url' => $imageUrl,
                        'thumbnail' => $imageUrl,
                        'title' => $imageData['title'] ?? 'Generated Image',
                        'source' => '',
                        'sourceUrl' => '',
                        'photographer' => '',
                        'photographerUrl' => '',
                        'isGenerated' => true,
                        'imageType' => $imageData['imageType'] ?? 'photo',
                    ];
                }
            }

            Log::warning('Failed to save generated image', ['url_length' => strlen($url)]);
            return null;

        } catch (\Exception $e) {
            Log::error('Error saving generated image: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Resize and compress an image using GD library.
     *
     * @param string $imageData Raw image data
     * @param int $maxWidth Maximum width
     * @param int $maxHeight Maximum height
     * @param int $quality JPEG quality (0-100)
     * @return string|null Compressed image data or null on failure
     */
    protected function resizeAndCompressImage(string $imageData, int $maxWidth = 800, int $maxHeight = 600, int $quality = 70): ?string
    {
        try {
            // Create image from string
            $sourceImage = @imagecreatefromstring($imageData);
            if (!$sourceImage) {
                Log::warning('Failed to create image from data');
                return $imageData; // Return original if can't process
            }

            // Get original dimensions
            $origWidth = imagesx($sourceImage);
            $origHeight = imagesy($sourceImage);

            // Calculate new dimensions maintaining aspect ratio
            $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);

            // Only resize if image is larger than max dimensions
            if ($ratio < 1) {
                $newWidth = (int)($origWidth * $ratio);
                $newHeight = (int)($origHeight * $ratio);

                // Create new image with new dimensions
                $newImage = imagecreatetruecolor($newWidth, $newHeight);

                // Preserve transparency for PNG
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);

                // Resize
                imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

                // Free source image
                imagedestroy($sourceImage);
                $sourceImage = $newImage;
            }

            // Output as JPEG with compression
            ob_start();
            imagejpeg($sourceImage, null, $quality);
            $compressedData = ob_get_clean();

            // Free memory
            imagedestroy($sourceImage);

            return $compressedData;

        } catch (\Exception $e) {
            Log::error('Image resize/compress error: ' . $e->getMessage());
            return $imageData; // Return original on error
        }
    }

    /**
     * Call Gemini API for image generation.
     *
     * @param AiApiSetting $setting API settings
     * @param string $query The image prompt
     * @param int $variation Variation index
     * @param string $type Image type: 'infographic' (with labels) or 'photo' (no text)
     */
    protected function callGeminiImageGeneration(AiApiSetting $setting, string $query, int $variation = 0, string $type = 'photo'): ?array
    {
        // Use Gemini 2.5 Flash Image for native image generation (Nano Banana)
        // This model supports responseModalities: ["TEXT", "IMAGE"]
        // Reference: https://ai.google.dev/gemini-api/docs/image-generation
        $model = 'gemini-2.5-flash-image';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $setting->apiKey;

        $prompt = $query;

        if ($type === 'infographic') {
            // INFOGRAPHIC: Allow labels, diagrams, annotations
            if (strlen($query) < 100) {
                $prompt = "Generate an educational infographic diagram: {$query}. ";
                $prompt .= "Style: clean professional infographic with labeled parts, arrows pointing to key features, ";
                $prompt .= "scientific illustration quality, clear visual hierarchy, educational poster design.";
            }
        } else {
            // PHOTO: No text, realistic photograph
            if (strlen($query) < 80) {
                $prompt = "Generate a realistic photograph showing: {$query}. ";
                $prompt .= "Style: photorealistic, detailed, high quality, natural lighting. ";
                $prompt .= "IMPORTANT: No text, no labels, no words, no letters, no annotations anywhere in the image.";
            } else {
                // Add "no text" instruction if not already present
                if (stripos($query, 'no text') === false && stripos($query, 'no label') === false) {
                    $prompt = "Generate image: " . $query . '. No text, no labels, no words, no annotations.';
                } else {
                    $prompt = "Generate image: " . $query;
                }
            }
        }

        try {
            Log::debug('Calling Gemini image generation API', [
                'model' => $model,
                'prompt_length' => strlen($prompt),
            ]);

            $response = Http::timeout(90)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'responseModalities' => ['TEXT', 'IMAGE'],
                    ],
                ]);

            if (!$response->successful()) {
                Log::warning('Gemini image generation API failed, trying stable Flash', [
                    'model' => $model,
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 400),
                ]);
                // Try fallback to stable gemini-2.0-flash
                return $this->callGeminiFlashImageGeneration($setting, $query, $variation, $type);
            }

            $data = $response->json();

            // Look for image in the response (Gemini format with inlineData)
            $candidates = $data['candidates'] ?? [];
            foreach ($candidates as $candidate) {
                $parts = $candidate['content']['parts'] ?? [];
                foreach ($parts as $part) {
                    if (isset($part['inlineData'])) {
                        $base64 = $part['inlineData']['data'];
                        $mimeType = $part['inlineData']['mimeType'] ?? 'image/png';

                        Log::debug('Gemini image generation API returned image successfully', [
                            'model' => $model,
                            'mimeType' => $mimeType,
                            'base64_length' => strlen($base64),
                        ]);

                        return [
                            'url' => "data:{$mimeType};base64,{$base64}",
                            'thumbnail' => "data:{$mimeType};base64,{$base64}",
                            'title' => $query,
                            'source' => '',
                            'sourceUrl' => '',
                            'photographer' => '',
                            'photographerUrl' => '',
                            'isGenerated' => true,
                        ];
                    }
                }
            }

            Log::warning('Gemini image generation response missing image data, trying stable Flash', [
                'model' => $model,
                'has_candidates' => !empty($candidates),
                'response_keys' => array_keys($data ?? []),
            ]);
            // Try fallback if no image in response
            return $this->callGeminiFlashImageGeneration($setting, $query, $variation, $type);

        } catch (\Exception $e) {
            Log::error('Gemini image generation API error: ' . $e->getMessage());
            // Try fallback
            return $this->callGeminiFlashImageGeneration($setting, $query, $variation, $type);
        }
    }

    /**
     * Fallback: Try alternative Gemini models for image generation.
     * Tries multiple models in order until one works.
     *
     * @param string $type Image type: 'infographic' or 'photo'
     */
    protected function callGeminiFlashImageGeneration(AiApiSetting $setting, string $query, int $variation = 0, string $type = 'photo'): ?array
    {
        // List of models to try in order (models that support native image generation)
        // Reference: https://ai.google.dev/gemini-api/docs/image-generation
        $modelsToTry = [
            'gemini-3-pro-image-preview', // Nano Banana Pro - highest quality
            'gemini-2.0-flash-exp',       // Experimental 2.0 Flash with image support
            'gemini-2.0-flash',           // Stable 2.0 Flash (may not support images)
        ];

        if ($type === 'infographic') {
            $prompt = "Generate an educational infographic image of: {$query}. Include labels, arrows, and annotations to explain key features. Scientific illustration style, clean design, easy to understand.";
        } else {
            $prompt = "Generate a realistic photograph of: {$query}. Make it photorealistic and educational for agricultural purposes. High quality, natural lighting. No text or labels in the image.";
        }

        foreach ($modelsToTry as $model) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $setting->apiKey;

            try {
                Log::debug('Trying Gemini model for image generation', [
                    'model' => $model,
                    'type' => $type,
                ]);

                $response = Http::timeout(90)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                    ])
                    ->post($url, [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt]
                                ]
                            ]
                        ],
                        'generationConfig' => [
                            'responseModalities' => ['TEXT', 'IMAGE'],
                        ],
                    ]);

                if (!$response->successful()) {
                    $errorBody = substr($response->body(), 0, 300);
                    Log::warning("Gemini {$model} image generation failed", [
                        'status' => $response->status(),
                        'body' => $errorBody,
                    ]);

                    // Check if it's a "model not found" or "not supported" error - try next model
                    if (strpos($errorBody, 'not found') !== false || strpos($errorBody, 'not supported') !== false) {
                        continue; // Try next model
                    }

                    // For other errors (rate limit, etc.), don't try more models
                    return null;
                }

                $data = $response->json();

                // Look for image in the response
                $candidates = $data['candidates'] ?? [];
                foreach ($candidates as $candidate) {
                    $parts = $candidate['content']['parts'] ?? [];
                    foreach ($parts as $part) {
                        if (isset($part['inlineData'])) {
                            $base64 = $part['inlineData']['data'];
                            $mimeType = $part['inlineData']['mimeType'] ?? 'image/png';

                            Log::debug("Gemini {$model} generated image successfully", [
                                'mimeType' => $mimeType,
                                'base64_length' => strlen($base64),
                            ]);

                            return [
                                'url' => "data:{$mimeType};base64,{$base64}",
                                'thumbnail' => "data:{$mimeType};base64,{$base64}",
                                'title' => $query,
                                'source' => '',
                                'sourceUrl' => '',
                                'photographer' => '',
                                'photographerUrl' => '',
                                'isGenerated' => true,
                            ];
                        }
                    }
                }

                // If response was successful but no image, the model might not support image generation
                Log::warning("Gemini {$model} response successful but no image data", [
                    'has_candidates' => !empty($candidates),
                    'candidate_count' => count($candidates),
                ]);
                // Try next model
                continue;

            } catch (\Exception $e) {
                Log::error("Gemini {$model} image generation error: " . $e->getMessage());
                // Try next model
                continue;
            }
        }

        // All models failed
        Log::error('All Gemini models failed to generate image', [
            'models_tried' => $modelsToTry,
            'type' => $type,
        ]);
        return null;
    }

    /**
     * Extract image search query from user message.
     * Extracts the topic/subject that the user wants to see images of.
     *
     * @param string $message User's message
     * @return string Extracted search query
     */
    public function extractImageSearchQuery(string $message): string
    {
        // Remove image request patterns to get the subject
        $query = $message;

        // Remove common image request phrases
        $removePatterns = [
            '/\b(pwede.*pakita|puwede.*pakita|pwede.*magpakita)\b/i',
            '/\b(pakita mo|ipakita mo|magpakita ka)\b/i',
            '/\b(show me|can you show|please show|could you show)\b/i',
            '/\b(give me.*picture|give me.*photo)\b/i',
            '/\b(picture of|photo of|image of)\b/i',
            '/\b(ng|of|the|a|an)\b/i',
            '/\?/i',
        ];

        foreach ($removePatterns as $pattern) {
            $query = preg_replace($pattern, '', $query);
        }

        // Clean up extra spaces
        $query = trim(preg_replace('/\s+/', ' ', $query));

        // If query is too short, use the original message
        if (strlen($query) < 3) {
            $query = $message;
        }

        Log::debug('Extracted image search query', [
            'original' => $message,
            'extracted' => $query,
        ]);

        return $query;
    }
}
