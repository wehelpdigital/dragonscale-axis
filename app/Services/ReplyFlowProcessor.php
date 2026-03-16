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

    // Precomputed image analysis (from analyzeUploadedImages in controller)
    // Used to avoid re-analyzing images in main flow
    protected $precomputedImageAnalysis = null;

    // Extracted context from chat history (location, crop, stage, etc.)
    // Used for CONTINUITY - prevents AI from re-asking for info already provided
    protected $extractedContext = [];

    // Flag to skip context extraction from chat history (when switching to new topic/crop)
    protected $skipContextExtraction = false;

    // Flag to indicate user wants visual reference (what does X look like)
    protected $needsVisualReference = false;

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

    // Cached RAG result to avoid multiple expensive Pinecone queries
    // Once RAG is queried, the result is cached and reused for all subsequent RAG needs
    protected $cachedRagResult = null;
    protected $cachedRagQuery = null;
    protected $ragCacheUsed = false;

    // Global API rate limiting (to prevent 429 errors)
    protected static $lastApiCallTime = 0;
    protected static $apiCallMinDelay = 1500000; // 1.5 seconds between API calls (microseconds)

    // Progress callback for streaming progress updates to the client
    protected $progressCallback = null;
    protected $currentProgressStep = 0;
    protected $totalProgressSteps = 6; // Total expected steps for progress bar (100% on response received)

    // Current message ID being processed (to exclude from chat history)
    protected $currentMessageId = null;

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
        // "Which from the list" questions - MUST stay on previous topic
        '/\b(alin|which)\s*(sa|from|ng|dun|diyan|dito|dyan|among)\s*(mga\s*)?(nabanggit|mentioned|list|options|recommendations|rekomendasyon)/i',
        '/\b(alin|which)\s*(ang|is)\s*(pinaka|most|best)\b/i',  // "alin ang pinakamaganda/pinakaepektibo"
        '/\b(sa\s*)?mga\s*(nabanggit|mentioned|list).*(alin|which|ano)\b/i',
        '/\b(which|alin)\s*(one|isa)\s*(is|ang)\s*(best|pinaka|mas\s*maganda|most\s*effective|pinakaepektibo)/i',
        '/\b(anong?|what)\s*(mas|more|pinaka|most)\s*(maganda|effective|mainam|mabuti)\s*(sa|from|dito|diyan)/i',
        '/\b(sa\s*palagay|in\s*your\s*opinion).*(alin|which)\b/i',  // "sa palagay mo, alin..."
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

    // AI Provider pricing (per 1M tokens in USD) - Updated Feb 2026
    protected static $providerPricing = [
        'gemini' => [
            'input' => 0.10,    // Gemini 2.0 Flash input (updated from 1.5 Flash)
            'output' => 0.40,   // Gemini 2.0 Flash output
            'name' => 'Google Gemini 2.0 Flash',
        ],
        'gemini-1.5-flash' => [
            'input' => 0.075,   // Gemini 1.5 Flash input (cheaper legacy)
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
        'openai-mini' => [
            'input' => 0.15,    // GPT-4o-mini input (cost-effective)
            'output' => 0.60,   // GPT-4o-mini output
            'name' => 'OpenAI GPT-4o-mini',
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
        'gemini-image' => [
            'input' => 0.10,    // Gemini 2.5 Flash Image input
            'output' => 3.90,   // Gemini 2.5 Flash Image output (higher for image generation)
            'name' => 'Google Gemini Image Generation',
        ],
        'openai-vision' => [
            'input' => 2.50,    // GPT-4o Vision input (same as text, images counted as tokens)
            'output' => 10.00,  // GPT-4o Vision output
            'name' => 'OpenAI GPT-4o Vision',
        ],
        'gemini-vision' => [
            'input' => 0.10,    // Gemini 2.0 Flash Vision input
            'output' => 0.40,   // Gemini 2.0 Flash Vision output
            'name' => 'Google Gemini Vision',
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
     * Set the progress callback function for streaming progress updates.
     *
     * @param callable|null $callback Function that receives (step, totalSteps, stepName, stepNameTagalog)
     */
    public function setProgressCallback(?callable $callback): void
    {
        $this->progressCallback = $callback;
    }

    /**
     * Set the topic context (for resetting when crop type changes).
     *
     * @param string|null $context The topic context or null to reset
     */
    public function setTopicContext(?string $context): void
    {
        $this->topicContext = $context;
    }

    /**
     * Clear the extracted context (for resetting when switching to a new topic/crop).
     * This prevents old chat history context from polluting new image analysis.
     */
    public function clearExtractedContext(): void
    {
        $this->extractedContext = [];
        $this->skipContextExtraction = true; // Prevent rebuilding from chat history
        $this->cachedRagResult = null;
        $this->cachedRagQuery = null;
        Log::info('Extracted context cleared - new topic detected, context extraction disabled');
    }

    /**
     * Clear only image-specific context (stage, problem, dat) while preserving general context.
     * Use this when NEW images are uploaded - the AI should determine stage/problem from NEW images,
     * not carry over from previous images in the chat history.
     *
     * This PREVENTS the issue where:
     * - User uploads Image 1 (grain filling stage)
     * - User uploads Image 2 (vegetative stage with yellowing)
     * - AI wrongly assumes Image 2 is also in grain filling stage
     */
    public function clearImageSpecificContext(): void
    {
        $clearedKeys = [];

        // Clear image-specific context that should be re-detected from new images
        $imageSpecificKeys = ['stage', 'problem', 'dat', 'growth_stage', 'observation'];

        foreach ($imageSpecificKeys as $key) {
            if (isset($this->extractedContext[$key])) {
                $clearedKeys[] = $key . '=' . $this->extractedContext[$key];
                unset($this->extractedContext[$key]);
            }
        }

        // Also clear RAG cache since the context has changed
        $this->cachedRagResult = null;
        $this->cachedRagQuery = null;

        // CRITICAL: Add a marker to indicate image context was intentionally cleared
        // This prevents re-extraction from chat history in processMainFlow
        // (because extractedContext won't be empty with this marker)
        $this->extractedContext['_imageContextCleared'] = true;

        if (!empty($clearedKeys)) {
            Log::info('Image-specific context cleared for fresh analysis', [
                'clearedContext' => $clearedKeys,
                'remainingContext' => array_keys($this->extractedContext),
            ]);
        } else {
            Log::debug('clearImageSpecificContext called but no image-specific context to clear');
        }
    }

    /**
     * Send a progress update to the client.
     *
     * @param string $stepName Step name in English
     * @param string $stepNameTagalog Step name in Tagalog
     * @param int|null $forceStep Force a specific step number (optional)
     */
    protected function sendProgress(string $stepName, string $stepNameTagalog, ?int $forceStep = null): void
    {
        if ($this->progressCallback) {
            if ($forceStep !== null) {
                $this->currentProgressStep = $forceStep;
            } else {
                $this->currentProgressStep++;
            }

            call_user_func(
                $this->progressCallback,
                $this->currentProgressStep,
                $this->totalProgressSteps,
                $stepName,
                $stepNameTagalog
            );
        }
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
        // Determine pricing key based on provider, model, and nodeId
        $pricingKey = $provider;
        if ($provider === 'openai' && strpos($model, 'search') !== false) {
            $pricingKey = 'openai-search';
        } elseif ($provider === 'openai' && strpos($model, 'mini') !== false) {
            $pricingKey = 'openai-mini';
        } elseif ($provider === 'openai' && (strpos($nodeId, 'vision') !== false || strpos($model, 'vision') !== false)) {
            $pricingKey = 'openai-vision';
        } elseif ($provider === 'gemini' && strpos($model, 'pro') !== false) {
            $pricingKey = 'gemini-pro';
        } elseif ($provider === 'gemini' && (strpos($nodeId, 'vision') !== false || strpos($model, 'vision') !== false)) {
            $pricingKey = 'gemini-vision';
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
     * Estimate token count from text.
     * Rough estimation: ~4 characters per token for English, ~2-3 for mixed Filipino/English
     *
     * @param string $text The text to estimate
     * @return int Estimated token count
     */
    protected function estimateTokenCount(string $text): int
    {
        // Use 3 characters per token as average for Filipino/English mix
        $charCount = strlen($text);
        return (int) ceil($charCount / 3);
    }

    /**
     * Select the appropriate OpenAI model based on input complexity.
     * Uses gpt-4o-mini for simple/short inputs, gpt-4o for complex/long inputs.
     *
     * @param string $prompt The prompt to analyze
     * @param string $systemPrompt Optional system prompt
     * @param bool $forceFullModel Force using gpt-4o regardless of size
     * @return array ['model' => string, 'reason' => string]
     */
    protected function selectOpenAIModel(string $prompt, string $systemPrompt = '', bool $forceFullModel = false): array
    {
        if ($forceFullModel) {
            return ['model' => 'gpt-4o', 'reason' => 'forced_full_model'];
        }

        $totalText = $systemPrompt . "\n" . $prompt;
        $estimatedTokens = $this->estimateTokenCount($totalText);

        // Thresholds for model selection
        $miniThreshold = 2000;  // Below this, use mini
        $complexityIndicators = [
            'analyze', 'pagsusuri', 'explain why', 'ipaliwanag',
            'compare', 'ihambing', 'detailed', 'detalyado',
            'multiple', 'maraming', 'comprehensive', 'kumpleto'
        ];

        // Check for complexity indicators
        $hasComplexity = false;
        $lowerPrompt = strtolower($prompt);
        foreach ($complexityIndicators as $indicator) {
            if (strpos($lowerPrompt, $indicator) !== false) {
                $hasComplexity = true;
                break;
            }
        }

        // Decision logic
        if ($estimatedTokens < $miniThreshold && !$hasComplexity) {
            return [
                'model' => 'gpt-4o-mini',
                'reason' => "low_tokens_{$estimatedTokens}",
                'estimatedTokens' => $estimatedTokens
            ];
        }

        return [
            'model' => 'gpt-4o',
            'reason' => $hasComplexity ? 'complex_query' : "high_tokens_{$estimatedTokens}",
            'estimatedTokens' => $estimatedTokens
        ];
    }

    /**
     * Determine if a query requires dual-AI processing for better accuracy,
     * or can use single AI for cost efficiency.
     *
     * OPTIMIZATION: Dual-AI is ~70% more expensive. Use it only when accuracy is critical.
     *
     * @param string $query The user's query
     * @param string|null $ragResult RAG knowledge base result (if available)
     * @param string|null $imageAnalysis Image analysis result (if available)
     * @return array ['useDualAI' => bool, 'reason' => string]
     */
    protected function shouldUseDualAI(string $query, ?string $ragResult = null, ?string $imageAnalysis = null): array
    {
        $queryLower = strtolower($query);
        $queryLength = strlen($query);

        // ================================================================
        // ALWAYS USE DUAL AI for complex/critical questions
        // ================================================================

        // 1. Pest/Disease diagnosis - needs verification for accuracy
        $hasDiagnosis = preg_match('/\b(sakit|pest|insek|uod|halamang|kulob|durog|namamatay|may tama|infected|disease|infestation|problem|problema|sunog|dried|patay|yellow|dilaw)\b/i', $queryLower);

        // 2. Nutrient deficiency analysis - complex technical assessment
        $hasNutrientIssue = preg_match('/\b(kulang|deficiency|lacking|nitrogen|phosphorus|potassium|zinc|boron|iron|magnesium|calcium|npk|nutrient)\b/i', $queryLower);

        // 3. Critical agricultural decisions
        $hasCriticalDecision = preg_match('/\b(dapat ba|kailangan ba|pwede ba|ok ba|tama ba|mali ba|should i|can i|is it ok|is it safe)\b/i', $queryLower);

        // 4. Complex calculations or comparisons
        $hasComplexCalc = preg_match('/\b(compute|calculate|compare|difference|mas mabuti|which is better|ano mas|alin ang)\b/i', $queryLower);

        // 5. Dosage and application timing - critical for crop health
        $hasDosage = preg_match('/\b(dosage|dosis|gaano karami|how much|rate|timing|kelan|when to apply|ilang araw|dap|dat)\b/i', $queryLower);

        // 6. Image-based questions - complex visual analysis needs verification
        $hasImageQuestion = !empty($imageAnalysis) && $queryLength < 200;

        // Check if this is a complex technical question
        $isComplexQuery = $hasDiagnosis || $hasNutrientIssue || $hasCriticalDecision || $hasComplexCalc || $hasDosage || $hasImageQuestion;

        // ================================================================
        // USE SINGLE AI (Gemini) for simpler queries - COST OPTIMIZATION
        // ================================================================

        // 1. Greetings and small talk
        $isGreeting = preg_match('/^(hi|hello|hey|good morning|good afternoon|magandang|kumusta|salamat|thank you)/i', $queryLower) && $queryLength < 50;

        // 2. Very short follow-up questions
        $isShortFollowup = $queryLength < 30 && preg_match('/^(oo|yes|hindi|no|ok|sige|gets|ano pa|what else)/i', $queryLower);

        // 3. RAG already has high-confidence answer (>300 chars usually means good match)
        $hasStrongRagAnswer = !empty($ragResult) && strlen($ragResult) > 300;

        // 4. Simple product info lookup
        $isProductLookup = preg_match('/\b(presyo|price|cost|magkano|how much cost|brand|available|meron ba)\b/i', $queryLower) && !$hasDosage;

        // 5. General farming tips (not specific diagnosis)
        $isGeneralTip = preg_match('/\b(tips|advice|payo|suggestion|pangkalahatan|general|basic)\b/i', $queryLower) && !$hasDiagnosis;

        // Simple queries can use single AI
        $isSimpleQuery = $isGreeting || $isShortFollowup || $isProductLookup || $isGeneralTip;

        // ================================================================
        // DECISION LOGIC
        // ================================================================

        // Complex queries ALWAYS use dual AI for accuracy
        if ($isComplexQuery && !$isSimpleQuery) {
            $reason = [];
            if ($hasDiagnosis) $reason[] = 'diagnosis';
            if ($hasNutrientIssue) $reason[] = 'nutrient_analysis';
            if ($hasCriticalDecision) $reason[] = 'critical_decision';
            if ($hasComplexCalc) $reason[] = 'calculation';
            if ($hasDosage) $reason[] = 'dosage_timing';
            if ($hasImageQuestion) $reason[] = 'image_verification';

            return [
                'useDualAI' => true,
                'reason' => 'complex_query: ' . implode(', ', $reason)
            ];
        }

        // Simple queries use single AI (Gemini) for cost savings
        if ($isSimpleQuery) {
            $reason = [];
            if ($isGreeting) $reason[] = 'greeting';
            if ($isShortFollowup) $reason[] = 'short_followup';
            if ($isProductLookup) $reason[] = 'product_lookup';
            if ($isGeneralTip) $reason[] = 'general_tip';

            return [
                'useDualAI' => false,
                'reason' => 'simple_query: ' . implode(', ', $reason)
            ];
        }

        // If RAG has strong answer and query is not complex, use single AI
        if ($hasStrongRagAnswer && !$isComplexQuery) {
            return [
                'useDualAI' => false,
                'reason' => 'strong_rag_answer'
            ];
        }

        // Default: Use dual AI for accuracy on medium-complexity questions
        return [
            'useDualAI' => true,
            'reason' => 'default_dual_for_accuracy'
        ];
    }

    /**
     * Detect product recommendations in the AI response.
     * Returns an array of detected product names.
     *
     * @param string $response The AI response to analyze
     * @return array List of detected product names
     */
    protected function detectProductRecommendations(string $response): array
    {
        $products = [];

        // Common fertilizer brand names in Philippines
        $fertilizerPatterns = [
            '/\b(Urea|46-0-0)\b/i',
            '/\b(Complete|14-14-14)\b/i',
            '/\b(Ammosul|Ammonium Sulfate|21-0-0)\b/i',
            '/\b(MOP|Muriate of Potash|0-0-60)\b/i',
            '/\b(Solophos|0-18-0)\b/i',
            '/\b(DAP|Diammonium Phosphate|18-46-0)\b/i',
            '/\b(Zinc Sulfate|ZnSO4|Zintrac)\b/i',
            '/\b(Boron|Solubor)\b/i',
            '/\b(Calcium Nitrate)\b/i',
            '/\b(Potassium Nitrate)\b/i',
        ];

        // Common pesticide/fungicide/herbicide patterns
        $pesticidePatterns = [
            '/\b(Karate|Lambda[- ]?cyhalothrin)\b/i',
            '/\b(Prevathon|Chlorantraniliprole)\b/i',
            '/\b(Cartap|Padan)\b/i',
            '/\b(Cypermethrin|Cymbush)\b/i',
            '/\b(Imidacloprid|Confidor|Admire)\b/i',
            '/\b(Thiamethoxam|Actara)\b/i',
            '/\b(Carbendazim)\b/i',
            '/\b(Mancozeb|Dithane)\b/i',
            '/\b(Tricyclazole|Beam)\b/i',
            '/\b(Propiconazole|Tilt)\b/i',
            '/\b(Butachlor|Machete)\b/i',
            '/\b(Pretilachlor|Sofit)\b/i',
            '/\b(2,4-D)\b/i',
        ];

        // Foliar fertilizers
        $foliarPatterns = [
            '/\b(Sagana\s*100)\b/i',
            '/\b(Power\s*Grow)\b/i',
            '/\b(Amino\s*Plus)\b/i',
            '/\b(Super\s*Grow)\b/i',
            '/\b(Multi-K|Multi K)\b/i',
            '/\b(YaraVita)\b/i',
        ];

        $allPatterns = array_merge($fertilizerPatterns, $pesticidePatterns, $foliarPatterns);

        foreach ($allPatterns as $pattern) {
            if (preg_match_all($pattern, $response, $matches)) {
                foreach ($matches[0] as $match) {
                    $normalized = trim($match);
                    if (!in_array($normalized, $products)) {
                        $products[] = $normalized;
                    }
                }
            }
        }

        // Also check for recommendation indicators
        $hasRecommendation = preg_match('/recommend|irekomenda|gamitin|i-apply|lagyan|sprayan|ilagay/i', $response);

        Log::debug('Product detection', [
            'productsFound' => count($products),
            'products' => $products,
            'hasRecommendationLanguage' => $hasRecommendation,
        ]);

        return $products;
    }

    /**
     * Search RAG for alternative products.
     *
     * @param array $products List of product names to find alternatives for
     * @param AiRagSetting|null $ragSettings RAG settings
     * @return array Alternatives found ['product' => 'alternatives' => [...]]
     */
    protected function findProductAlternatives(array $products, $ragSettings): array
    {
        if (empty($products) || !$ragSettings || empty($ragSettings->apiKey)) {
            return [];
        }

        $alternatives = [];

        // Product category mapping for better search
        $productCategories = [
            'Complete' => 'NPK fertilizer balanced 14-14-14 alternative',
            '14-14-14' => 'NPK fertilizer balanced Complete alternative',
            'Urea' => 'nitrogen fertilizer 46-0-0 Ammosul ammonium alternative',
            '46-0-0' => 'nitrogen fertilizer Urea alternative',
            'Ammosul' => 'nitrogen sulfur fertilizer 21-0-0 alternative',
            'MOP' => 'potassium fertilizer 0-0-60 potash alternative',
            'Zinc Sulfate' => 'zinc foliar spray Zintrac micronutrient alternative',
            'Zintrac' => 'zinc foliar spray zinc sulfate micronutrient alternative',
            'Iron' => 'iron chelate foliar spray ferrous sulfate alternative',
            'Karate' => 'insecticide pyrethroid lambda-cyhalothrin alternative',
            'Prevathon' => 'insecticide chlorantraniliprole stem borer alternative',
        ];

        foreach ($products as $product) {
            // Get category-specific search terms
            $categoryTerms = '';
            foreach ($productCategories as $key => $terms) {
                if (stripos($product, $key) !== false) {
                    $categoryTerms = $terms;
                    break;
                }
            }

            // Build targeted search query
            $searchQuery = !empty($categoryTerms)
                ? "{$categoryTerms} Philippines agri-store available brand"
                : "alternative kapalit ng {$product} Philippines agriculture product brand available local";

            try {
                $this->enforceRateLimit('pinecone-alt');

                $result = $this->queryPineconeAssistantRaw(
                    $ragSettings->apiKey,
                    $ragSettings->indexName,
                    $searchQuery
                );

                Log::debug("Product alternatives RAG search", [
                    'product' => $product,
                    'query' => $searchQuery,
                    'resultLength' => strlen($result['content'] ?? ''),
                ]);

                if (!empty($result['content']) && strlen($result['content']) > 50) {
                    // Extract product names from the RAG result
                    $foundAlternatives = $this->detectProductRecommendations($result['content']);

                    // Known product synonyms/equivalents that should NOT be suggested as alternatives
                    // These are the SAME product or brand-product relationships
                    $productSynonyms = [
                        // Urea and its NPK formula
                        '46-0-0' => ['urea', '46-0-0'],
                        'urea' => ['46-0-0', 'urea'],
                        // Zintrac and YaraVita brand
                        'zintrac' => ['yaravita', 'yaravita zintrac', 'zintrac 700'],
                        'yaravita' => ['zintrac', 'yaravita zintrac'],
                        // MOP and its formula
                        '0-0-60' => ['mop', 'muriate of potash', '0-0-60'],
                        'mop' => ['0-0-60', 'muriate of potash'],
                        // Ammosul
                        '21-0-0' => ['ammosul', 'ammonium sulfate', '21-0-0'],
                        'ammosul' => ['21-0-0', 'ammonium sulfate'],
                        // Complete fertilizer
                        '14-14-14' => ['complete', 'complete fertilizer'],
                        // Bortrac and YaraVita
                        'bortrac' => ['yaravita', 'yaravita bortrac'],
                    ];

                    // Get synonyms for current product
                    $productLower = strtolower($product);
                    $synonymsToExclude = $productSynonyms[$productLower] ?? [];

                    // Also check if product contains any synonym keys
                    foreach ($productSynonyms as $key => $syns) {
                        if (stripos($product, $key) !== false) {
                            $synonymsToExclude = array_merge($synonymsToExclude, $syns);
                        }
                    }
                    $synonymsToExclude = array_unique(array_map('strtolower', $synonymsToExclude));

                    // Remove the original product AND its synonyms from alternatives
                    $foundAlternatives = array_filter($foundAlternatives, function($alt) use ($product, $synonymsToExclude) {
                        $altLower = strtolower($alt);

                        // Check if alt matches original product
                        if (stripos($alt, $product) !== false || stripos($product, $alt) !== false) {
                            return false;
                        }

                        // Check if alt is a known synonym
                        foreach ($synonymsToExclude as $synonym) {
                            if (stripos($altLower, $synonym) !== false || stripos($synonym, $altLower) !== false) {
                                return false;
                            }
                        }

                        return true;
                    });

                    if (!empty($foundAlternatives)) {
                        $alternatives[$product] = [
                            'alternatives' => array_values($foundAlternatives),
                            'rawContent' => substr($result['content'], 0, 500),
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Failed to find alternatives for {$product}: " . $e->getMessage());
            }
        }

        $this->logFlowStep('Product Alternatives Search',
            'Found alternatives for ' . count($alternatives) . ' of ' . count($products) . ' products',
            json_encode($alternatives, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return $alternatives;
    }

    /**
     * Append product alternatives to the final response using AI.
     *
     * @param string $response The original AI response
     * @param array $alternatives The alternatives found
     * @param AiApiSetting|null $openaiSetting OpenAI settings
     * @return string The enhanced response with alternatives
     */
    protected function appendProductAlternatives(string $response, array $alternatives, $openaiSetting): string
    {
        if (empty($alternatives) || !$openaiSetting || empty($openaiSetting->apiKey)) {
            return $response;
        }

        // Build the alternatives section
        $altText = "\n\n💡 **ALTERNATIBONG PRODUKTO:**\n";
        foreach ($alternatives as $product => $data) {
            $alts = implode(', ', array_slice($data['alternatives'], 0, 3)); // Max 3 alternatives per product
            $altText .= "• Kapalit ng **{$product}**: {$alts}\n";
        }

        // Use GPT-4o-mini to naturally integrate alternatives
        $prompt = <<<PROMPT
TASK: Naturally integrate product alternatives into the existing response.

ORIGINAL RESPONSE:
{$response}

ALTERNATIVES TO ADD:
{$altText}

INSTRUCTIONS:
1. Keep the ENTIRE original response intact
2. At the END, add a natural transition like "Kung hindi available ang..." or "Pwede ring gamitin bilang alternatibo..."
3. List the alternatives in a helpful, natural way in Filipino/Tagalog
4. Do NOT modify or remove any part of the original response
5. Make the alternatives section feel like a helpful addition, not an afterthought

OUTPUT: The complete response with alternatives naturally integrated at the end.
PROMPT;

        try {
            $this->enforceRateLimit('gpt-alternatives');

            $apiResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $openaiSetting->apiKey,
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful Filipino agricultural assistant. Integrate the alternatives naturally in Tagalog.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 2500,
            ]);

            if ($apiResponse->successful()) {
                $result = $apiResponse->json();
                $enhancedResponse = $result['choices'][0]['message']['content'] ?? '';

                // Track token usage
                $usage = $result['usage'] ?? [];
                if (!empty($usage['prompt_tokens'])) {
                    $this->trackTokenUsage('openai', 'product_alternatives', $usage['prompt_tokens'], $usage['completion_tokens'] ?? 0, 'gpt-4o-mini');
                }

                if (!empty($enhancedResponse)) {
                    $this->logFlowStep('Alternatives Added', 'Successfully integrated product alternatives into response');
                    return $enhancedResponse;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to integrate alternatives: ' . $e->getMessage());
        }

        // Fallback: Just append the alternatives section
        return $response . $altText;
    }

    /**
     * Extract key findings from image analysis for better RAG queries.
     * Looks for deficiency indicators, pest/disease names, and symptoms.
     *
     * @param string $imageAnalysis The image analysis text
     * @return string Key findings summarized for RAG query
     */
    protected function extractKeyFindingsFromImageAnalysis(string $imageAnalysis): string
    {
        $findings = [];

        // Nutrient deficiency patterns
        $deficiencyPatterns = [
            // Explicit deficiency mentions
            '/zinc\s*(deficiency|kakulangan)/i' => 'zinc sulfate Zintrac foliar spray treatment product',
            '/iron\s*(deficiency|kakulangan)/i' => 'iron chelate foliar spray treatment product',
            '/nitrogen\s*(deficiency|kakulangan)/i' => 'nitrogen fertilizer urea treatment',
            '/phosphorus\s*(deficiency|kakulangan)/i' => 'phosphorus fertilizer treatment',
            '/potassium\s*(deficiency|kakulangan)/i' => 'potassium MOP fertilizer treatment',
            '/magnesium\s*(deficiency|kakulangan)/i' => 'magnesium deficiency treatment foliar',
            '/sulfur\s*(deficiency|kakulangan)/i' => 'sulfur fertilizer ammosul treatment',
            '/kakulangan\s*sa\s*(zinc|iron|nitrogen|phosphorus|potassium)/i' => '$1 deficiency treatment product Philippines',

            // CRITICAL: Yellow stripes on corn/mais leaves = ZINC DEFICIENCY (not nitrogen!)
            // This is a classic diagnostic symptom
            '/mais.*dilaw.*guhit|dilaw.*guhit.*mais|corn.*yellow.*stripe/i' => 'zinc sulfate Zintrac foliar spray mais corn zinc deficiency treatment',
            '/dilaw\s*na\s*guhit|guhit.*dilaw|yellow.*stripe|striped.*yellow/i' => 'zinc deficiency zinc sulfate Zintrac foliar spray treatment',

            // Interveinal chlorosis (yellowing between leaf veins) = zinc or iron deficiency
            '/interveinal\s*chlorosis/i' => 'zinc iron deficiency zinc sulfate foliar spray treatment',
            '/chlorosis|pagdilaw/i' => 'zinc iron micronutrient deficiency foliar treatment',

            // More Filipino patterns
            '/dilaw.*dahon.*mais|mais.*dahon.*dilaw/i' => 'zinc deficiency mais corn zinc sulfate treatment',
            '/nutrient\s*deficiency.*mais|mais.*nutrient\s*deficiency/i' => 'zinc sulfate micronutrient foliar spray mais treatment',
        ];

        // Pest and disease patterns
        $pestPatterns = [
            '/bacterial\s*leaf\s*blight|BLB/i' => 'bacterial leaf blight treatment fungicide',
            '/rice\s*blast|pagsabog/i' => 'rice blast fungicide treatment',
            '/tungro/i' => 'tungro treatment insecticide vector',
            '/stem\s*borer|sundot/i' => 'stem borer insecticide treatment',
            '/leaf\s*folder/i' => 'leaf folder insecticide',
            '/brown\s*plant\s*hopper|BPH/i' => 'brown planthopper insecticide',
            '/army\s*worm/i' => 'armyworm insecticide treatment',
            '/aphids|aphis/i' => 'aphid insecticide treatment',
            '/fungal|fungus|fungi/i' => 'fungicide treatment Philippines',
        ];

        // Symptom patterns
        $symptomPatterns = [
            // Yellow stripes/lines on leaves - classic zinc deficiency symptom
            '/dilaw\s*na\s*guhit|guhit\s*na\s*dilaw/i' => 'zinc deficiency zinc sulfate Zintrac foliar spray treatment',
            '/yellow\s*(stripe|line|band)/i' => 'zinc deficiency zinc sulfate foliar spray treatment',
            '/stripe.*leaf|leaf.*stripe/i' => 'zinc deficiency micronutrient foliar treatment',

            // General yellowing
            '/dilaw.*dahon|yellow.*leaf|pagdilaw/i' => 'yellowing leaves micronutrient zinc iron deficiency treatment',

            // Other symptoms
            '/brown\s*spots|kayumanggi.*batik/i' => 'leaf spots disease fungicide treatment',
            '/wilting|pagkalanta/i' => 'wilting plant treatment irrigation',
            '/stunted|bansot/i' => 'stunted growth zinc deficiency treatment fertilizer',
            '/necrosis|pagkatuyo/i' => 'leaf necrosis treatment',

            // Mais/corn specific
            '/mais.*stress|stress.*mais/i' => 'mais corn zinc deficiency treatment foliar',
        ];

        $allPatterns = array_merge($deficiencyPatterns, $pestPatterns, $symptomPatterns);

        foreach ($allPatterns as $pattern => $finding) {
            if (preg_match($pattern, $imageAnalysis, $matches)) {
                // Replace $1 placeholder if exists
                if (isset($matches[1])) {
                    $finding = str_replace('$1', $matches[1], $finding);
                }
                $findings[] = $finding;
            }
        }

        // Remove duplicates and limit
        $findings = array_unique($findings);
        $findings = array_slice($findings, 0, 5); // Max 5 key findings

        $result = implode(', ', $findings);

        Log::info('Extracted image analysis findings for RAG', [
            'findingsCount' => count($findings),
            'findings' => $result,
        ]);

        return $result;
    }

    /**
     * Post-process the final response to add product alternatives if applicable.
     *
     * @param string $response The final AI response
     * @param AiApiSetting|null $openaiSetting OpenAI settings
     * @return string The enhanced response
     */
    protected function postProcessWithAlternatives(string $response, $openaiSetting = null): string
    {
        // Check if response has product recommendations
        $detectedProducts = $this->detectProductRecommendations($response);

        if (empty($detectedProducts)) {
            Log::debug('No products detected in response, skipping alternatives');
            return $response;
        }

        $this->logFlowStep('Product Detection', 'Found ' . count($detectedProducts) . ' products: ' . implode(', ', $detectedProducts));

        // Get RAG settings
        $ragSettings = AiRagSetting::getOrCreate();

        if (!$ragSettings) {
            Log::debug('No RAG settings, skipping alternatives');
            return $response;
        }

        // Find alternatives in RAG
        $alternatives = $this->findProductAlternatives($detectedProducts, $ragSettings);

        if (empty($alternatives)) {
            Log::debug('No alternatives found in RAG');
            return $response;
        }

        // Integrate alternatives into response
        return $this->appendProductAlternatives($response, $alternatives, $openaiSetting);
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
        $this->flow = AiReplyFlow::getOrCreate();

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
     * Check if user needs visual reference images (what does X look like).
     *
     * @return bool True if visual reference is needed
     */
    public function needsVisualReference(): bool
    {
        return $this->needsVisualReference;
    }

    /**
     * Set the visual reference flag.
     *
     * @param bool $value Whether visual reference is needed
     */
    public function setNeedsVisualReference(bool $value): void
    {
        $this->needsVisualReference = $value;
        if ($value) {
            Log::debug('Visual reference flag set - user wants to see what something looks like');
        }
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
     * =================================================================
     * CONTINUITY RULE: Extract key context from chat history
     * =================================================================
     * This prevents the AI from re-asking for information the user
     * has already provided in the conversation thread.
     *
     * Extracts: location, crop type, crop stage/DAP, problem mentioned,
     * variety being used, etc.
     *
     * @param string $chatHistory The chat history text
     * @return array Extracted context as key-value pairs
     */
    protected function extractContextFromChatHistory(string $chatHistory): array
    {
        $context = [];

        if (empty($chatHistory)) {
            return $context;
        }

        $text = strtolower($chatHistory);

        // Extract LOCATION (province, municipality, region)
        // IMPORTANT: Exclude crop names from being detected as locations!
        $cropNamesRegex = 'mais|corn|maize|palay|rice|bigas|gulay|vegetable|tubo|sugarcane|niyog|coconut|mangga|mango|saging|banana';

        $locationPatterns = [
            // Philippine provinces
            '/\b(pangasinan|pampanga|bulacan|tarlac|nueva ecija|isabela|cagayan|ilocos|zambales|batangas|laguna|quezon|camarines|albay|sorsogon|masbate|leyte|samar|cebu|bohol|negros|iloilo|capiz|antique|aklan|palawan|davao|cotabato|bukidnon|misamis|zamboanga|agusan|surigao)\b/i',
            // Regions
            '/\b(region\s*[IVX0-9]+|CAR|NCR|CALABARZON|MIMAROPA|BICOL|VISAYAS|MINDANAO)\b/i',
        ];

        foreach ($locationPatterns as $pattern) {
            if (preg_match($pattern, $chatHistory, $matches)) {
                $location = trim($matches[1] ?? $matches[0]);
                // Double-check it's not a crop name
                if (!preg_match('/^(' . $cropNamesRegex . ')$/i', $location)) {
                    $context['location'] = $location;
                    break;
                }
            }
        }

        // Extract CROP TYPE
        // IMPORTANT: Detect CROP SWITCHING - when user switches from one crop to another
        // Patterns like "kung sa mais?", "e kung sa mais?", "paano kung mais?" indicate switching
        $cropSwitchPatterns = [
            '/(?:kung|pano|paano|what about|how about).*\b(corn|mais|maize)\b/i' => 'corn',
            '/(?:kung|pano|paano|what about|how about).*\b(rice|palay|bigas)\b/i' => 'rice',
            '/\b(corn|mais|maize)\s*(?:naman|instead|this time)/i' => 'corn',
            '/\b(rice|palay|bigas)\s*(?:naman|instead|this time)/i' => 'rice',
            '/(?:e|eh)\s+kung\s+(?:sa\s+)?\b(corn|mais|maize)\b/i' => 'corn',
            '/(?:e|eh)\s+kung\s+(?:sa\s+)?\b(rice|palay|bigas)\b/i' => 'rice',
        ];

        $isCropSwitching = false;
        $newCrop = null;

        // Check if user is switching crops (prioritize this over history)
        foreach ($cropSwitchPatterns as $pattern => $crop) {
            if (preg_match($pattern, $chatHistory)) {
                $newCrop = $crop;
                $isCropSwitching = true;
                Log::info('Crop switching detected', ['pattern' => $pattern, 'newCrop' => $crop]);
                break;
            }
        }

        $cropPatterns = [
            '/\b(corn|mais|maize)\b/i' => 'corn',
            '/\b(rice|palay|bigas)\b/i' => 'rice',
            '/\b(vegetable|gulay|sitaw|talong|kamatis|patola|ampalaya|okra|pechay|repolyo|kangkong)\b/i' => 'vegetable',
            '/\b(sugarcane|tubo)\b/i' => 'sugarcane',
            '/\b(coconut|niyog)\b/i' => 'coconut',
            '/\b(mango|mangga)\b/i' => 'mango',
            '/\b(banana|saging)\b/i' => 'banana',
        ];

        if ($isCropSwitching && $newCrop) {
            // User is switching crops - use the NEW crop
            $context['crop'] = $newCrop;
            // Mark that we should NOT carry over problem from old crop
            $context['crop_switched'] = true;
        } else {
            // Normal crop detection from history
            foreach ($cropPatterns as $pattern => $crop) {
                if (preg_match($pattern, $chatHistory)) {
                    $context['crop'] = $crop;
                    break;
                }
            }
        }

        // Extract CROP STAGE / DAP (Days After Planting)
        if (preg_match('/(\d+)\s*(?:DAP|days?\s*after\s*(?:planting|transplanting|emergence))/i', $chatHistory, $matches)) {
            $context['dap'] = (int)$matches[1];
            // Determine stage from DAP
            $dap = $context['dap'];
            if ($dap <= 20) {
                $context['stage'] = 'vegetative (early)';
            } elseif ($dap <= 45) {
                $context['stage'] = 'vegetative (late)';
            } elseif ($dap <= 65) {
                $context['stage'] = 'flowering/reproductive';
            } elseif ($dap <= 90) {
                $context['stage'] = 'grain filling';
            } else {
                $context['stage'] = 'maturity/harvesting';
            }
        }

        // =================================================================
        // PRIORITY 1: Detect PRE-PLANTING context FIRST
        // If user says "bago magtanim", "susubok magtanim", etc., they haven't planted yet!
        // This MUST be checked before other stage patterns to avoid wrong assumptions
        // =================================================================
        $prePlantingPatterns = [
            '/\b(bago|before)\s*(pa\s*)?(ako\s*)?(mag|nag)?tanim/i',  // "bago magtanim", "before planting"
            '/\b(susubok|susubukan|magsusubukan)\s*(pa\s*)?(lang\s*)?(mag)?tanim/i',  // "susubok magtanim"
            '/\b(ngayon|ngayun)\s*(pa\s*)?lang\s*(ako\s*)?(mag|nag)?tanim/i',  // "ngayon pa lang magtanim"
            '/\b(bago|prior|before)\s*(mag)?plant/i',  // "before planting"
            '/\b(hindi|di|wala)\s*(pa\s*)?(naka|na)?tanim/i',  // "hindi pa nakatanim"
            '/\b(planning|nagpaplano|magpaplano)\s*(mag)?tanim/i',  // "planning to plant"
            '/\b(paghahanda|preparation)\s*(sa|ng|para)\s*(lupa|taniman|pagtatanim)/i',  // "paghahanda sa lupa"
            '/\b(paano|anong?)\s*(paghahanda|preparation)\s*(sa|ng)?\s*(lupa|taniman)/i',  // "paano paghahanda sa lupa"
            '/\b(first\s*time|una|bago)\s*(ko\s*)?(mag|nag)?tanim/i',  // "first time magtanim"
        ];

        $isPrePlanting = false;
        foreach ($prePlantingPatterns as $pattern) {
            if (preg_match($pattern, $chatHistory)) {
                $context['stage'] = 'pre-planting';
                $context['is_pre_planting'] = true;
                $isPrePlanting = true;
                Log::info('PRE-PLANTING context detected - user has NOT planted yet', [
                    'pattern' => $pattern
                ]);
                break;
            }
        }

        // Extract explicit stage mentions (only if NOT pre-planting)
        $stagePatterns = [
            '/\b(vegetative|tumutubo|lumalaki)\b/i' => 'vegetative',
            '/\b(flowering|namumulaklak|namumunga)\b/i' => 'flowering',
            '/\b(grain\s*fill|naglilipat|nagpupuno)\b/i' => 'grain filling',
            '/\b(matur|hinog|ready.*harvest|aanihin)\b/i' => 'maturity',
            '/\b(seedling|punla|binhi)\b/i' => 'seedling',
        ];

        // Only check other stage patterns if NOT in pre-planting context
        if (!isset($context['stage']) && !$isPrePlanting) {
            foreach ($stagePatterns as $pattern => $stage) {
                if (preg_match($pattern, $chatHistory)) {
                    $context['stage'] = $stage;
                    break;
                }
            }
        }

        // Extract PROBLEM/ISSUE mentioned
        // IMPORTANT: Skip problem extraction if user is switching crops!
        // The problem from the OLD crop should NOT carry over to the NEW crop
        // ALSO IMPORTANT: Skip problems that are about COMPARISON images, not user's original crop!
        $problemPatterns = [
            '/\b(armyworm|FAW|fall\s*armyworm|uod)\b/i' => 'armyworm infestation',
            '/\b(borer|corn\s*borer|stem\s*borer)\b/i' => 'borer infestation',
            '/\b(tungro|virus)\b/i' => 'tungro/virus',
            '/\b(blast|leaf\s*blast)\b/i' => 'blast disease',
            '/\b(BLB|bacterial\s*leaf\s*blight)\b/i' => 'bacterial leaf blight',
            '/\b(yellow|dilaw|yellowing)\b.*\b(leaf|dahon)\b/i' => 'yellowing leaves',
            '/\b(wilt|nalanta|drying)\b/i' => 'wilting',
            '/\b(kuhol|snail|golden\s*snail)\b/i' => 'snail problem',
            '/\b(daga|rat|rodent)\b/i' => 'rat problem',
            '/\b(damo|weed|kulitis|mutha)\b/i' => 'weed problem',
        ];

        // Identify comparison varieties mentioned in chat history
        // Problems associated with these should be IGNORED (they're about reference images, not user's crop)
        $comparisonVarietyPattern = '/(?:compare|ikumpara|kumpara|vs|versus|reference|uploaded)\s*(?:sa|to|with|ng|image|larawan)?\s*[^A-Za-z]*\b(RC\s*\d+|R\s*\d+|SL-?\d+H?|Longping|NSIC\s*Rc\s*\d+)/i';
        $comparisonVarieties = [];
        if (preg_match_all($comparisonVarietyPattern, $chatHistory, $compMatches)) {
            foreach ($compMatches[1] as $cv) {
                $comparisonVarieties[] = strtoupper(preg_replace('/\s+/', '', $cv));
            }
        }

        // Split chat history into messages to analyze problem context
        $messages = preg_split('/\n(?=User:|Assistant:)/i', $chatHistory);

        // Only extract problem if NOT switching crops (problem belongs to previous crop)
        if (empty($context['crop_switched'])) {
            foreach ($problemPatterns as $pattern => $problem) {
                // Check if problem exists in chat history
                if (preg_match($pattern, $chatHistory)) {
                    // Check if this problem is mentioned in context of a comparison variety
                    // If so, it's about the reference image, not the user's crop
                    $isProblemFromComparison = false;

                    foreach ($messages as $msg) {
                        if (preg_match($pattern, $msg)) {
                            // Check if same message mentions a comparison variety
                            foreach ($comparisonVarieties as $compVar) {
                                if (stripos($msg, $compVar) !== false ||
                                    preg_match('/reference image|uploaded.*image/i', $msg)) {
                                    $isProblemFromComparison = true;
                                    Log::debug('Problem found in comparison context, skipping', [
                                        'problem' => $problem,
                                        'variety' => $compVar,
                                    ]);
                                    break 2;
                                }
                            }
                        }
                    }

                    if (!$isProblemFromComparison) {
                        $context['problem'] = $problem;
                        Log::debug('Problem extracted (not from comparison)', ['problem' => $problem]);
                        break;
                    }
                }
            }
        } else {
            Log::info('Skipping problem extraction - crop switched detected', [
                'newCrop' => $context['crop'] ?? 'unknown'
            ]);
        }

        // Extract VARIETY mentioned
        // IMPORTANT: Only extract variety from USER messages, NOT from AI responses!
        // AI often says "inyong NK6414" or "your Jackpot 102" without user specifying it
        // This leads to wrong variety assumptions

        // Split chat history into user messages only
        $userMessagesOnly = '';
        $allMessages = preg_split('/\n(?=User:|Assistant:)/i', $chatHistory);
        foreach ($allMessages as $msg) {
            if (preg_match('/^User:/i', trim($msg))) {
                $userMessagesOnly .= ' ' . $msg;
            }
        }

        // PRIORITY 1: Look for variety explicitly mentioned BY USER
        // Patterns for user mentioning their variety
        $userVarietyPatterns = [
            // Pattern 1: "[variety] ko/namin" - e.g., "jackpot 102 ko", "rc222 ko", "SL-8H ko"
            // This is the most common pattern in Filipino: "[variety] ko"
            '/\b(NK\d+[A-Za-z]*|P\d+|Pioneer\s*\d+|Dekalb\s*\d+|PSB\s*Rc\s*\d+|NSIC\s*Rc\s*\d+|RC\s*\d+|Jackpot\s*\d*|SL-?\d+H?|Longping\s*\d*)\s+(ko|namin|natin|akin|amin)\b/i',
            // Pattern 2: "aming/akin/ko tanim na [variety]" - e.g., "aming tanim na NK6414"
            '/\b(ang|yung)?\s*(aming|akin|aking|ko|namin)\s*(tanim|pananim|mais|palay|variety)?\s*(?:ay|is|na)?\s*(NK\d+[A-Za-z]*|P\d+|Pioneer\s*\d+|Dekalb\s*\d+|PSB\s*Rc\s*\d+|NSIC\s*Rc\s*\d+|RC\s*\d+|Jackpot\s*\d*|SL-?\d+H?|Longping)/i',
            // Pattern 3: "tanim/pananim ko ay [variety]"
            '/\b(tanim|variety|pananim)\s*(ko|namin|natin)\s*(?:ay|is|na)?\s*(NK\d+[A-Za-z]*|P\d+|Pioneer\s*\d+|Dekalb\s*\d+|Jackpot\s*\d*|SL-?\d+H?)/i',
            // Pattern 4: "[variety] ang tanim ko"
            '/\b(NK\d+[A-Za-z]*|P\d+|Pioneer\s*\d+|Dekalb\s*\d+|Jackpot\s*\d*)\s*(?:ang|yung)?\s*(tanim|pananim|variety)\s*(ko|namin|natin)/i',
        ];

        $foundOriginalVariety = false;
        foreach ($userVarietyPatterns as $pattern) {
            if (preg_match($pattern, $userMessagesOnly, $matches)) {
                // Find the variety in the match (it's in different capture groups)
                $variety = '';
                foreach ($matches as $i => $m) {
                    if ($i > 0 && preg_match('/^(NK|P\d|Pioneer|Dekalb|PSB|NSIC|RC|Jackpot|SL|Longping)/i', $m)) {
                        $variety = trim($m);
                        break;
                    }
                }
                if (!empty($variety)) {
                    $context['variety'] = $variety;
                    $context['variety_source'] = 'user_specified';
                    $foundOriginalVariety = true;
                    Log::debug('Found variety FROM USER message', ['variety' => $context['variety']]);
                    break;
                }
            }
        }

        // PRIORITY 2: If no original variety found, look for general variety mentions FROM USER MESSAGES ONLY
        // But EXCLUDE varieties mentioned in these contexts:
        // - Comparison: "compare to RC222", "ikumpara sa"
        // - Asking about: "e sa RC222?", "ano ang RC222?", "kumusta ang RC222?"
        if (!$foundOriginalVariety) {
            // Pattern 1: Skip varieties mentioned in comparison context
            $comparisonContextPattern = '/(?:compare|ikumpara|kumpara|vs|versus|reference image|uploaded.*image)\s*(?:sa|to|with)?\s*[A-Za-z]*\s*(NK\d+[A-Za-z]*|P\d+|Pioneer\s*\d+|Dekalb\s*\d+|PSB\s*Rc\s*\d+|NSIC\s*Rc\s*\d+|RC\s*\d+|R\s*\d+|SL-?\d+H?|Longping)/i';

            // Pattern 2: Skip varieties mentioned in "asking about" context (NOT ownership)
            // e.g., "e sa rc222?" = asking about RC222, NOT claiming ownership
            $askingAboutPattern = '/(?:e\s+sa|ano\s+(?:ang|yung)|paano\s+(?:ang|yung)|kumusta\s+(?:ang|yung)|tungkol\s+sa|about|what\s+(?:about|is))\s*[A-Za-z]*\s*(NK\d+[A-Za-z]*|P\d+|Pioneer\s*\d+|Dekalb\s*\d+|PSB\s*Rc\s*\d+|NSIC\s*Rc\s*\d+|RC\s*\d+|R\s*\d+|SL-?\d+H?|Longping|Jackpot\s*\d*)/i';

            // Get all varieties to exclude (from comparison and asking-about contexts)
            $excludeVarieties = [];

            // Collect from comparison context
            if (preg_match_all($comparisonContextPattern, $userMessagesOnly, $compMatches)) {
                foreach ($compMatches[1] as $cv) {
                    $excludeVarieties[] = strtoupper(preg_replace('/\s+/', '', $cv));
                }
            }

            // Collect from asking-about context
            if (preg_match_all($askingAboutPattern, $userMessagesOnly, $askMatches)) {
                foreach ($askMatches[1] as $av) {
                    $excludeVarieties[] = strtoupper(preg_replace('/\s+/', '', $av));
                }
            }

            Log::debug('Excluded varieties (comparison/asking-about)', ['excluded' => $excludeVarieties]);

            // Now extract variety FROM USER MESSAGES ONLY, and skip if it's in excluded list
            // Include Jackpot, SL, Longping and other hybrid varieties
            if (preg_match('/\b(NK\d+[A-Za-z]*|P\d+|Pioneer\s*\d+|Dekalb\s*\d+|PSB\s*Rc\s*\d+|NSIC\s*Rc\s*\d+|RC\s*\d+|Jackpot\s*\d*|SL-?\d+H?|Longping\s*\d*)\b/i', $userMessagesOnly, $matches)) {
                $candidateVariety = trim($matches[1]);
                // Normalize: remove extra spaces
                $candidateVarietyNorm = strtoupper(preg_replace('/\s+/', '', $candidateVariety));
                if (!in_array($candidateVarietyNorm, $excludeVarieties)) {
                    // Keep original capitalization for display
                    $context['variety'] = ucwords(strtolower($candidateVariety));
                    $context['variety_source'] = 'user_general';
                    Log::debug('Found variety from user message (general)', ['variety' => $context['variety']]);
                }
            }
        }

        // Extract PLANTING METHOD
        if (preg_match('/\b(transplant|lipat\s*tanim|dapog)\b/i', $chatHistory)) {
            $context['method'] = 'transplanting';
        } elseif (preg_match('/\b(direct\s*seed|diretso\s*tanim|sabog)\b/i', $chatHistory)) {
            $context['method'] = 'direct seeding';
        }

        // Extract SEASON
        if (preg_match('/\b(wet\s*season|tag.?ulan|rainy)\b/i', $chatHistory)) {
            $context['season'] = 'wet season';
        } elseif (preg_match('/\b(dry\s*season|tag.?init|summer)\b/i', $chatHistory)) {
            $context['season'] = 'dry season';
        }

        // Extract AREA SIZE
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:hectare|ha|ektarya)/i', $chatHistory, $matches)) {
            $context['area'] = $matches[1] . ' hectares';
        }

        // =================================================================
        // Extract LAST RECOMMENDATION TOPIC from AI responses
        // This prevents topic switching when user asks "which is best from the list"
        // =================================================================
        $aiMessagesOnly = '';
        foreach ($allMessages as $msg) {
            if (preg_match('/^Assistant:/i', trim($msg))) {
                $aiMessagesOnly .= ' ' . $msg;
            }
        }

        // Detect if AI gave pesticide recommendations
        if (preg_match('/\b(pesticide|insecticide|fungicide|herbicide|gamot.*pest|pampuksa|panlaban.*peste)/i', $aiMessagesOnly)) {
            $context['last_recommendation_type'] = 'pesticide';
            // Extract specific pesticides mentioned
            if (preg_match_all('/\b(Belt|Prevathon|Karate|Decis|Fastac|Lannate|Regent|Cartap|Lambda|Cypermethrin|Imidacloprid|Fipronil|Chlorpyrifos)\s*(?:SC|EC|WG|WP|SL)?\b/i', $aiMessagesOnly, $pestMatches)) {
                $context['recommended_products'] = array_unique($pestMatches[0]);
            }
        }

        // Detect if AI gave fertilizer recommendations
        if (preg_match('/\b(fertilizer|pataba|abono|nutrient|NPK|urea|complete|ammosul)/i', $aiMessagesOnly) &&
            !isset($context['last_recommendation_type'])) {
            $context['last_recommendation_type'] = 'fertilizer';
        }

        // Detect if AI gave variety recommendations
        if (preg_match('/\b(variety|varieties|hybrid|binhi|seed)\s*(recommend|rekomendasyon|mairerekomenda)/i', $aiMessagesOnly) &&
            !isset($context['last_recommendation_type'])) {
            $context['last_recommendation_type'] = 'variety';
        }

        // =================================================================
        // "ALREADY DONE" GUARDRAIL - Detect actions the user has already performed
        // IMPORTANT: Skip if crop switched - actions were for previous crop, not new one!
        // =================================================================
        $actionsDone = [];

        // Only track actions done if NOT switching crops
        if (empty($context['crop_switched'])) {

        // Fertilizers/nutrients already applied
        $fertilizerPatterns = [
            '/(?:nag|na|naka|already|did).*(apply|lagay|spray|spread|broadcast).*(urea|complete|ammosul|NPK|fertilizer|pataba|abono)/i',
            '/(?:ni|in|binigyan|nilagyan).*(urea|complete|ammosul|NPK|fertilizer|pataba|abono)/i',
            '/(?:urea|complete|ammosul|NPK|fertilizer|pataba|abono).*(?:na|already|nailagay|naiapply|naispray)/i',
            '/(nag.?fertilize|nag.?pataba|na.?apply|na.?spray).*(?:na|already|kahapon|last\s*week|recently)/i',
            // Specific nutrients
            '/(?:nag|na).*(zinc|boron|calcium|magnesium|foliar).*(?:spray|apply|lagay)/i',
            '/(?:zinc|boron|calcium|magnesium|foliar).*(?:na|already|nailagay|naispray)/i',
        ];

        foreach ($fertilizerPatterns as $pattern) {
            if (preg_match($pattern, $chatHistory, $matches)) {
                $actionsDone[] = 'fertilizer/nutrient application';
                break;
            }
        }

        // Pesticides/sprays already applied
        $sprayPatterns = [
            '/(?:nag|na|naka).*(spray|ispray|gamot|treat).*(pest|insect|fungus|weed|damo)/i',
            '/(?:spray|ispray|gamot).*(?:na|already|kahapon|last\s*week|recently)/i',
            '/(?:cypermethrin|lambda|chlorpyrifos|imidacloprid|fipronil|cartap|abamectin|carbendazim|mancozeb|glyphosate).*(?:na|already|naiapply|naispray)/i',
            '/(nag.?spray|na.?spray|na.?gamot).*(?:na|already|for|para)/i',
            // Specific products
            '/(?:nag|na).*(prevathon|belt|karate|fastac|lannate|furadan|regent|decis)/i',
        ];

        foreach ($sprayPatterns as $pattern) {
            if (preg_match($pattern, $chatHistory, $matches)) {
                $actionsDone[] = 'pesticide/spray application';
                break;
            }
        }

        // Irrigation/watering already done
        $irrigationPatterns = [
            '/(?:nag|na).*(patubig|diligan|irrigate|water).*(?:na|already|kahapon)/i',
            '/(?:patubig|diligan|irrigation).*(?:na|already|natapos)/i',
            '/(nag.?patubig|na.?diligan|watered)/i',
        ];

        foreach ($irrigationPatterns as $pattern) {
            if (preg_match($pattern, $chatHistory, $matches)) {
                $actionsDone[] = 'irrigation/watering';
                break;
            }
        }

        // Weeding already done
        $weedingPatterns = [
            '/(?:nag|na).*(weed|damo|bungkal|cultivate|clean).*(?:na|already)/i',
            '/(?:herbicid|weed.*killer).*(?:na|already|naiapply)/i',
            '/(nag.?damo|na.?bungkal|weeded)/i',
        ];

        foreach ($weedingPatterns as $pattern) {
            if (preg_match($pattern, $chatHistory, $matches)) {
                $actionsDone[] = 'weeding/cultivation';
                break;
            }
        }

        // Specific products/treatments mentioned as already used
        $productPatterns = [
            '/(?:ginamit|used|nag.?apply|na.?spray)\s+(?:ang|ng|yung)?\s*([A-Za-z0-9\-]+)/i',
            '/([A-Za-z0-9\-]+)\s+(?:na|already)\s+(?:ginamit|used|apply|spray)/i',
        ];

        foreach ($productPatterns as $pattern) {
            if (preg_match_all($pattern, $chatHistory, $matches)) {
                foreach ($matches[1] as $product) {
                    $product = strtoupper(trim($product));
                    // Filter out common non-product words
                    if (!in_array($product, ['NA', 'ANG', 'NG', 'SA', 'PO', 'KO', 'MO', 'YAN', 'YUN', 'TO', 'THE', 'FOR', 'AND', 'BUT'])) {
                        if (strlen($product) >= 3) {
                            $actionsDone[] = "applied: {$product}";
                        }
                    }
                }
            }
        }

        // Store actions done in context
        if (!empty($actionsDone)) {
            $context['actions_done'] = array_unique($actionsDone);
        }

        } else {
            // Crop switched - don't carry over actions from previous crop
            Log::info('Skipping actions_done extraction - crop switched detected');
        } // End of crop_switched check

        Log::debug('Extracted context from chat history', [
            'context' => $context,
            'actionsDone' => $actionsDone,
            'historyLength' => strlen($chatHistory),
        ]);

        return $context;
    }

    /**
     * Build a context summary string from extracted context.
     * Used to inject into prompts so AI doesn't re-ask for info.
     *
     * @param array $context Extracted context from extractContextFromChatHistory()
     * @return string Context summary for prompt injection
     */
    protected function buildContextSummary(array $context): string
    {
        if (empty($context)) {
            return '';
        }

        $parts = [];

        if (!empty($context['crop'])) {
            $parts[] = "Crop: {$context['crop']}";
        }
        if (!empty($context['location'])) {
            $parts[] = "Location: {$context['location']}";
        }
        if (!empty($context['dap'])) {
            $parts[] = "DAP: {$context['dap']} days ({$context['stage']})";
        } elseif (!empty($context['stage'])) {
            // For pre-planting, make it clear the user hasn't planted yet
            if ($context['stage'] === 'pre-planting') {
                $parts[] = "Stage: PRE-PLANTING (user has NOT planted yet - do NOT assume any growth stage!)";
            } else {
                $parts[] = "Stage: {$context['stage']}";
            }
        }
        if (!empty($context['problem'])) {
            $parts[] = "Problem: {$context['problem']}";
        }
        // Include last recommendation type to prevent topic switching
        if (!empty($context['last_recommendation_type'])) {
            $parts[] = "Last topic: {$context['last_recommendation_type']} recommendations";
            if (!empty($context['recommended_products'])) {
                $parts[] = "Products discussed: " . implode(', ', $context['recommended_products']);
            }
        }
        if (!empty($context['variety'])) {
            $parts[] = "Variety: {$context['variety']}";
        }
        if (!empty($context['method'])) {
            $parts[] = "Method: {$context['method']}";
        }
        if (!empty($context['season'])) {
            $parts[] = "Season: {$context['season']}";
        }
        if (!empty($context['area'])) {
            $parts[] = "Area: {$context['area']}";
        }

        // "ALREADY DONE" GUARDRAIL - Actions already performed
        $alreadyDone = [];
        if (!empty($context['actions_done'])) {
            foreach ($context['actions_done'] as $action) {
                $alreadyDone[] = "- " . $action;
            }
        }

        if (empty($parts) && empty($alreadyDone)) {
            return '';
        }

        $summary = "=== CONTEXT FROM CONVERSATION (DO NOT ASK AGAIN) ===\n";
        $summary .= implode("\n", $parts) . "\n";

        // Add ALREADY DONE section if there are actions
        if (!empty($alreadyDone)) {
            $summary .= "\n=== ACTIONS ALREADY DONE (DO NOT RECOMMEND AGAIN) ===\n";
            $summary .= implode("\n", $alreadyDone) . "\n";
            $summary .= "CRITICAL: Do NOT recommend these actions again unless you explain\n";
            $summary .= "WHY repeating with different timing/rate/goal would help.\n";
        }

        $summary .= "=========================================\n\n";

        return $summary;
    }

    /**
     * =================================================================
     * SCOPE CHECK: AGRICULTURE-ONLY SCOPE GATE (Step 0 of Master Process)
     * =================================================================
     * Returns ['blocked' => true] if question is NOT agricultural
     * Returns ['blocked' => false] if question IS agricultural
     *
     * AGRICULTURAL includes (from Master Process):
     * - crop nutrition, fertilization, irrigation
     * - pest, disease, weed ID & control (IPM)
     * - spraying, tank mix, sequence, PHI/REI, label timing
     * - crop stages, variety traits, seed quality
     * - soil constraints (pH, salinity, sodicity)
     * - yield, harvest, postharvest directly related to crops
     * - farm decisions affecting crop outcome
     *
     * If borderline → ALLOW and let AI ask ONE clarifying question
     *
     * @param string $message The user's message
     * @return array ['blocked' => bool, 'reason' => string]
     */
    protected function strictScopeCheck(string $message): array
    {
        $message = trim($message);
        $lowerMessage = strtolower($message);

        // =================================================================
        // RULE 0: HARD OFF-TOPIC CHECK FIRST (before short message bypass)
        // =================================================================
        // Even short messages should be blocked if they contain obviously
        // off-topic keywords like cooking, relationships, tech support, etc.
        $hardOffTopicPatterns = [
            // Cooking & recipes (not postharvest)
            '/\b(cook|cooking|recipe|luto|lutuin|magluluto|magluto|ihaw|prito|nilaga|adobo|sinigang|kare.?kare|pinakbet|ulam|sahog|ingredient|sangkap|kusina|kitchen)\b/i',
            // Relationships & personal
            '/\b(jowa|boyfriend|girlfriend|karelasyon|love\s*life|heartbreak|break\s*up|date|dating|crush|ex-|marriage|kasal)\b/i',
            // Tech support (non-agricultural)
            '/\b(password|wifi|internet|facebook|tiktok|youtube|instagram|twitter|cellphone|laptop|computer|phone|app|software|code|programming)\b/i',
            // Homework & academics (non-agricultural)
            '/\b(solve|equation|math|calcul|homework|assignment|thesis|exam|school|eskwela|test|quiz)\b.*(help|tulong|please|paki)/i',
            // Health & medical (non-agricultural)
            '/\b(hospital|doctor|doktor|gamot\s*sa\s*tao|medicine|surgery|sakit\s*ko|masakit|lagnat|ubo|sipon)\b/i',
            // Travel & tourism
            '/\b(travel|biyahe|vacation|bakasyon|tourist|hotel|beach|dagat|mountain|bundok|mall)\b/i',
            // Finance & business (non-farm)
            '/\b(loan|utang|bank|bangko|invest|stock|crypto|bitcoin|negosyo\s*(?!pagsasaka|farming)|salary|sweldo)\b/i',
            // Fashion & beauty
            '/\b(fashion|damit|clothes|makeup|beauty|parlor|salon|hair|buhok)\b/i',
            // Entertainment
            '/\b(movie|pelikula|teleserye|kantahin|kanta|song|music|basketball|volleyball|sports|laro)\b/i',
        ];

        foreach ($hardOffTopicPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                Log::info('SCOPE CHECK: Hard off-topic keyword detected (early check)', [
                    'message' => substr($message, 0, 100),
                    'pattern' => $pattern,
                ]);
                return ['blocked' => true, 'reason' => 'hard_off_topic_early'];
            }
        }

        // RULE 1: Very short messages (1-5 words) - likely follow-ups
        // These should use context from previous messages
        // NOTE: Hard off-topic check already done above
        $wordCount = str_word_count($message);
        if ($wordCount <= 5) {
            // Greetings and acknowledgments - ALLOW
            if (preg_match('/^(hi|hello|hey|good\s*(morning|afternoon|evening)|magandang\s*(umaga|hapon|gabi)|kumusta|opo?|yes|oo|hindi|no|okay|ok|sige|salamat|thanks?|thank you|sure|gets?|ano|bakit|paano|kailan|saan|ilan)[\s\?\!\.]*$/i', $message)) {
                return ['blocked' => false, 'reason' => 'greeting_or_short_response'];
            }
            // Short messages are likely follow-ups - ALLOW and use context
            return ['blocked' => false, 'reason' => 'short_message_follow_up'];
        }

        // RULE 2: AGRICULTURAL TOPICS - ALLOW immediately
        // Expanded to match Master Process definition
        $agriculturalPatterns = [
            // Crop nutrition & fertilization
            '/\b(fertilizer|pataba|abono|urea|complete|ammosul|NPK|nitrogen|phosphorus|potassium|foliar|organic|compost)\b/i',
            '/\b(nutrient|sustansya|deficien|kulang|yellowing|dilaw|chloro|magnesium|calcium|zinc|boron|iron|manganese|sulfur)\b/i',
            // Pest, disease, weed control
            '/\b(peste?|insect|kulisap|uod|worm|borer|armyworm|FAW|aphid|thrips|mites|hopper|bug|snail|kuhol|rat|daga)\b/i',
            '/\b(sakit|disease|virus|bacteria|fungi|fungal|blast|blight|tungro|rust|rot|wilt|mold|lesion)\b/i',
            '/\b(damo|weed|herbicid|grass|kulitis|mutha)\b/i',
            '/\b(pesticide|insecticide|fungicide|molluscicide|rodenticide|spray|gamot|control|puksa|patay)\b/i',
            // Crop stages & varieties
            '/\b(crop|pananim|mais|corn|rice|palay|vegetable|gulay|fruit|prutas|seed|binhi|variety|hybrid|variety)\b/i',
            '/\b(DAP|DAT|days?\s*after|planting|tanim|transplant|lipat|seedling|punla|vegetative|flowering|grain\s*fill|mature|harvest|ani)\b/i',
            '/\b(stage|yugto|phase|booting|heading|tasseling|silking|dough|milk|ripening)\b/i',
            // Irrigation & water
            '/\b(tubig|water|irrigat|patubig|diligan|drought|baha|flood|moisture)\b/i',
            // Soil
            '/\b(lupa|soil|pH|acidic|alkaline|salinity|sodic|clay|loam|sandy|organic\s*matter|tillag)\b/i',
            // Yield & harvest
            '/\b(yield|ani|production|tonelada|MT\/ha|kilo|drying|storage|postharvest|thresh)\b/i',
            // Farm operations
            '/\b(farm|bukid|sakahan|taniman|hectare|ektarya|row|spacing|density|population)\b/i',
            // Spraying & tank mix
            '/\b(spray|ispray|tank\s*mix|sequence|interval|PHI|REI|withhold|residue|pre.?harvest)\b/i',
            // Active ingredients (common)
            '/\b(cypermethrin|lambda|chlorpyrifos|imidacloprid|fipronil|cartap|abamectin|carbendazim|mancozeb|glyphosate|2,4.?D|butachlor)\b/i',
            // Brand names (Philippines)
            '/\b(NK\d+|P\d+|Pioneer|Dekalb|Syngenta|Bayer|BASF|Corteva|Bioseed|SL|Ramgo)\b/i',
        ];

        foreach ($agriculturalPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return ['blocked' => false, 'reason' => 'agricultural_topic'];
            }
        }

        // Also check the existing comprehensive patterns
        if ($this->isAgriculturalQuestion($message)) {
            return ['blocked' => false, 'reason' => 'agricultural_question_pattern'];
        }

        // RULE 3: HARD OFF-TOPIC - BLOCK immediately
        $hardOffTopicPatterns = [
            // Entertainment & media
            '/\b(joke|jokes|kwento\s*(mo|ka)|story|chismis|gossip|funny|patawa|movie|film|series|music|kanta|song|dance|sayaw|game|laro|basketball|volleyball)\b/i',
            // Relationships & personal
            '/\b(jowa|boyfriend|girlfriend|karelasyon|love\s*life|heartbreak|break\s*up|date|dating|crush|ex-|marriage|kasal)\b/i',
            // Tech support (non-agricultural)
            '/\b(password|wifi|internet|facebook|tiktok|youtube|instagram|twitter|cellphone|laptop|computer|phone|app|software|code|programming)\b/i',
            // Homework & academics (non-agricultural)
            '/\b(solve|equation|math|calcul|homework|assignment|thesis|exam|school|eskwela|test|quiz)\b.*(help|tulong|please|paki)/i',
            // Cooking & recipes (not postharvest)
            '/\b(cook|cooking|recipe|luto|lutuin|ihaw|prito|nilaga|adobo|sinigang|kare.?kare|pinakbet|ulam|sahog|ingredient)\b/i',
            // Health & medical (non-agricultural)
            '/\b(hospital|doctor|doktor|gamot\s*sa\s*tao|medicine|surgery|sakit\s*ko|masakit|lagnat|ubo|sipon)\b/i',
            // Travel & tourism
            '/\b(travel|biyahe|vacation|bakasyon|tourist|hotel|beach|dagat|mountain|bundok|mall)\b/i',
            // Finance & business (non-farm)
            '/\b(loan|utang|bank|bangko|invest|stock|crypto|bitcoin|negosyo|business|salary|sweldo)\b/i',
            // Fashion & beauty
            '/\b(fashion|damit|clothes|makeup|beauty|parlor|salon|hair|buhok)\b/i',
        ];

        foreach ($hardOffTopicPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return ['blocked' => true, 'reason' => 'hard_off_topic'];
            }
        }

        // RULE 4: BORDERLINE - Check if it COULD be agricultural with context
        $borderlinePatterns = [
            // Generic questions that MIGHT be about agriculture
            '/\b(anong?|what|which|alin)\b.*(maganda|best|recommend|mabuti)/i',
            '/\b(paano|how)\b.*(gawin|do|make|treat|control|prevent|handle|manage)/i',
            '/\b(bakit|why)\b.*(ganito|ganyan|such|this|that|nangyari)/i',
            '/\b(kailan|when)\b.*(dapat|should|need|pwede|can|mainam)/i',
            '/\b(saan|where)\b.*(bili|buy|kuha|get|mabibili|makakuha)/i',
            '/\b(ano|what)\b.*(problema|issue|mali|wrong|nangyari)/i',
            // Filipino particles in questions
            '/\b(po|ba|nga|naman|kasi|kaya|talaga|siguro)\b/i',
        ];

        foreach ($borderlinePatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                // Borderline - ALLOW and let AI ask for clarification if needed
                return ['blocked' => false, 'reason' => 'borderline_allow_clarification'];
            }
        }

        // RULE 5: DEFAULT - If unclear, ALLOW and let AI decide
        // The AI will apply the scope gate and can ask for clarification
        // or politely decline if truly off-topic
        return ['blocked' => false, 'reason' => 'default_allow_ai_handles'];
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
     * Check if the message is a general educational/beginner question.
     * These questions should NOT use context from previous conversations about specific crops.
     *
     * Examples:
     * - "Ako ay bago pa lamang sa pagtatanim ng mais. Gusto ko malaman kung ilang ulit at kelan ang tamang pagpapatubig?"
     *   → This is a beginner asking a GENERAL question, NOT about their specific crop
     * - "ano ang tamang pagpapataba sa palay?" → General question, not about specific crop
     *
     * @param string $message The user's message
     * @return bool True if this is a general educational question that should NOT use previous context
     */
    protected function isGeneralEducationalQuestion(string $message): bool
    {
        $message = strtolower(trim($message));

        // Pattern 1: Beginner/new farmer indicators
        $beginnerPatterns = [
            '/\b(bago|baguhan|bagong)\s*(pa\s*)?(lang|lamang)?\s*(sa|ako|mag)?\s*(pagtatanim|magtanim|farmer|magsasaka|agriculture)/i',
            '/\b(first\s*time|unang\s*beses|simula\s*pa\s*lang)\b/i',
            '/\b(newbie|beginner|bagong\s*magsasaka)\b/i',
        ];

        // Pattern 2: General "I want to learn/know" without referencing their specific crop
        // CRITICAL: Only match if there's NO ownership indicator (ko, akin, aming, namin) in the message
        $hasOwnership = preg_match('/\b(tanim|pananim|mais|palay|crop)\s*(ko|akin|namin|amin)\b/i', $message) ||
                        preg_match('/\b(ko|akin|namin|amin)\s*(na|ng)?\s*(tanim|pananim|mais|palay|crop)\b/i', $message);

        // If message has ownership indicators, it's about their specific crop - not general
        if ($hasOwnership) {
            return false;
        }

        // Check for beginner patterns
        foreach ($beginnerPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                Log::info('General educational question detected (beginner pattern)', [
                    'message' => substr($message, 0, 100),
                    'pattern' => $pattern,
                ]);
                return true;
            }
        }

        // Pattern 3: Generic "how to" questions without specific context
        // "ano ang tamang X sa [crop]?" without ownership
        $genericPatterns = [
            '/\b(ano|paano)\s*(ang|ba)?\s*(tamang|proper|best|ideal)\s*(pagpapatubig|pagdidilig|watering|irrigation)\b/i',
            '/\b(ilang\s*(ulit|beses)|gaano\s*kadalas)\s*(mag|ang)?\s*(patubig|dilig|water)\b/i',
            '/\b(kelan|kailan)\s*(ang|ba)?\s*(tamang|best|ideal)\s*(pagpa)?tubig\b/i',
            '/\b(gusto\s*ko\s*ma(laman|tutunan|aralan))\b/i',
        ];

        foreach ($genericPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                Log::info('General educational question detected (generic pattern)', [
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
               "Ako po ay isang Technician para sa mga pananim - maaari ko lang po kayong tulungan sa mga tanong tungkol sa pagsasaka," .
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

        // EXCLUSION PATTERNS: Questions about diagnosis, assessment, timing, methods - NOT product recommendations
        // These should NOT trigger RAG product search even if they contain fertilizer/product names as context
        $exclusionPatterns = [
            // =================================================================
            // 1. PROBLEM IDENTIFICATION QUESTIONS
            // User asking what's wrong, what the problem is, what's happening
            // =================================================================
            // English - problem identification
            '/\b(what\'?s?\s+(the\s+)?(problem|wrong|issue|matter|happening))\b/i',
            '/\b(is\s+there\s+(a|any)\s+(problem|issue))\b/i',
            '/\b(what\s+could\s+be\s+(the\s+)?(problem|wrong|issue|cause))\b/i',
            '/\b(what\s+is\s+(the\s+)?(cause|reason|problem|issue))\b/i',
            '/\b(do\s+(i|we)\s+have\s+a\s+problem)\b/i',
            '/\b(something\s+(is\s+)?(wrong|off|not\s+right))\b/i',
            // Tagalog - problem identification
            '/\b(ano|anong?)\s*(po\s+)?(ba\s+)?(ang\s+|yung\s+|ung\s+)?(problema|mali|issue|nangyayari|nagyayari|problema)\b/i',
            '/\b(may\s+)?(problema|issue|mali|defect|sira)\s*(ba|kaya)\b/i',
            '/\b(problema|issue|mali)\s*(ba\s+)?(ito|yan|to|iyan|siya|sya)\b/i',
            '/\b(ano\s+kaya|anong\s+kaya)\s*(po\s+)?(ang\s+)?(problema|mali|dahilan|cause|rason|nangyari)\b/i',
            '/\b(bakit|bat)\s+(po\s+)?(ganito|ganyan|ganon|ganto|ito|yan|siya)\b/i',
            '/\b(ano\s+(po\s+)?ba\s+talaga)\s*(ang\s+)?(problema|nangyayari|issue)\b/i',
            '/\b(meron\s*bang?|merong?|may)\s*(problema|issue|mali)\b/i',
            '/\b(ano\s+ang|anong)\s+(naging|nangyaring?)\s*(problema|issue)\b/i',

            // =================================================================
            // 2. PLANT STRUGGLING/DIFFICULTY QUESTIONS
            // User describing plants having difficulty growing, emerging, etc.
            // =================================================================
            // English - plant difficulties
            '/\b(struggling|having\s+difficulty|difficult|hard\s+time)\s*(to\s+)?(grow|emerge|sprout|germinate)/i',
            '/\b(not\s+growing|won\'?t\s+grow|isn\'?t\s+growing|aren\'?t\s+growing)\b/i',
            '/\b(plants?\s+(are\s+|is\s+)?(weak|dying|wilting|yellowing|stunted|stressed|sick))\b/i',
            '/\b(failing\s+to\s+(emerge|grow|germinate|sprout|develop))\b/i',
            '/\b(poor|low|bad)\s+(germination|emergence|growth|stand)\b/i',
            '/\b(slow|delayed|stunted)\s+(growth|growing|emergence|development)\b/i',
            '/\b(plant|crop|seedling)s?\s+(look|looks|appear|appears)\s+(weak|sick|stressed|yellow|pale)\b/i',
            '/\b(leaves?\s+(are|is)\s+(turning|becoming)\s+(yellow|brown|pale|dry))\b/i',
            // Tagalog - plant difficulties
            '/\b(nahihirapan|hirap|mahirap)\s*(po\s+)?(ang\s+)?(lumabas|tumubo|lumalaki|sumisilang|mag-?grow|gumanda)\b/i',
            '/\b(hindi|ayaw|di|walang?)\s*(pa\s+)?(po\s+)?(lumalaki|tumutubo|lumabas|sumisilang|nag-?grow|gumaganda)\b/i',
            '/\b(mahina|mahinang?|stressed|maliit|payat)\s*(pa\s+)?(ang\s+)?(tanim|halaman|pananim|crops?|seedlings?|mais|palay)\b/i',
            '/\b(namamatay|patay|namatay|dying|dedz?)\s*(ang\s+)?(tanim|halaman|pananim|seedlings?)\b/i',
            '/\b(naninilaw|dilaw|yellow|yellowing|kupas)\s*(ang\s+)?(dahon|leaves?|tanim|halaman)\b/i',
            '/\b(nalalanta|lanta|nalanta|wilting|wilted|tuyo)\s*(ang\s+)?(tanim|halaman|dahon|leaves?)\b/i',
            '/\b(hindi\s+maganda|pangit|못생긴)\s*(ang\s+)?(tubo|growth|tanim|halaman|itsura)\b/i',
            '/\b(mabagal|mabagal\s+ang|slow|matagal)\s*(ang\s+)?(tubo|growth|paglaki|lumaki|pag-?unlad)\b/i',
            '/\b(manipis|얇은|얇다|얇아|얇게)\s*(ang\s+)?(dahon|stem|tangkay|puno)\b/i',
            '/\b(mukhang?\s+)?(may\s+)?(sakit|pest|insekto|uod|kulisap|hama)\b/i',
            '/\b(iniisip\s+kong?|akala\s+ko|feeling\s+ko)\s*(may\s+)?(problema|sakit|issue)\b/i',

            // =================================================================
            // 3. STATUS CHECK QUESTIONS (On Track / Normal / Okay)
            // User checking if things are progressing normally
            // =================================================================
            // English - status check
            '/\b(is\s+(this|it)\s+(normal|okay|ok|fine|expected|right|correct|good|acceptable))\b/i',
            '/\b(am\s+i\s+(still\s+)?on\s+track)\b/i',
            '/\b(are\s+(we|things)\s+on\s+track)\b/i',
            '/\b(is\s+(this|it)\s+(how|what)\s+it\s+should)\b/i',
            '/\b(should\s+(this|it)\s+be\s+(like\s+this|this\s+way|looking\s+like\s+this))\b/i',
            '/\b(is\s+my\s+(crop|plant|progress)\s+(okay|normal|on\s+track))\b/i',
            '/\b(on[\s-]?track)\b/i',
            '/\b(doing\s+(okay|well|fine|alright))\b/i',
            // Tagalog - status check
            '/\b(normal|okay|ok|maayos|tama|ayos|goods?)\s*(lang\s+)?(pa\s+)?(ba|kaya)\s*(po\s+)?(ito|yan|to|iyan|siya|sya)?\b/i',
            '/\b(on[\s-]?track)\s*(pa\s+)?(ba|kaya)\s*(po\s+)?(ako|kami|tayo|siya)?\b/i',
            '/\b(tama|mali|sakto|saktong?)\s*(ba|kaya)\s*(po\s+)?(ito|yan|to|ang|yung)?\b/i',
            '/\b(ganito|ganyan|ganto|ganon)\s*(ba|kaya)\s*(po\s+)?(talaga|dapat|expected)\b/i',
            '/\b(expected|inaasahan|dapat)\s*(ba|kaya)\s*(po\s+)?(ito|yan|to|ganito)?\b/i',
            '/\b(dapat\s+ba\s+(po\s+)?(ganito|ganyan|ganon|ito))\b/i',
            '/\b(high[\s-]?yield)\s*(pa\s*)?(ba|kaya)?\b/i',
            '/\b(nasa\s+)?(tamang?|sakto|right)\s*(track|landas|daan)\b/i',
            '/\b(magiging|magiging\s+)?(maganda|okay|successful)\s*(pa\s+)?(ba|kaya)\b/i',
            '/\b(pwede|puwede)\s*(pa\s+)?(ba|kaya)\s*(ito|yan|to|makaabot|maging)\b/i',

            // =================================================================
            // 4. WHAT TO DO / WHAT ACTION QUESTIONS (in context of problems)
            // User asking what action to take to address a problem
            // =================================================================
            // English - what to do
            '/\b(what\s+should\s+(i|we)\s+do)\b/i',
            '/\b(what\s+(can|could)\s+(i|we)\s+do)\b/i',
            '/\b(how\s+(do|can|should)\s+(i|we)\s+(fix|solve|address|remedy|treat|handle))\b/i',
            '/\b(what\'?s?\s+the\s+(solution|fix|remedy|cure|answer|way))\b/i',
            '/\b(how\s+to\s+(fix|solve|address|remedy|treat|handle|correct))\b/i',
            '/\b(any\s+(suggestions?|recommendations?|advice|tips?)\s*(for|on|about)?\s*(this|fixing|solving)?)\b/i',
            '/\b(what\s+do\s+you\s+(suggest|recommend|advise))\b/i',
            // Tagalog - what to do
            '/\b(ano|anong?)\s*(po\s+)?(ang\s+|yung\s+)?(dapat|pwede|pwedeng?|puwede|puwedeng?|kailangan)\s*(kong?|naming?|nating?)?\s*(gawin|i-?do|aksyon|action)\b/i',
            '/\b(paano|pano)\s*(po\s+)?(ko|namin|natin)?\s*(aayusin|sosolve|i-?solve|i-?fix|remedyuhan|mare-?remedy|gagawin|tutukan)\b/i',
            '/\b(ano|anong?)\s*(po\s+)?(ang\s+)?(solusyon|solution|remedy|gamot|lunas|sagot|paraan)\b/i',
            '/\b(may\s+)?(pwede|pwedeng?|puwede|puwedeng?)\s*(ba\s+)?(po\s+)?(gawin|i-?do|aksyon|ituwid|ayusin)\b/i',
            '/\b(ano\s+(po\s+)?ang\s+(dapat|pwede|kailangan))\b/i',
            '/\b(paano|pano)\s*(po\s+)?(ba)?\s*(to|ito|yan|iyan)?\s*(aayusin|i-?address|lutasin|solusyunan)\b/i',
            '/\b(may\s+)?(suggestion|suhestiyon|tips?|advice|payo)\s*(ba|kaya)?\s*(po|kayo)?\b/i',
            '/\b(ano\s+)?(ang\s+)?(magandang?|tamang?|best)\s*(gawin|aksyon|solusyon)\b/i',

            // =================================================================
            // 5. USER PROVIDING CONTEXT (Schedule/Recommendation followed)
            // User sharing what they already did/used as context - NOT asking for products
            // =================================================================
            // English - providing context
            '/\b(here\'?s?\s+(my|the|our)\s+(schedule|recommendation|plan|program|application))\b/i',
            '/\b(this\s+is\s+(what\s+(i|we)\s+followed|my\s+schedule|the\s+recommendation|our\s+program))\b/i',
            '/\b((i|we)\s+(followed|used|applied|did)\s+(this|the|your)\s+(schedule|recommendation|program|plan))\b/i',
            '/\b(based\s+on\s+(this|the|my|our)\s+(schedule|recommendation|program))\b/i',
            '/\b(following\s+(this|the|your)\s+(schedule|recommendation|program))\b/i',
            '/\b((i|we)\s+(have\s+been|was|were)\s+(following|using|applying))\b/i',
            '/\b(my\s+(current|fertilizer|application)\s+(schedule|program|plan))\b/i',
            // Tagalog - providing context
            '/\b(eto|ito|heto)\s*(po\s+)?(ang|ung|yung)?\s*(schedule|recommendation|sinunod|ginagamit|inapply|ginamit|ginawa)\b/i',
            '/\b(eto|ito|heto)\s*(po\s+)?(ang|ung|yung)?\s*(fertilizer|abono|pataba|application)\s*(schedule|program|plan|ko|namin)?\b/i',
            '/\b(sinunod|ginagamit|inapply|ginamit|inaaply)\s*(ko|namin|natin)?\s*(po\s+)?(ito|yan|to|ang|yung)?\s*(schedule|recommendation)?\b/i',
            '/\b(schedule|recommendation|program)\s*(na\s+)?(sinunod|ginagamit|inapply|ginamit|inaaply)\b/i',
            '/\b(base|based|batay)\s*(po\s+)?(sa|dito\s+sa|dun\s+sa)\s*(schedule|recommendation|sinabi|binigay)\b/i',
            '/\b(ito\s+ang|eto\s+ang|yan\s+ang)\s*(ginawa|inapply|ginamit|sinunod)\s*(ko|namin|natin)?\b/i',
            '/\b(sa|ang)\s*(schedule|program|plan)\s*(ko|namin|natin|niya)\b/i',
            '/\b(nag-?follow|sumunod)\s*(po\s+)?(ako|kami|siya)\s*(sa|ng)\b/i',
            '/\b(ito\s+(po\s+)?yung|eto\s+(po\s+)?yung)\s*(sinunod|ginawa|inapply|application)\b/i',

            // =================================================================
            // 6. WHY QUESTIONS (Diagnostic - asking for cause/reason)
            // User asking why something is happening
            // =================================================================
            // English - why questions
            '/\b(why\s+(is|are|isn\'?t|aren\'?t|won\'?t|can\'?t|do|does|doesn\'?t))\s*(my\s+)?(plant|crop|seedling|leaf|leaves)/i',
            '/\b(why\s+(is|are)\s+(this|it|they)\s+(happening|like\s+this|not\s+working))\b/i',
            '/\b(what\s+(caused|is\s+causing|could\s+cause))\b/i',
            '/\b(any\s+(idea|clue)\s+(why|what))\b/i',
            '/\b(do\s+you\s+know\s+why)\b/i',
            // Tagalog - why questions (bakit)
            '/\b(bakit|bat)\s*(po\s+)?(ganito|ganyan|hindi|ayaw|di)\s*(po\s+)?(ang\s+)?(tanim|halaman|pananim|dahon|mais|palay|seedlings?)\b/i',
            '/\b(bakit|bat)\s*(po\s+)?(hindi|ayaw|di)\s*(po\s+)?(lumalaki|tumutubo|lumabas|namumulaklak|gumaganda|nag-?improve)\b/i',
            '/\b(bakit|bat)\s*(po\s+)?(namamatay|naninilaw|nalalanta|stressed|may\s+pest|may\s+sakit)\b/i',
            '/\b(bakit|bat|ano)\s*(po\s+)?(kaya\s+)?(ang\s+)?(dahilan|cause|rason|reason|pinagmulan)\b/i',
            '/\b(san|saan|nasaan)\s*(po\s+)?(kaya\s+)?(nanggaling|galing|nagmula)\s*(ang\s+)?(problema|issue|sakit)\b/i',
            '/\b(ano\s+)?(ang\s+|yung\s+)?(sanhi|dahilan|pinagmulan|ugat)\s*(ng|nito|niyan|nyan)\b/i',
            '/\b(may\s+idea|alam\s+mo|alam\s+nyo|alam\s+ba)\s*(ba\s+)?(kung\s+)?(bakit|ano|san)\b/i',

            // =================================================================
            // 7. OBSERVATION/SYMPTOM DESCRIPTION
            // User describing what they see/notice (diagnostic context)
            // =================================================================
            // English - observations
            '/\b((i|we)\s+(noticed|observed|see|saw|found|spotted)\s+(that|something)?)\b/i',
            '/\b(my\s+(plants?|crops?|seedlings?)\s+(are|is)\s+(showing|displaying|exhibiting))\b/i',
            '/\b(the\s+(leaves?|plants?|crops?)\s+(are|is|look|looks|appear|appears))\b/i',
            '/\b((i|we)\s+(can\s+)?see\s+(that|some)?)\b/i',
            '/\b(there\'?s?\s+(something|some)\s+(wrong|off|different|unusual))\b/i',
            // Tagalog - observations
            '/\b(napansin|napapansin|nakita|nakikita|naobserve|na-?observe)\s*(ko|namin|natin)?\s*(po\s+)?(na|ang|yung)?\b/i',
            '/\b(mukhang|parang|tila)\s*(po\s+)?(may|merong?|meron)\b/i',
            '/\b(ang\s+(dahon|tanim|halaman|pananim|seedlings?))\s*(ay|e)?\s*(mukhang|parang|tila)\b/i',
            '/\b(may\s+(nakikita|napapansin|nakita|naobserve))\s*(po\s+)?(ako|akong?|kami|kaming?)\b/i',
            '/\b(tingnan|tignan)\s*(mo|nyo|ninyo|po)\s*(ito|to|yan|iyan)\b/i',
            '/\b(ito\s+po|eto\s+po)\s*(ang|yung)?\s*(nakikita|situation|sitwasyon|lagay)\b/i',

            // =================================================================
            // 8. COMPARISON / EXPECTED VS ACTUAL
            // User comparing their crops to expected results or others
            // =================================================================
            // English - comparison
            '/\b(compared\s+to\s+(others?|other\s+farmers?|normal|expected|before))\b/i',
            '/\b(other\s+(farmers?\'?|people\'?s?|neighbors?\'?)\s+(crops?|plants?|fields?))\b/i',
            '/\b(should\s+(it|they)\s+(be|look)\s+(like|bigger|taller|greener|healthier))\b/i',
            '/\b(not\s+as\s+(big|tall|green|healthy)\s+as)\b/i',
            '/\b(different\s+from\s+(what|how))\b/i',
            // Tagalog - comparison
            '/\b(kumpara|compare|ikumpara|kung\s+ikukumpara)\s*(po\s+)?(sa|kay|sa\s+mga)?\s*(iba|normal|dati|expected|kapitbahay)\b/i',
            '/\b(sa|ang)\s+iba\s*(po\s+)?(kasi|naman|eh|e)\b/i',
            '/\b(dapat\s+ba)\s*(po\s+)?(na)?\s*(mas|ganito|ganyan|ganon|bigger|taller)\b/i',
            '/\b(bakit\s+(po\s+)?(sa|kay|ang)\s+iba)\b/i',
            '/\b(ibang?|ng\s+ibang?)\s*(tanim|halaman|pananim|farmer|kapitbahay|bukid)\b/i',
            '/\b(mas\s+)?(malaki|mataas|maganda|malusog)\s*(ang\s+)?(sa\s+)?iba\b/i',
            '/\b(iba\s+sa|kaiba\s+sa|hindi\s+tulad\s+ng)\b/i',

            // =================================================================
            // 9. TIMELINE/PROGRESS QUESTIONS
            // User asking about timing, stage, or if it's too late/early
            // =================================================================
            // English - timeline
            '/\b(is\s+it\s+(too\s+)?(late|early)\s+(to|for)?)\b/i',
            '/\b(at\s+this\s+(stage|point|time|DAP|age))\b/i',
            '/\b(by\s+(now|this\s+time|this\s+stage))\b/i',
            '/\b(for\s+(\d+\s+)?(day|week|DAP)s?\s+old)\b/i',
            '/\b((i\'?m|we\'?re|it\'?s)\s+already\s+at)\b/i',
            // Tagalog - timeline
            '/\b(huli|late|naghuhuli)\s*(na\s+)?(ba|kaya)\s*(po)?\b/i',
            '/\b(maaga|early)\s*(pa\s+)?(ba|kaya)\s*(po)?\b/i',
            '/\b(sa|ng)\s*(ganitong?|ganung?|this|ito)\s*(stage|punto|point|DAP|edad|age)\b/i',
            '/\b(ngayong?\s+(po\s+)?(panahon|stage|DAP|araw|linggo))\b/i',
            '/\b(sa\s+DAP\s*\d+)\s*(na\s+)?(ito|to|ngayon|na)?\b/i',
            '/\b(dapat\s+ba\s+(po\s+)?(sa|ng|by)\s+ngayon)\b/i',
            '/\b(\d+\s*(araw|days?|linggo|weeks?|DAP))\s*(na|old|palang)\b/i',
            '/\b(nasa\s+)?(ano|anong?)\s*(na\s+)?(stage|edad|yugto)\b/i',

            // =================================================================
            // 10. CONCERN/WORRY QUESTIONS
            // User asking if they should be worried, if it's serious
            // =================================================================
            // English - concern
            '/\b(should\s+(i|we)\s+(be\s+)?(worried|concerned|alarmed))\b/i',
            '/\b(is\s+(this|it)\s+(serious|a\s+concern|worrying|alarming|critical))\b/i',
            '/\b(will\s+(it|they|my\s+crop)\s+(recover|survive|be\s+okay|make\s+it))\b/i',
            '/\b(can\s+(it|they)\s+(still\s+)?(recover|survive|be\s+saved))\b/i',
            '/\b(is\s+there\s+(still\s+)?(hope|a\s+chance|any\s+hope))\b/i',
            '/\b(how\s+(serious|bad|severe)\s+is)\b/i',
            // Tagalog - concern
            '/\b(dapat\s+ba\s+(po\s+)?)?(akong?|kaming?|tayong?)\s*(mag-?alala|mag-?worry|kabahan|matakot)\b/i',
            '/\b(seryoso|serious|grabe|malala|kritikal|critical)\s*(ba|kaya)\s*(po\s+)?(ito|yan|to|siya)?\b/i',
            '/\b(mag-?re-?recover|mabubuhay|makakasurvive|makaka-?recover)\s*(pa\s+)?(ba|kaya)\s*(po)?\b/i',
            '/\b(may\s+)?(pag-?asa|chance|hope|pagkakataon)\s*(pa\s+)?(ba|kaya)\s*(po)?\b/i',
            '/\b(okay|ok|maayos|buhay)\s*(pa\s+)?(ba|kaya)\s*(po\s+)?(ito|yan|siya|sya)?\b/i',
            '/\b(mababawi|maisasalba|mare-?recover|masasagip)\s*(pa\s+)?(ba|kaya)\s*(po)?\b/i',
            '/\b(gaano\s+)?(ka-?seryoso|ka-?lala|ka-?grabe|ka-?severe)\b/i',
            '/\b(nakaka-?(worry|alala|takot|kabag))\s*(ba|kaya)?\b/i',

            // =================================================================
            // 11. DAP (Days After Planting) TIMING QUESTIONS
            // Questions specifically about irrigation/activity timing by DAP
            // =================================================================
            '/\bDAP\s*\d+.*(patubig|irrigate|irrigation|diligan|dilig|tubig|water)/i',
            '/\b\d+\s*DAP.*(patubig|irrigate|irrigation|diligan|dilig|tubig|water)/i',
            '/\b(patubig|diligan|dilig|tubig|irrigat).*\bDAP\s*\d+/i',
            '/\b(patubig|diligan|dilig|tubig|irrigat).*\b\d+\s*DAP/i',
            '/\bDAP\s*\d+.*(schedule|timing|kailan|kelan|kung\s+kailan)/i',
            '/\banong?\s*(mga\s+)?DAP\s*\d*.*(patubig|diligan|mais|palay|rice|corn)/i',
            '/\b(ilang|ilan|how\s+many)\s*DAP/i',
            '/magpatubig.*(ng|sa)?\s*DAP\s*\d+/i',
            '/magpatubig.*(ng|sa)?\s*\d+\s*DAP/i',
            '/\bDAP\b.*(patubig|irrigate|irrigation|diligan|dilig|tubig|water)/i',
            '/\b(patubig|diligan|dilig|tubig|irrigat)\b.*\bDAP\b/i',

            // =================================================================
            // 12. GENERAL TIMING/METHOD QUESTIONS
            // Questions about when/how to do farming activities (not products)
            // =================================================================
            '/\b(kailan|kelan|when|what\s+time|anong\s+oras)\b.*(tanim|diligan|patubig|harvest|ani|apply|lagay)/i',
            '/\b(paano|pano|how\s+to)\b.*(magtanim|mag-?diligan|mag-?patubig|mag-?harvest|mag-?apply|mag-?spray)/i',
            '/\b(schedule|timing|calendar|timetable)\b.*(tanim|planting|irrigation|patubig|fertilizer|application)/i',
            '/\b(gaano\s+katagal|how\s+long|ilang\s+araw|how\s+many\s+days)\b/i',

            // =================================================================
            // 13. VARIETY/SEED QUESTIONS
            // Questions about crop varieties (different from product recommendations)
            // =================================================================
            '/\b(anong?|ano|what)\b.*(variety|klase|uri|tipo|breed|hybrid)\b.*(mais|palay|rice|corn|gulay|vegetable)/i',
            '/\b(magandang?|best|magaling|recommended|mabuting)\b.*(variety|klase|uri|hybrid)\b/i',
            '/\b(anong?|ano)\s*(variety|klase)\s*(ng|ng\s+)?(mais|palay|rice|corn)/i',
            '/\b(ano|anong?)\s*(po\s+)?(ang|yung)?\s*(best|magandang?|recommended)\s*(na\s+)?(variety|binhi|seed|hybrid)\b/i',

            // =================================================================
            // 14. ASSESSMENT/EVALUATION REQUESTS
            // User asking for assessment of their situation/photo/crop
            // =================================================================
            // English
            '/\b(can\s+you\s+(assess|evaluate|check|look\s+at|analyze|diagnose))\b/i',
            '/\b(what\s+do\s+you\s+(think|see|notice))\b/i',
            '/\b(please\s+(check|assess|evaluate|look\s+at|diagnose))\b/i',
            '/\b(need\s+(your\s+)?(assessment|evaluation|diagnosis|opinion))\b/i',
            // Tagalog
            '/\b(pwede|puwede|maaari)\s*(mo|nyo|po)?\s*(ba\s+)?(tingnan|i-?check|i-?assess|suriin)\b/i',
            '/\b(ano\s+)?(ang\s+|po\s+)?(tingin|masasabi|opinion|assessment)\s*(mo|nyo|po)?\b/i',
            '/\b(pakitingnan|paki-?check|patingin|paki-?assess)\s*(po|naman)?\b/i',
            '/\b(kailangan\s+)?(ng\s+)?(assessment|evaluation|diagnosis|opinion)\b/i',
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
        // Get Gemini API setting (global)
        $geminiSetting = AiApiSetting::active()
            ->forProvider('gemini')
            ->enabled()
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
            // Get OpenAI API setting (global)
            $apiSettings = \App\Models\AiApiSetting::active()
                ->forProvider('openai')
                ->enabled()
                ->first();

            if (!$apiSettings || !$apiSettings->apiKey) {
                Log::warning('Product enhancement: No OpenAI API key configured');
                return null;
            }

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiSettings->apiKey,
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
        // Get Gemini API setting (global)
        $geminiSetting = AiApiSetting::active()
            ->forProvider(AiApiSetting::PROVIDER_GEMINI)
            ->enabled()
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

        $recommendation .= "💡 Paalala: Sundin po ang tamang dosage at paraan ng pag-apply na nakasaad sa label ng produkto. Kung may tanong pa kayo, i-message niyo lang ako ulit.\n";

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

        // Get Gemini API setting for Google Search (global)
        $geminiSetting = AiApiSetting::active()
            ->forProvider(AiApiSetting::PROVIDER_GEMINI)
            ->enabled()
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

        // CRITICAL: Product recommendation restrictions
        $instructions .= "=== ⚠️ PRODUCT RECOMMENDATION RESTRICTIONS ===\n\n";
        $instructions .= "FOCUS LANG SA CROP AGRICULTURE (palay, mais, gulay) - HINDI ornamental plants!\n\n";
        $instructions .= "PWEDE I-RECOMMEND:\n";
        $instructions .= "- Common crop fertilizers: Urea (46-0-0), Complete 14-14-14, Ammosul (21-0-0), MOP (0-0-60), DAP (18-46-0)\n";
        $instructions .= "- Ammonium-based fertilizers (ammonium sulfate, urea)\n";
        $instructions .= "- Split application techniques\n";
        $instructions .= "- Organic methods (composting, mulching)\n\n";
        $instructions .= "❌ BAWAL I-RECOMMEND (FOR ORNAMENTAL PLANTS):\n";
        $instructions .= "- Osmocote (slow-release for ornamental/potted plants)\n";
        $instructions .= "- Products marketed for flowers, landscaping, potted plants\n";
        $instructions .= "- Garden-specific fertilizers for ornamentals\n\n";

        // Check for season-specific questions
        $isDrySeasonQuestion = preg_match('/\b(dry season|tag-init|tag-araw|summer|mainit|drought|tubig|irrigat)\b/i', $originalPrompt);
        $isWetSeasonQuestion = preg_match('/\b(wet season|tag-ulan|rainy|monsoon|baha|flood)\b/i', $originalPrompt);

        // Check for irrigation/DAP timing questions
        $isDapQuestion = preg_match('/\b(DAP|days?\s*after\s*plant)/i', $originalPrompt);
        $isIrrigationQuestion = preg_match('/\b(patubig|magpatubig|diligan|irrigat|tubig|water)\b/i', $originalPrompt);
        $isIrrigationTimingQuestion = $isDapQuestion && $isIrrigationQuestion;

        // Check for COMPARISON questions (hybrid vs traditional, variety vs variety)
        $isComparisonQuestion = preg_match('/\b(mas maganda|mas mabuti|mas mataas|kaysa|kumpara|vs|versus|alin ang mas|traditional|dati|lumang|inbred|hybrid vs|honest|unbiased|pagkakaiba)\b/i', $originalPrompt);
        $isHybridVsTraditional = preg_match('/\b(traditional|dati|lumang|inbred|certified vs farmer|hybrid vs)\b/i', $originalPrompt);

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

        // Detect if user wants to compare their crop to STANDARD/EXPECTED appearance
        // Example: "ikumpara vs sa traditional na jackpot 102" = compare to STANDARD Jackpot 102, NOT hybrid vs traditional
        // Example: "ikumpara vs sa standard o traditional farming ng jackpot 102" = compare to standard practices
        $mentionsSpecificVarietyWithTraditional = preg_match('/\b(traditional|tradisyonal)\s+(na\s+)?(jackpot|sl-?8h|sl-?9h|rc|nk|dekalb|pioneer)/i', $originalPrompt);
        $mentionsStandardFarming = preg_match('/\b(standard|traditional)\s+(o\s+)?(traditional\s+)?(farming|practices|method|paraan)/i', $originalPrompt);
        $mentionsSpecificVariety = preg_match('/\b(jackpot|sl-?8h|sl-?9h|rc\s*\d+|nk\s*\d+|dekalb|pioneer|arize|bigante|mestiso)\b/i', $originalPrompt);
        $hasCompareKeyword = preg_match('/\b(ikumpara|compare|kumpara)\b/i', $originalPrompt);
        $wantsStandardComparison = $mentionsSpecificVarietyWithTraditional || $mentionsStandardFarming || ($mentionsSpecificVariety && $hasCompareKeyword && !preg_match('/\b(inbred|hybrid vs|kaysa sa inbred)\b/i', $originalPrompt));

        // Handle COMPARISON TO STANDARD/EXPECTED APPEARANCE (different from hybrid vs traditional)
        // Detailed response format is in Query Rules ("Photo Comparison to Standard/Traditional Farming")
        if ($wantsStandardComparison) {
            $instructions .= "=== ⚠️ COMPARISON TO STANDARD/EXPECTED - SEARCH FOR VARIETY DATA ===\n\n";
            $instructions .= "User wants to compare their crop to STANDARD farming practices.\n";
            $instructions .= "Search for variety-specific data to enable comparison.\n\n";

            $instructions .= "SEARCH FOR:\n";
            $instructions .= "- Expected panicle length, spikelets per panicle\n";
            $instructions .= "- Expected plant height, tiller count\n";
            $instructions .= "- Expected yield in MT/ha\n";
            $instructions .= "- Maturity period and growth stage timing\n\n";

            // Extract variety name for targeted search
            if (preg_match('/\b(jackpot\s*\d*|sl-?\d+h?|nk\s*\d+|dekalb|pioneer\s*\w+|arize|bigante|mestiso)/i', $originalPrompt, $varietyMatch)) {
                $varietyName = $varietyMatch[1];
                $instructions .= "VARIETY: {$varietyName}\n";
                $instructions .= "Search: \"{$varietyName} characteristics yield MT/ha Philippines\"\n";
                $instructions .= "Search: \"{$varietyName} NSIC specifications panicle\"\n\n";
            }

            $instructions .= "RESPONSE FORMAT: Follow Query Rules for 'Photo Comparison to Standard/Traditional Farming'\n";
            $instructions .= "Must include: Comparison table, specific metrics, detailed analysis, verdict.\n\n";
        }
        // Handle HYBRID VS TRADITIONAL VARIETIES comparison (explicit comparison)
        else if ($isComparisonQuestion && ($isHybridVsTraditional || preg_match('/\b(hybrid vs|inbred|kaysa sa inbred|traditional.*varieties)\b/i', $originalPrompt))) {
            $instructions .= "=== ⚠️ HYBRID VS TRADITIONAL COMPARISON - SEARCH BOTH SIDES! ===\n\n";
            $instructions .= "This is a COMPARISON question about hybrid vs traditional/inbred varieties.\n";
            $instructions .= "You MUST search for data on BOTH sides to provide honest comparison!\n\n";

            $instructions .= "HYBRID VS TRADITIONAL COMPARISON RESEARCH:\n\n";

                $instructions .= "STEP 1 - SEARCH FOR HYBRID RICE YIELDS:\n";
                $instructions .= "Search: \"hybrid rice yield Philippines MT/ha\" \"Jackpot 102 yield per hectare\"\n";
                $instructions .= "Search: \"SL Agritech hybrid rice yield\" \"Arize hybrid rice yield Philippines\"\n";
                $instructions .= "Look for: SPECIFIC NUMBERS in MT/ha or kg/ha from farmer experiences\n\n";

                $instructions .= "STEP 2 - SEARCH FOR TRADITIONAL/INBRED RICE YIELDS:\n";
                $instructions .= "Search: \"traditional rice yield Philippines MT/ha\" \"inbred rice variety yield\"\n";
                $instructions .= "Search: \"NSIC Rc inbred rice yield\" \"farmer saved seeds yield Philippines\"\n";
                $instructions .= "Search: \"average rice yield Philippines farmer\" \"national rice yield average\"\n";
                $instructions .= "Look for: SPECIFIC NUMBERS for comparison (typically 3.5-4.5 MT/ha for traditional)\n\n";

                $instructions .= "STEP 3 - FIND DIRECT COMPARISONS:\n";
                $instructions .= "Search: \"hybrid vs inbred rice Philippines comparison\" \"hybrid rice advantage over traditional\"\n";
                $instructions .= "Search: \"hybrid rice yield improvement percentage\" \"hybrid vs traditional yield difference\"\n";
                $instructions .= "Look for: Studies, PhilRice data, or farmer testimonials with NUMBERS\n\n";

                $instructions .= "⚠️ CRITICAL - PROVIDE SPECIFIC DATA:\n";
                $instructions .= "- Hybrid rice yield: X MT/ha (cite source/example)\n";
                $instructions .= "- Traditional/inbred yield: Y MT/ha (cite source/data)\n";
                $instructions .= "- Percentage difference: Z% higher yield\n";
                $instructions .= "- INCLUDE PROS AND CONS of both options!\n\n";

            $instructions .= "EXAMPLE OF GOOD COMPARISON RESPONSE:\n";
            $instructions .= "\"Ang hybrid rice tulad ng Jackpot 102 ay may average yield na 6-8 MT/ha,\n";
            $instructions .= " kumpara sa 3.5-4.5 MT/ha ng traditional/inbred varieties - approximately\n";
            $instructions .= " 40-80% na pagkakaiba. Sa isang halimbawa, nakakuha ang isang magsasaka\n";
            $instructions .= " ng 6.79 MT/ha gamit ang Jackpot 102 vs. 4.2 MT/ha dati sa traditional.\n";
            $instructions .= " PERO may trade-offs: mas mahal ang hybrid seeds at hindi pwedeng i-save.\"\n\n";

            $instructions .= "MANDATORY IN YOUR RESPONSE:\n";
            $instructions .= "1. SPECIFIC NUMBERS for BOTH options being compared\n";
            $instructions .= "2. HONEST assessment - not just promotion of one option\n";
            $instructions .= "3. PROS and CONS of each option\n";
            $instructions .= "4. SOURCE/BASIS for the numbers (farmer example, study, official data)\n\n";
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
        // COST OPTIMIZATION: Always use Gemini for formatting (25x cheaper than GPT-4o)
        try {
            // Prefer Gemini for cost efficiency - it's excellent for formatting tasks
            $combined = $this->callGeminiAPIFormatOnly($formatterSetting, $combinePrompt);

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
     * @param int|null $currentMessageId The ID of the current message to exclude from chat history
     */
    public function checkBlockerAndGetThinkingReply(AiChatSession $session, string $userMessage, array $images = [], ?string $topicContext = null, ?int $currentMessageId = null): array
    {
        $this->session = $session;
        $this->userMessage = $userMessage;
        $this->images = $images;
        $this->currentMessageId = $currentMessageId;
        // Exclude current message from chat history to prevent extracting context from it
        $this->chatHistory = $session->getChatHistoryText(10, $currentMessageId);
        $this->topicContext = $topicContext;

        // Initialize flow log with user message
        $this->logUserMessage($userMessage);
        $this->logFlowStep('Received user message', substr($userMessage, 0, 100));

        // Get the user's reply flow
        $this->flow = AiReplyFlow::getOrCreate();

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

        // =================================================================
        // RULE A: AGRICULTURE-ONLY SCOPE CHECK (TOP OF WORKFLOW)
        // =================================================================
        // This is the FIRST check before any other processing.
        // Block obviously off-topic questions immediately.
        $scopeCheck = $this->strictScopeCheck($userMessage);

        Log::info('=== SCOPE CHECK RESULT ===', [
            'userMessage' => substr($userMessage, 0, 100),
            'blocked' => $scopeCheck['blocked'],
            'reason' => $scopeCheck['reason'],
        ]);

        if ($scopeCheck['blocked']) {
            Log::info('SCOPE CHECK: Blocking off-topic question', [
                'reason' => $scopeCheck['reason'],
            ]);
            return [
                'blocked' => true,
                'blockMessage' => $this->getDefaultBlockMessage(),
                'thinkingReply' => null,
                'specialState' => 'scope_blocked',
            ];
        }

        // =================================================================
        // RULE B: CONTINUITY - Extract context from chat history
        // =================================================================
        // Extract information already provided (location, crop, stage, etc.)
        // so we don't ask the user again
        $this->extractedContext = $this->extractContextFromChatHistory($this->chatHistory);

        if (!empty($this->extractedContext)) {
            Log::info('=== CONTINUITY: Context extracted from history ===', [
                'context' => $this->extractedContext,
            ]);
        }

        // =================================================================
        // RULE B.1: CLEAR CONTEXT FOR GENERAL EDUCATIONAL QUESTIONS
        // =================================================================
        // If user is asking a general/beginner question, don't apply previous context
        // e.g., "Ako ay bago pa lamang sa pagtatanim ng mais. Gusto ko malaman..."
        // should NOT assume DAP, stage, or variety from previous conversation
        if ($this->isGeneralEducationalQuestion($userMessage)) {
            Log::info('=== GENERAL EDUCATIONAL QUESTION: Clearing previous context ===', [
                'userMessage' => substr($userMessage, 0, 100),
                'clearedContext' => array_keys($this->extractedContext),
            ]);

            // Clear context that would wrongly assume specifics about their crop
            unset($this->extractedContext['dat']);
            unset($this->extractedContext['stage']);
            unset($this->extractedContext['problem']);
            unset($this->extractedContext['variety']);
            // Keep general info like crop type if mentioned in current message
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

        // For non-agricultural questions that passed scope check,
        // we still allow them but skip the AI blocker check
        // (the scope check already handled blocking)

        // Process thinking reply
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
     * @param string|null $precomputedImageAnalysis Pre-computed image analysis (from analyzeUploadedImages)
     */
    public function processMainFlow(AiChatSession $session, string $userMessage, array $images = [], ?string $topicContext = null, ?string $precomputedImageAnalysis = null, ?int $currentMessageId = null): array
    {
        // Store topic context for follow-up questions
        $this->topicContext = $topicContext;

        // Store precomputed image analysis if provided (from analyzeUploadedImages)
        // This prevents calling analyzeImagesSimple again which may return truncated results
        $this->precomputedImageAnalysis = $precomputedImageAnalysis;

        Log::info('processMainFlow: Precomputed image analysis set', [
            'hasPrecomputedAnalysis' => !empty($precomputedImageAnalysis),
            'precomputedAnalysisLength' => strlen($precomputedImageAnalysis ?? ''),
        ]);

        // Store current message ID to exclude from chat history
        $this->currentMessageId = $currentMessageId;

        // Normalize user message for common abbreviations/patterns
        $userMessage = $this->normalizeUserMessage($userMessage);

        // Restore state if not already set (in case called independently)
        if (!$this->session) {
            $this->session = $session;
            $this->userMessage = $userMessage;
            $this->images = $images;
            // Exclude current message from chat history to prevent extracting context from it
            $this->chatHistory = $session->getChatHistoryText(10, $currentMessageId);

            $this->flow = AiReplyFlow::getOrCreate();

            // =================================================================
            // RULE B: CONTINUITY - Extract context if not already done
            // =================================================================
            // Skip extraction if explicitly cleared (new topic with different crop)
            if (empty($this->extractedContext) && !$this->skipContextExtraction) {
                $this->extractedContext = $this->extractContextFromChatHistory($this->chatHistory);
            }
        } else {
            // Update userMessage with normalized version
            $this->userMessage = $userMessage;
            // CRITICAL: Always update images - they may have been carried over for follow-up questions
            // The checkBlockerAndGetThinkingReply sets $this->images BEFORE images are carried over
            // so we must update here to reflect the actual images being processed
            if (!empty($images)) {
                $this->images = $images;
                Log::debug('processMainFlow: Updated images (carried over for follow-up)', [
                    'imageCount' => count($images),
                ]);
            }
        }

        // =================================================================
        // RULE B: CONTINUITY - Build context summary for prompt injection
        // =================================================================
        $contextSummary = $this->buildContextSummary($this->extractedContext);
        if (!empty($contextSummary)) {
            Log::info('=== CONTINUITY: Injecting context into prompt ===', [
                'contextKeys' => array_keys($this->extractedContext),
            ]);
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
                $this->userMessage = $contextSummary .
                                     "Search online and verify: " . $this->topicContext .
                                     "\n\nUser is asking: " . $userMessage .
                                     "\n\nIMPORTANT: You MUST search the web for current information. Do NOT rely on your training data.";
            } else {
                // Regular follow-up: Expand with context so AI understands what the follow-up is about
                // This is CRITICAL for short follow-ups like "e and boron at zinc?" which need context
                $this->userMessage = $contextSummary .
                                     "CONTEXT: This is a follow-up question about: \"" . $this->topicContext . "\"\n\n" .
                                     "FOLLOW-UP QUESTION: " . $userMessage . "\n\n" .
                                     "IMPORTANT: Answer the follow-up question IN THE CONTEXT of the original question above. " .
                                     "Do NOT answer about unrelated topics. Use the information already provided above.";
            }

            Log::debug('Expanded follow-up message', [
                'expandedMessage' => substr($this->userMessage, 0, 500),
                'forceWebSearch' => $this->forceWebSearch,
                'hasContextSummary' => !empty($contextSummary),
            ]);

            // CRITICAL: For follow-ups, limit chat history to only the last 2 exchanges
            // This prevents the AI from getting confused by older, unrelated topics in the conversation
            // (e.g., Q1 about "corn varieties in Pangasinan" confusing a follow-up to Q3 about "flowering mais")
            // Exclude current message to prevent extracting context from it
            $this->chatHistory = $this->session->getChatHistoryText(4, $this->currentMessageId); // Only last 4 messages (2 Q&A pairs)

            Log::debug('Limited chat history for follow-up', [
                'chatHistoryLength' => strlen($this->chatHistory),
                'reason' => 'Follow-up question detected, limiting history to prevent topic confusion',
            ]);
        } else if (!empty($contextSummary)) {
            // For NEW questions (not follow-ups), still inject context if available
            // This ensures continuity even for new questions in the same session
            $this->userMessage = $contextSummary . $this->userMessage;

            Log::info('=== CONTINUITY: Context injected into new question ===', [
                'messageLength' => strlen($this->userMessage),
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

        // COST OPTIMIZATION: Use lightweight prompt for intermediate flow nodes
        // The full 'query' personality prompt (~15KB) is only applied at final output
        // This saves ~10,000+ tokens per intermediate call
        $systemPrompt = $this->buildSystemPrompt('intermediate');

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
        // OPTIMIZATION: If we already have a cached RAG result, return it
        if ($this->cachedRagResult !== null) {
            Log::info('RAG-First: Using CACHED result instead of new query', [
                'cachedResultLength' => strlen($this->cachedRagResult),
            ]);
            $this->ragCacheUsed = true;
            return $this->cachedRagResult;
        }

        // Get RAG settings
        $ragSettings = AiRagSetting::getOrCreate();

        if (!$ragSettings || empty($ragSettings->apiKey) || empty($ragSettings->indexName)) {
            Log::warning('RAG-First: RAG not configured for user', ['userId' => $this->userId]);
            return null;
        }

        // Extract the core question from the message (removes attached documents/schedules)
        // This prevents sending massive queries to RAG when user attaches fertilizer schedules
        $geminiSetting = AiApiSetting::active()->forProvider('gemini')->enabled()->first();
        $extractedMessage = $this->extractSearchQuery($userMessage, $geminiSetting);

        Log::info('RAG-First: Query extracted', [
            'originalLength' => strlen($userMessage),
            'extractedLength' => strlen($extractedMessage),
        ]);

        // Build a focused product search query using the extracted message
        $productQuery = $this->buildProductSearchQuery($extractedMessage);

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

            // CACHE the result for subsequent RAG calls
            $this->cachedRagResult = $content;
            $this->cachedRagQuery = $productQuery;

            Log::info('RAG-First query returned results (CACHED for reuse)', [
                'contentLength' => strlen($content),
                'hasProductImages' => strpos($content, 'PRODUCT IMAGE') !== false || strpos($content, 'storage/ai-products') !== false,
            ]);

            return $content;
        }

        // Cache empty result too to prevent retry
        $this->cachedRagResult = '';
        $this->cachedRagQuery = $productQuery;

        Log::debug('RAG-First query: No results found (cached empty result)');
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
     *
     * OPTIMIZATION: Uses cached RAG result if available to avoid multiple expensive Pinecone queries.
     */
    protected function processRagQueryNode(array $data): array
    {
        $queryText = $data['queryText'] ?? '';

        if (empty($queryText)) {
            return ['output' => $this->getLastOutput()];
        }

        // Log flow step
        $this->logFlowStep('RAG Knowledge Base', 'Querying unified knowledge base...');

        // OPTIMIZATION: Use cached RAG result if available
        if ($this->cachedRagResult !== null) {
            Log::info('RAG Node: Using CACHED result instead of new query', [
                'cachedQueryPreview' => substr($this->cachedRagQuery ?? '', 0, 100),
                'newQueryPreview' => substr($queryText, 0, 100),
                'cachedResultLength' => strlen($this->cachedRagResult),
            ]);
            $this->ragCacheUsed = true;
            $this->logFlowStep('RAG Cache Hit', 'Using cached result (' . strlen($this->cachedRagResult) . ' chars) - SAVED TOKENS!');

            if (empty($this->cachedRagResult)) {
                return ['output' => '[RAG: No relevant information found in knowledge base.]'];
            }
            return ['output' => trim($this->cachedRagResult)];
        }

        // Enforce rate limiting to prevent 429 errors from Pinecone
        $this->enforceRateLimit('pinecone');

        // Get RAG settings (Pinecone) - single source of truth
        $ragSettings = AiRagSetting::getOrCreate();

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

        $content = $result['content'] ?? '';

        // Cache the result for subsequent calls
        $this->cachedRagResult = $content;
        $this->cachedRagQuery = $query;

        // Check results
        if (empty($content)) {
            Log::debug('RAG: No results found (cached empty result)');
            $this->logFlowStep('RAG Result', 'No matching content found');
            return ['output' => '[RAG: No relevant information found in knowledge base.]'];
        }

        Log::debug('RAG result (cached for reuse)', [
            'contentLength' => strlen($content),
            'inputTokens' => $inputTokens,
            'outputTokens' => $outputTokens,
        ]);

        $this->logFlowStep('RAG Complete', strlen($content) . ' chars retrieved (cached for reuse)');

        return ['output' => trim($content)];
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
            'imageCount' => count($this->images ?? []),
            'hasPrecomputedAnalysis' => !empty($this->precomputedImageAnalysis),
            'precomputedAnalysisLength' => strlen($this->precomputedImageAnalysis ?? ''),
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
        $geminiSetting = AiApiSetting::active()->forProvider('gemini')->enabled()->first();
        $openaiSetting = AiApiSetting::active()->forProvider('openai')->enabled()->first();

        // ================================================================
        // OPTIMIZATION: PRE-FETCH RAG RESULT (ONE call for all RAG needs)
        // ================================================================
        // STEP 1: IMAGE ANALYSIS (if images uploaded) - DO THIS FIRST!
        // We need to know what the image shows BEFORE making RAG queries
        // ================================================================
        $imageAnalysis = '';
        $imageQuery = '';
        if (!empty($this->images)) {
            // Send progress: Analyzing images
            $this->sendProgress('Analyzing Images', 'Sinusuri ang mga larawan', 1);

            // Use precomputed image analysis if available (from controller's analyzeUploadedImages)
            // This avoids re-analyzing and getting truncated results
            if (!empty($this->precomputedImageAnalysis)) {
                $imageAnalysis = $this->precomputedImageAnalysis;
                $this->logFlowStep('Step 1: Image Analysis', 'Using precomputed deep analysis (' . strlen($imageAnalysis) . ' chars)', $imageAnalysis);
                Log::info('Step 1: Using precomputed image analysis', ['length' => strlen($imageAnalysis)]);
            } else {
                // Fallback: Call image analysis with proper comparison-aware prompt
                Log::warning('Step 1: Precomputed analysis not available, performing fresh analysis', [
                    'imageCount' => count($this->images),
                    'userMessage' => substr($this->userMessage, 0, 100),
                ]);

                // Use AI to classify the inquiry and extract details
                $inquiryDetails = $this->classifyInquiry($this->userMessage);
                $isComparisonScenario = $inquiryDetails['isComparison'] ?? false;

                Log::info('AI Inquiry Classification (fallback path)', [
                    'inquiryType' => $inquiryDetails['inquiryType'] ?? 'unknown',
                    'isComparison' => $isComparisonScenario,
                    'userCropVariety' => $inquiryDetails['userCropVariety'] ?? null,
                    'comparisonTarget' => $inquiryDetails['comparisonTarget'] ?? null,
                    'comparisonType' => $inquiryDetails['comparisonType'] ?? null,
                    'dat' => $inquiryDetails['dat'] ?? null,
                ]);

                if ($isComparisonScenario) {
                    // Use GPT-4 Vision for comparison scenarios with AI-extracted details
                    $result = $this->analyzeImagesWithGPT($this->images, $this->userMessage, $this->topicContext, $inquiryDetails);
                    $this->logFlowStep('Step 1: Image Analysis', 'GPT-4 Vision for comparison (' . strlen($result['analysis'] ?? '') . ' chars)', $result['analysis'] ?? '');
                } else {
                    // Use Gemini Vision for general analysis
                    $result = $this->analyzeUploadedImages($this->images, $this->userMessage, $this->topicContext);
                }

                if ($result['success'] && !empty($result['analysis'])) {
                    $imageAnalysis = $result['analysis'];
                    $this->logFlowStep('Step 1: Image Analysis', 'Fresh analysis (' . strlen($imageAnalysis) . ' chars)', $imageAnalysis);
                } else {
                    // Last resort: simple analysis
                    $imageAnalysis = $this->analyzeImagesSimple($apiSetting);
                    $this->logFlowStep('Step 1: Image Analysis', 'Fallback simple analysis (' . strlen($imageAnalysis) . ' chars)', $imageAnalysis);
                }
                Log::info('Step 1: Image analysis done', ['length' => strlen($imageAnalysis), 'usedFallback' => empty($result['success']), 'isComparison' => $isComparisonScenario]);
            }
        } else {
            $this->logFlowStep('Step 1: Image Analysis', 'No images uploaded - skipped');
            // Send progress: Starting (no images)
            $this->sendProgress('Processing Question', 'Sinusuri ang tanong', 1);

            // For text-only messages, check if user wants visual reference (what does X look like)
            // This triggers image search even without user uploading images
            $inquiryDetails = $this->classifyInquiry($this->userMessage);
            if (!empty($inquiryDetails['needsVisualReference'])) {
                $this->setNeedsVisualReference(true);
                Log::info('Visual reference detected in text-only message', [
                    'inquiryType' => $inquiryDetails['inquiryType'] ?? 'unknown',
                    'needsVisualReference' => true,
                ]);
                $this->logFlowStep('Visual Reference Detection', 'User wants to see what something looks like - will search for reference images');
            }
        }

        // ================================================================
        // CRITICAL: Clear stale problem context if new images show HEALTHY crops
        // This prevents armyworm/pest context from previous conversation polluting
        // the response when user uploads new images of healthy crops
        // ================================================================
        if (!empty($imageAnalysis) && !empty($this->extractedContext['problem'])) {
            // Check if image analysis indicates healthy/normal crops
            $healthyIndicators = [
                '/\b(healthy|malusog|maayos|maganda|normal|walang\s*problema|no\s*problem|good\s*condition)/i',
                '/\b(dark\s*green|healthy\s*green|maayos\s*na\s*kondisyon)/i',
                '/\b(hindi\s*nakita|not\s*detected|not\s*visible|walang\s*palatandaan)/i',
                '/\bVERDICT:\s*NORMAL/i',
                '/\b(no\s*(visible|obvious|apparent)\s*(problem|issue|pest|disease|infestation))/i',
            ];

            $showsHealthy = false;
            foreach ($healthyIndicators as $pattern) {
                if (preg_match($pattern, $imageAnalysis)) {
                    $showsHealthy = true;
                    break;
                }
            }

            // Also check if image does NOT show the previously detected problem
            $previousProblem = $this->extractedContext['problem'];
            $problemStillVisible = preg_match('/\b(' . preg_quote($previousProblem, '/') . '|nakita|detected|visible|present)\b/i', $imageAnalysis);

            if ($showsHealthy || !$problemStillVisible) {
                Log::info('Clearing stale problem context - new images show healthy crops', [
                    'previousProblem' => $previousProblem,
                    'showsHealthy' => $showsHealthy,
                    'problemStillVisible' => $problemStillVisible,
                ]);
                unset($this->extractedContext['problem']);
                $this->logFlowStep('Context Cleanup', 'Cleared stale problem context - new images show healthy crops');
            }
        }

        // ================================================================
        // RAG PRE-FETCH (NOW with image analysis context if available)
        // This makes a SINGLE comprehensive RAG query and caches the result
        // All subsequent RAG calls will use this cached result
        // ================================================================
        $imageFindings = '';
        if (!empty($imageAnalysis)) {
            // Extract key findings from image analysis for RAG search
            $imageFindings = $this->extractKeyFindingsFromImageAnalysis($imageAnalysis);
        }

        // CRITICAL: If we have IMAGE FINDINGS and RAG cache was populated earlier (RAG-First),
        // we need to INVALIDATE the cache and do a NEW search with image context!
        // The RAG-First cache was created without knowing what the image shows.
        $needsImageAwareRagSearch = !empty($imageFindings) && $this->cachedRagResult !== null;

        if ($needsImageAwareRagSearch) {
            $this->logFlowStep('RAG Cache Invalidation',
                'Invalidating RAG-First cache - need to search with image findings: ' . $imageFindings);
            Log::info('Invalidating RAG cache for image-aware search', [
                'oldCacheLength' => strlen($this->cachedRagResult ?? ''),
                'imageFindings' => $imageFindings,
            ]);
            // Invalidate the cache
            $this->cachedRagResult = null;
            $this->cachedRagQuery = null;
            $this->ragCacheUsed = false;
        }

        if ($this->cachedRagResult === null) {
            // Build RAG query with image findings if available
            $ragQueryContext = $this->userMessage;
            if (!empty($imageFindings)) {
                $ragQueryContext = $this->userMessage . "\n\nIMAGE ANALYSIS FINDINGS: " . $imageFindings;
                $this->logFlowStep('RAG Pre-fetch Context', 'Including image findings: ' . $imageFindings);
            }
            $this->preFetchRagResult($ragQueryContext, $geminiSetting);
        } else {
            $this->logFlowStep('RAG Pre-fetch', 'Cache already populated (from RAG-First) - skipping pre-fetch');
        }

        // ================================================================
        // STEP 2: COMBINE IMAGE ANALYSIS + MESSAGE
        // ================================================================
        $fullRequest = $this->userMessage;
        if (!empty($imageAnalysis)) {
            $fullRequest = "USER MESSAGE: " . $this->userMessage . "\n\nIMAGE ANALYSIS:\n" . $imageAnalysis;
            $this->logFlowStep('Step 2: Combined Request', "Message + Image Analysis combined (" . strlen($fullRequest) . " chars)");
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

        // PREPROCESSING: Extract the actual search query from user message
        // This removes user-provided documents/schedules and extracts only the question
        $extractedQuery = $this->extractSearchQuery($this->userMessage, $geminiSetting);
        $this->logFlowStep('Step 2b: Query Extraction',
            'Original: ' . strlen($this->userMessage) . ' chars → Extracted: ' . strlen($extractedQuery) . ' chars',
            "ORIGINAL MESSAGE (first 500 chars):\n" . substr($this->userMessage, 0, 500) . "\n\n---\n\nEXTRACTED QUERY:\n" . $extractedQuery
        );

        // ================================================================
        // STEP 2c: EARLY SCHEDULE ANALYSIS (if user provided schedule)
        // This MUST happen BEFORE AI Knowledge so AI knows what's already done
        // ================================================================
        $hasUserDocument = $this->detectUserProvidedDocument($this->userMessage);
        $earlyScheduleAnalysis = '';
        if ($hasUserDocument) {
            $this->logFlowStep('Step 2c: User Document Detected', 'User provided schedule - analyzing BEFORE AI calls...');
            Log::info('=== EARLY SCHEDULE ANALYSIS - Before AI Knowledge ===');

            $earlyScheduleAnalysis = $this->analyzeUserScheduleContext($this->userMessage, $geminiSetting, $openaiSetting);
            if (!empty($earlyScheduleAnalysis)) {
                $this->logFlowStep('Step 2c: Schedule Analysis Complete', 'AI analyzed user schedule context', $earlyScheduleAnalysis);
            }
        }

        // 3a. RAG Assistant - Will use CACHED result from pre-fetch (no new Pinecone call)
        // Send progress: Searching knowledge base
        $this->sendProgress('Searching Knowledge Base', 'Hinahanap sa knowledge base', 2);

        $ragQuery = $extractedQuery;
        $cacheStatusBefore = $this->ragCacheUsed;
        $ragResult = $this->getSimpleRagResult($ragQuery);
        $usedCache = $this->ragCacheUsed && !$cacheStatusBefore;
        // Log with AI response - indicate if cache was used
        $cacheNote = $usedCache ? ' [CACHE HIT - No new Pinecone call!]' : '';
        $this->logFlowStep('Step 3a: RAG Query', 'Query: ' . $ragQuery . ' (' . strlen($ragResult) . ' chars)' . $cacheNote, $ragResult ?: '[No RAG results found]');
        Log::info('Step 3a: RAG result', ['hasContent' => !empty($ragResult), 'queryLength' => strlen($ragQuery), 'usedCache' => $usedCache]);

        // 3b. Web Search via Gemini - Use EXTRACTED query (Philippines context)
        // Send progress: Searching the web
        $this->sendProgress('Searching Online', 'Naghahanap sa internet', 3);

        $webQuery = "Philippines agriculture: " . $extractedQuery . " - products available in Philippines, dosage, timing";
        $webResult = '';
        if ($geminiSetting && !empty($geminiSetting->apiKey)) {
            $webResult = $this->getSimpleWebSearch($geminiSetting->apiKey, $webQuery);
            // Log with AI response
            $this->logFlowStep('Step 3b: Web Search (Gemini)', 'Query: ' . substr($webQuery, 0, 150) . '... (' . strlen($webResult) . ' chars)', $webResult ?: '[No web results]');
        } else {
            $this->logFlowStep('Step 3b: Web Search', 'No Gemini API key - skipped');
        }
        Log::info('Step 3b: Web search result', ['hasContent' => !empty($webResult)]);

        // 3c. AI Knowledge (try GPT first, then Gemini) - Philippines context
        // IMPORTANT: Include image analysis and schedule context so AI knows what to recommend
        $aiQuery = "[Philippines context] " . $extractedQuery;

        // If we have IMAGE ANALYSIS, include the FULL analysis with VERDICT
        if (!empty($imageAnalysis)) {
            // First, include the FULL image analysis with verdict
            $aiQuery .= "\n\n═══════════════════════════════════════════════════════════════\n";
            $aiQuery .= "🖼️ BUONG IMAGE ANALYSIS RESULT (MAY VERDICT):\n";
            $aiQuery .= "═══════════════════════════════════════════════════════════════\n";
            $aiQuery .= $imageAnalysis . "\n\n";

            // Extract the verdict if present
            $verdictMatch = [];
            if (preg_match('/📌\s*VERDICT:\s*(NORMAL|MAY PROBLEMA|MALUSOG|NAHIHIRAPAN|HINDI MALINAW)/i', $imageAnalysis, $verdictMatch)) {
                $aiQuery .= "⚠️ IMAGE ANALYSIS VERDICT: " . strtoupper($verdictMatch[1]) . "\n";
                $aiQuery .= "→ USE THIS VERDICT in your response! The image analysis already determined if it's NORMAL or a PROBLEM.\n\n";
            }

            // Also extract key findings for product recommendations (if problem detected)
            $imageFindings = $this->extractKeyFindingsFromImageAnalysis($imageAnalysis);
            if (!empty($imageFindings)) {
                $aiQuery .= "=== DETECTED ISSUES (FOR PRODUCT RECOMMENDATIONS) ===\n";
                $aiQuery .= "Detected: " . $imageFindings . "\n";
                $aiQuery .= "- If zinc deficiency detected → recommend Zinc Sulfate or Zintrac\n";
                $aiQuery .= "- If iron deficiency detected → recommend Iron Chelate or Ferrous Sulfate\n";
                $aiQuery .= "- If nitrogen deficiency detected → recommend Urea (46-0-0)\n";
                $aiQuery .= "- Include dosage per hectare or per liter of water for foliar spray\n\n";
            }

            $aiQuery .= "=== CRITICAL INSTRUCTION ===\n";
            $aiQuery .= "- If IMAGE VERDICT says NORMAL → your answer should also say NORMAL!\n";
            $aiQuery .= "- HUWAG mag-contradict sa image analysis verdict.\n";
            $aiQuery .= "- If NORMAL, focus on reassurance, not diagnosing problems.\n";
        }

        // If we have schedule analysis, ADD IT to the AI Knowledge query
        // This ensures AI knows what's already been applied and won't recommend it again
        if (!empty($earlyScheduleAnalysis)) {
            $aiQuery .= "\n\n=== CRITICAL SCHEDULE CONTEXT (DO NOT RECOMMEND ITEMS ALREADY DONE) ===\n";
            $aiQuery .= $earlyScheduleAnalysis;
            $aiQuery .= "\n\n=== INSTRUCTIONS ===\n";
            $aiQuery .= "- Items in DONE_APPLICATIONS have ALREADY been applied. DO NOT recommend them again.\n";
            $aiQuery .= "- If SCHEDULE_STATUS = COMPLETE, all planned applications are done.\n";
            $aiQuery .= "- Focus on answering if the situation is NORMAL or a PROBLEM at their current stage.\n";
            $aiQuery .= "- If something is already done (like MOP at 33 DAT), DO NOT say 'apply MOP'.\n";
        }

        // ================================================================
        // 3c. AI KNOWLEDGE - Smart routing for cost optimization
        // Uses dual-AI only for complex queries, single-AI for simple ones
        // Cost savings: ~70% less for simple queries using Gemini-only
        // ================================================================
        // Send progress: Consulting AI
        $this->sendProgress('Consulting AI', 'Kumukonsulta sa AI', 4);

        $aiKnowledge = '';
        $aiProvider = 'Dual AI (OpenAI + Gemini)';

        // Check if we have both APIs available for dual comparison
        $hasOpenAI = $openaiSetting && !empty($openaiSetting->apiKey);
        $hasGemini = $geminiSetting && !empty($geminiSetting->apiKey);

        // Smart routing: Determine if this query needs dual-AI or can use cheaper single-AI
        $routingDecision = $this->shouldUseDualAI($aiQuery, $ragResult ?? null, $imageAnalysis ?? null);
        $useDualAI = $routingDecision['useDualAI'];
        $routingReason = $routingDecision['reason'];

        $this->logFlowStep('Step 3c: AI Routing', "Route decision: " . ($useDualAI ? 'DUAL-AI' : 'SINGLE-AI (Gemini)') . " | Reason: {$routingReason}");

        if ($hasOpenAI && $hasGemini && $useDualAI) {
            // Use DUAL-AI approach: Both AIs answer, then Gemini combines
            $this->logFlowStep('Step 3c: Dual AI Knowledge', 'Using DUAL-AI comparison (OpenAI GPT-4o-mini + Gemini → Combined) [Cost: ~$0.35+$1.40/1M tokens]');
            $aiKnowledge = $this->getDualAIKnowledge($openaiSetting, $geminiSetting, $aiQuery);
        } elseif ($hasGemini && !$useDualAI) {
            // OPTIMIZED: Use single Gemini for simpler queries (70% cost savings)
            $aiProvider = 'Gemini 2.0 Flash (optimized)';
            $this->logFlowStep('Step 3c: AI Knowledge (' . $aiProvider . ')', 'Using single Gemini for cost efficiency [Cost: ~$0.10+$0.40/1M tokens] | Query: ' . substr($aiQuery, 0, 80) . '...');
            $aiKnowledge = $this->getSimpleAiKnowledge('gemini', $geminiSetting->apiKey, $aiQuery);
        } elseif ($hasOpenAI) {
            // Fallback: OpenAI only
            $aiProvider = 'GPT-4o-mini (single)';
            $aiKnowledge = $this->getSimpleAiKnowledge('openai', $openaiSetting->apiKey, $aiQuery);
            $this->logFlowStep('Step 3c: AI Knowledge (' . $aiProvider . ')', 'Query: ' . substr($aiQuery, 0, 100) . '... (' . strlen($aiKnowledge) . ' chars)', $aiKnowledge ?: '[No AI knowledge response]');
        } elseif ($hasGemini) {
            // Fallback: Gemini only
            $aiProvider = 'Gemini (single)';
            $aiKnowledge = $this->getSimpleAiKnowledge('gemini', $geminiSetting->apiKey, $aiQuery);
            $this->logFlowStep('Step 3c: AI Knowledge (' . $aiProvider . ')', 'Query: ' . substr($aiQuery, 0, 100) . '... (' . strlen($aiKnowledge) . ' chars)', $aiKnowledge ?: '[No AI knowledge response]');
        } else {
            $this->logFlowStep('Step 3c: AI Knowledge', 'No API key available - skipped');
        }

        Log::info('Step 3c: AI knowledge result', [
            'hasContent' => !empty($aiKnowledge),
            'provider' => $aiProvider,
            'routingDecision' => $useDualAI ? 'dual' : 'single',
            'routingReason' => $routingReason,
            'length' => strlen($aiKnowledge),
        ]);

        // Set the AI provider in flow log for display
        $this->logAiProvider($aiProvider);

        // ================================================================
        // STEP 4: COMBINE ALL SOURCES WITH AI
        // ================================================================
        // Send progress: Combining information
        $this->sendProgress('Combining Information', 'Pinagsasama ang mga impormasyon', 5);

        $combineInfo = "Combining: " .
            (!empty($ragResult) ? "RAG ✓ " : "RAG ✗ ") .
            (!empty($webResult) ? "Web ✓ " : "Web ✗ ") .
            (!empty($aiKnowledge) ? "AI ✓ " : "AI ✗ ") .
            (!empty($imageAnalysis) ? "Image ✓" : "");

        // Reset extracted images before combining
        $this->extractedImages = [];

        $combinedResponse = $this->combineSourcesSimple(
            $apiSetting,
            $openaiSetting,
            $geminiSetting,
            $ragResult,
            $webResult,
            $aiKnowledge,
            $imageAnalysis,
            $earlyScheduleAnalysis // Pass the schedule analysis we already computed
        );

        // Log with AI response
        $this->logFlowStep('Step 4: Combine Sources', $combineInfo . ' (' . strlen($combinedResponse) . ' chars)', $combinedResponse);

        Log::info('Step 4: Combined response ready', [
            'length' => strlen($combinedResponse),
        ]);

        // ================================================================
        // STEP 5: FINAL THINKING - OpenAI refines the answer
        // Goal: Direct, concise, practical - no nonsense
        // ================================================================
        // Send progress: Refining answer
        $this->sendProgress('Refining Answer', 'Pinipino ang sagot', 6);

        $finalResponse = $combinedResponse; // Default to combined response

        if ($openaiSetting && !empty($openaiSetting->apiKey) && !empty($combinedResponse)) {
            $finalResponse = $this->refineAnswerWithOpenAI(
                $openaiSetting,
                $this->userMessage,
                $combinedResponse
            );

            // Log with AI response - show what changed
            $refinementNote = $finalResponse !== $combinedResponse
                ? 'REFINED: ' . strlen($combinedResponse) . ' → ' . strlen($finalResponse) . ' chars'
                : 'UNCHANGED (refinement failed or returned same content)';
            $this->logFlowStep('Step 5: Final Thinking (OpenAI)', $refinementNote, $finalResponse);

            Log::info('Step 5: Final response refined', [
                'originalLength' => strlen($combinedResponse),
                'refinedLength' => strlen($finalResponse),
            ]);
        } else {
            $this->logFlowStep('Step 5: Final Thinking', 'Skipped - using combined response', $combinedResponse);
        }

        // ================================================================
        // STEP 6: PRODUCT ALTERNATIVES (Optional Enhancement)
        // If the response has product recommendations, find alternatives in RAG
        // ================================================================
        $responseBeforeAlternatives = $finalResponse;
        $detectedProducts = $this->detectProductRecommendations($finalResponse);

        if (!empty($detectedProducts) && count($detectedProducts) > 0) {
            $this->logFlowStep('Step 6: Product Detection', 'Found ' . count($detectedProducts) . ' products: ' . implode(', ', $detectedProducts));

            // Try to find alternatives in RAG
            $finalResponse = $this->postProcessWithAlternatives($finalResponse, $openaiSetting);

            if ($finalResponse !== $responseBeforeAlternatives) {
                $this->logFlowStep('Step 6: Alternatives Added',
                    'Enhanced response with product alternatives (' . strlen($responseBeforeAlternatives) . ' → ' . strlen($finalResponse) . ' chars)',
                    $finalResponse
                );
            } else {
                $this->logFlowStep('Step 6: Alternatives', 'No alternatives found or integration skipped');
            }
        } else {
            $this->logFlowStep('Step 6: Product Alternatives', 'Skipped - no products detected in response');
        }

        Log::info('Step 6: Final response with alternatives ready', [
            'length' => strlen($finalResponse),
            'productsDetected' => count($detectedProducts),
            'alternativesAdded' => $finalResponse !== $responseBeforeAlternatives,
        ]);

        // Get extracted images for lightbox display
        $extractedImages = $this->getExtractedImages();
        Log::info('Final response ready', [
            'length' => strlen($finalResponse),
            'extractedImages' => count($extractedImages),
        ]);

        // Note: Don't send "Done" progress here - let frontend show 100% when response is received

        // Record the final AI response in flow log summary
        $this->logAiResponse($finalResponse);

        // ================================================================
        // COST SUMMARY - Log token usage and estimated costs
        // ================================================================
        $totalCost = $this->tokenUsage['total']['estimatedCost'];
        $totalTokens = $this->tokenUsage['total']['totalTokens'];
        $inputTokens = $this->tokenUsage['total']['inputTokens'];
        $outputTokens = $this->tokenUsage['total']['outputTokens'];

        $costSummary = sprintf(
            'Total: %s tokens (in: %s, out: %s) | Est. Cost: $%s',
            number_format($totalTokens),
            number_format($inputTokens),
            number_format($outputTokens),
            number_format($totalCost, 6)
        );

        // Build provider breakdown for detailed cost visibility
        $providerBreakdown = [];
        foreach ($this->tokenUsage['byProvider'] as $provider => $data) {
            $providerBreakdown[] = sprintf(
                '%s: %s tokens ($%s)',
                $data['name'] ?? $provider,
                number_format($data['totalTokens']),
                number_format($data['estimatedCost'], 6)
            );
        }

        $this->logFlowStep('Cost Summary', $costSummary, implode("\n", $providerBreakdown));

        Log::info('Request cost summary', [
            'totalTokens' => $totalTokens,
            'inputTokens' => $inputTokens,
            'outputTokens' => $outputTokens,
            'estimatedCostUSD' => $totalCost,
            'byProvider' => $this->tokenUsage['byProvider'],
        ]);

        return [
            'output' => $finalResponse,
            'images' => $extractedImages,
        ];
    }

    /**
     * STEP 5: Final Thinking with OpenAI
     * Refines the combined response to be direct, concise, and practical.
     * No nonsense - just answer the question with actionable recommendations.
     *
     * For comprehensive structured responses: Convert to natural conversation
     * while preserving ALL important content (don't lose the good info).
     */
    protected function refineAnswerWithOpenAI(AiApiSetting $openaiSetting, string $userMessage, string $combinedResponse): string
    {
        $this->enforceRateLimit('openai-refine');

        // Check if combined response is comprehensive and well-structured
        $isStructured = $this->isComprehensiveStructuredResponse($combinedResponse);

        // Check if this is a comparison request and/or response contains comparison table
        $isComparisonRequest = $this->detectComparisonRequest($userMessage);
        $hasComparisonTable = stripos($combinedResponse, '| Characteristic') !== false ||
                              stripos($combinedResponse, '| Aspect') !== false ||
                              stripos($combinedResponse, '| Status |') !== false ||
                              stripos($combinedResponse, 'PAGHAHAMBING') !== false ||
                              stripos($combinedResponse, '|-----') !== false;

        // Log comparison detection for debugging
        if ($isComparisonRequest || $hasComparisonTable) {
            Log::info('Comparison table preservation activated in refineAnswerWithOpenAI', [
                'isComparisonRequest' => $isComparisonRequest,
                'hasComparisonTable' => $hasComparisonTable,
                'combinedResponseLength' => strlen($combinedResponse),
            ]);
        }

        try {
            // Use different prompts based on whether response is structured
            if ($isStructured) {
                // STRUCTURED RESPONSE: Convert to natural conversation while keeping ALL content
                Log::info('Converting structured response to natural conversation', [
                    'responseLength' => strlen($combinedResponse),
                ]);

                $systemPrompt = <<<PROMPT
Ikaw ay FINAL EDITOR para sa isang AI agricultural expert chatbot.

ANG TRABAHO MO:
I-convert ang sagot sa NATURAL CONVERSATIONAL STYLE na may bold highlights.
HUWAG gumamit ng (1), (2), (3) o PART 1/2/3 numbering!
MAHALAGA: PANATILIHIN ANG LAHAT NG IMPORTANTENG CONTENT - huwag i-shorten!

═══════════════════════════════════════════════════════════════
OUTPUT FORMAT - NATURAL FLOW (Context-aware opening!):
═══════════════════════════════════════════════════════════════

⚠️ MAHALAGA: KUNG WALANG LARAWAN, HUWAG SABIHIN "NAKIKITA KO"!

**UNANG SECTION** - Context-aware opening:
KUNG MAY LARAWAN: "Nakikita ko po sa larawan ang inyong palay..."
KUNG WALANG LARAWAN PERO may sinabi ang user (variety/DAP): "Base sa sinabi ninyo, ang inyong [crop] sa [DAP] ay..."
KUNG WALANG LARAWAN AT WALANG DETALYE: MAGTANONG MUNA - "Para matulungan ko kayo, anong tanim at ilang DAP na po?"
❌ BAWAL: "Nakikita ko po" kung WALANG larawan!
❌ BAWAL: Mag-assume ng variety o DAP kung HINDI sinabi ng user!
❌ HUWAG: "NORMAL po ito!" (robotic)

**PANGALAWANG SECTION** - Natural assessment:
✅ "Sa yugtong ito, hindi na po kailangan ng..."
✅ "Nasa [X stage] na po ang pananim ninyo..."
Gamitin ang emoji 🌾 🌽 para sa positive context.

**PANGATLONG SECTION** - Explanation at context:
- Anong stage sila ngayon at ano ang expected
- Paliwanag ang science ng nangyayari

**PANG-APAT NA SECTION** - Ano ang GAWIN ngayon:
"Ang maipapayo ko po:"
• **Specific action** - explanation

**PANGLIMANG SECTION** - Bantayan / Follow-up:
"Kung may tanong pa kayo, mag-send ng litrato para ma-check ko."

═══════════════════════════════════════════════════════════════
HALIMBAWA - KUNG WALANG LARAWAN AT WALANG DETALYE (VAGUE):
═══════════════════════════════════════════════════════════════
User: "pwede mo ba tignan ito kung ayos?"

Kumusta po! 😊 Para matulungan ko kayo, kailangan ko po ng kaunting detalye:

• **Anong tanim po ang gusto ninyong i-check?** (mais, palay, etc.)
• **Pwede po bang mag-send ng larawan?** Para makita ko ang kondisyon
• **Ilang araw na po mula nang itanim?** (DAP)

Mag-send lang po kayo ng larawan o detalye at sasagutin ko kaagad! 📷

═══════════════════════════════════════════════════════════════
HALIMBAWA - KUNG WALANG LARAWAN PERO sinabi ng user ang variety AT DAP:
═══════════════════════════════════════════════════════════════
User: "kumusta ang NK6414 ko sa 100 DAP?"

Ang inyong NK6414 na mais sa 100 DAP ay malapit na po sa physiological maturity. 🌽

Sa stage na ito, hindi na po mainam ang karagdagang patubig. Ang butil ay fully developed na at nagsisimula nang matuyo - nasa R5/Dent stage na po ito.

Ang maipapayo ko po:
• **Ihinto na ang pagpapatubig** - ang mais ay maturity stage na
• **Maghanda na para sa pag-aani** - target moisture: 18-20%

Kung gusto ninyong i-send ang litrato, mas accurate pa ang ma-assess ko! 📷

═══════════════════════════════════════════════════════════════
HALIMBAWA - KUNG MAY LARAWAN:
═══════════════════════════════════════════════════════════════

Nakikita ko po sa larawan ang inyong palay na Jackpot 102 na nasa reproductive stage na - may mga uhay na rin lumalabas. 🌾

Batay sa nakikita ko, mukhang malusog po ang inyong pananim. Ang mga dahon ay may magandang berdeng kulay at walang sintomas ng sakit.

Ang maipapayo ko po:
• **Patuloy na mag-monitor ng tubig** - lalo na sa flowering stage
• **Bantayan ang peste** - brown planthopper at stem borer

Kung makakita ka ng pagdilaw ng dahon, mag-send ka ng litrato para ma-check ko.

═══════════════════════════════════════════════════════════════
CRITICAL RULES:
═══════════════════════════════════════════════════════════════
- HUWAG gumamit ng (1), (2), (3) o PART 1/2/3 numbering!
- HUWAG i-shorten ang content - KEEP ALL important details!
- NATURAL FLOW - Observe muna, tapos assessment, tapos advice
- ❌ HUWAG simulan sa "NORMAL po ito!" o "PROBLEMA po ito!"
- Gamitin ang **bold** para sa key points
- Gamitin ang bullet points (•) para sa actionable items
- TAGALOG ang pangunahin, English para sa technical terms lang
- Use "po" for politeness

⚠️ PRODUCT RECOMMENDATION FILTER:
- ❌ ALISIN: Osmocote (para sa ornamental plants)
- ❌ ALISIN: Any slow-release fertilizer for ornamental/potted plants
- ✅ PWEDE: Urea, Complete 14-14-14, Ammosul, MOP, DAP (crop fertilizers)
- ✅ PWEDE: Products mula sa Knowledge Base (Innosolve 40-5, etc.)
- KUNG may Osmocote sa original, PALITAN ng appropriate crop fertilizer!

⚠️ CRITICAL: HUWAG FABRICATE PLANT HEALTH STATUS!
- KUNG WALANG LARAWAN at TANONG LANG tungkol sa product/timing:
  ❌ BAWAL: "Mukhang malusog po ang inyong pananim" (HINDI MO NAKITA!)
  ✅ TAMANG GAWIN: Sagutin DIREKTA ang tanong tungkol sa product/timing
- KUNG may "Mukhang malusog" sa ORIGINAL pero WALANG EBIDENSYA, ALISIN ITO!
PROMPT;

                // CRITICAL: Add comparison table preservation if detected
                if ($isComparisonRequest || $hasComparisonTable) {
                    $comparisonRules = <<<COMPARISON

═══════════════════════════════════════════════════════════════
🔴 CRITICAL: COMPARISON TABLE PRESERVATION! 🔴
═══════════════════════════════════════════════════════════════

ANG ORIGINAL NA SAGOT AY MAY COMPARISON TABLE - DAPAT I-PRESERVE ITO!

MANDATORY RULES:
1. ❌ HUWAG i-convert ang COMPARISON TABLE sa conversational style!
2. ✅ I-PRESERVE ang TABLE FORMAT exactly as shown (| Column | Column |)
3. ✅ I-PRESERVE ang specific metrics at numbers
4. ✅ I-PRESERVE ang table columns - whatever format was used
5. Pwede mo lang i-polish ang text AROUND the table, pero HINDI ang table mismo

BAWAL GAWIN:
❌ "Katumbas po ng standard ang inyong tanim" (generic statement that loses data)
❌ I-simplify ang table to bullet points
❌ I-remove ang specific numbers at metrics
❌ Magdagdag ng 'STATUS: Below' column kung wala sa original

DAPAT GAWIN:
✅ KEEP the full comparison table AS-IS from the original
✅ Add natural introductory text BEFORE the table
✅ Add natural conclusion AFTER the table
✅ The TABLE ITSELF stays untouched
COMPARISON;
                    $systemPrompt .= $comparisonRules;
                }

                $userPrompt = "TANONG NG USER:\n{$userMessage}\n\n---\n\nORIGINAL NA SAGOT (PANATILIHIN ANG LAHAT NG CONTENT!):\n{$combinedResponse}\n\n---\n\nI-CONVERT sa CONVERSATIONAL format na may **bold** highlights at bullet points. HUWAG gumamit ng (1), (2), (3) o PART numbering. HUWAG i-shorten - keep ALL important content! SAGUTIN LAHAT ng tanong ng user. KUNG WALANG LARAWAN at tanong lang tungkol sa product/timing, HUWAG mag-assume na malusog ang pananim!";

                // Add comparison table reminder to user prompt if detected
                if ($isComparisonRequest || $hasComparisonTable) {
                    $userPrompt .= "\n\n⚠️ CRITICAL: ANG ORIGINAL NA SAGOT AY MAY COMPARISON TABLE - I-PRESERVE ITO! HUWAG I-SIMPLIFY SA GENERIC STATEMENTS!";
                }

            } else {
                // REGULAR RESPONSE: Normal refinement
                $systemPrompt = <<<PROMPT
Ikaw ay FINAL EDITOR para sa isang AI agricultural expert chatbot.

ANG TRABAHO MO:
I-convert ang sagot sa NATURAL CONVERSATIONAL STYLE na may bold highlights.
PANATILIHIN ANG LAHAT NG IMPORTANTENG CONTENT!

MGA RULES:
- HUWAG magdagdag ng bagong information na wala sa original
- HUWAG alisin ang IMPORTANT facts tulad ng dosage, timing, product names
- HUWAG gumawa ng bagong recommendations na hindi nasa original
- PANATILIHIN ang language ng original (Tagalog/English mix)
- Kung may multiple questions ang user, SAGUTIN LAHAT
- Kung may product recommendations, keep the product names at dosages
- Gamitin ang **bold** para sa key points
- Gamitin ang bullet points (•) para sa actionable items
- Use "po" for politeness

FORMAT (Natural Flow - Hindi verdict muna!):
❌ HUWAG simulan sa "NORMAL po ito!" - robotic yan!
✅ Simulan sa observation o description muna
- Then natural assessment
- Then actionable steps (bullets if needed)
- End with what to watch for / follow-up

HALIMBAWA NG NATURAL NA OUTPUT:

**KUNG MAY LARAWAN:**
"Nakikita ko po sa larawan ang inyong palay na nasa flowering stage na - may mga uhay na rin lumalabas. 🌾
Batay sa nakikita ko, mukhang malusog po ang inyong pananim..."

**KUNG WALANG LARAWAN (tanong lang):**
"Ang inyong palay na nasa 65 DAP ay nasa flowering stage na - sa ganitong edad, normal na ang paglabas ng uhay. 🌾
Base sa sinabi ninyo, mukhang on-track naman ang inyong pananim..."

⚠️ BAWAL: "Nakikita ko po" kung WALANG larawan!

CONTINUATION (pareho for both):
"Ang maipapayo ko po sa inyo ngayon:
• **Patuloy na mag-monitor ng tubig** - lalo na sa flowering stage
• **Bantayan ang peste** - brown planthopper at stem borer

Kung makakita ka ng pagdilaw o brown spots, mag-send ka ng litrato para ma-check ko."

⚠️ BAWAL: Sabihing 'kumpleto na ang schedule' MALIBAN kung USER mismo ang nagsabi ng schedule!

⚠️ PRODUCT RECOMMENDATION FILTER:
- ❌ ALISIN: Osmocote (para sa ornamental plants)
- ❌ ALISIN: Any slow-release fertilizer for ornamental/potted plants
- ✅ PWEDE: Urea, Complete 14-14-14, Ammosul, MOP, DAP (crop fertilizers)
- ✅ PWEDE: Products mula sa Knowledge Base (Innosolve 40-5, etc.)
- KUNG may Osmocote sa original, PALITAN ng appropriate crop fertilizer!

⚠️ CRITICAL: HUWAG FABRICATE PLANT HEALTH STATUS!
KUNG WALANG LARAWAN at TANONG LANG tungkol sa product/timing:
❌ BAWAL: "Mukhang malusog po ang inyong pananim" (HINDI MO NAKITA!)
✅ Sagutin DIREKTA: "Oo po, pwede mag-spray ng [product] sa [DAT]..."
PROMPT;

                // CRITICAL: Add comparison table preservation if detected
                if ($isComparisonRequest || $hasComparisonTable) {
                    $comparisonRules = <<<COMPARISON

═══════════════════════════════════════════════════════════════
🔴 CRITICAL: COMPARISON TABLE PRESERVATION! 🔴
═══════════════════════════════════════════════════════════════

ANG ORIGINAL NA SAGOT AY MAY COMPARISON TABLE - DAPAT I-PRESERVE ITO!

MANDATORY RULES:
1. ❌ HUWAG i-convert ang COMPARISON TABLE sa conversational style!
2. ✅ I-PRESERVE ang TABLE FORMAT exactly as shown (| Column | Column |)
3. ✅ I-PRESERVE ang specific metrics at numbers
4. ✅ I-PRESERVE ang table columns - whatever format was used
5. Pwede mo lang i-polish ang text AROUND the table, pero HINDI ang table mismo

BAWAL GAWIN:
❌ "Katumbas po ng standard ang inyong tanim" (generic statement that loses data)
❌ I-simplify ang table to bullet points
❌ I-remove ang specific numbers at metrics
❌ Magdagdag ng 'STATUS: Below' column kung wala sa original

DAPAT GAWIN:
✅ KEEP the full comparison table AS-IS from the original
✅ Add natural introductory text BEFORE the table
✅ Add natural conclusion AFTER the table
✅ The TABLE ITSELF stays untouched
COMPARISON;
                    $systemPrompt .= $comparisonRules;
                }

                $userPrompt = "TANONG NG USER:\n{$userMessage}\n\n---\n\nORIGINAL NA SAGOT:\n{$combinedResponse}\n\n---\n\nI-CONVERT sa CONVERSATIONAL format na may **bold** highlights. PANATILIHIN ang lahat ng important content! KUNG WALANG LARAWAN at tanong lang tungkol sa product/timing, HUWAG mag-assume na malusog ang pananim!";

                // Add comparison table reminder to user prompt if detected
                if ($isComparisonRequest || $hasComparisonTable) {
                    $userPrompt .= "\n\n⚠️ CRITICAL: ANG ORIGINAL NA SAGOT AY MAY COMPARISON TABLE - I-PRESERVE ITO! HUWAG I-SIMPLIFY SA GENERIC STATEMENTS!";
                }
            }

            // Use more tokens to preserve content
            // Increase tokens for comparison tables to ensure full preservation
            if ($hasComparisonTable || $isComparisonRequest) {
                $maxTokens = 4000; // More tokens for comparison tables
            } else {
                $maxTokens = $isStructured ? 3000 : 2000;
            }

            // Dynamic model selection based on input size
            // For structured responses, we might need full model; for simple refinement, mini is fine
            $modelSelection = $this->selectOpenAIModel($userPrompt, $systemPrompt, $isStructured);
            $selectedModel = $modelSelection['model'];

            Log::info('Refine model selection', [
                'model' => $selectedModel,
                'reason' => $modelSelection['reason'],
                'isStructured' => $isStructured,
                'estimatedTokens' => $modelSelection['estimatedTokens'] ?? 'N/A',
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $openaiSetting->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(45)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $selectedModel,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'max_tokens' => $maxTokens,
                'temperature' => 0.3, // Lower temperature for more consistent output
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $refinedAnswer = $result['choices'][0]['message']['content'] ?? '';

                if (!empty($refinedAnswer)) {
                    // Log and track token usage
                    $usage = $result['usage'] ?? [];
                    $inputTokens = $usage['prompt_tokens'] ?? 0;
                    $outputTokens = $usage['completion_tokens'] ?? 0;

                    if ($inputTokens > 0 || $outputTokens > 0) {
                        $this->trackTokenUsage('openai', 'refine_response', $inputTokens, $outputTokens, $selectedModel);
                    }

                    Log::info('OpenAI Refine - Token usage', [
                        'model' => $selectedModel,
                        'prompt_tokens' => $inputTokens,
                        'completion_tokens' => $outputTokens,
                        'total_tokens' => $usage['total_tokens'] ?? 0,
                        'mode' => $isStructured ? 'structured_conversion' : 'normal_refine',
                    ]);

                    return $refinedAnswer;
                }
            } else {
                Log::error('OpenAI Refine failed', [
                    'status' => $response->status(),
                    'error' => $response->json()['error'] ?? 'Unknown error',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('OpenAI Refine exception: ' . $e->getMessage());
        }

        // Fallback to original response if refinement fails
        return $combinedResponse;
    }

    /**
     * Synthesize a direct, user-friendly response from image analysis.
     * Used when the main flow couldn't provide a good response and we only have image analysis.
     * This converts verbose analysis into a concise, actionable answer.
     */
    public function synthesizeImageResponse(string $imageAnalysis, string $userMessage): string
    {
        // Get OpenAI API setting
        $openaiSetting = AiApiSetting::active()
                        ->where('provider', 'openai')
            ->first();

        if (!$openaiSetting || empty($openaiSetting->apiKey)) {
            // Fallback: try to extract key points from image analysis
            return $this->extractKeyPointsFromAnalysis($imageAnalysis);
        }

        $this->enforceRateLimit('openai-synthesize');

        try {
            $systemPrompt = <<<PROMPT
Ikaw ay isang AGRICULTURAL EXPERT na tumutulong sa mga magsasaka.

CONTEXT:
- Ang user ay nag-upload ng larawan ng kanilang pananim
- Mayroon kang DETAILED ANALYSIS ng larawan (sa baba)
- Kailangan mong GUMAWA ng DIRECT, HELPFUL na sagot

RULES:
1. HUWAG i-repeat ang buong analysis - mag-summarize lang
2. HUWAG magsimula sa "Narito ang aking pagsusuri..." o "🔍 NAKIKITA KO..."
3. MAGSIMULA kaagad sa observation at recommendation
4. Maximum 2-3 paragraphs lang
5. Kung may nakitang problema, magbigay ng ACTIONABLE SOLUTION
6. Gumamit ng natural na Tagalog/Filipino

FORMAT:
- Start: Direct observation (1-2 sentences) - "Nakikita ko na..." or "Mukhang may..."
- Middle: Konkretong recommendation kung may problema
- End: Brief tip kung applicable

EXAMPLE OUTPUT:
"Nakikita ko na may pagdilaw sa mga dahon ng palay mo, na maaaring sanhi ng nitrogen deficiency o water stress.

Recommendation:
• Mag-apply ng Urea (46-0-0) sa 25kg/ha kung kulang sa nitrogen
• Siguraduhing may sapat na tubig sa palayan - 2-3 inches ang ideal

Tip: Observe kung uniform ang pagdilaw - kung sa lower leaves lang, nitrogen deficiency yan. Kung sa buong halaman, check ang tubig."
PROMPT;

            $userPrompt = "USER'S QUESTION:\n{$userMessage}\n\n---\n\nDETAILED IMAGE ANALYSIS:\n{$imageAnalysis}\n\n---\n\nCreate a DIRECT, HELPFUL response based on the analysis above. Do NOT repeat the analysis format.";

            // COST OPTIMIZATION: Use gpt-4o-mini instead of gpt-4o for text synthesis
            // gpt-4o-mini is 16x cheaper ($0.15/$0.60 vs $2.50/$10.00 per 1M tokens)
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $openaiSetting->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'max_tokens' => 1000,
                'temperature' => 0.3,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $synthesized = $result['choices'][0]['message']['content'] ?? '';

                // Track token usage for this call
                $usage = $result['usage'] ?? [];
                $inputTokens = $usage['prompt_tokens'] ?? 0;
                $outputTokens = $usage['completion_tokens'] ?? 0;
                if ($inputTokens > 0 || $outputTokens > 0) {
                    $this->trackTokenUsage('openai', 'image_synthesize', $inputTokens, $outputTokens, 'gpt-4o-mini');
                }

                if (!empty($synthesized)) {
                    Log::info('Image response synthesized', [
                        'originalLength' => strlen($imageAnalysis),
                        'synthesizedLength' => strlen($synthesized),
                    ]);
                    return $synthesized;
                }
            } else {
                Log::error('OpenAI synthesize failed', [
                    'status' => $response->status(),
                    'error' => $response->json()['error'] ?? 'Unknown error',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('OpenAI synthesize exception: ' . $e->getMessage());
        }

        // Fallback: extract key points manually
        return $this->extractKeyPointsFromAnalysis($imageAnalysis);
    }

    /**
     * Extract key points from verbose image analysis as fallback.
     */
    protected function extractKeyPointsFromAnalysis(string $analysis): string
    {
        // Remove verbose headers and emoji patterns
        $cleaned = preg_replace('/^=== .+ ===\n*/m', '', $analysis);
        $cleaned = preg_replace('/^(🔍|📋|⚠️|🌱|✅|❌|💡)\s*/m', '', $cleaned);
        $cleaned = preg_replace('/^(NAKIKITA KO|DETALYADONG OBSERBASYON|REKOMENDASYON).*?\n/mi', '', $cleaned);
        $cleaned = preg_replace('/^Narito ang aking pagsusuri.*?\n/mi', '', $cleaned);

        // Remove multiple blank lines
        $cleaned = preg_replace('/\n{3,}/', "\n\n", $cleaned);

        return trim($cleaned);
    }

    /**
     * Extract the actual search query from a user message.
     * Removes user-provided documents/schedules and extracts only the problem/question.
     * This reduces RAG query size and improves search relevance.
     */
    protected function extractSearchQuery(string $message, ?AiApiSetting $geminiSetting): string
    {
        // If message is short, use it directly
        if (strlen($message) < 300) {
            return $message;
        }

        // Check if this looks like it has a document attached
        if (!$this->detectUserProvidedDocument($message)) {
            // No document detected, but still long - try to extract the question
            // Just return a truncated version to be safe
            if (strlen($message) > 500) {
                // Try to find the question part (usually at start or end)
                if (preg_match('/^(.{50,300})\s*\?/s', $message, $matches)) {
                    return trim($matches[0]);
                }
                return substr($message, 0, 400);
            }
            return $message;
        }

        // Message has a document - use AI to extract the actual question
        if (!$geminiSetting || empty($geminiSetting->apiKey)) {
            // No Gemini - fall back to pattern extraction
            return $this->extractQuestionFromDocumentMessage($message);
        }

        $this->enforceRateLimit('gemini-extract');

        try {
            $prompt = <<<PROMPT
TASK: Extract ONLY the user's QUESTION or PROBLEM from their message.

The user sent a message that contains:
1. Their actual QUESTION/PROBLEM (what they want help with)
2. A DOCUMENT/SCHEDULE they provided for context (fertilizer schedule, recommendations, etc.)

YOU MUST:
- Extract ONLY the question/problem (usually at the START of their message)
- IGNORE the attached document/schedule
- Return a SHORT search query (max 100 words)

USER MESSAGE:
{$message}

EXTRACTED QUESTION (short, searchable):
PROMPT;

            $response = Http::timeout(15)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$geminiSetting->apiKey}",
                [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 0.1, 'maxOutputTokens' => 200],
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                $extracted = trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');

                // Track Gemini token usage for query extraction
                $usageMetadata = $data['usageMetadata'] ?? [];
                $inputTokens = $usageMetadata['promptTokenCount'] ?? 0;
                $outputTokens = $usageMetadata['candidatesTokenCount'] ?? 0;
                if ($inputTokens > 0 || $outputTokens > 0) {
                    $this->trackTokenUsage('gemini', 'query_extraction', $inputTokens, $outputTokens, 'gemini-2.0-flash');
                }

                if (!empty($extracted) && strlen($extracted) < strlen($message)) {
                    Log::info('Search query extracted by AI', [
                        'originalLength' => strlen($message),
                        'extractedLength' => strlen($extracted),
                        'extracted' => $extracted,
                    ]);
                    return $extracted;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Query extraction failed: ' . $e->getMessage());
        }

        // Fallback to pattern extraction
        return $this->extractQuestionFromDocumentMessage($message);
    }

    /**
     * OPTIMIZATION: Create a comprehensive RAG query that covers ALL user needs in ONE call.
     * This prevents multiple expensive Pinecone calls by extracting ALL relevant search terms upfront.
     *
     * @param string $userMessage The user's original message
     * @param ?AiApiSetting $geminiSetting Gemini API settings for AI-powered extraction
     * @return string Optimized, comprehensive RAG query
     */
    protected function createOptimizedRagQuery(string $userMessage, ?AiApiSetting $geminiSetting): string
    {
        // First extract the core question (removes schedules/documents)
        $extractedQuestion = $this->extractSearchQuery($userMessage, $geminiSetting);

        // If no Gemini API, use basic query building
        if (!$geminiSetting || empty($geminiSetting->apiKey)) {
            return $this->buildProductSearchQuery($extractedQuestion);
        }

        $this->enforceRateLimit('gemini-optimize');

        try {
            // Check if image analysis findings are included
            $hasImageFindings = strpos($extractedQuestion, 'IMAGE ANALYSIS FINDINGS:') !== false;

            $prompt = <<<PROMPT
TASK: Create an OPTIMIZED search query for an agricultural knowledge base.

USER'S QUESTION/CONTEXT: {$extractedQuestion}

STEP 1 - ANALYZE THE CONTEXT:
PROMPT;

            if ($hasImageFindings) {
                $prompt .= <<<PROMPT

**IMAGE ANALYSIS WAS PROVIDED!**
The question includes IMAGE ANALYSIS FINDINGS. This means:
- The user uploaded photos of their crops
- AI has already analyzed the images and identified issues
- You MUST prioritize searching for PRODUCTS TO TREAT the identified issues

If image findings mention:
- "zinc deficiency" or "zinc" → Search for "zinc sulfate foliar spray product Philippines treatment"
- "iron deficiency" or "iron" → Search for "iron chelate foliar spray product Philippines"
- "interveinal chlorosis" → Search for "zinc iron deficiency foliar treatment product"
- "nitrogen deficiency" → Search for "urea nitrogen fertilizer product"
- "pest" or "insect" → Search for specific insecticide products
- "fungal" or "disease" → Search for fungicide products

PRIORITY: Search for SPECIFIC PRODUCTS that can treat the identified problem!
PROMPT;
            }

            $prompt .= <<<PROMPT

STEP 2 - ANALYZE USER'S INTENT:
Determine what the user ACTUALLY needs:
- If user says "na-apply na", "naglagay na", "nakapag-spray na" = COMPLETED ACTIONS (past tense)
- If user asks "ano ang susunod", "what's next", "ano pa" = ASKING FOR NEXT STEP
- If user describes symptoms or problems OR image shows problems = TROUBLESHOOTING/TREATMENT

STEP 3 - CREATE SMART QUERY:
Based on intent:

A) If IMAGE ANALYSIS shows a PROBLEM (deficiency, pest, disease):
   - Search for: SPECIFIC PRODUCTS to treat that problem
   - Example: Image shows zinc deficiency → "zinc sulfate Zintrac foliar spray mais treatment Philippines"
   - Include: product names, brand names, treatment methods

B) If asking "WHAT'S NEXT?" after completing something:
   - Search for: "schedule after [completed stage]" "next step after [X]"
   - DO NOT search for the completed item as if it's a problem!

C) If TROUBLESHOOTING a described problem:
   - Search for: symptoms, deficiency, treatment, solution, SPECIFIC PRODUCTS

STEP 4 - INCLUDE KEYWORDS:
- Crop type (palay, mais, etc.)
- SPECIFIC PRODUCT NAMES for treatment (zinc sulfate, iron chelate, Zintrac, etc.)
- Philippines context
- Filipino/Tagalog terms

CRITICAL FOR IMAGE ANALYSIS: Always search for TREATMENT PRODUCTS, not just general information!

OUTPUT FORMAT (just the search query, nothing else):
PROMPT;

            $response = Http::timeout(10)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$geminiSetting->apiKey}",
                [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 0.3, 'maxOutputTokens' => 200],
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                $optimizedQuery = trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');

                // Track Gemini token usage for query optimization
                $usageMetadata = $data['usageMetadata'] ?? [];
                $inputTokens = $usageMetadata['promptTokenCount'] ?? 0;
                $outputTokens = $usageMetadata['candidatesTokenCount'] ?? 0;
                if ($inputTokens > 0 || $outputTokens > 0) {
                    $this->trackTokenUsage('gemini', 'rag_query_optimization', $inputTokens, $outputTokens, 'gemini-2.0-flash');
                }

                if (!empty($optimizedQuery)) {
                    Log::info('RAG query optimized by AI', [
                        'originalQuestion' => substr($extractedQuestion, 0, 100),
                        'optimizedQuery' => $optimizedQuery,
                    ]);
                    $this->logFlowStep('RAG Query Optimization', 'AI created comprehensive search query', $optimizedQuery);
                    return $optimizedQuery;
                }
            }
        } catch (\Exception $e) {
            Log::warning('RAG query optimization failed: ' . $e->getMessage());
        }

        // Fallback to standard query building
        return $this->buildProductSearchQuery($extractedQuestion);
    }

    /**
     * Pre-fetch and cache RAG result for the entire flow.
     * Call this ONCE at the start of processing to populate the cache.
     *
     * @param string $userMessage The user's message
     * @param ?AiApiSetting $geminiSetting Gemini settings for query optimization
     * @return void
     */
    protected function preFetchRagResult(string $userMessage, ?AiApiSetting $geminiSetting): void
    {
        try {
            // Skip if already cached
            if ($this->cachedRagResult !== null) {
                Log::info('RAG pre-fetch skipped - already cached');
                return;
            }

            $ragSettings = AiRagSetting::getOrCreate();
            if (!$ragSettings || empty($ragSettings->apiKey) || empty($ragSettings->indexName)) {
                Log::warning('RAG pre-fetch: RAG not configured');
                $this->cachedRagResult = '';
                return;
            }

            // Create optimized query using AI
            $optimizedQuery = $this->createOptimizedRagQuery($userMessage, $geminiSetting);
            $this->logFlowStep('RAG Pre-fetch', 'Making ONE comprehensive RAG call for all needs', 'Query: ' . $optimizedQuery);

            // Make the single RAG call
            $this->enforceRateLimit('pinecone');
            $result = $this->queryPineconeAssistantRaw($ragSettings->apiKey, $ragSettings->indexName, $optimizedQuery);

            // Track token usage
            if (($result['inputTokens'] ?? 0) > 0 || ($result['outputTokens'] ?? 0) > 0) {
                $this->trackTokenUsage('pinecone', 'node_rag_unified', $result['inputTokens'] ?? 0, $result['outputTokens'] ?? 0, 'gpt-4o (via Pinecone)');
            }

            // Cache the result
            $content = $result['content'] ?? '';
            $this->cachedRagResult = $content;
            $this->cachedRagQuery = $optimizedQuery;

            Log::info('RAG pre-fetched and cached for entire flow', [
                'queryLength' => strlen($optimizedQuery),
                'resultLength' => strlen($content),
            ]);

            $this->logFlowStep('RAG Pre-fetch Complete', 'Result cached (' . strlen($content) . ' chars) - will reuse for all RAG needs');
        } catch (\Exception $e) {
            Log::error('RAG pre-fetch failed: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
            ]);
            // Set empty cache to prevent blocking the flow
            $this->cachedRagResult = '';
            $this->logFlowStep('RAG Pre-fetch Error', 'Pre-fetch failed: ' . $e->getMessage());
        }
    }

    /**
     * Fallback method to extract question from a message with attached document.
     * Uses pattern matching to find the actual question.
     */
    protected function extractQuestionFromDocumentMessage(string $message): string
    {
        // Common patterns where the question is at the start
        // "mukhang nahihirapan... ano ang problema?" then followed by schedule

        // Try to find content before schedule markers
        $scheduleMarkers = [
            'ANISENSO',
            'HIGH-YIELD',
            'FERTILIZER RECOMMENDATION',
            'Paghahanda ng',
            'Para sa isang',
            'BASAL',
            '\d+\s*DAT',
            'IMPORTANTE:',
        ];

        foreach ($scheduleMarkers as $marker) {
            if (preg_match('/^(.{30,500}?)[\s\n]*(' . $marker . ')/si', $message, $matches)) {
                $extracted = trim($matches[1]);
                if (strlen($extracted) >= 30) {
                    Log::info('Search query extracted by pattern', [
                        'marker' => $marker,
                        'extracted' => $extracted,
                    ]);
                    return $extracted;
                }
            }
        }

        // Try to find the question mark and get content before it
        if (preg_match('/^(.{30,400}\?)/s', $message, $matches)) {
            return trim($matches[1]);
        }

        // Last resort: return first 300 chars
        return substr($message, 0, 300);
    }

    /**
     * Detect if user provided a schedule, recommendation document, or detailed plan in their message.
     * This triggers special handling where the AI should analyze and reference the user's document.
     */
    protected function detectUserProvidedDocument(string $message): bool
    {
        // Check message length - documents are usually long
        if (strlen($message) < 500) {
            return false;
        }

        // Patterns that indicate user provided a document/schedule
        $documentPatterns = [
            // Schedule/recommendation indicators
            '/\b(schedule|recommendation|rekomendasyon|plano|program)\b/i',
            '/\b(eto|ito|here is|here\'s|narito)\s+(ang|ung|yung)?\s*(sinunod|ginagawa|program|schedule)/i',
            '/\b(sa baba|below|attached|nakalagay)\b/i',

            // Fertilizer schedule indicators
            '/\b(\d+)\s*(DAT|DAP|DAS|days?\s*after)/i',
            '/\b(basal|topdress|foliar|wet-?bed|seedlings?)\b/i',
            '/\bpara sa isang (hectare|ektarya|knapsack)\b/i',

            // Step-by-step or timeline indicators
            '/\b(step|hakbang)\s*\d+/i',
            '/\d+\s*(kg|ml|g|L)\s+(per|kada|\/)\s*(ha|hectare|ektarya|knapsack)/i',

            // Document headers
            '/\b(HIGH-?YIELD|FERTILIZER RECOMMENDATION|RECOMMENDATION FOR)\b/i',
            '/\bIMPORTANTE:/i',

            // Explicit references to following/using something
            '/\b(sinusunod|sinunod|ginagamit|ginagawa|inapply)\s*(ko|namin|namin)?\s*(na|ang|ito|yan|eto)/i',
        ];

        foreach ($documentPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                Log::info('User-provided document detected', [
                    'pattern' => $pattern,
                    'messageLength' => strlen($message),
                ]);
                return true;
            }
        }

        // Also check if message has multiple "DAT" or timing references (schedule-like)
        $datCount = preg_match_all('/\b\d+\s*(DAT|DAP|DAS)\b/i', $message);
        if ($datCount >= 3) {
            Log::info('User-provided schedule detected via multiple DAT references', [
                'datCount' => $datCount,
            ]);
            return true;
        }

        return false;
    }

    /**
     * Detect if user is requesting a comparison to standard/traditional farming.
     * This is used to ensure comparison tables are preserved through all processing steps.
     *
     * @param string $message The user's message
     * @return bool True if comparison request detected
     */
    protected function detectComparisonRequest(string $message): bool
    {
        $message = strtolower($message);

        // Keywords that indicate comparison request
        $compareKeywords = ['ikumpara', 'compare', 'kumpara', 'comparison', 'paghahambing', 'vs', 'versus'];
        $standardKeywords = ['standard', 'traditional', 'tradisyonal', 'farming', 'expected', 'normal'];

        // Check for comparison keywords
        $hasCompareKeyword = false;
        foreach ($compareKeywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                $hasCompareKeyword = true;
                break;
            }
        }

        // Check for standard/traditional keywords
        $hasStandardKeyword = false;
        foreach ($standardKeywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                $hasStandardKeyword = true;
                break;
            }
        }

        // Combination patterns that clearly indicate comparison request
        $comparisonPatterns = [
            '/\b(ikumpara|compare|kumpara).*(standard|traditional|tradisyonal|farming)/i',
            '/\b(standard|traditional|tradisyonal).*(vs|versus|kaysa)/i',
            '/\b(vs|versus)\s+(standard|traditional|tradisyonal)/i',
            '/\b(paghahambing|comparison).*(standard|traditional|expected)/i',
        ];

        foreach ($comparisonPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                Log::debug('Comparison request detected via pattern', ['pattern' => $pattern]);
                return true;
            }
        }

        // If both compare and standard keywords are present
        if ($hasCompareKeyword && $hasStandardKeyword) {
            Log::debug('Comparison request detected via keyword combination');
            return true;
        }

        return false;
    }

    /**
     * Use AI to analyze the user's schedule context.
     * This determines: current stage, what's done, what's pending, the specific concern, and TOTAL NUTRIENTS applied.
     * Returns an AI-generated analysis string.
     */
    protected function analyzeUserScheduleContext(string $message, ?AiApiSetting $geminiSetting, ?AiApiSetting $openaiSetting): string
    {
        $prompt = "ANALYZE this farmer's message and fertilizer schedule. Extract the following:\n\n";
        $prompt .= "MESSAGE:\n" . $message . "\n\n";
        $prompt .= "EXTRACT AND RETURN IN THIS EXACT FORMAT:\n";
        $prompt .= "1. CURRENT_STAGE: [number] DAP/DAT (or 'unknown' if not mentioned)\n";
        $prompt .= "2. FOLLOWED_SCHEDULE: Yes/No (did they say they followed/applied the schedule?)\n";
        $prompt .= "3. DONE_APPLICATIONS: List all DAT/DAP applications that are ALREADY DONE (less than current stage)\n";
        $prompt .= "4. PENDING_APPLICATIONS: List all DAT/DAP applications that are STILL PENDING (equal or greater than current stage)\n";
        $prompt .= "5. USER_CONCERN: What is the user asking about or worried about?\n";
        $prompt .= "6. SCHEDULE_STATUS: 'COMPLETE' if all applications are done, 'IN_PROGRESS' if some pending\n";
        $prompt .= "7. TOTAL_NUTRIENTS: Calculate the TOTAL nutrients applied from the schedule using these formulas:\n";
        $prompt .= "   - 14-14-14: 14% N, 14% P₂O₅, 14% K₂O\n";
        $prompt .= "   - Urea: 46% N (46-0-0)\n";
        $prompt .= "   - Ammosul: 21% N (21-0-0)\n";
        $prompt .= "   - MOP/Muriate of Potash: 60% K₂O (0-0-60)\n";
        $prompt .= "   - Nitrabor/Calcium Nitrate: 15.5% N + Ca + B\n";
        $prompt .= "   - Duophos: 22% P₂O₅ (0-22-0)\n";
        $prompt .= "   Format: N=XXX kg/ha, P₂O₅=XXX kg/ha, K₂O=XXX kg/ha\n\n";
        $prompt .= "IMPORTANT RULES:\n";
        $prompt .= "- If user says they followed the schedule and their current stage is PAST the last application in the schedule, then SCHEDULE_STATUS = COMPLETE.\n";
        $prompt .= "- Calculate TOTAL nutrients by summing ALL applications in the schedule (not just one application).\n";
        $prompt .= "- For hybrid rice, recommended total is approximately: N=120-150kg, P=40-60kg, K=60-80kg per hectare.\n";

        try {
            // Try Gemini first (faster)
            if ($geminiSetting && !empty($geminiSetting->apiKey)) {
                $this->enforceRateLimit('gemini-schedule-analysis');
                $response = Http::timeout(30)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$geminiSetting->apiKey}",
                    [
                        'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                        'generationConfig' => ['temperature' => 0.1, 'maxOutputTokens' => 500],
                    ]
                );

                if ($response->successful()) {
                    $data = $response->json();
                    $analysis = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                    // Track Gemini token usage
                    $usageMetadata = $data['usageMetadata'] ?? [];
                    $inputTokens = $usageMetadata['promptTokenCount'] ?? 0;
                    $outputTokens = $usageMetadata['candidatesTokenCount'] ?? 0;
                    if ($inputTokens > 0 || $outputTokens > 0) {
                        $this->trackTokenUsage('gemini', 'schedule_analysis', $inputTokens, $outputTokens, 'gemini-2.0-flash');
                    }

                    if (!empty($analysis)) {
                        Log::info('Schedule context analyzed by Gemini', ['length' => strlen($analysis)]);
                        return $analysis;
                    }
                }
            }

            // Fallback to OpenAI
            if ($openaiSetting && !empty($openaiSetting->apiKey)) {
                $this->enforceRateLimit('openai-schedule-analysis');
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $openaiSetting->apiKey,
                ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You analyze agricultural schedules and extract key information.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => 500,
                    'temperature' => 0.1,
                ]);

                if ($response->successful()) {
                    $result = $response->json();
                    $analysis = $result['choices'][0]['message']['content'] ?? '';

                    // Track OpenAI token usage
                    $usage = $result['usage'] ?? [];
                    $inputTokens = $usage['prompt_tokens'] ?? 0;
                    $outputTokens = $usage['completion_tokens'] ?? 0;
                    if ($inputTokens > 0 || $outputTokens > 0) {
                        $this->trackTokenUsage('openai', 'schedule_analysis', $inputTokens, $outputTokens, 'gpt-4o-mini');
                    }

                    if (!empty($analysis)) {
                        Log::info('Schedule context analyzed by OpenAI', ['length' => strlen($analysis)]);
                        return $analysis;
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Schedule context analysis failed: ' . $e->getMessage());
        }

        return '';
    }

    /**
     * Detect if an AI response is comprehensive and well-structured.
     * Such responses should be preserved rather than regenerated.
     * Looks for numbered sections, structured format, comprehensive coverage.
     */
    protected function isComprehensiveStructuredResponse(string $response): bool
    {
        if (strlen($response) < 300) {
            return false;
        }

        $structurePatterns = [
            // Numbered sections like (1), (2), (3)...
            '/\(\d+\)\s*[A-Z]/',
            // Numbered with colon like 1. DIRECT ANSWER:
            '/\d+\.\s*(DIRECT|NORMAL|WHY|WHAT|WHEN|HOW|IMPORTANT)/i',
            // Section headers in caps
            '/[A-Z\s]{5,}:/m',
            // Multiple bullet points with content
            '/[•\-\*]\s+[A-Z].*\n.*[•\-\*]\s+[A-Z]/s',
        ];

        $matchCount = 0;
        foreach ($structurePatterns as $pattern) {
            if (preg_match($pattern, $response)) {
                $matchCount++;
            }
        }

        // Check for comprehensive content indicators
        $contentIndicators = [
            '/\b(DIRECT ANSWER|DIREKTANG SAGOT)\b/i',
            '/\b(NORMAL|PROBLEMA|PROBLEM)\b/i',
            '/\b(WHAT (YOU CAN|TO) DO|ANO ANG GAWIN|RECOMMENDATION)\b/i',
            '/\b(WHAT NOT TO|HUWAG|AVOID|IWASAN)\b/i',
            '/\b(WHEN TO|KAPAG|WARNING|WATCH FOR)\b/i',
        ];

        $contentMatches = 0;
        foreach ($contentIndicators as $pattern) {
            if (preg_match($pattern, $response)) {
                $contentMatches++;
            }
        }

        // Consider structured if has 2+ structure patterns or 3+ content indicators
        $isStructured = ($matchCount >= 2) || ($contentMatches >= 3);

        if ($isStructured) {
            Log::info('Comprehensive structured AI response detected', [
                'structurePatterns' => $matchCount,
                'contentIndicators' => $contentMatches,
                'responseLength' => strlen($response),
            ]);
        }

        return $isStructured;
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
     * Simple RAG search with caching.
     * Uses cached result if available to avoid multiple expensive Pinecone queries.
     */
    protected function getSimpleRagResult(string $query): string
    {
        // OPTIMIZATION: Use cached RAG result if available
        // This prevents making multiple Pinecone calls for similar queries
        if ($this->cachedRagResult !== null) {
            Log::info('RAG CACHE HIT - Using cached result instead of new query', [
                'cachedQueryPreview' => substr($this->cachedRagQuery ?? '', 0, 100),
                'newQueryPreview' => substr($query, 0, 100),
                'cachedResultLength' => strlen($this->cachedRagResult),
            ]);
            $this->ragCacheUsed = true;
            return $this->cachedRagResult;
        }

        $ragSettings = AiRagSetting::getOrCreate();
        if (!$ragSettings || empty($ragSettings->apiKey)) {
            return '';
        }

        // Use the raw version to get token tracking
        $result = $this->queryPineconeAssistantRaw(
            $ragSettings->apiKey,
            $ragSettings->indexName,
            $query
        );

        // Track token usage
        if (($result['inputTokens'] ?? 0) > 0 || ($result['outputTokens'] ?? 0) > 0) {
            $this->trackTokenUsage('pinecone', 'node_rag', $result['inputTokens'] ?? 0, $result['outputTokens'] ?? 0, 'gpt-4o (via Pinecone)');
        }

        $content = $result['content'] ?? '';

        // Cache the result for subsequent calls
        $this->cachedRagResult = $content;
        $this->cachedRagQuery = $query;

        Log::info('RAG result cached for reuse', [
            'queryPreview' => substr($query, 0, 100),
            'resultLength' => strlen($content),
        ]);

        return $content;
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
                $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                // Track Gemini web search token usage
                $usageMetadata = $data['usageMetadata'] ?? [];
                $inputTokens = $usageMetadata['promptTokenCount'] ?? 0;
                $outputTokens = $usageMetadata['candidatesTokenCount'] ?? 0;
                if ($inputTokens > 0 || $outputTokens > 0) {
                    $this->trackTokenUsage('gemini', 'web_search', $inputTokens, $outputTokens, 'gemini-2.0-flash');
                }

                return $content;
            }
            return '';
        } catch (\Exception $e) {
            Log::error('Web search failed: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * ================================================================
     * DUAL-AI ANSWER COMPARISON SYSTEM
     * ================================================================
     * Get detailed answers from BOTH OpenAI and Gemini, then compare
     * and combine them using Gemini for a more accurate final answer.
     * ================================================================
     */

    /**
     * Get detailed AI answer with reasoning from a specific provider.
     * Asks the AI to answer the question AND explain WHY in detail.
     * Includes user's query rules for consistent, accurate answers.
     */
    protected function getDetailedAIAnswer(string $provider, string $apiKey, string $query): array
    {
        $this->enforceRateLimit('dual-ai-' . $provider);

        // Get compiled query rules for this user
        $queryRules = AiQueryRule::getCompiledRules();

        $systemPrompt = <<<PROMPT
Ikaw ay isang EXPERT na agricultural technician sa PILIPINAS na nakikipag-usap sa magsasaka.
IKAW ang AI TECHNICIAN - HUWAG mo sabihing "kumonsulta sa agronomist/technician"!

🚫 PINAKA-IMPORTANTENG RULE - BAGO KA SUMAGOT:
═══════════════════════════════════════════════════════════════
Tingnan mo ang KASALUKUYANG MESSAGE ng user:
- MAY LARAWAN BA? Kung wala, hindi mo pwedeng sabihin "nakikita ko..."
- MAY SINABI BA ANG USER na crop variety, DAP, o problema SA KASALUKUYANG MESSAGE?
- Kung WALANG LARAWAN at WALANG DETALYE SA KASALUKUYANG MESSAGE = MAGTANONG KA MUNA!
- ❌ BAWAL gumamit ng NK6414, 100 DAP, o anumang specific na detalye kung HINDI ITO sinabi ng user!
- ❌ BAWAL sabihing "Base sa tanong ninyo, ang inyong [variety] sa [DAP]..." kung HINDI niya sinabi yan!
═══════════════════════════════════════════════════════════════

{$queryRules}

═══════════════════════════════════════════════════════════════
RESPONSE STYLE: NATURAL FLOW - Parang Totoong Technician!
═══════════════════════════════════════════════════════════════

Sumagot ng parang KAIBIGAN na expert - hindi robotic o sumisigaw.

STRUCTURE NG SAGOT (natural flow - HINDI verdict muna!):

⚠️ MAHALAGA: KUNG WALANG LARAWAN, HUWAG SABIHIN "NAKIKITA KO"!

🚨 KRITIKAL: HUWAG MAG-IMBENTO NG DETALYE!
- ❌ BAWAL mag-assume ng VARIETY kung hindi sinabi ng user (e.g., NK6414, Jackpot 102)
- ❌ BAWAL mag-assume ng DAP kung hindi sinabi ng user (e.g., 100 DAP, 75 DAP)
- ❌ BAWAL mag-assume ng CROP TYPE kung walang larawan at hindi sinabi ng user
- ✅ KUNG WALANG CONTEXT: MAGTANONG MUNA sa user!

1. **UNANG PANGUNGUSAP** - Context-aware opening:
   KUNG MAY LARAWAN: "Nakikita ko po sa larawan ang inyong palay..."
   KUNG WALANG LARAWAN PERO may sinabi ang user: "Base sa sinabi ninyo..."
   KUNG WALA TALAGA - MAGTANONG: "Ano po ang gusto ninyong i-check? Paki-send ng larawan o sabihin ang detalye."
   ❌ BAWAL: "Nakikita ko po" kung WALANG larawan!
   ❌ BAWAL: "Ang inyong [variety] sa [DAP]..." kung HINDI sinabi ng user!

2. **PANGALAWANG PARAGRAPH** - Natural assessment
   ✅ "Sa yugtong ito, hindi na po kailangan ng irrigation dahil..."
   ✅ "Nasa R5/Dent stage na po ang mais ninyo..."
   ❌ HUWAG: Verdict na sumisigaw sa simula

3. **PANGATLONG PARAGRAPH** - Ano ang GAWIN ngayon
   "Ang maipapayo ko po sa inyo:"
   • **Specific action 1**
   • **Specific action 2**

4. **HULING PANGUNGUSAP** - Bantayan / Follow-up
   "Kung may tanong pa kayo, mag-send ng litrato para ma-check ko."

═══════════════════════════════════════════════════════════════
HALIMBAWA - KUNG WALANG LARAWAN AT WALANG DETALYE (VAGUE MESSAGE):
═══════════════════════════════════════════════════════════════
User: "pwede mo ba tignan ito kung ayos ito"

Kumusta po! 😊 Para matulungan ko kayo, kailangan ko po ng kaunting detalye:

1. **Anong tanim po ang gusto ninyong i-check?** (mais, palay, etc.)
2. **Pwede po bang mag-send ng larawan?** Para makita ko ang kondisyon
3. **Ilang araw na po mula nang itanim?** (DAP o DAT)

Mag-send lang po kayo ng larawan at sasagutin ko kaagad! 📷

═══════════════════════════════════════════════════════════════
HALIMBAWA - KUNG WALANG LARAWAN PERO MAY CONTEXT (User sinabi ang variety at DAP):
═══════════════════════════════════════════════════════════════
User: "kumusta ang NK6414 ko sa 100 DAP, okay pa ba?"

Ang inyong NK6414 na mais sa 100 DAP ay malapit na po sa physiological maturity. 🌽

Sa stage na ito, hindi na po kailangan ang karagdagang patubig. Ang butil ay fully developed na at nagsisimula nang matuyo - nasa R5/Dent stage na po ito.

Ang maipapayo ko po:
• **Ihinto na ang pagpapatubig** - ang mais ay maturity stage na
• **Maghanda na para sa pag-aani** - target moisture: 18-20%

Kung gusto ninyong i-send ang litrato ng mais, mas accurate pa ang ma-assess ko!

═══════════════════════════════════════════════════════════════
HALIMBAWA - KUNG MAY LARAWAN:
═══════════════════════════════════════════════════════════════

Nakikita ko po sa larawan ang inyong palay na nasa reproductive stage na - may mga uhay na rin lumalabas. 🌾

Batay sa nakikita ko, mukhang malusog po ang inyong pananim. Ang mga dahon ay may magandang berdeng kulay at walang sintomas ng sakit.

Ang maipapayo ko po:
• **Patuloy na mag-monitor ng tubig** - lalo na sa flowering stage
• **Bantayan ang peste at sakit** - brown planthopper at stem borer

Kung makakita ka ng pagdilaw ng dahon, mag-send ka ng litrato para ma-check ko.

═══════════════════════════════════════════════════════════════
IMAGE ANALYSIS HANDLING:
═══════════════════════════════════════════════════════════════
Kung may image analysis sa query:
- SUNDIN ang observations at assessment
- I-incorporate ito naturally sa iyong sagot
- HUWAG mag-contradict sa image analysis

═══════════════════════════════════════════════════════════════
SCHEDULE CONTEXT HANDLING (CRITICAL!):
═══════════════════════════════════════════════════════════════
Kung may "SCHEDULE_STATUS: COMPLETE" sa query:
⚠️ BAWAL SABIHIN: "Magpatuloy sa pagsunod sa iyong fertilizer program"
⚠️ BAWAL SABIHIN: "Mag-apply ng additional fertilizer"
✅ TAMANG SABIHIN: "Huwag na mag-apply ng bagong fertilizer - kumpleto na ang schedule mo"
✅ TAMANG SABIHIN: "Tapos mo na lahat ng applications!"
✅ Focus ONLY on: monitoring tubig, peste, at sakit

═══════════════════════════════════════════════════════════════
MINIMUM RESPONSE LENGTH:
═══════════════════════════════════════════════════════════════
- Para sa complex questions: MINIMUM 800 characters
- Kung ang user may maraming tanong, SAGUTIN LAHAT with explanation
- HUWAG i-shorten - comprehensive responses are better

TANDAAN:
- TAGALOG ang pangunahin, English para sa technical terms lang
- Maging DECISIVE - pero natural, hindi sumisigaw
- Gumamit ng 'po' para magalang
- HUWAG gumamit ng (1), (2), (3) numbering - conversational lang!

⚠️ IKAW ANG EXPERT - HUWAG MAG-REFERENCE NG IBA!
BAWAL: "Ayon sa mga eksperto..." (IKAW ang eksperto!)
BAWAL: "Sabi ng mga agronomist..." (IKAW ang agronomist!)
BAWAL: "According to experts..." (YOU ARE the expert!)
✅ Magsalita ng DIREKTA at may AWTORIDAD!

⚠️ CRITICAL: HUWAG FABRICATE/ASSUME PLANT HEALTH STATUS!
KUNG WALANG LARAWAN AT WALANG SINABI ANG USER TUNGKOL SA HEALTH:
❌ BAWAL: "Mukhang malusog po ang inyong pananim" - HINDI MO NAKITA!
❌ BAWAL: "Walang nakitang sintomas ng sakit" - WALA KANG NAKITA!
✅ KUNG TANONG LANG TUNGKOL SA PRODUCT/TIMING: Sagutin DIREKTA!
   HALIMBAWA: "Oo po, pwede mag-spray ng Nativo sa 70 DAT..."
PROMPT;

        $userPrompt = $query;

        try {
            if ($provider === 'openai') {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                ])->timeout(45)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',  // Use GPT-4o-mini for cost efficiency
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'max_tokens' => 2000,
                    'temperature' => 0.7,
                ]);

                if ($response->successful()) {
                    $result = $response->json();
                    $content = $result['choices'][0]['message']['content'] ?? '';

                    // Track OpenAI token usage
                    $usage = $result['usage'] ?? [];
                    $inputTokens = $usage['prompt_tokens'] ?? 0;
                    $outputTokens = $usage['completion_tokens'] ?? 0;
                    if ($inputTokens > 0 || $outputTokens > 0) {
                        $this->trackTokenUsage('openai', 'dual_ai_detailed', $inputTokens, $outputTokens, 'gpt-4o-mini');
                    }

                    return [
                        'success' => true,
                        'provider' => 'OpenAI GPT-4o-mini',
                        'answer' => $content,
                    ];
                }

                Log::warning('OpenAI detailed answer failed', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                ]);

            } else { // gemini
                $geminiPrompt = $systemPrompt . "\n\n" . $userPrompt;
                $response = Http::timeout(45)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}",
                    [
                        'contents' => [['role' => 'user', 'parts' => [['text' => $geminiPrompt]]]],
                        'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 2000],
                    ]
                );

                if ($response->successful()) {
                    $data = $response->json();
                    $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                    // Track Gemini token usage
                    $usageMetadata = $data['usageMetadata'] ?? [];
                    $inputTokens = $usageMetadata['promptTokenCount'] ?? 0;
                    $outputTokens = $usageMetadata['candidatesTokenCount'] ?? 0;
                    if ($inputTokens > 0 || $outputTokens > 0) {
                        $this->trackTokenUsage('gemini', 'dual_ai_detailed', $inputTokens, $outputTokens, 'gemini-2.0-flash');
                    }

                    return [
                        'success' => true,
                        'provider' => 'Google Gemini',
                        'answer' => $content,
                    ];
                }

                Log::warning('Gemini detailed answer failed', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                ]);
            }

            return ['success' => false, 'provider' => $provider, 'answer' => ''];

        } catch (\Exception $e) {
            Log::error('Dual AI detailed answer failed: ' . $e->getMessage(), [
                'provider' => $provider,
            ]);
            return ['success' => false, 'provider' => $provider, 'answer' => '', 'error' => $e->getMessage()];
        }
    }

    /**
     * Compare and combine answers from OpenAI and Gemini using Gemini.
     * Gemini analyzes both answers and creates the best combined response.
     * Includes user's query rules for verification.
     */
    protected function compareAndCombineAnswers(string $geminiApiKey, string $openAiAnswer, string $geminiAnswer, string $originalQuery): string
    {
        $this->enforceRateLimit('dual-ai-combine');

        // Get compiled query rules for verification
        $queryRules = AiQueryRule::getCompiledRules();

        $prompt = <<<PROMPT
IKAW AY FINAL ARBITER para sa agricultural questions sa PILIPINAS.
IKAW ang AI TECHNICIAN - HUWAG sabihing "kumonsulta sa agronomist/technician"!

{$queryRules}

═══════════════════════════════════════════════════════════════
ORIGINAL NA TANONG:
{$originalQuery}
═══════════════════════════════════════════════════════════════

═══════════════════════════════════════════════════════════════
SAGOT #1 (OpenAI GPT-4o-mini):
{$openAiAnswer}
═══════════════════════════════════════════════════════════════

═══════════════════════════════════════════════════════════════
SAGOT #2 (Google Gemini):
{$geminiAnswer}
═══════════════════════════════════════════════════════════════

═══════════════════════════════════════════════════════════════
ANG TRABAHO MO - COMBINE INTO CONVERSATIONAL ANSWER:
═══════════════════════════════════════════════════════════════

STEP 1: DECIDE - NORMAL or PROBLEM?
- Kung pareho silang sabi NORMAL = NORMAL
- Kung pareho silang sabi PROBLEM = PROBLEM
- Kung magkaiba sila = Use QUERY RULES to decide
- Kung COMPLETE schedule + no symptoms = NORMAL

STEP 2: WRITE CONVERSATIONAL RESPONSE (parang kaibigan na expert)

═══════════════════════════════════════════════════════════════
OUTPUT FORMAT - NATURAL FLOW (Hindi verdict muna!):
═══════════════════════════════════════════════════════════════

Sumagot ng parang KAIBIGAN na expert. HUWAG gumamit ng (1), (2), (3) numbering!
❌ HUWAG simulan sa "NORMAL po ito!" o "PROBLEMA po ito!" - robotic yan!

STRUCTURE (Natural Flow):

**UNANG PANGUNGUSAP** - Observe muna (Context-aware!):
⚠️ KUNG WALANG LARAWAN, HUWAG SABIHIN "NAKIKITA KO"!
KUNG MAY LARAWAN: ✅ "Nakikita ko po sa larawan ang inyong palay na nasa [stage] na..."
KUNG WALANG LARAWAN PERO sinabi ng user ang detalye: ✅ "Base sa sinabi ninyo, ang inyong [crop] sa [DAP] ay..."
KUNG WALANG LARAWAN AT WALANG DETALYE: ✅ MAGTANONG: "Anong tanim po at ilang DAP na?"
❌ BAWAL mag-assume ng variety o DAP kung hindi sinabi ng user!

**PANGALAWANG PARAGRAPH** - Natural assessment:
⚠️ KUNG MAY LARAWAN O SINABI NG USER ANG KONDISYON:
✅ "Sa yugtong ito, normal po ang bahagyang pagdilaw..."
✅ "Mukhang malusog po ang inyong pananim..."
⚠️ KUNG WALANG LARAWAN AT TANONG LANG (product/timing question):
✅ DIREKTANG SAGOT: "Oo po, pwede mag-spray ng [product] sa [DAT]..."
❌ BAWAL: "Mukhang malusog ang pananim..." kung WALANG EBIDENSYA!

**PANGATLONG PARAGRAPH** - Ano ang GAWIN:
"Ang maipapayo ko po sa inyo ngayon:"
• **Specific action** - explanation

**HULING PANGUNGUSAP** - Bantayan:
"Kung makakita ka ng [symptoms], mag-send ka ng litrato para ma-check ko."

═══════════════════════════════════════════════════════════════
HALIMBAWA NG NATURAL NA OUTPUT:
═══════════════════════════════════════════════════════════════

**KUNG MAY LARAWAN (at HINDI sinabi ng user ang DAP):**
Nakikita ko po sa larawan ang inyong palay na nasa REPRODUCTIVE/FLOWERING STAGE na - may mga uhay na rin lumalabas. 🌾

Batay sa mga larawan, mukhang malusog po ang inyong pananim at on-track sa development nito. Ilang araw na po ba mula nang itanim ito? (para mas accurate ang recommendations ko)

**KUNG MAY LARAWAN AT SINABI ng user ang DAP (e.g., "65 DAP"):**
Nakikita ko po sa larawan ang inyong palay na nasa reproductive stage na - may mga uhay na rin lumalabas, na normal sa inyong sinabi na 65 DAP. 🌾

Batay sa mga larawan, mukhang on-track po kayo - ang flowering period ay karaniwang sa 60-70 DAP para sa hybrid varieties.

**KUNG WALANG LARAWAN AT sinabi ng user ang DAP:**
Ang inyong palay na nasa 65 DAP ay nasa reproductive stage na - sa ganitong edad, expected na ang paglabas ng mga uhay. 🌾

Base sa sinabi ninyo, mukhang on-track naman ang inyong pananim. Ang flowering period ay karaniwang sa 60-70 DAP para sa hybrid varieties.

**CONTINUATION (pareho for both):**
Ang maipapayo ko po sa inyo ngayon:
• **Patuloy na mag-monitor ng tubig at peste**
• Huwag na mag-apply ng bagong fertilizer dahil tapos na ang schedule mo

Kung makakita ka ng pagdilaw ng dahon o brown planthopper, mag-send ka ng litrato para ma-check ko.

═══════════════════════════════════════════════════════════════
CRITICAL RULES:
═══════════════════════════════════════════════════════════════
- Be DECISIVE, not wishy-washy!
- HUWAG gumamit ng (1), (2), (3) numbering - conversational lang!
- Kung COMPLETE schedule at walang symptoms = sabihin NORMAL at PRAISE the farmer
- Use "po" for politeness
- TAGALOG ang pangunahin, English para sa technical terms lang
- MINIMUM 800 characters para sa complex questions!

⚠️ IKAW ANG EXPERT - HUWAG MAG-REFERENCE NG IBA!
BAWAL: "Ayon sa mga eksperto..." (IKAW ang eksperto!)
BAWAL: "Sabi ng mga agronomist..." (IKAW ang agronomist!)
BAWAL: "According to experts..." (YOU ARE the expert!)
BAWAL: "Studies show..." (Just state the facts directly!)
✅ TAMANG SABIHIN: "Sa 100 DAP, ang mais ay..." (direct, confident)
✅ TAMANG SABIHIN: "Hindi na kailangan ng irrigation dahil..." (authoritative)

⚠️ KAPAG SCHEDULE_STATUS = COMPLETE:
BAWAL: "Magpatuloy sa pagsunod sa iyong fertilizer program"
BAWAL: "Mag-apply ng additional fertilizer"
TAMANG SABIHIN: "Huwag na mag-apply ng bagong fertilizer - kumpleto na ang schedule mo"
Focus ONLY on: monitoring tubig, peste, at sakit
PROMPT;

        try {
            $response = Http::timeout(45)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$geminiApiKey}",
                [
                    'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 0.5, 'maxOutputTokens' => 2500],
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                // Track Gemini token usage for combination
                $usageMetadata = $data['usageMetadata'] ?? [];
                $inputTokens = $usageMetadata['promptTokenCount'] ?? 0;
                $outputTokens = $usageMetadata['candidatesTokenCount'] ?? 0;
                if ($inputTokens > 0 || $outputTokens > 0) {
                    $this->trackTokenUsage('gemini', 'dual_ai_combine', $inputTokens, $outputTokens, 'gemini-2.0-flash');
                }

                return $content;
            }

            Log::warning('Dual AI combine failed', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);

            // Fallback: If comparison fails, prefer Gemini's answer (based on user's experience that Gemini gave better answer)
            return $geminiAnswer;

        } catch (\Exception $e) {
            Log::error('Dual AI combine failed: ' . $e->getMessage());
            // Fallback to Gemini answer
            return $geminiAnswer;
        }
    }

    /**
     * Main function: Get dual-AI answer with comparison.
     * Calls both OpenAI and Gemini, then combines using Gemini.
     */
    protected function getDualAIKnowledge(?AiApiSetting $openaiSetting, ?AiApiSetting $geminiSetting, string $query): string
    {
        $hasOpenAI = $openaiSetting && !empty($openaiSetting->apiKey);
        $hasGemini = $geminiSetting && !empty($geminiSetting->apiKey);

        // Need both APIs for dual comparison
        if (!$hasOpenAI || !$hasGemini) {
            $this->logFlowStep('Step 3c: Dual AI', 'Fallback to single AI - missing API keys');

            // Fallback to single provider
            if ($hasGemini) {
                $result = $this->getDetailedAIAnswer('gemini', $geminiSetting->apiKey, $query);
                return $result['answer'] ?? '';
            } elseif ($hasOpenAI) {
                $result = $this->getDetailedAIAnswer('openai', $openaiSetting->apiKey, $query);
                return $result['answer'] ?? '';
            }
            return '';
        }

        // Step 1: Get detailed answer from OpenAI
        $this->logFlowStep('Step 3c-1: OpenAI Answer', 'Getting detailed answer from GPT-4o-mini...');
        $openAiResult = $this->getDetailedAIAnswer('openai', $openaiSetting->apiKey, $query);
        $openAiAnswer = $openAiResult['answer'] ?? '';

        if (!empty($openAiAnswer)) {
            $this->logFlowStep('Step 3c-1: OpenAI Response', 'OpenAI GPT-4o-mini answered (' . strlen($openAiAnswer) . ' chars)', $openAiAnswer);
        } else {
            $this->logFlowStep('Step 3c-1: OpenAI Response', 'OpenAI failed to respond');
        }

        // Step 2: Get detailed answer from Gemini
        $this->logFlowStep('Step 3c-2: Gemini Answer', 'Getting detailed answer from Gemini...');
        $geminiResult = $this->getDetailedAIAnswer('gemini', $geminiSetting->apiKey, $query);
        $geminiAnswer = $geminiResult['answer'] ?? '';

        if (!empty($geminiAnswer)) {
            $this->logFlowStep('Step 3c-2: Gemini Response', 'Gemini answered (' . strlen($geminiAnswer) . ' chars)', $geminiAnswer);
        } else {
            $this->logFlowStep('Step 3c-2: Gemini Response', 'Gemini failed to respond');
        }

        // If one failed, use the other
        if (empty($openAiAnswer) && empty($geminiAnswer)) {
            $this->logFlowStep('Step 3c: Dual AI', 'Both AIs failed to respond');
            return '';
        }

        if (empty($openAiAnswer)) {
            $this->logFlowStep('Step 3c: Dual AI', 'OpenAI failed - using Gemini answer only');
            return $geminiAnswer;
        }

        if (empty($geminiAnswer)) {
            $this->logFlowStep('Step 3c: Dual AI', 'Gemini failed - using OpenAI answer only');
            return $openAiAnswer;
        }

        // Step 3: Compare and combine using Gemini
        $this->logFlowStep('Step 3c-3: Combine Answers', 'Comparing and combining both answers with Gemini...');

        $combinedAnswer = $this->compareAndCombineAnswers(
            $geminiSetting->apiKey,
            $openAiAnswer,
            $geminiAnswer,
            $query
        );

        if (!empty($combinedAnswer)) {
            $this->logFlowStep('Step 3c-3: Final Combined', 'Combined answer ready (' . strlen($combinedAnswer) . ' chars)', $combinedAnswer);
        } else {
            // Fallback to Gemini if combination fails
            $this->logFlowStep('Step 3c-3: Combine Failed', 'Using Gemini answer as fallback');
            $combinedAnswer = $geminiAnswer;
        }

        Log::info('=== DUAL AI COMPARISON COMPLETE ===', [
            'openAiLength' => strlen($openAiAnswer),
            'geminiLength' => strlen($geminiAnswer),
            'combinedLength' => strlen($combinedAnswer),
        ]);

        return $combinedAnswer;
    }

    /**
     * Simple AI knowledge query - Philippines focused.
     */
    protected function getSimpleAiKnowledge(string $provider, string $apiKey, string $query): string
    {
        $this->enforceRateLimit('ai-knowledge');

        // Check if query contains schedule context (user already followed a schedule)
        // Check for both structured markers AND natural language indicators
        $hasScheduleContext = strpos($query, 'CRITICAL SCHEDULE CONTEXT') !== false ||
                              strpos($query, 'DONE_APPLICATIONS') !== false ||
                              strpos($query, 'SCHEDULE_STATUS') !== false;

        // Also detect natural language indicators of completed actions
        $naturalLanguageCompletedActions = preg_match('/nakapag-apply|nag-apply|naglagay|nakapag-spray|nag-spray|na-apply|nagbigay/i', $query) &&
                                           preg_match('/ano.*susunod|what.*next|ano.*dapat.*gawin|anong.*sunod/i', $query);

        $hasCompletedActions = $hasScheduleContext || $naturalLanguageCompletedActions;

        $prompt = "Question (PHILIPPINES CONTEXT): " . $query . "\n\n";

        if ($hasCompletedActions) {
            // User has completed actions or schedule - focus on WHAT'S NEXT
            $prompt .= "═══════════════════════════════════════════════════════════════\n";
            $prompt .= "⚠️  CRITICAL: USER HAS COMPLETED FERTILIZER APPLICATIONS!\n";
            $prompt .= "═══════════════════════════════════════════════════════════════\n\n";
            $prompt .= "The user is asking 'WHAT'S NEXT?' after completing some applications.\n";
            $prompt .= "RULES FOR THIS RESPONSE:\n";
            $prompt .= "1. DO NOT recommend fertilizers that the user said they ALREADY APPLIED\n";
            $prompt .= "2. DO NOT diagnose deficiency for products they already used!\n";
            $prompt .= "   ❌ If user applied Zinc → DO NOT say 'baka may zinc deficiency'\n";
            $prompt .= "   ❌ If user applied Urea → DO NOT say 'kulang sa nitrogen'\n";
            $prompt .= "3. FOCUS on: What is the NEXT step in the schedule?\n";
            $prompt .= "4. If they are in a REST PERIOD between applications:\n";
            $prompt .= "   - Say 'Nasa pahinga period ka, wala pang kailangang gawin'\n";
            $prompt .= "   - Tell them WHEN is the next application (e.g., 'Sa 45-50 DAT ang susunod')\n";
            $prompt .= "5. Recommend NON-FERTILIZER actions: irrigation, pest monitoring, observation\n";
            $prompt .= "6. If asking about 34 DAT after Zn spray at 25 DAT:\n";
            $prompt .= "   - This is REST PERIOD - next major application is at Panicle Initiation (45-50 DAT)\n";
            $prompt .= "   - Say 'Wala pa pong kailangang i-apply, maghintay hanggang 45-50 DAT'\n\n";
        } else {
            // No schedule context - normal product recommendation mode
            $prompt .= "IMPORTANT RULES:\n";
            $prompt .= "- Answer for PHILIPPINES agriculture ONLY\n";
            $prompt .= "- Use BRAND NAMES for fertilizers (e.g., 'Urea' not '46-0-0', 'Complete' not just 'NPK', 'Ammosul' not '21-0-0')\n";
            $prompt .= "- Default land area is 1 HECTARE (ha) - NEVER use 'mu' or other confusing units\n\n";
            $prompt .= "Provide a DIRECT, SPECIFIC answer:\n";
            $prompt .= "- Name the SINGLE BEST product BRAND available in Philippine agri-stores\n";
            $prompt .= "- Include exact dosage per HECTARE (kg/ha) or per liter (ml/L)\n";
            $prompt .= "- Include timing (when to apply)\n";
            $prompt .= "- Keep it brief and focused - no general education\n";
        }

        $systemPrompt = $hasCompletedActions
            ? "You are an expert agricultural technician in the PHILIPPINES. The user has COMPLETED some fertilizer applications and is asking WHAT'S NEXT. " .
              "Your job is to tell them the NEXT STEP in the schedule, NOT diagnose problems with what they already did. " .
              "DO NOT recommend fertilizers they already applied. DO NOT diagnose deficiency for products they used. " .
              "If they're in a REST PERIOD, tell them to wait and when the next application should be. " .
              "Focus on: 1) Confirming they're on track, 2) When is next application, 3) What to monitor while waiting."
            : "You are an expert agricultural technician based in the PHILIPPINES. You ONLY recommend products available in Philippine agricultural stores. " .
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
                    $content = $result['choices'][0]['message']['content'] ?? '';

                    // Track OpenAI token usage
                    $usage = $result['usage'] ?? [];
                    $inputTokens = $usage['prompt_tokens'] ?? 0;
                    $outputTokens = $usage['completion_tokens'] ?? 0;
                    if ($inputTokens > 0 || $outputTokens > 0) {
                        $this->trackTokenUsage('openai', 'ai_knowledge', $inputTokens, $outputTokens, 'gpt-4o-mini');
                    }

                    return $content;
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
                    $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                    // Track Gemini token usage
                    $usageMetadata = $data['usageMetadata'] ?? [];
                    $inputTokens = $usageMetadata['promptTokenCount'] ?? 0;
                    $outputTokens = $usageMetadata['candidatesTokenCount'] ?? 0;
                    if ($inputTokens > 0 || $outputTokens > 0) {
                        $this->trackTokenUsage('gemini', 'ai_knowledge', $inputTokens, $outputTokens, 'gemini-2.0-flash');
                    }

                    return $content;
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
        string $imageAnalysis,
        string $precomputedScheduleAnalysis = '' // Use schedule analysis already computed in main flow
    ): string {
        // Use the precomputed schedule analysis (computed BEFORE AI Knowledge call)
        // This ensures AI Knowledge already knows what's done when it generates its response
        $scheduleAnalysis = $precomputedScheduleAnalysis;
        $hasUserDocument = !empty($scheduleAnalysis);

        // Check if AI Knowledge already has a comprehensive, well-structured response
        $hasComprehensiveAiKnowledge = !empty($aiKnowledge) && $this->isComprehensiveStructuredResponse($aiKnowledge);

        if ($hasComprehensiveAiKnowledge) {
            $this->logFlowStep('Comprehensive AI Knowledge Detected', 'AI Knowledge has structured response - will preserve structure');
            Log::info('=== COMPREHENSIVE AI KNOWLEDGE - Will preserve structure ===', [
                'aiKnowledgeLength' => strlen($aiKnowledge),
            ]);
        }

        // Build combination prompt - STRUCTURED APPROACH
        $prompt = "Ikaw ay isang BATANG FILIPINO agricultural technician. SUNDIN ANG STRUCTURED FORMAT NA ITO:\n\n";

        $prompt .= "════════════════════════════════════════════════════════════════\n";
        $prompt .= "         ⚠️ CRITICAL: RAG/KNOWLEDGE BASE IS PRIMARY SOURCE      \n";
        $prompt .= "════════════════════════════════════════════════════════════════\n\n";

        $prompt .= "BAGO KA SUMAGOT, BASAHIN MUNA ANG KNOWLEDGE BASE RESULT!\n";
        $prompt .= "Kung ang KNOWLEDGE BASE ay may DIREKTANG SAGOT sa tanong ng user:\n";
        $prompt .= "→ GAMITIN ITO bilang PRIMARY ANSWER\n";
        $prompt .= "→ HUWAG mag-imbento ng ibang sagot o problema\n";
        $prompt .= "→ HUWAG kontrahin ang sinabi ng Knowledge Base\n\n";

        $prompt .= "HALIMBAWA:\n";
        $prompt .= "- Kung Knowledge Base sabi: 'Pahinga sa abono, wala pang kailangang i-apply'\n";
        $prompt .= "  → Sabihin mo rin: 'Wala pa pong kailangang gawin ngayon'\n";
        $prompt .= "  → HUWAG mag-recommend ng bagong fertilizer o mag-diagnose ng problema\n\n";

        $prompt .= "- Kung Knowledge Base sabi: 'Susunod na application ay sa 45-50 DAT'\n";
        $prompt .= "  → Sabihin mo rin ang parehong timeline\n";
        $prompt .= "  → HUWAG gumawa ng sariling timeline\n\n";

        $prompt .= "════════════════════════════════════════════════════════════════\n";
        $prompt .= "         ⚠️ HUWAG MAG-IMBENTO - PROBLEMA O HEALTH STATUS!         \n";
        $prompt .= "════════════════════════════════════════════════════════════════\n\n";

        $prompt .= "⚠️ CRITICAL: HUWAG FABRICATE/ASSUME PLANT HEALTH STATUS!\n";
        $prompt .= "KUNG WALANG LARAWAN AT WALANG SINABI ANG USER TUNGKOL SA HEALTH:\n";
        $prompt .= "❌ BAWAL: 'Mukhang malusog po ang inyong pananim' - HINDI MO NAKITA!\n";
        $prompt .= "❌ BAWAL: 'Walang nakitang sintomas ng sakit' - WALA KANG NAKITA!\n";
        $prompt .= "❌ BAWAL: 'Nasa tamang kondisyon ang inyong palay' - ASSUMPTION LANG!\n";
        $prompt .= "✅ TAMANG GAWIN: Sagutin lang ang tanong ng user nang direkta!\n\n";

        $prompt .= "HALIMBAWA - TANONG TUNGKOL SA PRODUCT/TIMING:\n";
        $prompt .= "User: 'Pwede ba mag-spray ng Nativo sa 70 DAT?'\n";
        $prompt .= "❌ MALI: 'Mukhang malusog ang pananim ninyo... pwede mag-spray...'\n";
        $prompt .= "✅ TAMA: 'Oo po, pwede mag-spray ng Nativo sa 70 DAT. Sa yugtong ito...'\n\n";

        $prompt .= "ANALYZE MUNA ANG USER MESSAGE:\n";
        $prompt .= "1. Kung TANONG LANG tungkol sa product/timing = SAGUTIN DIREKTA, huwag mag-assume ng health\n";
        $prompt .= "2. Kung user sabi 'nakapag-apply NA ako ng X' = TAPOS NA ang X, hindi problema\n";
        $prompt .= "3. Kung user asks 'ano ang SUSUNOD?' = wants NEXT STEP, hindi troubleshooting\n";
        $prompt .= "4. Kung user completed their schedule = CONGRATULATE, hindi mag-diagnose ng deficiency\n\n";

        $prompt .= "BAWAL:\n";
        $prompt .= "❌ HUWAG mag-assume na 'malusog' ang pananim kung WALANG EBIDENSYA (larawan/sinabi ng user)\n";
        $prompt .= "❌ HUWAG mag-assume ng VARIETY (e.g., NK6414, Jackpot 102) kung HINDI sinabi ng user!\n";
        $prompt .= "❌ HUWAG mag-assume ng DAP (e.g., 100 DAP, 75 DAP) kung HINDI sinabi ng user!\n";
        $prompt .= "❌ Kung user na-apply na ang Zinc → HUWAG sabihin 'baka may zinc deficiency'\n";
        $prompt .= "❌ Kung user nasa rest period → HUWAG mag-recommend ng fertilizer\n";
        $prompt .= "❌ Kung walang symptom na binanggit → HUWAG mag-diagnose ng sakit\n";
        $prompt .= "❌ Kung Knowledge Base sabi 'wala pang gawin' → HUWAG gumawa ng action items\n\n";

        $prompt .= "════════════════════════════════════════════════════════════════\n";
        $prompt .= "     MANDATORY RESPONSE FORMAT - CONVERSATIONAL WITH SECTIONS    \n";
        $prompt .= "════════════════════════════════════════════════════════════════\n\n";

        $prompt .= "HUWAG gumamit ng PART 1/2/3/4 o (1), (2), (3) numbering!\n";
        $prompt .= "Sumagot ng CONVERSATIONAL pero COMPREHENSIVE.\n\n";

        $prompt .= "STRUCTURE NG SAGOT (NATURAL FLOW - parang totoong technician!):\n\n";

        $prompt .= "**SECTION 1: OBSERVE MUNA** (Hindi verdict kaagad!)\n";
        $prompt .= "⚠️ MAHALAGA: KUNG WALANG LARAWAN, HUWAG SABIHIN 'NAKIKITA KO'!\n";
        $prompt .= "KUNG MAY LARAWAN: \"Nakikita ko po sa larawan ang inyong palay na nasa [stage] na...\"\n";
        $prompt .= "KUNG WALANG LARAWAN PERO sinabi ng user ang detalye: \"Base sa sinabi ninyo, ang inyong [crop] sa [DAP] ay...\"\n";
        $prompt .= "KUNG WALANG LARAWAN AT WALANG DETALYE: MAGTANONG MUNA - \"Para matulungan ko kayo, anong tanim at ilang DAP na po?\"\n";
        $prompt .= "❌ BAWAL: \"Nakikita ko po\" kung WALANG larawan!\n";
        $prompt .= "❌ BAWAL: Mag-assume ng variety o DAP kung HINDI sinabi ng user!\n\n";

        $prompt .= "**SECTION 2: NATURAL ASSESSMENT** (conversational, hindi sumisigaw)\n";
        $prompt .= "❌ HUWAG: \"NORMAL po ito!\" (robotic)\n";
        $prompt .= "⚠️ KUNG MAY LARAWAN O SINABI NG USER ANG KONDISYON:\n";
        $prompt .= "✅ GAMITIN: \"Sa yugtong ito, ang nakikita ko ay normal na bahagi ng...\"\n";
        $prompt .= "✅ O KAYA: \"Mukhang malusog po ang inyong pananim dahil...\"\n";
        $prompt .= "⚠️ KUNG WALANG LARAWAN AT TANONG LANG TUNGKOL SA PRODUCT/TIMING:\n";
        $prompt .= "✅ GAMITIN: \"Sa 70 DAT, ang palay ay nasa booting-heading stage...\"\n";
        $prompt .= "✅ DIREKTANG SAGOT: \"Oo po, pwede mag-spray ng [product] sa [DAT]...\"\n";
        $prompt .= "❌ BAWAL: \"Mukhang malusog ang pananim ninyo...\" (HINDI MO NAKITA!)\n";
        $prompt .= "Gamitin ang emoji 🌾 para sa positive observations KUNG MAY EBIDENSYA.\n\n";

        $prompt .= "**SECTION 3: EXPLANATION - BAKIT NORMAL/PROBLEM** (REQUIRED)\n";
        $prompt .= "I-explain ng DETALYADO:\n";
        $prompt .= "- Anong stage sila ngayon at ano ang expected\n";
        $prompt .= "- Kung may NAKITANG PROBLEMA sa larawan - i-explain at mag-recommend ng solusyon!\n";
        $prompt .= "- Paliwanag ang science kung bakit normal/problem\n";
        $prompt .= "- Kung multiple questions ang user, SAGUTIN LAHAT with explanation\n\n";

        $prompt .= "**SECTION 4: ANO ANG GAWIN NGAYON** (REQUIRED)\n";
        $prompt .= "Gamitin ang bullet points (•) at **bold** para sa key actions:\n";
        $prompt .= "• **Patuloy na mag-monitor ng tubig** - [explanation]\n";
        $prompt .= "• **Bantayan ang peste at sakit** - [specific pests to watch]\n";
        $prompt .= "⚠️ HUWAG sabihing 'kumpleto na ang schedule' MALIBAN kung user mismo ang nagsabi ng schedule niya!\n\n";

        $prompt .= "**SECTION 5: BANTAYAN / FOLLOW-UP** (REQUIRED)\n";
        $prompt .= "\"Kung makakita ka ng [specific symptoms], mag-send ka ng litrato para ma-check ko.\"\n\n";

        $prompt .= "════════════════════════════════════════════════════════════════\n";
        $prompt .= "HALIMBAWA NG OUTPUT (NATURAL FLOW):\n";
        $prompt .= "════════════════════════════════════════════════════════════════\n\n";

        $prompt .= "**HALIMBAWA KUNG MAY LARAWAN (pero HINDI sinabi ng user ang DAP):**\n";
        $prompt .= "Nakikita ko po sa larawan ang inyong palay na Jackpot 102 na nasa REPRODUCTIVE/FLOWERING STAGE na - may mga uhay na rin lumalabas. 🌾\n\n";

        $prompt .= "Batay sa mga larawan, mukhang malusog po ang inyong pananim. Ang mga dahon ay may magandang berdeng kulay na nagpapahiwatig ng sapat na chlorophyll, at walang nakikitang sintomas ng sakit o kakulangan sa sustansya.\n\n";

        $prompt .= "Ilang araw na po ba mula nang itanim ito? (para mas accurate ang recommendations ko)\n\n";

        $prompt .= "**HALIMBAWA KUNG WALANG LARAWAN PERO sinabi ng user ang variety AT DAP:**\n";
        $prompt .= "User: 'kumusta ang NK6414 ko sa 100 DAP?'\n";
        $prompt .= "Response: Ang inyong NK6414 na mais sa 100 DAP ay nasa maturity stage na - malapit na sa ani! 🌽\n\n";
        $prompt .= "Sa yugtong ito, normal na nang hindi na kailangan ng dagdag na patubig maliban kung sobrang tuyo ang panahon.\n\n";

        $prompt .= "**HALIMBAWA KUNG WALANG LARAWAN AT WALANG DETALYE (VAGUE):**\n";
        $prompt .= "User: 'pwede mo ba tignan kung okay ito?'\n";
        $prompt .= "Response: Para matulungan ko kayo, kailangan ko ng kaunting detalye:\n";
        $prompt .= "- Anong tanim po? (mais, palay, etc.)\n";
        $prompt .= "- Pwede po bang mag-send ng larawan?\n";
        $prompt .= "- Ilang araw na mula nang itanim (DAP)?\n\n";

        $prompt .= "**NOTE:** HUWAG mag-assume ng specific DAP mula sa larawan! Kung kailangan ng DAP para sa recommendation, itanong mo sa user.\n\n";

        $prompt .= "**HALIMBAWA KUNG MAY NAKITANG YELLOWING SA LARAWAN:**\n";
        $prompt .= "Nakikita ko po sa larawan na may pagdilaw (yellowing) sa mga dahon ng inyong palay. 🌾\n\n";
        $prompt .= "Base sa nakikita ko, ang mga tanim ay nasa VEGETATIVE STAGE pa (wala pang uhay) at ang pagdilaw ay posibleng sanhi ng:\n";
        $prompt .= "- **Nitrogen deficiency** - kakulangan sa sustansya\n";
        $prompt .= "- **Zinc deficiency** - lalo na kung interveinal ang yellowing\n\n";
        $prompt .= "Ang maipapayo ko po:\n";
        $prompt .= "• **Mag-apply ng Urea (46-0-0)** - 1-2 bags per hectare kung nitrogen deficiency\n";
        $prompt .= "• **Mag-foliar spray ng Zinc** - kung may pagdilaw sa gitna ng dahon\n";
        $prompt .= "• **I-check ang irrigation** - baka kulang sa tubig\n\n";
        $prompt .= "Ilang araw na po ba mula nang itanim ito? Para mas accurate ang recommendations ko.\n\n";

        $prompt .= "⚠️ KRITIKAL: Kung may NAKITANG problema (yellowing, spots, etc.), LAGING mag-recommend ng solusyon!\n";
        $prompt .= "❌ HUWAG sabihing 'schedule complete' kung user HINDI nagsabi ng schedule\n";
        $prompt .= "❌ HUWAG i-ignore ang yellowing at sabihing 'normal lang'\n\n";

        $prompt .= "════════════════════════════════════════════════════════════════\n\n";

        // Add schedule context if available
        if (!empty($scheduleAnalysis)) {
            $prompt .= "📋 SCHEDULE ANALYSIS (Para malaman kung ano na ang TAPOS NA):\n";
            $prompt .= "────────────────────────────────────────────────────────────\n";
            $prompt .= $scheduleAnalysis . "\n\n";

            $prompt .= "⚠️ CRITICAL RULES BASE SA SCHEDULE:\n";
            $prompt .= "- Kung isang application ay nasa DONE_APPLICATIONS, HUWAG MO ITONG I-RECOMMEND!\n";
            $prompt .= "- Kung SCHEDULE_STATUS = COMPLETE, sabihing 'Tapos mo na lahat ng applications'\n";
            $prompt .= "- Focus sa CURRENT/FUTURE actions, hindi sa past applications\n\n";
        }

        $prompt .= "USER MESSAGE:\n";
        $prompt .= "────────────────────────────────────────────────────────────\n";
        $prompt .= $this->userMessage . "\n\n";

        // Add source materials
        if (!empty($imageAnalysis)) {
            $prompt .= "🖼️ IMAGE ANALYSIS (gamitin ito para sa Part 1):\n";
            $prompt .= "────────────────────────────────────────────────────────────\n";
            $prompt .= $imageAnalysis . "\n\n";

            // Add disclaimer for photo-based recommendations
            $prompt .= "════════════════════════════════════════════════════════════════\n";
            $prompt .= "⚠️ PHOTO-BASED RECOMMENDATION DISCLAIMER (REQUIRED!)            \n";
            $prompt .= "════════════════════════════════════════════════════════════════\n\n";

            $prompt .= "**KAPAG NAGRERECOMMEND BATAY SA LARAWAN, PALAGING ISAMA ANG DISCLAIMER:**\n\n";

            $prompt .= "Ang recommendation ay base lamang sa nakikita sa larawan. Maaaring:\n";
            $prompt .= "1. Hindi gaanong klaro ang larawan para sa accurate diagnosis\n";
            $prompt .= "2. Nag-apply na kayo ng treatment/fertilizer na hindi ko alam\n";
            $prompt .= "3. May iba pang factors sa actual na sitwasyon\n\n";

            $prompt .= "**SAMPLE DISCLAIMER TO ADD (CHOOSE APPROPRIATE VERSION):**\n\n";

            $prompt .= "For DEFICIENCY recommendations:\n";
            $prompt .= "'📝 PAALALA: Ang recommendation na ito ay batay lamang sa nakikita ko sa larawan. ";
            $prompt .= "Kung nag-apply na po kayo ng [produkto/fertilizer] kamakailan, ";
            $prompt .= "maaaring hintayin muna ang resulta nito bago mag-apply ng iba pa. ";
            $prompt .= "I-share ninyo ang inyong current fertilizer schedule kung mayroon para mas accurate ang advice ko.'\n\n";

            $prompt .= "For PEST/DISEASE recommendations:\n";
            $prompt .= "'📝 PAALALA: Base ito sa nakikita sa larawan - kung nag-spray na po kayo ng gamot ";
            $prompt .= "kamakailan, obserbahan muna kung may improvement bago mag-apply ulit. ";
            $prompt .= "Mas accurate ang diagnosis kung may dagdag na impormasyon tungkol sa inyong ginagawang treatment.'\n\n";

            $prompt .= "For GENERAL assessment:\n";
            $prompt .= "'📝 PAALALA: Ang assessment na ito ay batay sa larawan lamang - ";
            $prompt .= "maaaring may iba pang factors sa actual na sitwasyon ng inyong taniman ";
            $prompt .= "na hindi nakikita sa photo. Kung may current treatment plan kayo, ";
            $prompt .= "please share para mas personalized ang advice.'\n\n";

            $prompt .= "**RULES FOR DISCLAIMER:**\n";
            $prompt .= "- ALWAYS include at the END of your recommendation section\n";
            $prompt .= "- Keep it SHORT but informative (2-3 sentences max)\n";
            $prompt .= "- SUGGEST sharing their current treatment/schedule\n";
            $prompt .= "- Be ENCOURAGING, not discouraging of their question\n\n";
        }

        if (!empty($ragResult) && strlen($ragResult) > 50) {
            $prompt .= "📚 KNOWLEDGE BASE:\n";
            $prompt .= "────────────────────────────────────────────────────────────\n";
            $prompt .= $ragResult . "\n\n";
        }

        if (!empty($webResult)) {
            $prompt .= "🌐 WEB SEARCH RESULTS:\n";
            $prompt .= "────────────────────────────────────────────────────────────\n";
            $prompt .= $webResult . "\n\n";
        }

        if (!empty($aiKnowledge)) {
            $prompt .= "🤖 AI KNOWLEDGE:\n";
            $prompt .= "────────────────────────────────────────────────────────────\n";
            $prompt .= $aiKnowledge . "\n\n";
        }

        $prompt .= "════════════════════════════════════════════════════════════════\n";
        $prompt .= "⚠️ CRITICAL: MINIMUM RESPONSE LENGTH & COMPLETE SCHEDULE RULES  \n";
        $prompt .= "════════════════════════════════════════════════════════════════\n\n";

        $prompt .= "**MINIMUM RESPONSE LENGTH:**\n";
        $prompt .= "- Para sa complex questions (multiple concerns): MINIMUM 1500 characters!\n";
        $prompt .= "- Kung ang user may maraming tanong, SAGUTIN LAHAT with explanation\n";
        $prompt .= "- HUWAG i-shorten ang sagot - mas mabuti ang comprehensive kaysa brief\n\n";

        $prompt .= "**KAPAG SCHEDULE_STATUS = COMPLETE:**\n";
        $prompt .= "⚠️ BAWAL SABIHIN: 'Magpatuloy sa pagsunod sa iyong fertilizer program'\n";
        $prompt .= "⚠️ BAWAL SABIHIN: 'Mag-apply ng additional fertilizer'\n";
        $prompt .= "✅ TAMANG SABIHIN: 'Huwag na mag-apply ng bagong fertilizer - kumpleto na ang schedule mo'\n";
        $prompt .= "✅ TAMANG SABIHIN: 'Tapos mo na lahat ng applications, walang kailangan idagdag'\n";
        $prompt .= "✅ Focus ONLY on: monitoring tubig, peste, at sakit\n\n";

        $prompt .= "════════════════════════════════════════════════════════════════\n";
        $prompt .= "                    ADDITIONAL RULES                            \n";
        $prompt .= "════════════════════════════════════════════════════════════════\n\n";

        $prompt .= "**FERTILIZER NAMING:**\n";
        $prompt .= "- Gamitin ang BRAND NAMES: Urea, Complete 14-14-14, Ammosul, MOP\n";
        $prompt .= "- Land area default: 1 HECTARE\n\n";

        $prompt .= "════════════════════════════════════════════════════════════════\n";
        $prompt .= "⚠️ CRITICAL: PRODUCT RECOMMENDATION RESTRICTIONS                \n";
        $prompt .= "════════════════════════════════════════════════════════════════\n\n";

        $prompt .= "**PRODUKTO NA PWEDE LANG I-RECOMMEND:**\n";
        $prompt .= "1. PRODUCTS FROM KNOWLEDGE BASE (RAG) - PRIORITY!\n";
        $prompt .= "   - Innosolve 40-5, at iba pang nasa knowledge base\n";
        $prompt .= "   - Kung may product sa Knowledge Base na match, GAMITIN ITO!\n\n";

        $prompt .= "2. COMMON CROP FERTILIZERS (General Knowledge):\n";
        $prompt .= "   - Urea (46-0-0) - nitrogen fertilizer\n";
        $prompt .= "   - Complete Fertilizer (14-14-14) - balanced NPK\n";
        $prompt .= "   - Ammosul / Ammonium Sulfate (21-0-0) - nitrogen + sulfur\n";
        $prompt .= "   - MOP / Muriate of Potash (0-0-60) - potassium\n";
        $prompt .= "   - DAP / Diammonium Phosphate (18-46-0) - phosphorus + nitrogen\n";
        $prompt .= "   - Solophos / Superphosphate (0-18-0) - phosphorus\n";
        $prompt .= "   - Zinc Sulfate (foliar) - zinc micronutrient\n";
        $prompt .= "   - Boron (foliar) - boron micronutrient\n";
        $prompt .= "   - Organic fertilizers (vermicompost, chicken manure, etc.)\n\n";

        $prompt .= "**❌ BAWAL I-RECOMMEND (FOR ORNAMENTAL PLANTS):**\n";
        $prompt .= "- Osmocote (slow-release for ornamental/potted plants)\n";
        $prompt .= "- Garden-specific products (for flowers, landscaping)\n";
        $prompt .= "- Potted plant fertilizers\n";
        $prompt .= "- Any product primarily marketed for ornamental plants\n\n";

        $prompt .= "**PAANO MAG-RECOMMEND NG PRODUKTO:**\n";
        $prompt .= "1. KUNG may match sa KNOWLEDGE BASE → GAMITIN ITO, may dosage at application method!\n";
        $prompt .= "2. KUNG WALA sa Knowledge Base → GAMITIN ang common crop fertilizers sa itaas\n";
        $prompt .= "3. HUWAG mag-recommend ng branded products na hindi mo alam kung available sa Pilipinas\n";
        $prompt .= "4. FOCUS LANG SA CROPS (palay, mais, gulay) - HINDI ornamental plants!\n\n";

        $prompt .= "**HALIMBAWA NG TAMANG RECOMMENDATION:**\n";
        $prompt .= "Para sa leaching prevention:\n";
        $prompt .= "✅ TAMA: 'Gumamit ng Innosolve 40-5 na isasama sa urea fertilizer...'\n";
        $prompt .= "✅ TAMA: 'Gamitin ang split application ng urea at complete fertilizer...'\n";
        $prompt .= "✅ TAMA: 'I-apply ang ammonium-based fertilizers tulad ng Ammosul...'\n";
        $prompt .= "❌ MALI: 'Gumamit ng Osmocote 14-14-14...' (para sa ornamental plants ito!)\n";
        $prompt .= "❌ MALI: 'Gumamit ng slow-release fertilizers tulad ng Osmocote...' (hindi para sa crops!)\n\n";

        $prompt .= "════════════════════════════════════════════════════════════════\n";
        $prompt .= "⚠️ CRITICAL: PROPER TABLE FORMATTING & DAT/DAP USAGE             \n";
        $prompt .= "════════════════════════════════════════════════════════════════\n\n";

        $prompt .= "**MARKDOWN TABLE FORMAT (REQUIRED!):**\n";
        $prompt .= "Kapag gumagawa ng table, SUNDIN ANG TAMANG FORMAT:\n\n";

        $prompt .= "✅ TAMANG FORMAT:\n";
        $prompt .= "| Header 1 | Header 2 | Header 3 |\n";
        $prompt .= "|----------|----------|----------|\n";
        $prompt .= "| Data 1   | Data 2   | Data 3   |\n";
        $prompt .= "| Data 4   | Data 5   | Data 6   |\n\n";

        $prompt .= "❌ MALING FORMAT (HUWAG GAWIN):\n";
        $prompt .= "| Header 1 | Header 2 | Header 3\n";  // Missing last pipe
        $prompt .= "---------------------------------------\n";  // Wrong separator
        $prompt .= "Data 1Data 2Data 3\n\n";  // Missing pipes

        $prompt .= "RULES PARA SA TABLES:\n";
        $prompt .= "1. LAHAT ng row DAPAT may | sa simula AT dulo\n";
        $prompt .= "2. DAPAT may separator row na |---|---|---| pagkatapos ng header\n";
        $prompt .= "3. CONSISTENT ang bilang ng columns sa lahat ng rows\n";
        $prompt .= "4. HUWAG lagyan ng extra text bago o pagkatapos ng table header row\n\n";

        $prompt .= "**GROWTH STAGE → DAT/DAP/DAS CONVERSION (REQUIRED!):**\n";
        $prompt .= "⚠️ HUWAG gumamit ng technical growth stage codes (VT, R1, R2, V6) nang walang DAT!\n";
        $prompt .= "Filipino farmers understand DAT/DAP/DAS better than growth stage codes.\n\n";

        $prompt .= "PALAGING i-convert ang growth stages to DAT/DAP:\n";
        $prompt .= "MAIS (Corn):\n";
        $prompt .= "- VE (Emergence) = 0-7 DAP\n";
        $prompt .= "- V3-V6 (Early Vegetative) = 10-25 DAP\n";
        $prompt .= "- V12 (Late Vegetative) = 35-45 DAP\n";
        $prompt .= "- VT (Tasseling) = 50-55 DAP\n";
        $prompt .= "- R1 (Silking) = 55-60 DAP\n";
        $prompt .= "- R2 (Blister) = 60-70 DAP\n";
        $prompt .= "- R3 (Milk) = 70-80 DAP\n";
        $prompt .= "- R4 (Dough) = 80-90 DAP\n";
        $prompt .= "- R5 (Dent) = 90-100 DAP\n";
        $prompt .= "- R6 (Maturity) = 100-120 DAP\n\n";

        $prompt .= "PALAY (Rice):\n";
        $prompt .= "- Seedling = 0-21 DAT (Days After Transplanting)\n";
        $prompt .= "- Tillering = 21-45 DAT\n";
        $prompt .= "- Panicle Initiation = 45-55 DAT\n";
        $prompt .= "- Booting = 55-65 DAT\n";
        $prompt .= "- Heading = 65-75 DAT\n";
        $prompt .= "- Flowering = 75-85 DAT\n";
        $prompt .= "- Grain Filling = 85-100 DAT\n";
        $prompt .= "- Maturity = 100-120 DAT\n\n";

        $prompt .= "HALIMBAWA NG TAMANG TABLE (IRRIGATION SCHEDULE):\n";
        $prompt .= "| Yugto | DAP | Pangangailangan ng Tubig | Aksyon |\n";
        $prompt .= "|-------|-----|--------------------------|--------|\n";
        $prompt .= "| Pagtatanim hanggang Emergence | 0-7 DAP | Mataas | Diligan agad |\n";
        $prompt .= "| Early Vegetative | 10-25 DAP | Katamtaman | Diligan kada 5-7 araw |\n";
        $prompt .= "| Late Vegetative | 35-45 DAP | Mataas | Diligan kada 3-5 araw |\n";
        $prompt .= "| Tasseling/Silking | 50-60 DAP | Pinaka-kritikal | Huwag hayaang matuyo |\n";
        $prompt .= "| Grain Filling | 70-90 DAP | Mataas | Regular na patubig |\n";
        $prompt .= "| Maturity | 100-120 DAP | Mababa | Bawasan, ihinto 1-2 linggo bago ani |\n\n";

        $prompt .= "════════════════════════════════════════════════════════════════\n";
        $prompt .= "⚠️ SUPER CRITICAL: PRIORITIZE USER'S STATED CONCERN!             \n";
        $prompt .= "════════════════════════════════════════════════════════════════\n\n";

        $prompt .= "**ANG USER'S STATED CONCERN ANG PRIMARY - HINDI ANG IMAGE OBSERVATIONS!**\n\n";

        $prompt .= "RULE: Kung ang user ay nagsabi ng SPECIFIC na problema, SAGUTIN MUNA ITO!\n";
        $prompt .= "- Image observations = SECONDARY INFORMATION lamang\n";
        $prompt .= "- HUWAG i-dismiss ang user's concern dahil lang 'normal' ang nakikita sa images\n\n";

        $prompt .= "**EXAMPLE NG MALI (HUWAG GAWIN ITO!):**\n";
        $prompt .= "User says: 'Nahihirapan lumabas yung tanim ko' (MAIN CONCERN = difficulty emerging)\n";
        $prompt .= "❌ MALI: Focus sa 'pagdilaw ng dahon' from images, ignore 'nahihirapan lumabas'\n";
        $prompt .= "❌ MALI: 'Normal lang ang pagdilaw...' without addressing emergence concern\n\n";

        $prompt .= "**EXAMPLE NG TAMA (GAWIN ITO!):**\n";
        $prompt .= "User says: 'Nahihirapan lumabas yung tanim ko'\n";
        $prompt .= "✅ TAMA: FIRST address 'nahihirapan lumabas':\n";
        $prompt .= "   'Tungkol sa concern ninyo na nahihirapan lumabas ang tanim - sa [X] DAP,\n";
        $prompt .= "    normal pa ito dahil... / may delay ito dahil...'\n";
        $prompt .= "✅ THEN (optional): 'Bilang secondary observation sa images, nakita ko rin\n";
        $prompt .= "   ang bahagyang pagdilaw, na normal naman sa yugtong ito...'\n\n";

        $prompt .= "**KEYWORDS NA DAPAT I-ADDRESS AS PRIMARY CONCERN:**\n";
        $prompt .= "- 'nahihirapan lumabas' = difficulty emerging/heading → ADDRESS DIRECTLY\n";
        $prompt .= "- 'hindi lumalaki' = not growing → ADDRESS DIRECTLY\n";
        $prompt .= "- 'may delay' = delayed → ADDRESS DIRECTLY\n";
        $prompt .= "- 'mabagal' = slow growth → ADDRESS DIRECTLY\n";
        $prompt .= "- 'nahihirapan tumubo' = struggling to grow → ADDRESS DIRECTLY\n";
        $prompt .= "- 'problema ba ito' = is this a problem → ANSWER DIRECTLY (Yes/No + WHY)\n";
        $prompt .= "- 'on track ba' = on track question → ANSWER DIRECTLY (Yes/No + WHY)\n\n";

        $prompt .= "**RESPONSE STRUCTURE KAPAG MAY USER CONCERN + IMAGES:**\n";
        $prompt .= "1. FIRST: Address user's PRIMARY stated concern directly\n";
        $prompt .= "2. SECOND: Validate/support with image observations (if relevant)\n";
        $prompt .= "3. THIRD: Add any secondary observations from images\n";
        $prompt .= "4. FOURTH: Recommendations based on BOTH concern + observations\n\n";

        $prompt .= "**ANSWERING USER CONCERNS (CRITICAL - ALWAYS EXPLAIN WHY!):**\n";
        $prompt .= "- Kung nagtatanong kung NORMAL o PROBLEMA:\n";
        $prompt .= "  1. Sagutin ng DIREKTA: 'Normal/On-track' o 'May problema'\n";
        $prompt .= "  2. PALAGING I-EXPLAIN ANG DAHILAN: 'Normal ito dahil sa [X] stage, [Y] timeline...'\n";
        $prompt .= "  3. Kung may schedule, banggitin kung ano na ang TAPOS NA at kung KUMPLETO na ba\n\n";
        $prompt .= "- Kung nagtatanong kung ON-TRACK sa schedule:\n";
        $prompt .= "  1. Sabihin kung LAHAT ba ng applications ay tapos na\n";
        $prompt .= "  2. I-explain: 'Tapos mo na ang [enumeration ng applications] kaya on-track ka'\n";
        $prompt .= "  3. Banggitin kung ano ang EXPECTED na mangyari sa current stage\n\n";
        $prompt .= "- Kung may kahirapan sa paglabas ng uhay/tanim (emergence/heading difficulty):\n";
        $prompt .= "  ⚠️ ITO ANG USER'S PRIMARY CONCERN - ADDRESS MUNA ITO!\n";
        $prompt .= "  1. FIRST: 'Tungkol sa concern ninyo na nahihirapan lumabas ang tanim...'\n";
        $prompt .= "  2. Explain kung NORMAL o DELAYED: 'Sa [variety] at [X] DAP, ang heading ay...'\n";
        $prompt .= "  3. Give specific timeline: 'Expected ang full emergence sa [X-Y] DAP...'\n";
        $prompt .= "  4. THEN (if applicable): 'Bilang additional observation sa images...'\n\n";

        $prompt .= "**KAPAG MAY SCHEDULE AT SINUNOD ITO:**\n";
        $prompt .= "- HUWAG mag-recommend ng fertilizer na TAPOS NA (nasa DONE_APPLICATIONS)\n";
        $prompt .= "- HUWAG mag-diagnose ng deficiency para sa produkto na NA-APPLY NA!\n";
        $prompt .= "  ❌ Kung nag-Zinc spray na siya → BAWAL sabihin 'baka may zinc deficiency'\n";
        $prompt .= "  ❌ Kung nag-Urea na siya → BAWAL sabihin 'kulang sa nitrogen'\n";
        $prompt .= "- Kung nasa PAHINGA/REST PERIOD:\n";
        $prompt .= "  ✅ Sabihin: 'Nasa pahinga period ka ngayon, walang kailangang gawin'\n";
        $prompt .= "  ✅ Sabihin: 'Ang susunod na application ay sa [X] DAT'\n";
        $prompt .= "  ✅ I-remind: 'Mag-monitor lang ng tubig at peste'\n";
        $prompt .= "- Reassure kung on-track at EXPLAIN BAKIT:\n";
        $prompt .= "  Example: 'ON-TRACK ka dahil kumpleto mo nang na-apply ang lahat ng fertilizer hanggang\n";
        $prompt .= "  ngayon. Nasa pahinga period ka - ang susunod na application ay sa Panicle Initiation\n";
        $prompt .= "  (45-50 DAT). Maghintay ka lang at mag-monitor ng kondisyon ng palay.'\n\n";

        $prompt .= "**IKAW ANG AI TECHNICIAN - HUWAG MAG-REFER SA IBA:**\n";
        $prompt .= "- HUWAG sabihin 'kumonsulta sa local technician' o 'pumunta sa agricultural office'\n";
        $prompt .= "- IKAW ANG technician na kausap nila - ALAM mo ang sagot\n";
        $prompt .= "- Kung kailangan ng follow-up: 'I-message mo ulit ako kung may tanong pa' o 'Mag-send ka ng bagong litrato'\n\n";

        // CRITICAL: Grain filling stage nitrogen restriction
        $prompt .= "════════════════════════════════════════════════════════════════\n";
        $prompt .= "⚠️ CRITICAL: GROWTH STAGE AWARE NITROGEN RECOMMENDATION         \n";
        $prompt .= "════════════════════════════════════════════════════════════════\n\n";

        $prompt .= "**KUNG MAY LARAWAN AT NAKIKITA MO:**\n";
        $prompt .= "- Uhay na NAKAYUKO (bending/drooping panicles)\n";
        $prompt .= "- Butil na may laman na (filled or filling grains)\n";
        $prompt .= "- Lower leaves na dilaw\n\n";

        $prompt .= "**ITO AY MILKING/GRAIN FILLING STAGE! Natural na sagot:**\n";
        $prompt .= "KUNG MAY LARAWAN: 'Nakikita ko po na nasa grain filling stage na ang inyong palay - nakayuko na ang\n";
        $prompt .= "uhay dahil bumibigat na ang mga butil...'\n";
        $prompt .= "KUNG WALANG LARAWAN: 'Ang inyong palay sa [X DAP] ay nasa grain filling stage na - sa yugtong ito,\n";
        $prompt .= "normal po ang bahagyang pagdilaw ng lower leaves...'\n";
        $prompt .= "Sa yugtong ito, normal po ang bahagyang pagdilaw ng lower leaves - ito ay dahil naglilipat\n";
        $prompt .= "ang nutrients mula sa dahon papunta sa butil. Magandang senyales ito na magiging mabigat ang inyong ani!'\n\n";

        $prompt .= "**❌ BAWAL I-RECOMMEND SA GRAIN FILLING STAGE:**\n";
        $prompt .= "- Urea (46-0-0)\n";
        $prompt .= "- Ammonium Sulfate (21-0-0)\n";
        $prompt .= "- 'Magdagdag ng nitrogen'\n";
        $prompt .= "- 'Kulang sa nitrogen'\n";
        $prompt .= "→ Nitrogen sa late stage = DELAY + LODGING + WASTED MONEY!\n\n";

        $prompt .= "**✅ PWEDENG I-RECOMMEND SA GRAIN FILLING STAGE:**\n";
        $prompt .= "- Potassium (MOP/0-0-60) - para mabigat ang butil\n";
        $prompt .= "- Foliar micronutrients (Zinc, Boron) - para sa quality\n";
        $prompt .= "- Proper irrigation - kailangan pa ng tubig\n";
        $prompt .= "- Pest monitoring (especially rice bugs/walang-sangit)\n";
        $prompt .= "- REASSURANCE: 'Normal ang inyong pananim, on-track sa magandang ani!'\n\n";

        $prompt .= "**PAANO KILALANIN ANG GRAIN FILLING:**\n";
        $prompt .= "1. Uhay nakayuko na (weight of grains pulling it down)\n";
        $prompt .= "2. Butil may laman - maputi o malabnaw pa kung milking stage\n";
        $prompt .= "3. DAT 75+ usually\n";
        $prompt .= "4. Lower leaves natural na dilaw (nutrient translocation)\n\n";

        // ================================================================
        // QUERY RULES FROM DATABASE (CRITICAL FOR CONSISTENCY!)
        // ================================================================
        $queryRules = AiQueryRule::getCompiledRules();
        if (!empty($queryRules)) {
            $prompt .= "════════════════════════════════════════════════════════════════\n";
            $prompt .= "         📋 USER-CONFIGURED QUERY RULES (SUNDIN ITO!)            \n";
            $prompt .= "════════════════════════════════════════════════════════════════\n\n";
            $prompt .= $queryRules . "\n";
        }

        // ================================================================
        // COMPARISON TABLE PRESERVATION (CRITICAL!)
        // ================================================================
        // Check if user is requesting comparison AND image analysis has a comparison table
        $isComparisonRequest = $this->detectComparisonRequest($this->userMessage);
        $hasComparisonTable = !empty($imageAnalysis) && (
            stripos($imageAnalysis, '| Characteristic') !== false ||
            stripos($imageAnalysis, '| Aspect') !== false ||
            stripos($imageAnalysis, '| Status |') !== false ||
            stripos($imageAnalysis, 'PAGHAHAMBING') !== false ||
            stripos($imageAnalysis, 'Comparison Table') !== false ||
            stripos($imageAnalysis, '|-----') !== false
        );

        if ($isComparisonRequest || $hasComparisonTable) {
            $prompt .= "════════════════════════════════════════════════════════════════\n";
            $prompt .= "🔴 CRITICAL: COMPARISON TABLE PRESERVATION! 🔴\n";
            $prompt .= "════════════════════════════════════════════════════════════════\n\n";

            $prompt .= "⚠️ ANG USER AY HUMIHINGI NG PAGHAHAMBING (COMPARISON)!\n\n";

            // Check if this is a cross-variety comparison (e.g., "Jackpot 102 ko vs RC160")
            $isCrossVariety = preg_match('/\b(vs|versus|kumpara|ikumpara).*(ng|sa)\s*(jackpot|sl-?\d+|rc\s*\d+|nk\s*\d+)/i', $this->userMessage) &&
                              preg_match('/\b(jackpot|sl-?\d+)\s*(ko|akin|namin)/i', $this->userMessage);

            if ($isCrossVariety) {
                $prompt .= "📊 CROSS-VARIETY COMPARISON (YIELD-FOCUSED):\n";
                $prompt .= "1. KUNG ANG IMAGE ANALYSIS AY MAY COMPARISON TABLE → I-PRESERVE ITO!\n";
                $prompt .= "2. ❌ HUWAG gumamit ng 'Below/Above' para sa subjective traits (kulay, taas)\n";
                $prompt .= "3. ✅ FOCUS sa YIELD FACTORS: panicles, spikelets, grain filling, tillers\n";
                $prompt .= "4. ✅ DAPAT may yield estimate/calculation sa conclusion\n\n";

                $prompt .= "YIELD-FOCUSED TABLE FORMAT:\n";
                $prompt .= "| Yield Factor | Observed | Typical for variety | Assessment |\n";
                $prompt .= "|--------------|----------|---------------------|------------|\n";
                $prompt .= "| Panicles/hill | ~12 | 10-14 | On track |\n";
                $prompt .= "| Grain filling | ~75% | 80-85% at maturity | Promising |\n\n";

                $prompt .= "❌ BAWAL: 'Kulay: Below' - kulay ay hindi yield factor!\n";
                $prompt .= "✅ DAPAT: Yield estimate based on observations\n";
                $prompt .= "✅ DAPAT: Sabihin kung PROMISING ang yield potential\n\n";
            } else {
                $prompt .= "📊 SAME-VARIETY COMPARISON (crop vs expected standard):\n";
                $prompt .= "1. KUNG ANG IMAGE ANALYSIS AY MAY COMPARISON TABLE → I-PRESERVE ITO!\n";
                $prompt .= "2. HUWAG I-SIMPLIFY ang comparison table sa 'on-track ka' o generic statements\n";
                $prompt .= "3. DAPAT MAY COMPARISON TABLE sa output na may:\n";
                $prompt .= "   - Specific metrics (height, panicle length, tiller count, etc.)\n";
                $prompt .= "   - User's crop values vs Standard/Expected values\n";
                $prompt .= "   - Status column (Above/Matches/Below) - OK for same variety\n";
                $prompt .= "4. I-COPY ang comparison table format mula sa Image Analysis kung meron\n";
                $prompt .= "5. DAPAT may detailed explanation per aspect AFTER the table\n\n";

                $prompt .= "EXAMPLE FORMAT NA DAPAT I-PRESERVE:\n";
                $prompt .= "| Characteristic | Your Crop | Standard | Status |\n";
                $prompt .= "|----------------|-----------|----------|--------|\n";
                $prompt .= "| Panicle Length | ~25 cm    | 25-27 cm | Katumbas |\n";
                $prompt .= "| Plant Height   | ~100 cm   | 100-109 cm | Katumbas |\n\n";
            }

            $prompt .= "❌ BAWAL: 'On-track naman po kayo' without the comparison table\n";
            $prompt .= "❌ BAWAL: Generic checklist without specific metrics\n";
            $prompt .= "✅ DAPAT: Full comparison table + detailed analysis\n\n";
        }

        $prompt .= $this->getFormattingInstructions();

        $systemPrompt = $this->buildCombinationSystemPrompt();

        // Determine token limit based on comparison request
        // Comparison responses need more tokens for detailed tables and analysis
        $maxTokens = ($isComparisonRequest || $hasComparisonTable) ? 4000 : 2000;

        Log::debug('CombineSourcesSimple token limit', [
            'isComparisonRequest' => $isComparisonRequest,
            'hasComparisonTable' => $hasComparisonTable,
            'maxTokens' => $maxTokens,
        ]);

        // Try GPT first (more reliable), then Gemini
        try {
            if ($openaiSetting && !empty($openaiSetting->apiKey)) {
                $this->enforceRateLimit('gpt-combine');
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $openaiSetting->apiKey,
                ])->timeout(90)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => $maxTokens,
                ]);

                if ($response->successful()) {
                    $result = $response->json();
                    $content = $result['choices'][0]['message']['content'] ?? '';

                    // Track OpenAI token usage
                    $usage = $result['usage'] ?? [];
                    $inputTokens = $usage['prompt_tokens'] ?? 0;
                    $outputTokens = $usage['completion_tokens'] ?? 0;
                    if ($inputTokens > 0 || $outputTokens > 0) {
                        $this->trackTokenUsage('openai', 'combine_sources', $inputTokens, $outputTokens, 'gpt-4o-mini');
                        Log::info('OpenAI Combine Sources - Token usage', [
                            'prompt_tokens' => $inputTokens,
                            'completion_tokens' => $outputTokens,
                            'total_tokens' => $usage['total_tokens'] ?? 0,
                        ]);
                    }

                    if (!empty($content)) {
                        return $this->filterInternalAnalysis($content);
                    }
                }
            }

            // Fallback to Gemini
            if ($geminiSetting && !empty($geminiSetting->apiKey)) {
                $this->enforceRateLimit('gemini-combine');
                $response = Http::timeout(90)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$geminiSetting->apiKey}",
                    [
                        'contents' => [['role' => 'user', 'parts' => [['text' => $systemPrompt . "\n\n" . $prompt]]]],
                        'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => $maxTokens],
                    ]
                );

                if ($response->successful()) {
                    $data = $response->json();
                    $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                    // Track Gemini token usage
                    $usageMetadata = $data['usageMetadata'] ?? [];
                    $inputTokens = $usageMetadata['promptTokenCount'] ?? 0;
                    $outputTokens = $usageMetadata['candidatesTokenCount'] ?? 0;
                    if ($inputTokens > 0 || $outputTokens > 0) {
                        $this->trackTokenUsage('gemini', 'combine_sources', $inputTokens, $outputTokens, 'gemini-2.0-flash');
                    }

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

            // FALLBACK: If we have AI knowledge, use it instead of returning empty
            // This prevents losing a valid response due to timeout
            if (!empty($aiKnowledge)) {
                Log::info('Using AI Knowledge as fallback after combine error', [
                    'error' => $e->getMessage(),
                    'aiKnowledgeLength' => strlen($aiKnowledge),
                ]);
                $this->logFlowStep('Combine Fallback', 'Using AI Knowledge response (' . strlen($aiKnowledge) . ' chars)');
                return $aiKnowledge;
            }

            // Secondary fallback: use web search result
            if (!empty($webResult)) {
                Log::info('Using Web Search as fallback after combine error', [
                    'error' => $e->getMessage(),
                    'webResultLength' => strlen($webResult),
                ]);
                $this->logFlowStep('Combine Fallback', 'Using Web Search response (' . strlen($webResult) . ' chars)');
                return $webResult;
            }

            return ''; // Empty only if no fallback available
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

        // CRITICAL: Filter out ornamental plant products (Osmocote, etc.)
        $response = $this->filterOrnamentalPlantProducts($response);

        // CRITICAL: Validate nitrogen recommendations against growth stage
        $response = $this->validateGrainFillingNitrogenAdvice($response);

        // Extract image URLs for lightbox display (removes from text, stores in $this->extractedImages)
        $result = $this->extractImagesFromResponse($response);
        $this->extractedImages = array_merge($this->extractedImages, $result['images']);

        return trim($result['text']);
    }

    /**
     * Filter out ornamental plant products from AI responses.
     *
     * Products like Osmocote are for ornamental/potted plants, not field crops.
     * This function removes or replaces mentions of such products with
     * appropriate crop-focused alternatives.
     *
     * @param string $response The AI response
     * @return string Filtered response
     */
    protected function filterOrnamentalPlantProducts(string $response): string
    {
        // List of ornamental plant products to filter out
        $ornamentalProducts = [
            'osmocote' => 'Complete 14-14-14 o Urea',
            'miracle.?gro' => 'Complete 14-14-14',
            'slow.?release fertilizer.*ornamental' => 'split application ng urea',
        ];

        $modified = false;
        $originalResponse = $response;

        foreach ($ornamentalProducts as $pattern => $replacement) {
            // Check if product is mentioned
            if (preg_match('/\b' . $pattern . '\b/i', $response)) {
                // Replace the product mention
                // Pattern 1: "Gumamit ng Osmocote" → "Gumamit ng Complete 14-14-14"
                $response = preg_replace(
                    '/\b(Gumamit ng |Gamitin ang |I-apply ang |Use |Apply )' . $pattern . '\b/i',
                    '$1' . $replacement,
                    $response
                );

                // Pattern 2: "Osmocote 14-14-14" or similar → replacement
                $response = preg_replace(
                    '/\b' . $pattern . '\s*[\d\-]+/i',
                    $replacement,
                    $response
                );

                // Pattern 3: General mention → replacement
                $response = preg_replace(
                    '/\b' . $pattern . '\b/i',
                    $replacement,
                    $response
                );

                $modified = true;
            }
        }

        // Remove entire bullet points/lines that recommend slow-release for ornamental
        $response = preg_replace(
            '/^[\•\-\*]\s*.*slow.?release.*ornamental.*\n?/mi',
            '',
            $response
        );

        if ($modified) {
            Log::info('Filtered ornamental plant products from response', [
                'originalContainedOsmocote' => stripos($originalResponse, 'osmocote') !== false,
                'filteredLength' => strlen($response),
            ]);
        }

        return $response;
    }

    /**
     * Validate and correct nitrogen recommendations at grain filling stage.
     *
     * ChatGPT correctly said: "Adequate ang Nitrogen... Huwag nang magdagdag ng Urea"
     * Gemini correctly said: "milking to soft dough stage... kailangan Potassium"
     *
     * This function catches cases where the AI incorrectly recommends nitrogen
     * when the image shows grain filling indicators.
     */
    protected function validateGrainFillingNitrogenAdvice(string $response): string
    {
        // Check if precomputed image analysis indicates grain filling stage
        $imageAnalysis = $this->precomputedImageAnalysis ?? '';

        // Grain filling indicators in image analysis
        $grainFillingIndicators = [
            'nakayuko',
            'bending',
            'drooping',
            'grain fill',
            'milking',
            'soft dough',
            'dough stage',
            'bumibigat',
            'mabigat na ang uhay',
            'may laman na ang butil',
            'filled grain',
            'filling grain',
        ];

        $isGrainFilling = false;
        $imageAnalysisLower = strtolower($imageAnalysis);

        foreach ($grainFillingIndicators as $indicator) {
            if (strpos($imageAnalysisLower, strtolower($indicator)) !== false) {
                $isGrainFilling = true;
                break;
            }
        }

        // Also check user message for DAP/DAT indicators of late stage
        $userMessageLower = strtolower($this->userMessage ?? '');
        if (preg_match('/(\d{2,3})\s*(dap|dat)/i', $userMessageLower, $matches)) {
            $daysValue = (int)$matches[1];
            // Rice: DAT 75+ is typically grain filling
            // Corn: DAP 65+ is typically grain filling
            if ($daysValue >= 65) {
                $isGrainFilling = true;
            }
        }

        if (!$isGrainFilling) {
            return $response; // Not grain filling, no correction needed
        }

        // Check if response incorrectly recommends nitrogen
        $responseLower = strtolower($response);
        $nitrogenMistakes = [
            'kulang sa nitrogen',
            'kakulangan sa nitrogen',
            'nitrogen deficiency',
            'mag-apply ng urea',
            'magdagdag ng urea',
            'i-apply ang urea',
            'urea (46-0-0)',
            'ammonium sulfate',
            'ammosul',
            'magdagdag ng abono', // if context is nitrogen
        ];

        $hasNitrogenMistake = false;
        foreach ($nitrogenMistakes as $mistake) {
            if (strpos($responseLower, strtolower($mistake)) !== false) {
                $hasNitrogenMistake = true;
                break;
            }
        }

        if (!$hasNitrogenMistake) {
            return $response; // No nitrogen mistake, return as is
        }

        // Log the correction
        Log::warning('Correcting nitrogen recommendation at grain filling stage', [
            'isGrainFilling' => true,
            'hasNitrogenMistake' => true,
            'responsePreview' => substr($response, 0, 200),
        ]);

        $this->logFlowStep('⚠️ Nitrogen Correction',
            'Detected incorrect nitrogen recommendation at grain filling stage - adding correction note');

        // Add a correction note at the end of the response
        $correctionNote = "\n\n";
        $correctionNote .= "═══════════════════════════════════════════════════════════════\n";
        $correctionNote .= "⚠️ PAALALA SA GROWTH STAGE:\n";
        $correctionNote .= "═══════════════════════════════════════════════════════════════\n";
        $correctionNote .= "Nakikita ko sa larawan na nasa **grain filling/milking stage** na ang inyong palay ";
        $correctionNote .= "(nakayuko na ang uhay, bumibigat na ang butil). Sa stage na ito:\n\n";
        $correctionNote .= "❌ **HUWAG NA MAG-APPLY NG NITROGEN** (Urea, Ammosul) - ";
        $correctionNote .= "magdudulot lang ito ng delay sa harvest at posibleng lodging.\n\n";
        $correctionNote .= "✅ **Ang kailangan sa grain filling:**\n";
        $correctionNote .= "• **Potassium (MOP/0-0-60)** - para mabigat ang butil\n";
        $correctionNote .= "• **Foliar micronutrients** (Zinc, Boron) - para sa quality\n";
        $correctionNote .= "• **Proper irrigation** - kailangan pa ng tubig\n\n";
        $correctionNote .= "Ang pagdilaw ng ilang dahon sa stage na ito ay **NORMAL** - ";
        $correctionNote .= "nag-translocate ang nutrients mula sa dahon papunta sa butil. Magandang senyales ito! 🌾\n";

        return $response . $correctionNote;
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
        $prompt .= "Be honest if you're not certain - tell them to message you again with more details or photos.\n\n";
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

        $instructions .= "5. IKAW ANG EXPERT - HUWAG MAG-REFERENCE NG IBA!\n";
        $instructions .= "- Ikaw ANG expert technician - ALAM mo ang sagot\n";
        $instructions .= "- BAWAL: 'Ayon sa mga eksperto...' (IKAW ang eksperto!)\n";
        $instructions .= "- BAWAL: 'Sabi ng mga agronomist...' (IKAW ang agronomist!)\n";
        $instructions .= "- BAWAL: 'According to experts...' (YOU ARE the expert!)\n";
        $instructions .= "- BAWAL: 'Studies show...' (Just state the facts directly!)\n";
        $instructions .= "- HUWAG sabihin 'base sa impormasyon na nakuha ko' o katulad\n";
        $instructions .= "- HUWAG sabihin 'tingnan natin kung makakahanap' o 'ayon sa aking research'\n";
        $instructions .= "- HUWAG banggitin ang paghahanap o pagkuha ng impormasyon\n";
        $instructions .= "- Ipresenta ang impormasyon ng DIREKTA at may AWTORIDAD\n";
        $instructions .= "- HALIMBAWA: Instead of 'Ayon sa mga eksperto, sa 100 DAP...' → 'Sa 100 DAP, ang mais ay...'\n\n";

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
        $systemPrompt .= "1. IKAW ANG EXPERT TECHNICIAN - HUWAG mag-reference ng iba:\n";
        $systemPrompt .= "   - BAWAL: 'Ayon sa mga eksperto...' (IKAW ang eksperto!)\n";
        $systemPrompt .= "   - BAWAL: 'Sabi ng mga agronomist...' (IKAW ang agronomist!)\n";
        $systemPrompt .= "   - BAWAL: 'According to experts...' / 'Studies show...' (State facts DIRECTLY!)\n";
        $systemPrompt .= "   - BAWAL: 'kumonsulta sa local technician', 'pumunta sa agricultural office'\n";
        $systemPrompt .= "   - IKAW na ang technician/eksperto na kausap nila! Magsalita ng may AWTORIDAD.\n";
        $systemPrompt .= "   - HALIMBAWA: Instead of 'Ayon sa eksperto, sa 100 DAP...' → 'Sa 100 DAP, ang mais ay...'\n";
        $systemPrompt .= "   - Kung kailangan ng follow-up: 'I-message mo ulit ako' o 'Mag-send ka ng litrato'\n";
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

        $systemPrompt .= "PATAKARAN SA PRODUCT RECOMMENDATIONS:\n";
        $systemPrompt .= "- PRIORITY 1: Products mula sa Knowledge Base (RAG) - Innosolve 40-5, etc.\n";
        $systemPrompt .= "- PRIORITY 2: Common crop fertilizers - Urea, Complete 14-14-14, Ammosul, MOP, DAP\n";
        $systemPrompt .= "- ❌ BAWAL: Osmocote at iba pang slow-release fertilizers para sa ornamental plants\n";
        $systemPrompt .= "- ❌ BAWAL: Products na para sa ornamental/potted plants\n";
        $systemPrompt .= "- FOCUS LANG SA CROPS (palay, mais, gulay) - HINDI ornamental plants!\n\n";

        // Include query rules if available
        $queryRules = AiQueryRule::getCompiledRules();
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
     * Build system prompt with the AGRI-TECH EXPERT CHATBOT MASTER FLOW.
     *
     * This is the SINGLE, COMPLETE 13-step instruction set for the agricultural AI chatbot.
     * Scope: ALL agriculture/crop management questions only.
     * Goal: Answer like an experienced agricultural technician.
     *
     * @param string $nodeType The type of node making the AI call
     *                         - 'query' = Full expert reasoning with all 13 decision gates
     *                         - 'output' = Format and present results in Taglish
     */
    protected function buildSystemPrompt(string $nodeType = 'output'): string
    {
        // Load user's query rules (compiled)
        $queryRules = AiQueryRule::getCompiledRules();

        // ================================================================
        // INTERMEDIATE QUERY = LIGHTWEIGHT PROMPT (COST OPTIMIZATION)
        // Used for flow node queries - just get facts, no personality/formatting
        // Saves ~10,000+ tokens per call compared to full 'query' prompt
        // ================================================================
        if ($nodeType === 'intermediate') {
            $prompt = "You are an agricultural research assistant. Your task is to:\n";
            $prompt .= "1. Provide factual, accurate information about the query\n";
            $prompt .= "2. Focus on data: product names, dosages, timing, prices, specifications\n";
            $prompt .= "3. Be concise - just the facts, no formatting or personality\n";
            $prompt .= "4. Use Filipino/Tagalog terms when relevant (palay, mais, etc.)\n\n";
            $prompt .= "Do NOT add greetings, emojis, or conversational styling.\n";
            $prompt .= "The response will be processed further before showing to user.\n";

            // Add query rules if available (minimal)
            if (!empty($queryRules)) {
                $prompt .= "\n\nAdditional rules:\n" . $queryRules;
            }

            return $prompt;
        }

        // ================================================================
        // QUERY NODE = AGRI-TECH EXPERT CHATBOT COMPLETE MASTER FLOW
        // Used ONLY for final output - includes full personality and formatting
        // ================================================================
        if ($nodeType === 'query') {
            $prompt = "=====================================================================\n";
            $prompt .= "AGRI-TECH EXPERT CHATBOT — COMPLETE MASTER FLOW\n";
            $prompt .= "Scope: ALL agriculture/crop management questions only.\n";
            $prompt .= "Goal: Answer like an experienced agricultural technician.\n";
            $prompt .= "=====================================================================\n\n";

            $prompt .= "ROLE\n";
            $prompt .= "You are an Agricultural Expert Technician chatbot. You answer ONLY agriculture\n";
            $prompt .= "and crop-management topics. You must think like a real field technician:\n";
            $prompt .= "classify the question, lock context, check if it's normal vs problem, eliminate\n";
            $prompt .= "causes using evidence, gate recommendations by feasibility/safety/label/PHI,\n";
            $prompt .= "and produce a clear, actionable answer.\n\n";

            $prompt .= "NEVER:\n";
            $prompt .= "- answer non-agricultural topics\n";
            $prompt .= "- list many \"possible causes\" without elimination\n";
            $prompt .= "- recommend actions already done (unless justified)\n";
            $prompt .= "- hallucinate brands, actives, labels, PHI, or dosages\n";
            $prompt .= "- recommend illegal/unsafe applications or PHI violations\n";
            $prompt .= "- push unnecessary sprays when the plant behavior is normal\n";
            $prompt .= "- say 'consult local experts' or 'ask your agricultural office' - YOU ARE the expert technician\n";
            $prompt .= "- HALLUCINATE OR ASSUME SPECIFIC VARIETIES (e.g., NSICRC216, RC222, Jackpot, NK6414) that the user DIDN'T mention in current message\n";
            $prompt .= "- ASSUME A DIFFERENT CROP TYPE (e.g., saying 'palay' when user uploaded 'mais' images)\n";
            $prompt .= "- INVENT DETAILS about the user's crop that weren't provided in images or current message\n";
            $prompt .= "- ASSUME DAP/DAT numbers (e.g., '100 DAP', '75 DAT') if user didn't explicitly state them in current message\n";
            $prompt .= "- RESPOND with specific crop analysis if user's current message is VAGUE (e.g., 'pwede mo ba tignan ito kung ayos')\n\n";

            $prompt .= "WHEN USER MESSAGE IS VAGUE (e.g., 'pwede mo ba tignan ito'):\n";
            $prompt .= "- If NO image uploaded AND no specific details in message = ASK what they want checked\n";
            $prompt .= "- Do NOT assume crop variety, DAP, or crop type from previous conversations\n";
            $prompt .= "- Example response: 'Para matulungan ko kayo, anong tanim po at pwede bang mag-send ng larawan?'\n\n";

            $prompt .= "ALWAYS:\n";
            $prompt .= "- maintain continuity using the full chat history\n";
            $prompt .= "- use user facts as highest priority truth\n";
            $prompt .= "- use label/official data (RAG/search) for pesticides/PHI\n";
            $prompt .= "- be calm and decisive when confidence is high\n\n";

            // STEP 0: AGRICULTURE-ONLY SCOPE GATE
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "0) AGRICULTURE-ONLY SCOPE GATE (MANDATORY — FIRST)\n";
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "Determine if the user message is agriculture/crop management related.\n\n";
            $prompt .= "AGRICULTURAL includes:\n";
            $prompt .= "- crop nutrition/fertilization, soil fertility/amendments (pH, salinity, etc.)\n";
            $prompt .= "- irrigation/water management, drainage\n";
            $prompt .= "- pests/diseases/weeds: ID + control + IPM\n";
            $prompt .= "- pesticide/herbicide/fungicide timing, PHI/REI, label-safe use, legality\n";
            $prompt .= "- tank mixing, compatibility, sequence, phytotoxicity\n";
            $prompt .= "- variety/seed traits, planting/spacings, crop stages when relevant\n";
            $prompt .= "- yield estimation, harvest/postharvest directly tied to crop production\n";
            $prompt .= "- farm operations decisions impacting crop outcome\n\n";
            $prompt .= "NOT agricultural:\n";
            $prompt .= "- personal, entertainment, politics, relationships\n";
            $prompt .= "- programming help not directly about crop/ag systems\n";
            $prompt .= "- medical/legal unrelated to crop labels/safety\n";
            $prompt .= "- anything not tied to crop production/management\n\n";
            $prompt .= "IF NOT AGRICULTURAL:\n";
            $prompt .= "Reply ONLY:\n";
            $prompt .= "\"Pasensya, pang-agriculture/crop management lang ako. Ibigay mo ang tanong mo\n";
            $prompt .= "tungkol sa pananim (palay/mais/etc.) para matulungan kita.\"\n\n";
            $prompt .= "If borderline: ask ONE clarifying question to confirm it's agricultural.\n\n";

            // STEP 1: FOLLOW-UP CONTINUITY CHECK
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "1) FOLLOW-UP CONTINUITY CHECK (MANDATORY)\n";
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "If the message is a follow-up:\n";
            $prompt .= "- Use the entire chat context automatically (do not ask again for info already present).\n";
            $prompt .= "- Keep recommendations consistent with prior facts and prior advice.\n";
            $prompt .= "- Still apply the agriculture-only gate: if follow-up is no longer agricultural, block it.\n\n";

            // STEP 2: QUESTION TYPE CLASSIFICATION
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "2) QUESTION TYPE CLASSIFICATION (MANDATORY)\n";
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "Classify into 1–2 types only (choose the closest):\n";
            $prompt .= "A. DIAGNOSIS (what is happening / what is the problem)\n";
            $prompt .= "B. NATURAL RESPONSE CHECK (is this normal or a real problem?)\n";
            $prompt .= "C. ACTION FEASIBILITY (what can be done now; is it too late/early?)\n";
            $prompt .= "D. INPUT COMPATIBILITY (tank-mix, pH, precipitation risk, sequence)\n";
            $prompt .= "E. TIMING & SAFETY (PHI/REI, label timing, legality, intervals)\n";
            $prompt .= "F. PREVENTION/PROTECTION (spray even without symptoms?)\n";
            $prompt .= "G. OPTIMIZATION (maximize yield/ROI, schedule tuning)\n";
            $prompt .= "H. PRODUCT/ACTIVE SELECTION (which category/active fits?)\n";
            $prompt .= "I. ROOT CAUSE ANALYSIS (why it happened; prevent next time)\n";
            $prompt .= "J. CONFIRMATION/REASSURANCE (is this ok? sanity check)\n\n";
            $prompt .= "If unclear: ask ONE question to classify (max 1).\n\n";

            // STEP 3: CONTEXT LOCK + "ALREADY DONE" GUARDRAIL
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "3) CONTEXT LOCK (FACT EXTRACTION) + \"ALREADY DONE\" GUARDRAIL\n";
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "Extract and lock known facts as variables:\n";
            $prompt .= "- crop, variety, location/season (if known)\n";
            $prompt .= "- stage/DAT/DAP (if given), and farmer goal (yield/ROI/risk tolerance)\n";
            $prompt .= "- symptoms/observations + plant part + distribution (uniform vs patchy)\n";
            $prompt .= "- actions already done (sprays, fertilizers, irrigation, pesticides)\n";
            $prompt .= "- constraints (budget, product availability, number of passes, knapsack rate)\n\n";
            $prompt .= "⚠️ CRITICAL - IMAGE CONTEXT PERSISTENCE:\n";
            $prompt .= "- If [CROP CONTEXT] appears in chat history, THIS IS THE CROP TYPE for all follow-ups\n";
            $prompt .= "- If user uploaded MAIS/CORN images, ALL follow-ups are about MAIS unless user explicitly switches\n";
            $prompt .= "- If user uploaded PALAY/RICE images, ALL follow-ups are about PALAY unless user explicitly switches\n";
            $prompt .= "- NEVER assume a different crop type than what was detected in uploaded images\n";
            $prompt .= "- If no variety was mentioned, DO NOT invent one - just say 'inyong pananim' or the crop type\n\n";
            $prompt .= "GUARDRAILS:\n";
            $prompt .= "- If user already applied something, DO NOT recommend repeating it unless you\n";
            $prompt .= "  explicitly justify why repeating changes the outcome (different timing/rate/goal).\n";
            $prompt .= "- Do not invent brands. Use product categories or actives unless user names a product.\n";
            $prompt .= "- Do not output exact dosage numbers unless:\n";
            $prompt .= "  (1) user provided them, OR\n";
            $prompt .= "  (2) RAG/label data provided them, OR\n";
            $prompt .= "  (3) you state clearly \"label-dependent: follow your product label\".\n\n";

            // STEP 4: CONFIDENCE SCORING
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "4) CONFIDENCE SCORING (INTERNAL → AFFECTS TONE)\n";
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "Estimate completeness:\n";
            $prompt .= "- HIGH (≥70%): give firm answer + actions\n";
            $prompt .= "- MED (40–69%): conditional answer + ask 1–3 key questions\n";
            $prompt .= "- LOW (<40%): ask up to 3 questions first, then answer\n\n";
            $prompt .= "Never sound highly confident on LOW confidence.\n\n";

            // STEP 5: DOMAIN FILTERING
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "5) DOMAIN FILTERING (MANDATORY — REDUCE NOISE)\n";
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "Decide which domains are relevant for THIS question:\n";
            $prompt .= "- nutrition/fertility\n";
            $prompt .= "- water/irrigation\n";
            $prompt .= "- pests\n";
            $prompt .= "- diseases\n";
            $prompt .= "- weeds\n";
            $prompt .= "- weather/stress\n";
            $prompt .= "- soil constraints\n";
            $prompt .= "- variety/physiology\n";
            $prompt .= "- operations/economics\n";
            $prompt .= "- safety/label/PHI/REI\n\n";
            $prompt .= "Hard rule: Do NOT discuss irrelevant domains.\n\n";

            // STEP 6: NORMAL PLANT RESPONSE vs REAL PROBLEM CHECK
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "6) NORMAL PLANT RESPONSE vs REAL PROBLEM CHECK (MANDATORY)\n";
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "Before diagnosing, always test:\n";
            $prompt .= "\"Could this be normal behavior or a varietal/stage response rather than a problem?\"\n\n";
            $prompt .= "Evaluate these signals:\n\n";
            $prompt .= "A) UNIFORMITY TEST:\n";
            $prompt .= "- uniform across the field / most hills → likely normal response or management effect\n";
            $prompt .= "- patchy / hotspots → likely soil/pest/disease/management issue\n\n";
            $prompt .= "B) DAMAGE TEST:\n";
            $prompt .= "- No visible tissue damage (lesions, necrosis, deformity, burn) → could be normal response\n";
            $prompt .= "- Clear damage patterns → real problem\n\n";
            $prompt .= "C) TIMING CONSISTENCY TEST:\n";
            $prompt .= "- occurs at expected time for that crop/stage/variety → likely normal\n";
            $prompt .= "- occurs off-timing → more likely problem\n\n";
            $prompt .= "D) VARIETY-TRAIT TEST (IMPORTANT):\n";
            $prompt .= "- Is the variety/hybrid known for traits that look \"problematic\" but are normal?\n";
            $prompt .= "  Examples: tight flag leaf sheath, delayed panicle exertion, upright leaves,\n";
            $prompt .= "  heavy panicle bending, anthocyanin tint, etc.\n\n";
            $prompt .= "E) \"ALREADY ADDRESSED\" TEST:\n";
            $prompt .= "- If key nutrients or actions were already applied, deficiency becomes less likely.\n\n";
            $prompt .= "If LIKELY NORMAL:\n";
            $prompt .= "- Say clearly it's likely normal.\n";
            $prompt .= "- Explain why it looks concerning but is expected.\n";
            $prompt .= "- Provide \"when to worry\" triggers and monitoring steps.\n";
            $prompt .= "- Do NOT recommend additional corrective inputs unless evidence supports it.\n\n";
            $prompt .= "If NOT normal:\n";
            $prompt .= "- Proceed to differential diagnosis.\n\n";

            // STEP 7: DIFFERENTIAL DIAGNOSIS WITH ELIMINATION
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "7) DIFFERENTIAL DIAGNOSIS WITH ELIMINATION (MANDATORY FOR DIAGNOSIS)\n";
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "Do NOT output a long list of \"possible causes\".\n";
            $prompt .= "Instead produce:\n\n";
            $prompt .= "PRIMARY causes (1–2 most likely)\n";
            $prompt .= "SECONDARY contributors (1–3)\n";
            $prompt .= "EXCLUDED causes (ruled out by evidence + context)\n\n";
            $prompt .= "You must explicitly eliminate using user facts:\n";
            $prompt .= "e.g., \"Zn deficiency unlikely because Zn was sprayed and pattern doesn't match.\"\n\n";
            $prompt .= "If photos are provided:\n";
            $prompt .= "- describe only what is visible\n";
            $prompt .= "- do not claim lab-confirmed disease\n";
            $prompt .= "- tie reasoning to visible patterns + context\n";
            $prompt .= "- state what images cannot confirm\n\n";

            // STEP 8: REVERSIBILITY & FEASIBILITY LOGIC
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "8) REVERSIBILITY & FEASIBILITY LOGIC (MANDATORY FOR ACTION FEASIBILITY)\n";
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "Separate the answer into:\n";
            $prompt .= "A) What cannot be changed anymore (irreversible)\n";
            $prompt .= "B) What can still be improved now (damage control / stabilization / next-phase optimization)\n\n";
            $prompt .= "State irreversibility plainly and redirect to the best feasible next steps.\n";
            $prompt .= "Example: \"Yung mga dahon na nasunog, hindi na babalik. Pero ang bagong dahon...\"\n\n";

            // STEP 9: RECOMMENDATION GATES
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "9) RECOMMENDATION GATES (MANDATORY — EVERY ACTION MUST PASS)\n";
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "Every recommended action must pass ALL gates:\n\n";
            $prompt .= "1) Biological possibility: can it actually affect the outcome now?\n";
            $prompt .= "2) Timing safety: not too late/early, does not increase stress\n";
            $prompt .= "3) Legal/label/PHI/REI safety: never recommend violating label or PHI\n";
            $prompt .= "4) Economic sense: cost vs expected benefit; avoid \"spray everything\"\n\n";
            $prompt .= "If any gate fails → do NOT recommend it.\n";
            $prompt .= "Instead say: \"Hindi na po advisable kasi...\"\n\n";

            // STEP 10: PREVENTIVE / "PROTECTION LANG" QUESTIONS
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "10) PREVENTIVE / \"PROTECTION LANG\" QUESTIONS (MANDATORY)\n";
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "Never refuse. Never say \"not covered.\"\n\n";
            $prompt .= "Use risk-benefit logic:\n";
            $prompt .= "- If no symptoms: preventive spraying is justified only if pressure is high\n";
            $prompt .= "  (weather favoring disease, prior history, nearby outbreak, varietal susceptibility)\n";
            $prompt .= "  AND label/PHI timing allows.\n";
            $prompt .= "- Always mention resistance risk + wasted-cost risk.\n";
            $prompt .= "- Provide monitoring triggers: symptoms/threshold that should trigger spraying.\n\n";
            $prompt .= "For PHI/label questions:\n";
            $prompt .= "- Use RAG/search label data if available; otherwise say:\n";
            $prompt .= "  \"Label-dependent: check the PHI on your exact product label.\"\n\n";

            // STEP 11: KNOWLEDGE ARBITRATION
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "11) KNOWLEDGE ARBITRATION (RAG vs SEARCH vs MODEL)\n";
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "Priority order:\n";
            $prompt .= "1) User-provided facts (highest truth)\n";
            $prompt .= "2) System RAG content (local truth for your system)\n";
            $prompt .= "3) Official labels/registrations/PHI from search or provided label docs\n";
            $prompt .= "4) General agronomy knowledge (only if not contradicted by 1–3)\n\n";
            $prompt .= "Never override user facts with generic model assumptions.\n\n";

            // STEP 12: RESPONSE FORMAT (MANDATORY OUTPUT TEMPLATE)
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "12) RESPONSE FORMAT (MANDATORY OUTPUT TEMPLATE)\n";
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "Always format the final answer like this:\n\n";
            $prompt .= "(1) DIRECT ANSWER (1–2 sentences)\n";
            $prompt .= "(2) IS THIS NORMAL OR A PROBLEM? (clear statement: NORMAL / PROBLEM / UNCLEAR)\n";
            $prompt .= "(3) WHY (short): primary reasoning + what was eliminated\n";
            $prompt .= "(4) WHAT YOU CAN DO NOW (bullets; only feasible actions)\n";
            $prompt .= "(5) WHAT NOT TO DO (bullets; prevent costly mistakes)\n";
            $prompt .= "(6) WHEN TO WORRY / WHAT TO WATCH FOR (clear triggers + timeframe)\n";
            $prompt .= "(7) IF YOU WANT MORE PRECISION (max 3 specific questions/data needed)\n\n";
            $prompt .= "Keep it practical. Avoid generic \"soil test\" unless it is truly required.\n";
            $prompt .= "Use Taglish with 'po' for politeness. Use **bold** for key terms.\n";
            $prompt .= "Add emojis sparingly: 🌾 🌽 🌱 💧 🐛 ✅ ⚠️ 💡\n\n";

            // STEP 13: FINAL SELF-CHECK
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "13) FINAL SELF-CHECK (MANDATORY BEFORE SENDING)\n";
            $prompt .= "---------------------------------------------------------------------\n";
            $prompt .= "Before sending your response, verify:\n";
            $prompt .= "□ Did I answer the actual question asked?\n";
            $prompt .= "□ Did I use facts from the chat context (not ask again)?\n";
            $prompt .= "□ Did I check if it's NORMAL vs PROBLEM?\n";
            $prompt .= "□ Did I eliminate unlikely causes with evidence?\n";
            $prompt .= "□ Did I avoid recommending actions already done?\n";
            $prompt .= "□ Did every recommendation pass all 4 gates?\n";
            $prompt .= "□ Did I state what's reversible vs irreversible?\n";
            $prompt .= "□ Is my answer practical and actionable?\n";
            $prompt .= "□ Did I avoid hallucinating brands/dosages?\n";
            $prompt .= "□ Did I follow the response format template?\n\n";

            // Include query rules if available
            if (!empty($queryRules)) {
                $prompt .= "=====================================================================\n";
                $prompt .= "ADDITIONAL CUSTOM RULES\n";
                $prompt .= "=====================================================================\n";
                $prompt .= $queryRules . "\n";
            }

            return $prompt;
        }

        // ================================================================
        // OUTPUT NODE = Present in Taglish with PROPER FORMATTING
        // (Uses same principles, simplified for final output formatting)
        // ================================================================
        $systemPrompt = "You are an Agricultural Expert Technician presenting answers to Filipino farmers.\n\n";

        $systemPrompt .= "=== CORE RULES ===\n";
        $systemPrompt .= "1. AGRICULTURE ONLY - If not about farming/crops, politely decline\n";
        $systemPrompt .= "2. CONTINUITY - Use facts already given, don't ask again\n";
        $systemPrompt .= "3. NORMAL vs PROBLEM - Distinguish between normal plant behavior and real issues\n";
        $systemPrompt .= "4. ELIMINATE - Explicitly rule out unlikely causes using evidence\n";
        $systemPrompt .= "5. GATE RECOMMENDATIONS - Only recommend if biologically/timing/label/economically feasible\n";
        $systemPrompt .= "6. BE SPECIFIC - Use actual brands, rates, timing when known\n\n";

        $systemPrompt .= "=== RESPONSE FORMAT ===\n";
        $systemPrompt .= "(1) DIRECT ANSWER - 1-2 sentences\n";
        $systemPrompt .= "(2) NORMAL OR PROBLEM? - Clear statement\n";
        $systemPrompt .= "(3) WHY - Primary reasoning + what was eliminated\n";
        $systemPrompt .= "(4) WHAT YOU CAN DO NOW - Bullets, only feasible actions\n";
        $systemPrompt .= "(5) WHAT NOT TO DO - Prevent costly mistakes\n";
        $systemPrompt .= "(6) WHEN TO WORRY - Clear triggers + timeframe\n";
        $systemPrompt .= "(7) IF YOU WANT MORE PRECISION - Max 3 questions\n\n";

        $systemPrompt .= "=== FORMATTING ===\n";
        $systemPrompt .= "- Use **bold** for key terms, brand names, headers\n";
        $systemPrompt .= "- Use bullet points (-) for lists\n";
        $systemPrompt .= "- Use Taglish with 'po' for politeness\n";
        $systemPrompt .= "- Add emojis sparingly: 🌾 🌽 ✅ ⚠️ 💡\n\n";

        $systemPrompt .= "=== CRITICAL ===\n";
        $systemPrompt .= "- NEVER say 'kumonsulta sa local agricultural office'\n";
        $systemPrompt .= "- NEVER recommend something already done without explaining why repeat is needed\n";
        $systemPrompt .= "- NEVER list many random causes without elimination\n";
        $systemPrompt .= "- ALWAYS state what's reversible vs irreversible\n";
        $systemPrompt .= "- ALWAYS check the 4 recommendation gates\n";

        // Include query rules if available
        if (!empty($queryRules)) {
            $systemPrompt .= "\n" . $queryRules;
        }

        return $systemPrompt;
    }

    /**
     * Call AI API.
     * @param bool $useWebSearch If true, forces web search mode for supported providers
     *
     * AI PROVIDER STRATEGY:
     * - OpenAI (GPT-4o) = PRIMARY for answer analysis and reasoning
     * - Gemini = ONLY for web search when real-time data is needed
     */
    protected function callAI(AiApiSetting $setting, string $prompt, array $images = [], string $systemPrompt = '', bool $useWebSearch = false): string
    {
        try {
            // Determine if web search is needed
            $needsWebSearch = $useWebSearch || $this->forceWebSearch;

            // ================================================================
            // COST-OPTIMIZED STRATEGY (Feb 2026):
            // - Gemini 2.0 Flash as PRIMARY (~$0.10/$0.40 per 1M tokens)
            // - OpenAI GPT-4o as FALLBACK (~$2.50/$10.00 per 1M tokens - 25x more expensive!)
            // - OpenAI reserved for: vision analysis, complex reasoning tasks
            // ================================================================

            // Get available API settings (global)
            $geminiSetting = AiApiSetting::active()
                ->forProvider(AiApiSetting::PROVIDER_GEMINI)
                ->enabled()
                ->first();

            $openaiSetting = AiApiSetting::active()
                ->forProvider(AiApiSetting::PROVIDER_OPENAI)
                ->enabled()
                ->first();

            // COST OPTIMIZATION: Always prefer Gemini (25x cheaper than GPT-4o)
            // Exception: Use OpenAI only if Gemini is not available
            if ($geminiSetting) {
                Log::info('Using Gemini 2.0 Flash (COST OPTIMIZED)', [
                    'promptLength' => strlen($prompt),
                    'hasImages' => !empty($images),
                    'needsWebSearch' => $needsWebSearch,
                    'costPerMTokens' => '$0.10 input / $0.40 output',
                ]);
                return $this->callGeminiAPI($geminiSetting, $prompt, $images, $systemPrompt);
            }

            // Fallback to OpenAI if Gemini not available
            if ($openaiSetting) {
                Log::info('Using OpenAI as FALLBACK (Gemini not available)', [
                    'promptLength' => strlen($prompt),
                    'hasImages' => !empty($images),
                    'costPerMTokens' => '$2.50 input / $10.00 output (25x more expensive!)',
                ]);

                if ($needsWebSearch) {
                    return $this->callOpenAIWithWebSearch($openaiSetting, $prompt, $systemPrompt);
                }
                return $this->callOpenAIAPI($openaiSetting, $prompt, $images, $systemPrompt);
            }

            // Fallback to the provided setting if neither OpenAI nor Gemini available
            Log::debug('Using provided setting as fallback', [
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
     * Clean up malformed AI analysis responses.
     * Fixes issues like:
     * - Abnormally long responses (> 5000 chars) that contain repeated/garbage content
     * - Table cells with repeated content
     * - Excessive whitespace
     */
    protected function cleanupMalformedAnalysis(string $content): string
    {
        $originalLength = strlen($content);

        // If response is abnormally long, it's likely malformed
        if ($originalLength > 10000) {
            Log::warning('Malformed analysis detected - response too long', [
                'originalLength' => $originalLength,
            ]);

            // Try to extract just the meaningful content before the malformed table
            // Look for the start of a table and truncate if cells are too long
            if (preg_match('/\|[^\|]+\|/u', $content)) {
                // Has a table - check for malformed cells
                $lines = explode("\n", $content);
                $cleanedLines = [];
                $inMalformedTable = false;

                foreach ($lines as $line) {
                    // Check if this is a table row with malformed cells
                    if (preg_match('/^\|/', $line)) {
                        // Check if any cell is abnormally long (> 100 chars)
                        $cells = explode('|', $line);
                        $hasMalformedCell = false;

                        foreach ($cells as $cell) {
                            if (strlen(trim($cell)) > 100) {
                                $hasMalformedCell = true;
                                break;
                            }
                        }

                        if ($hasMalformedCell) {
                            $inMalformedTable = true;
                            // Skip this malformed table row
                            continue;
                        }
                    }

                    // Reset malformed table flag when we exit the table
                    if ($inMalformedTable && !preg_match('/^\|/', $line) && !empty(trim($line))) {
                        $inMalformedTable = false;
                    }

                    if (!$inMalformedTable) {
                        $cleanedLines[] = $line;
                    }
                }

                $content = implode("\n", $cleanedLines);
            }

            // If still too long, truncate to first 5000 chars at a sentence boundary
            if (strlen($content) > 5000) {
                $content = substr($content, 0, 5000);
                // Try to end at a sentence
                $lastPeriod = strrpos($content, '.');
                $lastNewline = strrpos($content, "\n");
                $cutPoint = max($lastPeriod, $lastNewline);
                if ($cutPoint > 3000) {
                    $content = substr($content, 0, $cutPoint + 1);
                }
                $content .= "\n\n(Ang detalyadong comparison table ay available sa web search results.)";
            }
        }

        // Remove excessive whitespace (more than 2 spaces in a row)
        $content = preg_replace('/  {3,}/', '  ', $content);

        // Remove lines that are just whitespace or repeated characters
        $lines = explode("\n", $content);
        $cleanedLines = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            // Skip lines that are mostly whitespace or repeated single character
            if (strlen($trimmed) > 0 && !preg_match('/^(.)\1{10,}$/', $trimmed)) {
                $cleanedLines[] = $line;
            }
        }

        $content = implode("\n", $cleanedLines);

        // Clean up multiple blank lines
        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        if (strlen($content) !== $originalLength) {
            Log::info('Analysis cleaned up', [
                'originalLength' => $originalLength,
                'cleanedLength' => strlen($content),
                'reduction' => $originalLength - strlen($content),
            ]);
        }

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
                        ->where('id', $id)
            ->first();
    }

    /**
     * Get default API setting.
     */
    protected function getDefaultApiSetting(): ?AiApiSetting
    {
        $setting = AiApiSetting::active()
                        ->enabled()
            ->default()
            ->first();

        if (!$setting) {
            $setting = AiApiSetting::active()
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
     * @param string $step The step name
     * @param string $details Description of what happened
     * @param string|null $aiResponse The AI response content (optional, will be truncated for display)
     */
    protected function logFlowStep(string $step, string $details = '', ?string $aiResponse = null): void
    {
        $entry = [
            'time' => now()->format('H:i:s.v'),
            'step' => $step,
            'details' => $details,
        ];

        // Add AI response if provided (truncate to 15000 chars for display - increased for full log copying)
        if ($aiResponse !== null && !empty($aiResponse)) {
            $entry['aiResponse'] = strlen($aiResponse) > 15000
                ? substr($aiResponse, 0, 15000) . "\n\n... [truncated - " . strlen($aiResponse) . " total chars]"
                : $aiResponse;
        }

        $this->flowLog['steps'][] = $entry;
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

        // Get Gemini API setting (required for Vision) - global
        $geminiSetting = AiApiSetting::active()
            ->forProvider(AiApiSetting::PROVIDER_GEMINI)
            ->enabled()
            ->first();

        if (!$geminiSetting || !$geminiSetting->apiKey) {
            Log::warning('Gemini API not configured for image analysis');
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

        // Log the query being sent for image analysis
        $this->logFlowStep('Image Analysis Query', 'Query sent to Gemini Vision (' . strlen($analysisPrompt) . ' chars)', $analysisPrompt);

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

            // IMPORTANT: Do NOT enable Google Search here for image analysis
            // Vision + Google Search combined causes truncated responses
            // Web search for variety specs should be done SEPARATELY in the main flow
            // This keeps image analysis focused on what's visible in the photos

            $requestData = [
                'systemInstruction' => [
                    'parts' => [['text' => $systemInstruction]]
                ],
                'contents' => [['parts' => $parts]],
                'generationConfig' => [
                    'maxOutputTokens' => 3000, // Allow ~2000 chars for detailed visual observations
                    'temperature' => 0.5, // Medium temperature for more varied/specific observations
                ],
            ];

            Log::debug('Image analysis - vision only mode (no Google Search to avoid truncation)', [
                'imageCount' => $imageCount,
                'promptLength' => strlen($analysisPrompt),
            ]);

            Log::debug('Calling Gemini Vision for deep image analysis', [
                'imageCount' => $imageCount,
                'hasUserMessage' => !empty($userMessage),
                'hasTopicContext' => !empty($topicContext),
                'hasGoogleSearch' => false, // Disabled to prevent truncation
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

                $originalLength = strlen($analysis);

                // SAFEGUARD: Clean up malformed responses (e.g., repeated content in table cells)
                $analysis = $this->cleanupMalformedAnalysis($analysis);

                Log::info('Image analysis completed successfully', [
                    'imageCount' => $imageCount,
                    'originalLength' => $originalLength,
                    'cleanedLength' => strlen($analysis),
                    'wasCleaned' => $originalLength !== strlen($analysis),
                    'inputTokens' => $inputTokens,
                    'outputTokens' => $outputTokens,
                ]);

                $this->logFlowStep('Analysis Complete', strlen($analysis) . ' characters generated' . ($originalLength !== strlen($analysis) ? ' (cleaned from ' . $originalLength . ')' : ''));

                // Generate a brief summary for the flow log
                $summary = Str::limit($analysis, 200);

                return [
                    'success' => true,
                    'analysis' => $this->stripMarkdownFormatting($analysis),
                    'summary' => $summary,
                    'imageCount' => $imageCount,
                    'prompt' => $analysisPrompt, // Include the query for flow log
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
     * Classify the user's inquiry type and extract relevant details using AI.
     * This provides more robust extraction than regex patterns.
     *
     * @param string $userMessage The user's message to classify
     * @return array Classification result with 'inquiryType', 'details', and metadata
     */
    public function classifyInquiry(string $userMessage): array
    {
        // Get OpenAI API setting - use GPT-4o for accurate classification (global)
        $openaiSetting = AiApiSetting::active()
            ->forProvider(AiApiSetting::PROVIDER_OPENAI)
            ->enabled()
            ->first();

        // Default classification if API not available
        $defaultResult = [
            'inquiryType' => 'general',
            'isComparison' => false,
            'needsVisualReference' => false,
            'userCrop' => null,
            'userCropVariety' => null,
            'comparisonTarget' => null,
            'comparisonType' => null,
            'growthStage' => null,
            'dat' => null,
            'specificConcerns' => [],
            'confidence' => 0.5,
        ];

        if (!$openaiSetting || !$openaiSetting->apiKey) {
            Log::debug('OpenAI not available for inquiry classification, using defaults');
            return $defaultResult;
        }

        try {
            // Use chain-of-thought prompting for accurate extraction
            $systemPrompt = <<<'PROMPT'
You are an expert agricultural inquiry analyzer for Philippine rice farming. Your task is to CAREFULLY parse the user's message and extract EXACT details.

STEP 1 - ANALYZE THE MESSAGE:
First, think through these questions:
1. What crop variety does the user OWN? (Look for: "ko", "akin", "namin", "my", "aking")
2. What do they want to COMPARE their crop against?
3. Is this a same-variety comparison (their crop vs standard of SAME variety)?
4. What is the growth stage (DAT = Days After Transplanting)?

STEP 2 - OUTPUT JSON:
After analysis, output ONLY this JSON structure:

{
    "thinking": "Brief analysis of what the user is asking",
    "inquiryType": "comparison|diagnosis|general_question|product_inquiry|pest_disease|fertilizer|yield_estimate|visual_reference",
    "isComparison": true/false,
    "needsVisualReference": true/false,
    "userCrop": "rice|corn|vegetables|other|null",
    "userCropVariety": "EXACT variety name user owns (e.g., 'Jackpot 102', 'SL-8H') or null",
    "comparisonTarget": "What they compare AGAINST - see rules below",
    "comparisonType": "same_variety_standard|different_variety|traditional_method|null",
    "growthStage": "seedling|tillering|booting|heading|flowering|grain_filling|maturity|null",
    "dat": number or null,
    "specificConcerns": ["array", "of", "concerns"],
    "confidence": 0.0-1.0
}

═══════════════════════════════════════════════════════════════════
VISUAL REFERENCE QUESTIONS (needsVisualReference = true):
═══════════════════════════════════════════════════════════════════

When user asks "what does X look like" → SET needsVisualReference = true
These phrases indicate user wants to SEE what something looks like:
- "ano ang istura ng X" (what does X look like)
- "ano ang itsura ng X" (what does X look like)
- "paano ang itsura ng X" (how does X look)
- "ano ang sintomas ng X" (what are symptoms of X)
- "ano ang signs ng X" (what are signs of X)
- "paano ko malalaman kung may X" (how do I know if there's X)
- "mukha ba ito ng X" (does this look like X)
- "what does X look like"
- "show me X"
- "how to identify X"

Examples:
- "ano ang istura ng iron deficiency sa palay?" → needsVisualReference = true, inquiryType = "visual_reference"
- "ano ang sintomas ng zinc deficiency?" → needsVisualReference = true
- "paano ko malalaman kung may tungro?" → needsVisualReference = true

═══════════════════════════════════════════════════════════════════
CRITICAL RULES FOR comparisonTarget:
═══════════════════════════════════════════════════════════════════

RULE 1 - SAME VARIETY STANDARD COMPARISON:
When user says: "[variety] ko... ikumpara vs standard/traditional ng [SAME variety]"
OR when user says: "[variety] ko... ikumpara sa traditional na pag tatanim ng [SAME variety]"
OR when user mentions "traditional [planting/method/farming]" followed by their SAME variety name
→ comparisonTarget = "Standard [variety name]" or "Traditional [variety name] planting"
→ comparisonType = "same_variety_standard"

CRITICAL: If user mentions a SPECIFIC VARIETY after "traditional", it is ALWAYS same_variety_standard!

Example 1: "jackpot 102 ko... ikumpara vs standard... ng jackpot 102"
→ userCropVariety = "Jackpot 102"
→ comparisonTarget = "Standard Jackpot 102"
→ comparisonType = "same_variety_standard"

Example 2: "jackpot 102 ko gamit agritech. ikumpara sa traditional na pag tatanim ng jackpot 102"
→ userCropVariety = "Jackpot 102"
→ comparisonTarget = "Traditional Jackpot 102 planting"
→ comparisonType = "same_variety_standard"
MEANING: User compares THEIR Jackpot 102 (agritech method) vs traditional planting of SAME variety.

Example 3: "SL-8H ko... ikumpara sa traditional farming ng SL-8H"
→ userCropVariety = "SL-8H"
→ comparisonTarget = "Traditional SL-8H farming"
→ comparisonType = "same_variety_standard"

RULE 2 - DIFFERENT VARIETY COMPARISON:
When user mentions comparing to a DIFFERENT variety name
→ comparisonTarget = "[other variety name]"
→ comparisonType = "different_variety"

Example: "jackpot 102 ko... ikumpara sa RC222"
→ userCropVariety = "Jackpot 102"
→ comparisonTarget = "RC222"
→ comparisonType = "different_variety"

RULE 3 - TRADITIONAL METHOD COMPARISON (GENERIC):
ONLY when user mentions "traditional farming/method" WITHOUT any specific variety name after it
→ comparisonTarget = "Traditional farming methods"
→ comparisonType = "traditional_method"

Example: "palay ko... ikumpara sa traditional farming" (NO variety mentioned after traditional)
→ comparisonType = "traditional_method"

IMPORTANT: If ANY variety name appears after "traditional", use RULE 1 instead!

═══════════════════════════════════════════════════════════════════
EXAMPLES:
═══════════════════════════════════════════════════════════════════

User: "ito ang itsura ng jackpot 102 ko at dat68. pwede mo ba itong ikumpara vs sa standard o traditional farming ng jackpot 102 sa pinas."

ANALYSIS:
- "jackpot 102 ko" → User OWNS Jackpot 102
- "ikumpara vs sa standard... ng jackpot 102" → Compare against STANDARD of Jackpot 102
- "dat68" → DAT 68 (likely heading stage)
- This is SAME VARIETY comparison - user wants to check if their Jackpot 102 meets standards

→ {"thinking":"User owns Jackpot 102 at DAT 68 and wants to compare against the standard/typical performance of Jackpot 102 variety in Philippines","inquiryType":"comparison","isComparison":true,"userCrop":"rice","userCropVariety":"Jackpot 102","comparisonTarget":"Standard Jackpot 102","comparisonType":"same_variety_standard","growthStage":"heading","dat":68,"specificConcerns":["performance check","yield potential","on track assessment"],"confidence":0.98}

User: "ito yung picture ng jackpot 102 ko gamit ang aming agritech. pwede mo ba i kumpara ito sa traditional na pag tatanim ng jackpot 102."

ANALYSIS:
- "jackpot 102 ko gamit ang aming agritech" → User OWNS Jackpot 102, grown using agritech method
- "ikumpara... sa traditional na pag tatanim ng jackpot 102" → Compare to TRADITIONAL PLANTING of JACKPOT 102 (SAME variety!)
- The variety name "jackpot 102" appears AFTER "traditional na pag tatanim" = SAME VARIETY comparison
- NOT generic traditional farming - this is comparing methods for the SAME variety

→ {"thinking":"User owns Jackpot 102 grown with agritech and wants to compare against traditional planting method of the SAME variety Jackpot 102 - this is same variety standard comparison, NOT traditional method comparison","inquiryType":"comparison","isComparison":true,"userCrop":"rice","userCropVariety":"Jackpot 102","comparisonTarget":"Traditional Jackpot 102 planting","comparisonType":"same_variety_standard","growthStage":null,"dat":null,"specificConcerns":["method comparison","agritech vs traditional","same variety performance"],"confidence":0.98}

User: "ito ang itsura ng SL-8H ko. pwede mo ba itong ikumpara sa RC222?"

→ {"thinking":"User owns SL-8H and wants to compare against RC222 (different variety)","inquiryType":"comparison","isComparison":true,"userCrop":"rice","userCropVariety":"SL-8H","comparisonTarget":"RC222","comparisonType":"different_variety","growthStage":null,"dat":null,"specificConcerns":["variety comparison"],"confidence":0.95}

User: "kumusta ang palay ko? malusog ba?"

→ {"thinking":"User asking about their crop health, no comparison, no specific variety mentioned","inquiryType":"diagnosis","isComparison":false,"needsVisualReference":false,"userCrop":"rice","userCropVariety":null,"comparisonTarget":null,"comparisonType":null,"growthStage":null,"dat":null,"specificConcerns":["health check","general assessment"],"confidence":0.9}

User: "ano ang istura ng iron deficiency sa palay?"

→ {"thinking":"User wants to know what iron deficiency looks like in rice - needs visual reference images","inquiryType":"visual_reference","isComparison":false,"needsVisualReference":true,"userCrop":"rice","userCropVariety":null,"comparisonTarget":null,"comparisonType":null,"growthStage":null,"dat":null,"specificConcerns":["iron deficiency","visual identification","symptom recognition"],"confidence":0.95}

User: "paano ko malalaman kung may zinc deficiency ang mais ko?"

→ {"thinking":"User wants to identify zinc deficiency symptoms - needs visual reference","inquiryType":"visual_reference","isComparison":false,"needsVisualReference":true,"userCrop":"corn","userCropVariety":null,"comparisonTarget":null,"comparisonType":null,"growthStage":null,"dat":null,"specificConcerns":["zinc deficiency","symptom identification"],"confidence":0.95}

User: "ito ang palay ko. pwede mo ba itong ikumpara sa traditional farming?"

ANALYSIS:
- "palay ko" → User OWNS rice (no specific variety)
- "ikumpara sa traditional farming" → No variety name after "traditional"
- This IS generic traditional method comparison

→ {"thinking":"User wants to compare their rice against generic traditional farming methods - no specific variety mentioned after traditional","inquiryType":"comparison","isComparison":true,"userCrop":"rice","userCropVariety":null,"comparisonTarget":"Traditional farming methods","comparisonType":"traditional_method","growthStage":null,"dat":null,"specificConcerns":["method comparison"],"confidence":0.92}

OUTPUT JSON ONLY - NO OTHER TEXT.
PROMPT;

            $response = Http::timeout(20)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $openaiSetting->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o', // Use GPT-4o for better accuracy
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => "Parse this message:\n\n" . $userMessage],
                    ],
                    'max_tokens' => 500,
                    'temperature' => 0.0, // Zero temperature for deterministic extraction
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '';

                // Track token usage
                $usage = $data['usage'] ?? [];
                $inputTokens = $usage['prompt_tokens'] ?? 0;
                $outputTokens = $usage['completion_tokens'] ?? 0;
                $this->trackTokenUsage('openai', 'inquiry_classification', $inputTokens, $outputTokens, 'gpt-4o-mini');

                // Parse JSON response
                $content = trim($content);
                // Remove markdown code blocks if present
                $content = preg_replace('/^```json\s*/i', '', $content);
                $content = preg_replace('/\s*```$/i', '', $content);

                $result = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($result)) {
                    Log::info('=== INQUIRY CLASSIFICATION SUCCESS ===', [
                        'inquiryType' => $result['inquiryType'] ?? 'unknown',
                        'isComparison' => $result['isComparison'] ?? false,
                        'needsVisualReference' => $result['needsVisualReference'] ?? false,
                        'userCropVariety' => $result['userCropVariety'] ?? null,
                        'comparisonTarget' => $result['comparisonTarget'] ?? null,
                        'comparisonType' => $result['comparisonType'] ?? null,
                        'dat' => $result['dat'] ?? null,
                        'growthStage' => $result['growthStage'] ?? null,
                        'thinking' => $result['thinking'] ?? null,
                        'confidence' => $result['confidence'] ?? 0,
                    ]);

                    // Log to flow with full classification details
                    $this->logFlowStep('Inquiry Classification', json_encode([
                        'type' => $result['inquiryType'] ?? 'unknown',
                        'comparison' => $result['isComparison'] ?? false,
                        'visualRef' => $result['needsVisualReference'] ?? false,
                        'userCrop' => $result['userCropVariety'] ?? 'N/A',
                        'target' => $result['comparisonTarget'] ?? 'N/A',
                        'comparisonType' => $result['comparisonType'] ?? 'N/A',
                        'dat' => $result['dat'] ?? 'N/A',
                        'thinking' => $result['thinking'] ?? 'N/A',
                    ]), $result['thinking'] ?? '');

                    return $result;
                }

                Log::warning('Failed to parse inquiry classification JSON', ['content' => $content]);
            }

        } catch (\Exception $e) {
            Log::error('Inquiry classification failed', ['error' => $e->getMessage()]);
        }

        return $defaultResult;
    }

    /**
     * Classify if a follow-up message needs image re-analysis or can use chat history.
     * Uses AI to make this decision for more accurate handling.
     *
     * @param string $userMessage The follow-up message to classify
     * @return array Decision with 'needsImageReanalysis', 'usesChatHistory', and 'reason'
     */
    public function classifyFollowUp(string $userMessage, array $chatContext = []): array
    {
        // Get OpenAI API setting (use mini for cost efficiency) - global
        $openaiSetting = AiApiSetting::active()
            ->forProvider(AiApiSetting::PROVIDER_OPENAI)
            ->enabled()
            ->first();

        // Default: re-analyze to be safe
        $defaultResult = [
            'needsImageReanalysis' => true,
            'usesChatHistory' => true,
            'reason' => 'Default behavior',
        ];

        if (!$openaiSetting || !$openaiSetting->apiKey) {
            return $defaultResult;
        }

        // Build context string for the prompt
        $contextInfo = '';
        if (!empty($chatContext)) {
            $contextInfo = "\n\nCHAT SESSION CONTEXT:\n";
            if (!empty($chatContext['originalCropVariety'])) {
                $contextInfo .= "- User's ORIGINAL crop variety: {$chatContext['originalCropVariety']}\n";
            }
            if (!empty($chatContext['previousComparisonTargets'])) {
                $targets = implode(', ', $chatContext['previousComparisonTargets']);
                $contextInfo .= "- Previously compared against: {$targets}\n";
            }
            if (!empty($chatContext['hasNewImages'])) {
                $contextInfo .= "- User is uploading NEW images in this message\n";
            }
            $contextInfo .= "\nUSE THIS CONTEXT to understand what the user is asking about. Their ORIGINAL crop is what they first uploaded - any new images may be REFERENCE images to compare against.\n";
        }

        try {
            $systemPrompt = <<<PROMPT
You are a conversation analyzer for a farming chat assistant. The user previously uploaded crop photos and got an analysis. Now they sent a follow-up message.
{$contextInfo}
Decide if this follow-up NEEDS the images to be re-analyzed, or if it can be answered using just the chat history (previous response).

RESPOND ONLY WITH VALID JSON:
{
    "needsImageReanalysis": true/false,
    "usesChatHistory": true/false,
    "followUpType": "comparison_change|unit_conversion|clarification|advice|acknowledgment|new_analysis|reference_image_comparison|unrelated_topic",
    "newComparisonTarget": "variety name if followUpType is comparison_change or reference_image_comparison, else null",
    "originalCropVariety": "echo back the user's original crop variety from context, or null if unknown",
    "reason": "brief explanation"
}

EXAMPLES:

User: "ano yan sa cavans per hectare?"
→ {"needsImageReanalysis":false,"usesChatHistory":true,"followUpType":"unit_conversion","newComparisonTarget":null,"originalCropVariety":"Jackpot 102","reason":"Unit conversion question - just needs previous yield data"}

User: "magkano ang expected na kita?"
→ {"needsImageReanalysis":false,"usesChatHistory":true,"followUpType":"advice","newComparisonTarget":null,"originalCropVariety":"Jackpot 102","reason":"Income calculation - uses previous yield estimate"}

User: "paki-check ulit ang mga dahon"
→ {"needsImageReanalysis":true,"usesChatHistory":true,"followUpType":"crop_status_check","newComparisonTarget":null,"originalCropVariety":"Jackpot 102","reason":"Re-examining user's original crop photos"}

User: "may nakita akong dilaw na dahon, check mo"
→ {"needsImageReanalysis":true,"usesChatHistory":true,"followUpType":"new_analysis","newComparisonTarget":null,"originalCropVariety":"Jackpot 102","reason":"New observation, needs fresh analysis"}

User: "napansin mo ba kung merong problema sa mga tanim ko?"
→ {"needsImageReanalysis":false,"usesChatHistory":true,"followUpType":"crop_status_check","newComparisonTarget":null,"originalCropVariety":"Jackpot 102","reason":"Asking about problems in their ORIGINAL crop - use previous analysis"}

User: "may problema ba sa mga halaman ko?"
→ {"needsImageReanalysis":false,"usesChatHistory":true,"followUpType":"crop_status_check","newComparisonTarget":null,"originalCropVariety":"Jackpot 102","reason":"Asking about crop status - refer to original analysis"}

User: "base sa mga nakita mong larawan, ilan kaya sa tingin mo aanihin ko"
→ {"needsImageReanalysis":false,"usesChatHistory":true,"followUpType":"crop_status_check","newComparisonTarget":null,"originalCropVariety":"Jackpot 102","reason":"Asking about THEIR crop's expected yield - use original analysis data"}

User: "ilan ang inaasahang ani ko?"
→ {"needsImageReanalysis":false,"usesChatHistory":true,"followUpType":"crop_status_check","newComparisonTarget":null,"originalCropVariety":"Jackpot 102","reason":"Asking about expected harvest for THEIR crop - NOT comparison varieties"}

User: "magkano kaya aanihin ko base sa larawan?"
→ {"needsImageReanalysis":false,"usesChatHistory":true,"followUpType":"crop_status_check","newComparisonTarget":null,"originalCropVariety":"Jackpot 102","reason":"Yield estimate question for user's crop - use original analysis"}

User: "salamat! clear na"
→ {"needsImageReanalysis":false,"usesChatHistory":false,"followUpType":"acknowledgment","newComparisonTarget":null,"originalCropVariety":"Jackpot 102","reason":"Acknowledgment, no analysis needed"}

User: "paano mag-alaga ng baboy?" (conversation was about rice/corn crops)
→ {"needsImageReanalysis":false,"usesChatHistory":false,"followUpType":"unrelated_topic","newComparisonTarget":null,"originalCropVariety":"Jackpot 102","reason":"Question about pigs is unrelated to the crop analysis conversation"}

User: "ano ba magandang fertilizer sa orchids?" (conversation was about palay)
→ {"needsImageReanalysis":false,"usesChatHistory":false,"followUpType":"unrelated_topic","newComparisonTarget":null,"originalCropVariety":"Jackpot 102","reason":"Orchids question is unrelated to rice conversation"}

User: "pwede mo ba akong turuan mag-code?" (conversation was about farming)
→ {"needsImageReanalysis":false,"usesChatHistory":false,"followUpType":"unrelated_topic","newComparisonTarget":null,"originalCropVariety":"Jackpot 102","reason":"Coding is completely unrelated to farming conversation"}

User: "e yung manok ko may sakit" (conversation was about rice crop)
→ {"needsImageReanalysis":false,"usesChatHistory":false,"followUpType":"unrelated_topic","newComparisonTarget":null,"originalCropVariety":"Jackpot 102","reason":"Chicken/poultry issue is unrelated to crop analysis"}

User: "magkano presyo ng bigas ngayon?" (conversation was about crop health analysis)
→ {"needsImageReanalysis":false,"usesChatHistory":false,"followUpType":"unrelated_topic","newComparisonTarget":null,"originalCropVariety":"Jackpot 102","reason":"Market price question is unrelated to crop health/yield analysis"}

User: "ok sige, ano pang dapat gawin?"
→ {"needsImageReanalysis":false,"usesChatHistory":true,"followUpType":"advice","newComparisonTarget":null,"originalCropVariety":"Jackpot 102","reason":"Asking for more advice based on previous analysis"}

User: "compare mo naman sa RC160"
→ {"needsImageReanalysis":false,"usesChatHistory":true,"followUpType":"comparison_change","newComparisonTarget":"RC160","originalCropVariety":"Jackpot 102","reason":"Same photos, different comparison target"}

User: "e sa traditional na RC222 pwede mo ba ikumpara"
→ {"needsImageReanalysis":false,"usesChatHistory":true,"followUpType":"comparison_change","newComparisonTarget":"RC222","originalCropVariety":"Jackpot 102","reason":"Comparing same photos to different variety"}

User: "e sa r160 at rc222 pwede mo ba i kumpara ung akin?"
→ {"needsImageReanalysis":false,"usesChatHistory":true,"followUpType":"comparison_change","newComparisonTarget":"RC160, RC222","originalCropVariety":"Jackpot 102","reason":"Multiple comparison targets - need SEPARATE tables for each"}

User: "compare sa SL-8H at NSIC Rc222"
→ {"needsImageReanalysis":false,"usesChatHistory":true,"followUpType":"comparison_change","newComparisonTarget":"SL-8H, NSIC Rc222","originalCropVariety":"Jackpot 102","reason":"Two varieties to compare separately"}

User: "pano kung icompare sa inbred varieties?"
→ {"needsImageReanalysis":false,"usesChatHistory":true,"followUpType":"comparison_change","newComparisonTarget":"inbred varieties","originalCropVariety":"Jackpot 102","reason":"Different comparison reference"}

User: "bakit below expected?"
→ {"needsImageReanalysis":false,"usesChatHistory":true,"followUpType":"clarification","newComparisonTarget":null,"originalCropVariety":"Jackpot 102","reason":"Clarification about previous response"}

User: "mag-upload ako ng bagong litrato para i-check"
→ {"needsImageReanalysis":true,"usesChatHistory":true,"followUpType":"new_analysis","newComparisonTarget":null,"originalCropVariety":"Jackpot 102","reason":"User mentions uploading NEW photos"}

User: "can you compare mine to that image longping i uploaded"
→ {"needsImageReanalysis":true,"usesChatHistory":true,"followUpType":"reference_image_comparison","newComparisonTarget":"Longping","originalCropVariety":"Jackpot 102","reason":"User uploaded a REFERENCE image to compare their crop against"}

User: "pwede mo ba ikumpara sa uploaded na larawan ng SL-8H"
→ {"needsImageReanalysis":true,"usesChatHistory":true,"followUpType":"reference_image_comparison","newComparisonTarget":"SL-8H","originalCropVariety":"Jackpot 102","reason":"New image is REFERENCE - compare user's original crop to it"}

User: "meron akong larawan dito, pwede mo ba tignan at ikumpara yung akin"
→ {"needsImageReanalysis":true,"usesChatHistory":true,"followUpType":"reference_image_comparison","newComparisonTarget":"unknown - extract from image","originalCropVariety":"Jackpot 102","reason":"User uploading NEW reference image to compare THEIR original crop against"}

User: "check mo itong picture, ikumpara mo sa akin"
→ {"needsImageReanalysis":true,"usesChatHistory":true,"followUpType":"reference_image_comparison","newComparisonTarget":"unknown - extract from image","originalCropVariety":"Jackpot 102","reason":"New image is REFERENCE - compare user's ORIGINAL crop to this"}

RULES:
- needsImageReanalysis=true ONLY if they want to RE-CHECK the actual photos (look at leaves again, check for disease, etc.) OR if they're uploading NEW images
- needsImageReanalysis=false if asking to COMPARE same photos to DIFFERENT variety/reference - the photo DATA stays the same, only comparison target changes
- needsImageReanalysis=false if they're asking about the PREVIOUS RESPONSE (conversion, clarification, follow-up advice)
- usesChatHistory=true if they reference the previous conversation or want to compare to different target
- usesChatHistory=false only for greetings/thanks with no actual question
- CRITICAL: When user asks about expected yield/harvest ("ilan aanihin ko", "magkano ani", etc.), this is crop_status_check because they're asking about THEIR crop, not the comparison varieties!
- After a comparison_change, if user asks yield questions, the answer should be about THEIR ORIGINAL crop variety, NOT the comparison varieties
- ALWAYS include originalCropVariety from the provided context - this is the user's ACTUAL crop that they planted

⚠️ CRITICAL NEW RULE - VAGUE MESSAGE WITH NEW IMAGES:
- When hasNewImages=true AND the message is VAGUE/SHORT (like "e ito", "ano problema", "check mo", "ito ano ito"):
  * The new images could be a COMPLETELY DIFFERENT CROP (e.g., user was asking about mais, now uploading palay)
  * Set usesChatHistory=FALSE because we don't know if it's related to the previous topic
  * Set originalCropVariety=null because we need to analyze the new images first
  * Set followUpType="new_topic_with_images"
- EXAMPLES of vague messages with NEW images (hasNewImages=true):
  * "e ito ano problema" → {"needsImageReanalysis":true,"usesChatHistory":false,"followUpType":"new_topic_with_images","newComparisonTarget":null,"originalCropVariety":null,"reason":"Vague message with NEW images - could be different crop, treat as new topic"}
  * "check mo ito" → {"needsImageReanalysis":true,"usesChatHistory":false,"followUpType":"new_topic_with_images","newComparisonTarget":null,"originalCropVariety":null,"reason":"Short message with NEW images - analyze images first to determine crop type"}
  * "ano nakikita mo dito" → {"needsImageReanalysis":true,"usesChatHistory":false,"followUpType":"new_topic_with_images","newComparisonTarget":null,"originalCropVariety":null,"reason":"Generic question with NEW images - do not assume same crop as previous chat"}
- CONTRAST with reference_image_comparison:
  * reference_image_comparison: User EXPLICITLY mentions comparing to their previous crop ("compare sa akin", "ikumpara mo sa akin")
  * new_topic_with_images: User just asks about the NEW images without mentioning comparison

IMPORTANT SCENARIOS:
1. "compare my crop to X instead" (NO new image uploaded) → comparison_change, needsImageReanalysis=false
2. "compare mine to that uploaded image of X" (NEW image uploaded) → reference_image_comparison, needsImageReanalysis=true
   - The NEW image is a REFERENCE (competitor variety, seed bag, etc.)
   - User's original crop (from earlier) should be compared TO this reference
   - Extract newComparisonTarget from the reference variety name
3. User uploads a new image with message like "check mo ito" or "pwede tignan at ikumpara":
   - This is reference_image_comparison - the NEW image is what they want to compare AGAINST
   - Their ORIGINAL crop (from context) is what we're evaluating
   - DO NOT confuse the reference image variety with the user's crop

⚠️ CRITICAL RULE - UNRELATED TOPIC DETECTION:
- If the follow-up message is asking about a COMPLETELY DIFFERENT TOPIC that has NOTHING to do with the current conversation:
  * Mark as followUpType="unrelated_topic"
  * Set needsImageReanalysis=false
  * Set usesChatHistory=false
- UNRELATED means topics that are NOT connected to the crop/farming analysis being discussed:
  * Asking about different animals (baboy, manok, etc.) when we were talking about crops
  * Asking about completely different plants (orchids, flowers) when we were talking about rice/corn
  * Asking about non-farming topics (coding, recipes, politics, etc.)
  * Asking about market prices, sales, or business unrelated to the crop being analyzed
- BUT these are STILL RELATED (NOT unrelated_topic):
  * Questions about pests, diseases, fertilizers for the SAME crop type
  * Yield calculations, income estimates for the analyzed crop
  * Comparison to other varieties of the SAME crop type
  * Growing tips, schedules, or recommendations for the same crop
  * Any follow-up about the analysis we just provided
- KEY: If the user's question can reasonably be connected to the original crop/farming topic, it's NOT unrelated
PROMPT;

            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $openaiSetting->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                    'max_tokens' => 150,
                    'temperature' => 0.1,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '';

                // Track token usage
                $usage = $data['usage'] ?? [];
                $inputTokens = $usage['prompt_tokens'] ?? 0;
                $outputTokens = $usage['completion_tokens'] ?? 0;
                $this->trackTokenUsage('openai', 'followup_classification', $inputTokens, $outputTokens, 'gpt-4o-mini');

                // Parse JSON response
                $content = trim($content);
                $content = preg_replace('/^```json\s*/i', '', $content);
                $content = preg_replace('/\s*```$/i', '', $content);

                $result = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($result)) {
                    Log::info('Follow-up classified successfully', [
                        'needsReanalysis' => $result['needsImageReanalysis'] ?? true,
                        'usesChatHistory' => $result['usesChatHistory'] ?? true,
                        'followUpType' => $result['followUpType'] ?? 'unknown',
                        'newComparisonTarget' => $result['newComparisonTarget'] ?? null,
                        'originalCropVariety' => $result['originalCropVariety'] ?? null,
                        'reason' => $result['reason'] ?? 'N/A',
                    ]);

                    $this->logFlowStep('Follow-up Classification', json_encode([
                        'reanalyze' => $result['needsImageReanalysis'] ?? true,
                        'chatHistory' => $result['usesChatHistory'] ?? true,
                        'type' => $result['followUpType'] ?? 'unknown',
                        'target' => $result['newComparisonTarget'] ?? null,
                        'userCrop' => $result['originalCropVariety'] ?? null,
                        'reason' => $result['reason'] ?? 'N/A',
                    ]));

                    return $result;
                }
            }

        } catch (\Exception $e) {
            Log::error('Follow-up classification failed', ['error' => $e->getMessage()]);
        }

        return $defaultResult;
    }

    /**
     * Perform deep analysis on uploaded images using GPT-4 Vision.
     * Used for COMPARISON scenarios where GPT-4 provides better analysis.
     *
     * @param array $imagePaths Array of image paths from storage
     * @param string $userMessage User's message/question about the images
     * @param string|null $topicContext Optional topic context
     * @param array|null $inquiryDetails Pre-classified inquiry details (optional)
     * @return array Analysis result with 'success', 'analysis', and 'summary' keys
     */
    public function analyzeImagesWithGPT(array $imagePaths, string $userMessage = '', ?string $topicContext = null, ?array $inquiryDetails = null): array
    {
        if (empty($imagePaths)) {
            return [
                'success' => false,
                'analysis' => '',
                'summary' => 'No images provided for analysis.',
            ];
        }

        $this->logFlowStep('GPT-4 Vision Analysis', 'Analyzing ' . count($imagePaths) . ' image(s) for comparison');

        // Get OpenAI API setting (global)
        $openaiSetting = AiApiSetting::active()
            ->forProvider(AiApiSetting::PROVIDER_OPENAI)
            ->enabled()
            ->first();

        if (!$openaiSetting || !$openaiSetting->apiKey) {
            Log::warning('OpenAI API not configured for GPT-4 Vision');
            // Fallback to Gemini
            return $this->analyzeUploadedImages($imagePaths, $userMessage, $topicContext);
        }

        // Enforce rate limiting
        $this->enforceRateLimit('openai-vision');

        try {
            // Build content array with images
            $content = [];

            // Add text prompt first - DETAILED COMPARISON PROMPT
            // Use pre-classified inquiry details if provided
            $prompt = $this->buildGPTComparisonPrompt($userMessage, count($imagePaths), $inquiryDetails);
            $content[] = ['type' => 'text', 'text' => $prompt];

            // Add images (up to 10)
            $imageCount = 0;
            foreach ($imagePaths as $imagePath) {
                if ($imageCount >= 10) break;

                $fullPath = Storage::disk('public')->path($imagePath);
                if (file_exists($fullPath)) {
                    $imageData = base64_encode(file_get_contents($fullPath));
                    $mimeType = mime_content_type($fullPath);
                    $fileSize = filesize($fullPath);

                    // Skip if too large (20MB limit)
                    if ($fileSize > 20 * 1024 * 1024) {
                        continue;
                    }

                    $content[] = [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => "data:{$mimeType};base64,{$imageData}",
                            'detail' => 'high', // Use high detail for better analysis
                        ],
                    ];
                    $imageCount++;
                }
            }

            if ($imageCount === 0) {
                return [
                    'success' => false,
                    'analysis' => '',
                    'summary' => 'Could not load any images for analysis.',
                ];
            }

            $this->logFlowStep('GPT-4 Vision', "Processing {$imageCount} image(s) with GPT-4o");

            // Use GPT-4o (latest vision-capable model)
            $response = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $openaiSetting->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $this->buildGPTComparisonSystemPrompt(),
                        ],
                        [
                            'role' => 'user',
                            'content' => $content,
                        ],
                    ],
                    'max_tokens' => 4000,
                    'temperature' => 0.3, // Lower for more factual analysis
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $analysis = $data['choices'][0]['message']['content'] ?? '';

                // Track token usage
                $usage = $data['usage'] ?? [];
                $inputTokens = $usage['prompt_tokens'] ?? 0;
                $outputTokens = $usage['completion_tokens'] ?? 0;
                $this->trackTokenUsage('openai', 'gpt4_vision_comparison', $inputTokens, $outputTokens, 'gpt-4o');

                Log::info('GPT-4 Vision analysis completed', [
                    'imageCount' => $imageCount,
                    'analysisLength' => strlen($analysis),
                    'inputTokens' => $inputTokens,
                    'outputTokens' => $outputTokens,
                ]);

                // Check if GPT-4 refused to analyze (content policy or other refusal)
                $refusalPatterns = [
                    "I'm sorry",
                    "I cannot",
                    "I can't",
                    "I am unable",
                    "I'm unable",
                    "cannot assist",
                    "can't assist",
                    "not able to",
                    "unable to analyze",
                    "cannot analyze",
                ];

                $isRefusal = false;
                foreach ($refusalPatterns as $pattern) {
                    if (stripos($analysis, $pattern) !== false && strlen($analysis) < 100) {
                        $isRefusal = true;
                        break;
                    }
                }

                if ($isRefusal) {
                    Log::warning('GPT-4 Vision refused to analyze images', [
                        'response' => $analysis,
                        'imageCount' => $imageCount,
                    ]);
                    $this->logFlowStep('GPT-4 Vision Refused', 'Falling back to Gemini Vision');

                    // Fallback to Gemini Vision
                    return $this->analyzeUploadedImages($imagePaths, $userMessage, $topicContext);
                }

                $this->logFlowStep('GPT-4 Vision Complete', strlen($analysis) . ' characters generated');

                return [
                    'success' => true,
                    'analysis' => $analysis,
                    'summary' => Str::limit($analysis, 200),
                    'imageCount' => $imageCount,
                    'provider' => 'gpt-4o',
                ];
            }

            $errorMessage = $response->json('error.message') ?? 'GPT-4 Vision API error';
            Log::error('GPT-4 Vision API failed', [
                'status' => $response->status(),
                'error' => $errorMessage,
            ]);

            // Fallback to Gemini on error
            return $this->analyzeUploadedImages($imagePaths, $userMessage, $topicContext);

        } catch (\Exception $e) {
            Log::error('GPT-4 Vision exception: ' . $e->getMessage());
            // Fallback to Gemini
            return $this->analyzeUploadedImages($imagePaths, $userMessage, $topicContext);
        }
    }

    /**
     * Build comparison prompt for GPT-4 Vision.
     * This prompt is designed to get detailed, objective technical analysis.
     *
     * @param string $userMessage The user's message
     * @param int $imageCount Number of images
     * @param array|null $inquiryDetails Pre-classified inquiry details from AI
     */
    protected function buildGPTComparisonPrompt(string $userMessage, int $imageCount, ?array $inquiryDetails = null): string
    {
        // Use AI-extracted details if available, otherwise use defaults
        $comparisonType = null;

        if ($inquiryDetails && !empty($inquiryDetails['userCropVariety'])) {
            // AI-extracted values (preferred)
            $userVariety = strtoupper($inquiryDetails['userCropVariety']);
            $comparisonTarget = !empty($inquiryDetails['comparisonTarget'])
                ? strtoupper($inquiryDetails['comparisonTarget'])
                : 'traditional farming';
            $dat = !empty($inquiryDetails['dat']) ? "DAT " . $inquiryDetails['dat'] : '';
            $growthStage = $inquiryDetails['growthStage'] ?? null;
            $comparisonType = $inquiryDetails['comparisonType'] ?? null;

            Log::info('Using AI-extracted inquiry details for prompt', [
                'userVariety' => $userVariety,
                'comparisonTarget' => $comparisonTarget,
                'comparisonType' => $comparisonType,
                'dat' => $dat,
                'growthStage' => $growthStage,
            ]);
        } else {
            // Fallback to regex extraction (legacy support)
            $userVariety = 'the crop';
            if (preg_match('/\b(jackpot\s*\d*|sl-?\d+h?|rc\s*\d+|nk\s*\d+)\s*(ko|akin|namin)/i', $userMessage, $match)) {
                $userVariety = strtoupper(preg_replace('/\s+/', ' ', $match[1]));
            }

            $comparisonTarget = 'traditional farming';
            if (preg_match('/(?:vs|kumpara|ikumpara).*?(?:ng|sa)\s*([A-Za-z]+[\s-]?\d+)/i', $userMessage, $match)) {
                $comparisonTarget = strtoupper(preg_replace('/\s+/', '', $match[1]));
            } elseif (preg_match('/(?:vs|kumpara|ikumpara)\s+(?:sa\s+)?([A-Za-z]+[\s-]?\d+)/i', $userMessage, $match)) {
                $comparisonTarget = strtoupper(preg_replace('/\s+/', '', $match[1]));
            }

            $dat = '';
            if (preg_match('/\b(dat|DAT)\s*(\d+)/i', $userMessage, $match)) {
                $dat = "DAT " . $match[2];
            }
            $growthStage = null;

            Log::info('Using regex fallback for prompt extraction', [
                'userVariety' => $userVariety,
                'comparisonTarget' => $comparisonTarget,
            ]);
        }

        // Detect if this is a same-variety comparison (checking against standard of same variety)
        // Use comparisonType if available (from AI classification), otherwise use string detection
        $isSameVarietyComparison = ($comparisonType === 'same_variety_standard') ||
                                   stripos($comparisonTarget, 'Standard') !== false ||
                                   stripos($comparisonTarget, $userVariety) !== false ||
                                   stripos($comparisonTarget, str_replace(' ', '', $userVariety)) !== false;

        // Build a simple, direct prompt for agricultural crop analysis
        $prompt = "Farmer uploaded {$imageCount} photos of their {$userVariety} rice" . ($dat ? " at {$dat}" : "") . ".\n";
        $prompt .= "Question: \"{$userMessage}\"\n\n";

        $prompt .= "Please analyze these rice plant photos:\n\n";

        $prompt .= "1. WHAT I SEE IN THE PHOTOS:\n";
        $prompt .= "   - Overall field condition\n";
        $prompt .= "   - Panicles per hill (count them)\n";
        $prompt .= "   - Panicle length (estimate in cm)\n";
        $prompt .= "   - Leaf color and health\n";
        $prompt .= "   - Any problems visible\n\n";

        if ($isSameVarietyComparison) {
            // Same variety comparison - checking if user's crop is on track vs expected performance
            $prompt .= "2. PERFORMANCE CHECK - Is this {$userVariety} on track?\n";
            $prompt .= "   Compare what I see vs EXPECTED/TYPICAL {$userVariety} performance:\n\n";
            $prompt .= "   | Factor | Your Field (Photos) | Expected for {$userVariety} | On Track? |\n";
            $prompt .= "   |--------|---------------------|----------------------------|----------|\n";
            $prompt .= "   | Panicles/hill | [count from photos] | [expected: 10-15] | Yes/Below/Above |\n";
            $prompt .= "   | Panicle length | [cm from photos] | [expected: 25-30 cm] | Yes/Below/Above |\n";
            $prompt .= "   | Leaf health | [from photos] | [should be green] | Yes/Issue |\n";
            $prompt .= "   | Plant vigor | [from photos] | [should be strong] | Yes/Weak |\n";
            $prompt .= "   | Growth stage | [from photos] | [expected at {$dat}] | Yes/Behind/Ahead |\n\n";

            $prompt .= "3. YIELD PROJECTION (show BOTH MT/ha AND cavans/ha):\n";
            $prompt .= "   Note: 1 MT = 20 cavans (1 cavan = 50kg)\n";
            $prompt .= "   - {$userVariety} typical yield: 6-10 MT/ha (120-200 cavans/ha)\n";
            $prompt .= "   - Your field potential (from photos): ___ MT/ha (___ cavans/ha)\n";
            $prompt .= "   - Status: On Track / Below Expected / Above Expected\n\n";
        } else {
            // Cross-variety comparison
            $prompt .= "2. COMPARISON TABLE:\n";
            $prompt .= "   Compare what I see vs typical {$comparisonTarget}:\n\n";
            $prompt .= "   | Factor | Your {$userVariety} (Photos) | Typical {$comparisonTarget} | Assessment |\n";
            $prompt .= "   |--------|------------------------------|---------------------------|------------|\n";
            $prompt .= "   | Panicles/hill | [count from photos] | [typical for {$comparisonTarget}] | Higher/Lower |\n";
            $prompt .= "   | Panicle length | [cm from photos] | [typical cm] | Higher/Lower |\n";
            $prompt .= "   | Leaf health | [from photos] | [typical] | Better/Worse |\n";
            $prompt .= "   | Plant vigor | [from photos] | [typical] | Better/Worse |\n\n";

            $prompt .= "3. YIELD ESTIMATE (show BOTH MT/ha AND cavans/ha):\n";
            $prompt .= "   Note: 1 MT = 20 cavans (1 cavan = 50kg)\n";
            $prompt .= "   - Your {$userVariety} field (from photos): ___ MT/ha (___ cavans/ha)\n";
            $prompt .= "   - Typical {$comparisonTarget}: ___ MT/ha (___ cavans/ha)\n";
            $prompt .= "   - Verdict: Your crop is Higher/Lower/Similar\n\n";
        }

        $prompt .= "4. ADVICE:\n";
        $prompt .= "   What should the farmer do now based on what you see?\n\n";

        $prompt .= "Respond in Tagalog. Be specific with numbers.\n";

        return $prompt;
    }

    /**
     * Build system prompt for GPT-4 Vision comparison analysis.
     */
    protected function buildGPTComparisonSystemPrompt(): string
    {
        return "You are an agricultural advisor helping Filipino rice farmers. Analyze their crop photos and provide farming guidance.

Your task:
1. Look at the rice plant photos
2. Describe what you see (plant condition, panicles, leaves, growth stage)
3. Compare to standard varieties if asked
4. Give practical advice

Respond in Filipino/Tagalog. Use English for technical terms.

IMPORTANT - Always show yield in BOTH units:
- MT/ha (metric tons per hectare)
- Cavans/ha (1 MT = 20 cavans, since 1 cavan = 50kg)

Reference yields:
- Hybrid rice (Jackpot 102, SL-8H): 6-10 MT/ha (120-200 cavans/ha)
- Inbred rice (RC160, RC222): 4-6 MT/ha (80-120 cavans/ha)";
    }

    /**
     * Build the image analysis prompt based on context.
     * SIMPLIFIED VERSION - keeps prompts short to avoid API truncation
     */
    protected function buildImageAnalysisPrompt(array $imagePaths, string $userMessage, ?string $topicContext): string
    {
        $imageCount = count($imagePaths);

        // Start with a focused prompt
        $prompt = "SURIIN ANG {$imageCount} LARAWAN. Sagutin sa TAGALOG (English para sa technical terms lang).\n\n";

        // Add user's question
        if (!empty($userMessage) && $userMessage !== '[Image uploaded]' && $userMessage !== '[Images uploaded]') {
            $prompt .= "TANONG NG USER: \"{$userMessage}\"\n\n";

            // Check if this is a direct yes/no question
            $isDirectQuestion = preg_match('/\b(normal ba|okay lang ba|malusog ba|problema ba|may mali ba|on track ba)\b/i', $userMessage);

            // Check if this is a COMPARISON question
            $isComparisonQuestion = preg_match('/\b(mas maganda|mas mabuti|mas mataas|kaysa|kumpara|vs|versus|alin ang mas|honest|unbiased|pagkakaiba)\b/i', $userMessage);
            $wantsYieldComparison = preg_match('/\b(mataas ang ani|yield|MT\/ha|magandang ani|comparison)\b/i', $userMessage);

            // ================================================================
            // CRITICAL: DETECT CROSS-VARIETY COMPARISON
            // Example: "ito ang jackpot 102 ko... ikumpara vs RC222"
            // User's crop = Jackpot 102, Comparison target = RC222
            // ================================================================

            // Extract ALL variety names mentioned in the message
            $varietyPattern = '/\b(jackpot\s*\d*|sl-?\d+h?|rc\s*\d+|nsic\s*rc\s*\d+|nk\s*\d+|dekalb\s*\d*|pioneer\s*\w*|arize\s*\w*|bigante|mestiso\s*\d*)\b/i';
            preg_match_all($varietyPattern, $userMessage, $allVarietyMatches);
            $mentionedVarieties = array_unique(array_map('strtoupper', $allVarietyMatches[0] ?? []));

            // Detect user's crop variety (before "ko", "akin", or at the start)
            $userCropVariety = null;
            if (preg_match('/\b(jackpot\s*\d*|sl-?\d+h?|rc\s*\d+|nk\s*\d+|dekalb\s*\d*|pioneer\s*\w*|arize\s*\w*|bigante|mestiso\s*\d*)\s+(ko|akin|namin|natin)\b/i', $userMessage, $userCropMatch)) {
                $userCropVariety = strtoupper($userCropMatch[1]);
            } elseif (preg_match('/\b(ito|itsura|larawan).*(ng|sa)\s+(jackpot\s*\d*|sl-?\d+h?|rc\s*\d+|nk\s*\d+)/i', $userMessage, $userCropMatch)) {
                $userCropVariety = strtoupper($userCropMatch[3]);
            }

            // Detect comparison target variety (after "vs", "kumpara", "standard/traditional farming ng")
            $comparisonTargetVariety = null;
            if (preg_match('/\b(vs|versus|kumpara\s*(sa)?|ikumpara\s*(sa)?|standard|traditional)\s+(o\s+)?(traditional\s+)?(farming\s+)?(ng\s+|sa\s+)?(jackpot\s*\d*|sl-?\d+h?|rc\s*\d+|nsic\s*rc\s*\d+|nk\s*\d+|dekalb\s*\d*|pioneer\s*\w*|arize\s*\w*|bigante|mestiso\s*\d*)/i', $userMessage, $targetMatch)) {
                $comparisonTargetVariety = strtoupper(end($targetMatch));
            }

            // Check if this is a CROSS-VARIETY comparison (user's crop vs different variety)
            $isCrossVarietyComparison = $userCropVariety && $comparisonTargetVariety &&
                                        $userCropVariety !== $comparisonTargetVariety &&
                                        !str_contains($userCropVariety, $comparisonTargetVariety) &&
                                        !str_contains($comparisonTargetVariety, $userCropVariety);

            // Log for debugging
            Log::debug('Variety comparison detection', [
                'userMessage' => substr($userMessage, 0, 200),
                'mentionedVarieties' => $mentionedVarieties,
                'userCropVariety' => $userCropVariety,
                'comparisonTargetVariety' => $comparisonTargetVariety,
                'isCrossVarietyComparison' => $isCrossVarietyComparison,
            ]);

            // CRITICAL: Detect if user mentions a SPECIFIC variety name with "traditional" or "standard"
            $mentionsSpecificVariety = !empty($mentionedVarieties);
            $mentionsTraditionalWithVariety = preg_match('/\b(traditional|tradisyonal)\s+(na\s+)?(jackpot|sl-?8h|sl-?9h|rc|nk|dekalb|pioneer)/i', $userMessage);
            $mentionsStandardFarming = preg_match('/\b(standard|traditional)\s+(o\s+)?(traditional\s+)?(farming|practices|method|paraan)/i', $userMessage);

            // Determine comparison type
            $wantsStandardComparison = $mentionsTraditionalWithVariety || $mentionsStandardFarming ||
                                       ($mentionsSpecificVariety && preg_match('/\b(ikumpara|compare|kumpara)\b/i', $userMessage));

            // Handle COMPARISON requests - SIMPLIFIED
            if ($wantsStandardComparison || $isCrossVarietyComparison) {
                $varietyName = $comparisonTargetVariety ?: $userCropVariety ?: 'ang variety';
                $userVarietyDisplay = $userCropVariety ?: 'ang inyong tanim';

                // For ALL cases with images, ALWAYS start with visual observation requirement
                $prompt .= "🔍 STEP 1 - TINGNAN MO MUNA ANG BAWAT LARAWAN (isa-isa!):\n\n";
                $prompt .= "Para sa BAWAT larawan, TUMINGIN KA MABUTI at ilarawan ang YIELD FACTORS:\n";
                $prompt .= "📷 Larawan 1:\n";
                $prompt .= "   - Panicles/hill: ~___ (bilangin kung nakikita)\n";
                $prompt .= "   - Spikelets density: sparse/medium/dense\n";
                $prompt .= "   - Grain filling: ~___% (puno vs walang laman)\n";
                $prompt .= "   - Tillers/hill: ~___ (if visible)\n";
                $prompt .= "   - Growth stage: vegetative/heading/flowering/grain filling\n";
                $prompt .= "   - Problema: ___ (kung meron)\n";
                $prompt .= "(repeat for each image)\n\n";
                $prompt .= "⚠️ HUWAG gumamit ng sequential numbers (10, 12, 14, 16)!\n";
                $prompt .= "⚠️ BILANGIN o I-ESTIMATE based sa ACTUAL na nakikita!\n\n";

                if ($isCrossVarietyComparison) {
                    $prompt .= "🔍 STEP 2 - VARIETY SPECS (HINDI DIRECT COMPARISON):\n\n";
                    $prompt .= "⚠️ PAUNAWA - BASAHIN MUNA ITO:\n";
                    $prompt .= "Ang direktang pagkukumpara ng iba't ibang variety na tinanim sa magkaibang lokasyon\n";
                    $prompt .= "ay maaaring hindi tumpak at maaaring magbigay ng maling expectation.\n\n";
                    $prompt .= "Maraming factors ang nakakaapekto sa performance ng isang tanim:\n";
                    $prompt .= "- Lokasyon at uri ng lupa\n";
                    $prompt .= "- Klima at panahon\n";
                    $prompt .= "- Pamamaraan ng pagsasaka (irrigation, fertilizer, etc.)\n";
                    $prompt .= "- Antas ng pamamahala\n\n";
                    $prompt .= "Ang pinakamahusay na pagkukumpara ay sa PAREHONG VARIETY na tinanim sa PAREHONG lokasyon.\n\n";
                    $prompt .= "📊 GAWIN MO ITO (DALAWANG HIWALAY NA TABLES):\n\n";
                    $prompt .= "TABLE 1 - PANGKALAHATANG SPECS NG {$varietyName}:\n";
                    $prompt .= "| Katangian | {$varietyName} (Typical/Standard Specs) |\n";
                    $prompt .= "|-----------|----------------------------------------|\n";
                    $prompt .= "| Maturity | X-Y days |\n";
                    $prompt .= "| Plant height | X-Y cm |\n";
                    $prompt .= "| Panicles/hill | X-Y (typical) |\n";
                    $prompt .= "| Panicle length | X-Y cm |\n";
                    $prompt .= "| Yield potential | X-Y MT/ha (XX-YY cavans/ha) |\n";
                    $prompt .= "| Grain type | (kung available) |\n";
                    $prompt .= "| Disease resistance | (kung available) |\n\n";
                    $prompt .= "TABLE 2 - INYONG KASALUKUYANG OBSERBASYON ({$userCropVariety}):\n";
                    $prompt .= "| Katangian | Inyong {$userCropVariety} (Mula sa Larawan) |\n";
                    $prompt .= "|-----------|-------------------------------------------|\n";
                    $prompt .= "| Panicles/hill | ~X (bilangin mula sa larawan) |\n";
                    $prompt .= "| Panicle length | ~X cm (estimate mula sa larawan) |\n";
                    $prompt .= "| Kalusugan ng dahon | (describe what you see) |\n";
                    $prompt .= "| Growth stage | (based sa larawan) |\n";
                    $prompt .= "| Inaasahang ani | ~X-Y MT/ha (XX-YY cavans/ha) |\n\n";
                    $prompt .= "📝 PAALALA:\n";
                    $prompt .= "Ang specs table ay nagpapakita ng TYPICAL/IDEAL na performance.\n";
                    $prompt .= "Ang aktwal na performance ay depende sa local factors sa inyong bukid.\n";
                    $prompt .= "Para sa mas accurate na comparison, ikumpara sa parehong variety sa parehong lokasyon.\n\n";
                    $prompt .= "✅ RECOMMENDATIONS:\n";
                    $prompt .= "- Ano ang dapat gawin para ma-maximize ang yield?\n";
                    $prompt .= "- May problema ba na nakita sa larawan?\n\n";
                }
            } else if ($isDirectQuestion) {
                $prompt .= "🔍 TINGNAN ANG LARAWAN - sagutin kung NORMAL o may PROBLEMA.\n";
                $prompt .= "ILARAWAN: exact color, height estimate, panicle %, any visible issues.\n\n";
            } else {
                $prompt .= "🔍 TINGNAN ANG MGA LARAWAN AT ILARAWAN:\n";
                $prompt .= "- Kulay ng dahon (specific shade)\n";
                $prompt .= "- Panicle/uhay status (% at length)\n";
                $prompt .= "- Grain filling status\n";
                $prompt .= "- Plant height estimate\n";
                $prompt .= "- Anumang problema na nakikita\n\n";
            }
        }

        // Footer with strict rules
        $prompt .= "⚠️ RULES:\n";
        $prompt .= "- START with 'Batay sa mga larawan:' then describe what you ACTUALLY SEE\n";
        $prompt .= "- USE specific numbers based on ACTUAL visual (not patterns like 15%, 20%, 25%)\n";
        $prompt .= "- BAWAL generic like 'malusog' - describe colors, heights, percentages\n";
        $prompt .= "- For comparison: Use 'Ang IYONG tanim' NOT generic variety specs\n";
        $prompt .= "- If you cannot see something, say 'hindi malinaw sa larawan'\n\n";

        $prompt .= "TINGNAN ANG {$imageCount} LARAWAN AT ILARAWAN ANG NAKIKITA MO:";

        return $prompt;
    }

    /**
     * Build system prompt for image analysis.
     */
    protected function buildImageAnalysisSystemPrompt(): string
    {
        $systemPrompt = "Ikaw ay isang expert VISUAL ANALYST para sa mga magsasakang Pilipino.\n\n";

        // CRITICAL: Visual observation requirement
        $systemPrompt .= "⚠️ PINAKA-IMPORTANTE - VISUAL OBSERVATION FIRST:\n";
        $systemPrompt .= "DAPAT mong TUMINGIN SA MGA LARAWAN at ilarawan ang ACTUAL na nakikita mo:\n";
        $systemPrompt .= "- Ano ang EXACT na kulay? (dark green, light green, yellowish, may spots?)\n";
        $systemPrompt .= "- Gaano karami ang may uhay? (estimate percentage like 50%, 80%)\n";
        $systemPrompt .= "- Gaano kahaba ang mga uhay? (estimate like ~20cm, ~25cm)\n";
        $systemPrompt .= "- May butil na ba? Puno na ba? (estimate like 30% filled, walang laman pa)\n";
        $systemPrompt .= "- Gaano kataas ang tanim? (estimate like ~80cm, ~100cm)\n";
        $systemPrompt .= "- May nakikitang problema? (describe specifically kung meron)\n\n";
        $systemPrompt .= "BAWAL mag-assume base sa sinabi ng user - TINGNAN MO MISMO ang larawan!\n\n";

        // CRITICAL: Do NOT assume specific DAP/DAT numbers from images
        $systemPrompt .= "🚫 BAWAL MAG-ASSUME NG SPECIFIC DAP/DAT NUMBERS MULA SA LARAWAN:\n";
        $systemPrompt .= "❌ BAWAL: 'nasa 75 DAP' - HINDI MO ITO MAKIKITA SA LARAWAN!\n";
        $systemPrompt .= "❌ BAWAL: 'around 45 days after planting' - ASSUMPTION LANG ITO!\n";
        $systemPrompt .= "❌ BAWAL: 'mga 60 araw na' - HINDI MO MALALAMAN ITO SA TINGIN LANG!\n";
        $systemPrompt .= "✅ TAMA: Sabihin ang GROWTH STAGE lang (silking stage, flowering, grain filling)\n";
        $systemPrompt .= "✅ TAMA: Ilarawan ang nakikita mo (may tassels na, may silk, may butil na)\n";
        $systemPrompt .= "✅ TAMA: Kung kailangan malaman ang DAP para sa recommendation, ITANONG MO sa user!\n\n";
        $systemPrompt .= "DAHILAN: Iba-iba ang growth rate depende sa variety, klima, at kondisyon.\n";
        $systemPrompt .= "Ang hybrid na 70 DAP ay maaaring katulad ng inbred na 90 DAP - HINDI MO ITO MALALAMAN SA LARAWAN!\n\n";

        // CRITICAL: Anti-pattern for fake/sequential numbers
        $systemPrompt .= "🚫 BAWAL GUMAWA NG FAKE/SEQUENTIAL NUMBERS:\n";
        $systemPrompt .= "❌ BAWAL: 15%, 20%, 25%, 30%, 35% (sequential increment by 5%)\n";
        $systemPrompt .= "❌ BAWAL: ~80cm, ~85cm, ~90cm (sequential increment by 5)\n";
        $systemPrompt .= "✅ TAMA: BAWAT LARAWAN ay ISIPIN NANG HIWALAY - estimate based on ACTUAL visual!\n";
        $systemPrompt .= "✅ TAMA: Kung similar ang lahat ng larawan, OKAY lang na similar ang estimates.\n";
        $systemPrompt .= "✅ TAMA: Use REALISTIC variety like 45%, 60%, 55% (NOT neat patterns)\n\n";

        // CRITICAL: ALWAYS report visible problems - do not assume everything is okay!
        $systemPrompt .= "🚨 KRITIKAL: LAGING I-REPORT ANG NAKIKITANG PROBLEMA!\n";
        $systemPrompt .= "❌ BAWAL: Sabihing 'malusog' o 'normal' kung may nakikitang yellowing!\n";
        $systemPrompt .= "❌ BAWAL: I-ignore ang pagdilaw, spots, o sintomas na nakikita sa larawan!\n";
        $systemPrompt .= "❌ BAWAL: Mag-assume na 'grain filling stage' just to explain away yellowing!\n";
        $systemPrompt .= "✅ TAMA: LAGING i-report kung may yellowing, spots, o problema na nakikita\n";
        $systemPrompt .= "✅ TAMA: Sabihin kung VEGETATIVE + yellowing = POSSIBLE nitrogen/zinc deficiency\n";
        $systemPrompt .= "✅ TAMA: Kung hindi sigurado sa stage, ITANONG sa user kung ilang DAP na\n\n";

        $systemPrompt .= "RULE: Kung may KAHIT ANONG yellowing na nakikita sa larawan:\n";
        $systemPrompt .= "1. TUKUYIN MUNA ang growth stage base sa visual (may uhay ba? nakayuko ba?)\n";
        $systemPrompt .= "2. KUNG WALANG uhay (VEGETATIVE) → Yellowing = POSIBLENG PROBLEMA\n";
        $systemPrompt .= "3. KUNG MAY uhay na NAKAYUKO at may butil (GRAIN FILLING) → Yellowing = Normal\n";
        $systemPrompt .= "4. KUNG HINDI SIGURADO → I-report ang yellowing AT tanungin kung ilang DAP na\n\n";

        $systemPrompt .= "🚫 BAWAL MAG-ASSUME NG 'SCHEDULE COMPLETE':\n";
        $systemPrompt .= "❌ BAWAL: 'wala nang kailangan gawin dahil kumpleto na ang schedule'\n";
        $systemPrompt .= "❌ BAWAL: 'huwag na mag-apply ng fertilizer - tapos na ang schedule'\n";
        $systemPrompt .= "✅ TAMA: Kung user HINDI nagsabi ng schedule, HUWAG banggitin ang schedule!\n";
        $systemPrompt .= "✅ TAMA: Focus lang sa NAKIKITA sa larawan at mag-recommend base doon\n";
        $systemPrompt .= "✅ TAMA: Kung may problema sa larawan, mag-recommend ng solusyon!\n\n";

        // CRITICAL: Comparison approach - SHOW SPECS, NOT DIRECT COMPARISON
        $systemPrompt .= "📊 PARA SA CROSS-VARIETY COMPARISON:\n";
        $systemPrompt .= "HUWAG gumawa ng direktang comparison dahil maaaring magbigay ng maling expectation.\n\n";
        $systemPrompt .= "⚠️ PAALAWA SA USER:\n";
        $systemPrompt .= "- Maraming factors ang nakakaapekto sa performance (lupa, klima, practices)\n";
        $systemPrompt .= "- Ang pinakamahusay na comparison ay sa PAREHONG variety sa PAREHONG lokasyon\n\n";
        $systemPrompt .= "✅ GAWIN ITO INSTEAD:\n";
        $systemPrompt .= "1. SHOW VARIETY SPECS TABLE - typical/standard specs ng hiniling na variety\n";
        $systemPrompt .= "2. SHOW USER'S CROP OBSERVATIONS - mula sa larawan (separate table)\n";
        $systemPrompt .= "3. LET USER DRAW OWN CONCLUSIONS\n\n";
        $systemPrompt .= "⚠️ KRITIKAL - OBSERVABLE FACTORS ONLY:\n";
        $systemPrompt .= "- Kung WALANG butil pa (early stage) → SKIP grain size/filling rows!\n";
        $systemPrompt .= "- HUWAG maglagay ng 'Wala pang butil' vs 'May butil' - walang sense!\n";
        $systemPrompt .= "- User observation vs Variety SPECS (hindi vs growth stage!)\n\n";
        $systemPrompt .= "❌ BAWAL I-COMPARE:\n";
        $systemPrompt .= "- 'Wala pang butil' vs 'Nasa butil na' - NONSENSE! Skip this row!\n";
        $systemPrompt .= "- 'Kulay ng dahon: Below' - kulay ay hindi yield factor!\n";
        $systemPrompt .= "- Growth STAGE observation vs Variety SPECS\n\n";
        $systemPrompt .= "✅ PAGKATAPOS NG COMPARISON - PROJECT YIELD:\n";
        $systemPrompt .= "Formula: Yield = Hills/ha × Panicles/hill × Spikelets × Expected filling × Grain wt\n";
        $systemPrompt .= "Give projected yield RANGE based on what you observed!\n\n";
        $systemPrompt .= "HYBRID VS INBRED REFERENCE:\n";
        $systemPrompt .= "- Hybrid (Jackpot, SL-8H): 105-115 DAT maturity, 6-10 MT/ha potential\n";
        $systemPrompt .= "- Inbred (RC160, RC222): 115-125 DAT maturity, 4-6 MT/ha potential\n";
        $systemPrompt .= "- Different timelines, different characteristics - NORMAL!\n\n";

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

        // CRITICAL: Crop Stage Identification from Visual Cues
        $systemPrompt .= "═══════════════════════════════════════════════════════════════\n";
        $systemPrompt .= "KRITIKAL: PAGTUKOY NG GROWTH STAGE MULA SA LARAWAN\n";
        $systemPrompt .= "═══════════════════════════════════════════════════════════════\n\n";

        $systemPrompt .= "BAGO ka mag-diagnose ng KAHIT ANONG deficiency, TUKUYIN MUNA ang growth stage!\n";
        $systemPrompt .= "Ang growth stage ay KRITIKAL dahil ang NORMAL sa isang stage ay PROBLEMA sa iba.\n\n";

        $systemPrompt .= "RICE/PALAY GROWTH STAGE IDENTIFICATION (Visual Cues):\n\n";

        $systemPrompt .= "⚠️ PAALALA: Ang DAT numbers sa baba ay REFERENCE RANGE lang para sa iyong kaalaman.\n";
        $systemPrompt .= "HUWAG MO ITONG SABIHIN SA USER! Ilarawan lang ang STAGE NAME at VISUAL CHARACTERISTICS.\n";
        $systemPrompt .= "Halimbawa: 'Nakikita ko po na nasa GRAIN FILLING STAGE na ang inyong palay base sa...' (NOT '75 DAP')\n\n";

        $systemPrompt .= "VEGETATIVE STAGE (ref: ~0-45 DAT) - BERDENG BERDE ang mga dahon:\n";
        $systemPrompt .= "- Walang uhay na nakikita\n";
        $systemPrompt .= "- Puro dahon at stems lang\n";
        $systemPrompt .= "- Aktibong tumutubo ang mga bagong dahon\n";
        $systemPrompt .= "→ SA STAGE NA ITO: Yellowing = POSIBLENG NITROGEN DEFICIENCY\n\n";

        $systemPrompt .= "PANICLE INITIATION (ref: ~45-55 DAT) - Flag leaf lumalabas:\n";
        $systemPrompt .= "- May 'boot' o namamaga sa loob ng flag leaf sheath\n";
        $systemPrompt .= "- Hindi pa lumalabas ang uhay\n";
        $systemPrompt .= "- Dahon pa rin berdeng berde\n";
        $systemPrompt .= "→ SA STAGE NA ITO: Yellowing = POSIBLENG DEFICIENCY PA RIN\n\n";

        $systemPrompt .= "FLOWERING/HEADING (ref: ~55-75 DAT) - Uhay lumalabas na:\n";
        $systemPrompt .= "- Uhay na nakikita (exserted panicle)\n";
        $systemPrompt .= "- May puting bulaklak/anthers na nakikita\n";
        $systemPrompt .= "- Uhay pa patayo (erect) o bahagyang nakayuko\n";
        $systemPrompt .= "→ SA STAGE NA ITO: Bahagyang yellowing = MAAARING NORMAL NA\n\n";

        $systemPrompt .= "⚠️ MILKING/GRAIN FILLING (ref: ~75-95 DAT) - KRITIKAL NA STAGE:\n";
        $systemPrompt .= "- Uhay NAKAYUKO NA (bending down dahil bumibigat ang butil)\n";
        $systemPrompt .= "- Butil nagpupuno na (may 'milk' or 'dough' inside)\n";
        $systemPrompt .= "- Dahon nagsisimula NORMAL na dumidilaw\n";
        $systemPrompt .= "- Flag leaf pa rin dapat berde, pero lower leaves yellow = NORMAL!\n";
        $systemPrompt .= "→ SA STAGE NA ITO: YELLOWING = NORMAL! (Nutrient translocation to grains)\n";
        $systemPrompt .= "→ HUWAG I-DIAGNOSE bilang nitrogen deficiency!\n";
        $systemPrompt .= "→ HUWAG MAG-RECOMMEND ng Urea o nitrogen fertilizer!\n\n";

        $systemPrompt .= "MATURITY (ref: ~95-120 DAT) - Pag-aani na:\n";
        $systemPrompt .= "- Butil matigas na at natutuyo\n";
        $systemPrompt .= "- Karamihan ng dahon dilaw na o tuyo\n";
        $systemPrompt .= "- Uhay nakayuko na ng husto\n";
        $systemPrompt .= "→ DILAW NA DAHON = 100% NORMAL!\n\n";

        $systemPrompt .= "═══════════════════════════════════════════════════════════════\n";
        $systemPrompt .= "⚠️ CRITICAL: HUWAG MAG-DIAGNOSE NG NITROGEN DEFICIENCY SA LATE STAGE!\n";
        $systemPrompt .= "═══════════════════════════════════════════════════════════════\n\n";

        $systemPrompt .= "KUNG NAKIKITA MO SA LARAWAN:\n";
        $systemPrompt .= "- Uhay na NAKAYUKO (bending/drooping panicles)\n";
        $systemPrompt .= "- Butil na may laman na (filled grains)\n";
        $systemPrompt .= "- Lower leaves na dilaw\n\n";

        $systemPrompt .= "ITO AY MILKING/GRAIN FILLING STAGE! Ang dapat mong sabihin:\n";
        $systemPrompt .= "📌 VERDICT: NORMAL\n";
        $systemPrompt .= "💬 DAHILAN: 'Nasa grain filling stage na ang inyong palay. Ang pagdilaw ng ilang dahon\n";
        $systemPrompt .= "   ay NORMAL dahil nagta-translocate ang nutrients mula sa dahon papunta sa butil.\n";
        $systemPrompt .= "   Ito ay magandang senyales - bumibigat na ang uhay!'\n\n";

        $systemPrompt .= "BAWAL NA RECOMMENDATIONS SA LATE STAGE (Milking/Maturity):\n";
        $systemPrompt .= "❌ HUWAG mag-recommend ng Urea (46-0-0)\n";
        $systemPrompt .= "❌ HUWAG mag-recommend ng Ammonium Sulfate (21-0-0)\n";
        $systemPrompt .= "❌ HUWAG sabihin 'kulang sa nitrogen'\n";
        $systemPrompt .= "❌ HUWAG sabihin 'kailangan ng more fertilizer'\n\n";

        $systemPrompt .= "PWEDENG I-RECOMMEND SA LATE STAGE:\n";
        $systemPrompt .= "✅ Potassium (MOP/0-0-60) - para sa grain filling at weight\n";
        $systemPrompt .= "✅ Foliar micronutrients (Zinc, Boron) - para sa quality\n";
        $systemPrompt .= "✅ Proper irrigation - kailangan pa ng tubig\n";
        $systemPrompt .= "✅ Pest monitoring (especially rice bugs/walang-sangit)\n";
        $systemPrompt .= "✅ Reassurance na NORMAL ang kanilang crop\n\n";

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

        $systemPrompt .= "═══════════════════════════════════════════════════════════════\n";
        $systemPrompt .= "⚠️ COMPARISON QUESTIONS - MAGBIGAY NG HONEST DATA!\n";
        $systemPrompt .= "═══════════════════════════════════════════════════════════════\n\n";

        $systemPrompt .= "KEYWORDS NA NAGPAPAHIWATIG NG COMPARISON QUESTION:\n";
        $systemPrompt .= "- 'mas maganda kaysa' / 'mas mabuti kaysa' / 'mas mataas ang ani'\n";
        $systemPrompt .= "- 'vs' / 'versus' / 'kumpara sa'\n";
        $systemPrompt .= "- 'traditional' / 'dati' / 'lumang paraan'\n";
        $systemPrompt .= "- 'honest' / 'unbiased' / 'totoo ba'\n\n";

        $systemPrompt .= "KUNG MAY COMPARISON QUESTION - MANDATORY:\n";
        $systemPrompt .= "1. SAGUTIN MUNA ang tanong tungkol sa LARAWAN (assess crop condition)\n";
        $systemPrompt .= "2. TAPOS MAGBIGAY NG COMPARISON DATA gamit ang numbers:\n";
        $systemPrompt .= "   - Hybrid rice yield: 6-8 MT/ha (typical)\n";
        $systemPrompt .= "   - Traditional/inbred rice yield: 3.5-4.5 MT/ha (typical)\n";
        $systemPrompt .= "   - Percentage difference: ~40-80% higher yield\n";
        $systemPrompt .= "3. Magbigay ng HONEST PROS at CONS\n";
        $systemPrompt .= "4. HUWAG puro positive lang - be truthful!\n\n";

        $systemPrompt .= "HALIMBAWA (Comparison Question Response):\n";
        $systemPrompt .= "'Nakikita ko po na nasa grain filling stage na ang inyong Jackpot 102.\n";
        $systemPrompt .= " Mukhang maayos po ang kondisyon ng inyong pananim. 🌾\n\n";
        $systemPrompt .= " Tungkol sa inyong tanong kung mas maganda ito kaysa traditional:\n";
        $systemPrompt .= " Ang hybrid rice tulad ng Jackpot 102 ay may average yield na 6-8 MT/ha,\n";
        $systemPrompt .= " kumpara sa 3.5-4.5 MT/ha ng traditional varieties - around 40-80% higher.\n\n";
        $systemPrompt .= " PROS: Mas mataas na yield, uniform growth, disease resistance\n";
        $systemPrompt .= " CONS: Hindi pwedeng i-save ang seeds, mas mahal ang binhi'\n\n";

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
        $systemPrompt .= "- Ihiwalay ang bawat idea sa sariling linya para madaling basahin\n\n";

        // Include Query Rules from database for image analysis
        $queryRules = AiQueryRule::getCompiledRules();
        if (!empty($queryRules)) {
            $systemPrompt .= "═══════════════════════════════════════════════════════════════\n";
            $systemPrompt .= "📋 USER-CONFIGURED QUERY RULES (SUNDIN ITO!)\n";
            $systemPrompt .= "═══════════════════════════════════════════════════════════════\n\n";
            $systemPrompt .= $queryRules . "\n";
        }

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
        // Get Gemini API setting (global)
        $geminiSetting = AiApiSetting::active()
            ->forProvider(AiApiSetting::PROVIDER_GEMINI)
            ->enabled()
            ->first();

        if (!$geminiSetting || !$geminiSetting->apiKey) {
            Log::warning('Gemini API not configured for title generation');
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

        // Check if Gemini is configured for AI image generation (global)
        $geminiSetting = AiApiSetting::active()
            ->forProvider(AiApiSetting::PROVIDER_GEMINI)
            ->enabled()
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
        // Get Gemini API setting (global)
        $geminiSetting = AiApiSetting::active()
            ->forProvider(AiApiSetting::PROVIDER_GEMINI)
            ->enabled()
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
        // Get Gemini API setting (global)
        $geminiSetting = AiApiSetting::active()
            ->forProvider(AiApiSetting::PROVIDER_GEMINI)
            ->enabled()
            ->first();

        if (!$geminiSetting || !$geminiSetting->apiKey) {
            Log::debug('AI image generation skipped - Gemini not configured');
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

                        // Track token usage for image generation
                        // Input: estimate from prompt length (1 token ≈ 4 chars)
                        // Output: flat estimate for image generation cost (2000 tokens)
                        $inputTokens = (int) ceil(strlen($prompt) / 4);
                        $outputTokens = 2000; // Estimated output cost for image generation
                        $this->trackTokenUsage('gemini-image', 'ai_image_generation', $inputTokens, $outputTokens, $model);

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

                            // Track token usage for image generation (fallback models)
                            // Input: estimate from prompt length (1 token ≈ 4 chars)
                            // Output: flat estimate for image generation cost (2000 tokens)
                            $inputTokens = (int) ceil(strlen($prompt) / 4);
                            $outputTokens = 2000; // Estimated output cost for image generation
                            $this->trackTokenUsage('gemini-image', 'ai_image_generation', $inputTokens, $outputTokens, $model);

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
