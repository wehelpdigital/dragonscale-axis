<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiQueryRule extends BaseModel
{
    protected $table = 'ai_query_rules';

    protected $fillable = [
        'usersId',
        'ruleName',
        'ruleCategory',
        'ruleDescription',
        'rulePrompt',
        'priority',
        'isEnabled',
        'isSystemRule',
        'appliesTo',
        'delete_status',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'isEnabled' => 'boolean',
        'isSystemRule' => 'boolean',
        'appliesTo' => 'array',
        'priority' => 'integer',
    ];

    /**
     * Rule categories
     */
    const CATEGORY_GENERAL = 'general';
    const CATEGORY_FORMATTING = 'formatting';
    const CATEGORY_DATA_PREFERENCE = 'data_preference';
    const CATEGORY_TERMINOLOGY = 'terminology';
    const CATEGORY_RESPONSE_STYLE = 'response_style';

    /**
     * Get all available categories
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_GENERAL => 'General',
            self::CATEGORY_FORMATTING => 'Formatting',
            self::CATEGORY_DATA_PREFERENCE => 'Data Preference',
            self::CATEGORY_TERMINOLOGY => 'Terminology',
            self::CATEGORY_RESPONSE_STYLE => 'Response Style',
        ];
    }

    /**
     * Scope: Active records only (soft delete)
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Scope: Filter by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Scope: Enabled rules only
     */
    public function scopeEnabled($query)
    {
        return $query->where('isEnabled', true);
    }

    /**
     * Scope: Order by priority (highest first)
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('created_at', 'asc');
    }

    /**
     * Scope: Filter by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('ruleCategory', $category);
    }

    /**
     * Relationship: User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    /**
     * Get all enabled rules for a user, compiled into a single prompt string
     */
    public static function getCompiledRulesForUser($userId): string
    {
        $rules = self::active()
            ->forUser($userId)
            ->enabled()
            ->byPriority()
            ->get();

        if ($rules->isEmpty()) {
            return '';
        }

        $compiled = "=== QUERY RULES (Follow these instructions strictly) ===\n\n";

        foreach ($rules as $index => $rule) {
            $compiled .= ($index + 1) . ". " . $rule->ruleName . ":\n";
            $compiled .= $rule->rulePrompt . "\n\n";
        }

        $compiled .= "=== END QUERY RULES ===\n";

        return $compiled;
    }

    /**
     * Get rules as an array for API responses
     */
    public static function getRulesArrayForUser($userId): array
    {
        return self::active()
            ->forUser($userId)
            ->enabled()
            ->byPriority()
            ->get()
            ->map(function ($rule) {
                return [
                    'name' => $rule->ruleName,
                    'category' => $rule->ruleCategory,
                    'prompt' => $rule->rulePrompt,
                ];
            })
            ->toArray();
    }

    /**
     * Create default system rules for a new user
     */
    public static function createDefaultRulesForUser($userId): void
    {
        $defaultRules = [
            [
                'ruleName' => 'Use Brand/Common Names for Crop Varieties',
                'ruleCategory' => self::CATEGORY_TERMINOLOGY,
                'ruleDescription' => 'When mentioning crop varieties, always use the brand name or common name (e.g., "Jackpot 102", "NK6414 VIP", "DEKALB 8282S") instead of NSIC registration codes or scientific nomenclature.',
                'rulePrompt' => 'IMPORTANT: When referring to any crop variety (corn, rice, vegetables, etc.), ALWAYS use the brand name or common commercial name that farmers recognize. Examples:
- Use "NK6414 VIP" not "NSIC 2018 Cn 23"
- Use "Jackpot 102" not "NSIC 2015 Rc 342"
- Use "DEKALB 8282S" not just "hybrid corn variety"
- Use "SL-8H" or "SL-19H" for rice, not NSIC codes
If you only know the NSIC code, try to find the corresponding brand name. Farmers know products by their commercial names, not registration codes.',
                'priority' => 100,
                'isSystemRule' => true,
            ],
            [
                'ruleName' => 'Always Provide Latest/Current Data',
                'ruleCategory' => self::CATEGORY_DATA_PREFERENCE,
                'ruleDescription' => 'Always search for and provide the most recent/latest information available. Prioritize current data over historical data.',
                'rulePrompt' => 'CRITICAL: Always prioritize the LATEST and MOST CURRENT information available. When answering questions:
- Search for the most recent data, studies, or recommendations
- If providing statistics, yields, or prices, use the most recent available data
- Mention the date/year of the information if relevant (e.g., "As of 2024..." or "Based on the latest 2024 data...")
- If information might be outdated, explicitly note this and recommend verifying current data
- For agricultural recommendations, consider current climate conditions and seasonal factors',
                'priority' => 95,
                'isSystemRule' => true,
            ],
            [
                'ruleName' => 'Accuracy Over Positivity',
                'ruleCategory' => self::CATEGORY_GENERAL,
                'ruleDescription' => 'Always give accurate answers, even if the answer is negative. It is okay to say "no" or "not recommended" when that is the correct answer.',
                'rulePrompt' => 'CRITICAL ACCURACY RULE - Your advice affects farmers\' livelihoods!

FOR "SHOULD I DO X?" or "RECOMMENDED BA?" QUESTIONS:
1. START with a clear verdict: YES / NO / CONDITIONAL
2. If the answer is NO or NOT RECOMMENDED, say it clearly:
   - "HINDI na recommended..."
   - "HINDI na kailangan..."
   - "Wala nang effect..."

3. For crop stage-specific questions (using DAP - Days After Planting):
   - DAP 0-30: Vegetative stage - most inputs effective
   - DAP 30-60: Active growth - timing critical
   - DAP 60-90: Reproductive stage - some inputs still useful
   - DAP 90-120: Grain fill to maturity - most inputs WASTED
   - At physiological maturity: irrigation/fertilizer NO LONGER helps yield

4. STRUCTURE for "recommended ba?" answers:
   - Clear YES/NO/CONDITIONAL verdict first
   - Explain WHY (biological/scientific reason)
   - List RISKS if they proceed anyway
   - Mention EXCEPTIONS (when it might be okay)

NEVER say "yes" just to be helpful when the correct answer is "no".',
                'priority' => 98,
                'isSystemRule' => true,
            ],
            [
                'ruleName' => 'Provide Supplemental Context',
                'ruleCategory' => self::CATEGORY_RESPONSE_STYLE,
                'ruleDescription' => 'Include helpful supplemental information that adds value to the primary answer.',
                'rulePrompt' => 'When answering questions, you may include relevant supplemental details that enhance the answer:
- Related tips or best practices
- Common mistakes to avoid
- Complementary information (e.g., if discussing a seed variety, mention compatible fertilizers or planting seasons)
- Practical considerations farmers should know
However, keep supplemental information concise and clearly distinguished from the main answer. Do not overwhelm with excessive details.',
                'priority' => 80,
                'isSystemRule' => true,
            ],
            [
                'ruleName' => 'Follow-up Context Focus',
                'ruleCategory' => self::CATEGORY_GENERAL,
                'ruleDescription' => 'When answering follow-up questions, focus ONLY on the immediate conversation context. Never answer unrelated topics from earlier in the chat.',
                'rulePrompt' => 'FOLLOW-UP QUESTION HANDLING:

When a message starts with "CONTEXT:" or "FOLLOW-UP QUESTION:", you MUST:
1. Answer ONLY about the topic mentioned in the CONTEXT
2. IGNORE any older/unrelated topics from earlier chat history
3. If the follow-up seems unrelated, ask for clarification RATHER than guessing

EXAMPLE:
- CONTEXT: "ano ang pwede ko ilagay sa namumulaklak na mais?"
- FOLLOW-UP: "e ang boron at zinc?"
- CORRECT: Answer about boron and zinc FOR FLOWERING MAIS
- WRONG: Answer about corn varieties or unrelated topics

NEVER let old chat history confuse you. The CONTEXT field tells you what topic the user is asking about.',
                'priority' => 97,
                'isSystemRule' => true,
            ],
            [
                'ruleName' => 'Comprehensive Structured Responses',
                'ruleCategory' => self::CATEGORY_RESPONSE_STYLE,
                'ruleDescription' => 'Provide comprehensive, well-structured responses with multiple recommendations, specific data, and selection guidance.',
                'rulePrompt' => 'RESPONSE STRUCTURE REQUIREMENTS:

1. WHEN RECOMMENDING PRODUCTS/VARIETIES:
   - List 5-8 specific options, not just 2-3
   - For EACH option, include: Brand Name (Company) - Yield potential (MT/ha) - Key features/advantages
   - Order from highest performing to alternatives
   - Include options from different seed companies (Syngenta, Bayer/DEKALB, Pioneer, Bioseed, etc.)

2. ALWAYS INCLUDE THESE SECTIONS:
   - Main recommendations with specific data
   - "Paano Pumili" (Selection Guide) - criteria based on soil, climate, budget
   - "Mga Dapat Isaalang-alang" (Considerations) - pests, timing, market demand

3. DATA REQUIREMENTS:
   - Yield: Always in metric tons per hectare (MT/ha)
   - Include resistance/tolerance info (e.g., "resistant to Fall Armyworm")
   - Mention maturity period (days to harvest) when relevant
   - Reference field trial results or derby winners when available

4. FOLLOW-UP QUESTIONS:
   - When user asks for "the best" or "highest yield", give the TOP 1 recommendation clearly first
   - Then provide 2-3 alternatives with comparison
   - Include WHY it\'s the best (specific data, trial results)',
                'priority' => 90,
                'isSystemRule' => true,
            ],
            [
                'ruleName' => 'Clean Response Formatting',
                'ruleCategory' => self::CATEGORY_FORMATTING,
                'ruleDescription' => 'Format responses cleanly without excessive markdown. Use simple formatting that is easy to read.',
                'rulePrompt' => 'RESPONSE FORMATTING RULES:

1. DO NOT use markdown bold (**text**) - just write text normally
2. Use simple numbered lists (1. 2. 3.) for recommendations
3. Use bullet points (-) for sub-items only
4. Section headers should be plain text followed by colon, like "Mga Rekomendasyon:"
5. Keep formatting clean and readable
6. Do not use excessive capitalization
7. Separate sections with a blank line for readability

Example format:
Mga Rekomendasyon:

1. NK6414 (Syngenta) - 15 MT/ha yield potential
   - Resistant to Fall Armyworm
   - 110-115 days maturity
   - Available sa mga agri-supply stores

2. DEKALB DK8282S (Bayer) - 12 MT/ha yield potential
   - Good for dry season
   - 105-110 days maturity',
                'priority' => 85,
                'isSystemRule' => true,
            ],
            [
                'ruleName' => 'Philippine Local Seed Brands',
                'ruleCategory' => self::CATEGORY_DATA_PREFERENCE,
                'ruleDescription' => 'Focus on seed varieties and brands that are locally available in Philippine agri-supply stores.',
                'rulePrompt' => 'PHILIPPINE LOCAL SEED BRANDS - Only recommend varieties farmers can actually buy:

CORN/MAIS BRANDS (available nationwide):
- SYNGENTA: NK6410, NK6414, NK7676, NK8840 (agri-supply stores)
- BAYER/DEKALB: DK8282S, DK9108, DK8118S, DK6919S
- PIONEER: P3396, P4546, Pioneer 30T80
- BIOSEED: Bioseed 9909, Bioseed 9818 (Philippine company)
- ASIAN HYBRID: AH6388, AH5818 (local brand)

RICE/PALAY BRANDS (available nationwide):
- SL AGRITECH: SL-8H, SL-18H, SL-19H, SL-20H (hybrid)
- PHILRICE/DA: NSIC Rc222, NSIC Rc216, NSIC Rc160 (inbred)
- BAYER: Arize series (hybrid)
- SYNGENTA: Mestizo series (hybrid)

Always mention:
- Where to buy: "Available sa local agri-supply stores" or specific dealers
- Approximate price range if known (per kg or per bag)
- Which brands are most widely available in the region',
                'priority' => 92,
                'isSystemRule' => true,
            ],
            [
                'ruleName' => 'Web Search Data Extraction',
                'ruleCategory' => self::CATEGORY_DATA_PREFERENCE,
                'ruleDescription' => 'When using web search, extract exact data from official sources and cite properly.',
                'rulePrompt' => 'WEB SEARCH DATA RULES:

1. SEARCH FOR OFFICIAL SOURCES:
   - Syngenta Philippines (syngenta.com.ph) for NK varieties
   - Bayer Crop Science for DEKALB varieties
   - Pioneer/Corteva for Pioneer varieties
   - Department of Agriculture (da.gov.ph)
   - PhilRice (philrice.gov.ph) for rice
   - Corn derby results for real field performance

2. EXTRACT EXACT DATA:
   - Use EXACT yield numbers from sources (if source says 15 MT/ha, use 15 MT/ha)
   - Do NOT make up or estimate numbers
   - If no official data found, say "data not available from official sources"

3. VERIFY SOURCES MATCH THE CROP:
   - Corn questions = corn sources only
   - Rice questions = rice sources only
   - Do NOT cite rice articles for corn questions

4. SEARCH QUERIES TO USE:
   - For corn: "NK6414 yield Philippines", "DEKALB corn derby results"
   - For rice: "SL-8H yield Philippines", "PhilRice recommended varieties"
   - Include "Philippines" and current year in searches',
                'priority' => 93,
                'isSystemRule' => true,
            ],
            [
                'ruleName' => 'Agricultural Terminology Definitions',
                'ruleCategory' => self::CATEGORY_TERMINOLOGY,
                'ruleDescription' => 'Common agricultural abbreviations and terms that must be understood correctly when users mention them.',
                'rulePrompt' => 'AGRICULTURAL TERMINOLOGY DICTIONARY - Understand these terms correctly:

=== GROWTH STAGE ABBREVIATIONS ===
- DAP = Days After Planting (Araw Pagkatapos Itanim) - kung gaano katagal na mula nang itanim
- DAT = Days After Transplanting (Araw Pagkatapos Ilipat) - para sa rice seedlings na inilipat
- DAE = Days After Emergence (Araw Pagkatapos Sumibol) - mula nang lumabas sa lupa
- DAS = Days After Sowing (Araw Pagkatapos Inihasik)

=== CORN GROWTH STAGES (by DAP) ===
- DAP 0-14: Germination and emergence (pagsibol)
- DAP 15-35: Vegetative stage (pagtubo ng dahon) - V1 to V8
- DAP 35-45: Rapid vegetative growth (mabilis na paglaki)
- DAP 45-55: Tasseling stage (paglabas ng bulaklak lalaki)
- DAP 55-65: Silking stage (paglabas ng buhok ng mais)
- DAP 65-75: Pollination and kernel formation (pagbuo ng butil)
- DAP 75-100: Grain filling stage (pagpuno ng butil) - NEEDS POTASSIUM (K)
- DAP 100-120: Maturity and drying (pagkahinog)

=== RICE GROWTH STAGES (by DAT) ===
- DAT 0-20: Establishment and tillering (pagsanga)
- DAT 20-45: Vegetative growth (pagtubo)
- DAT 45-65: Panicle initiation (pagbuo ng uhay)
- DAT 65-85: Flowering (pamumulaklak)
- DAT 85-120: Grain filling to maturity (pagpuno hanggang kahinugan)

=== NUTRIENT ABBREVIATIONS ===
- N = Nitrogen (Nitroheno) - para sa paglaki ng dahon
- P = Phosphorus (Posporus) - para sa ugat at bulaklak
- K = Potassium (Potasyum) - para sa mabigat na bunga/butil
- NPK = Nitrogen-Phosphorus-Potassium ratio (e.g., 14-14-14)
- Zn = Zinc (Sink) - micronutrient para sa healthy growth
- B = Boron - para sa pollination at fruit set
- Ca = Calcium - para sa cell walls
- Mg = Magnesium - para sa chlorophyll

=== FERTILIZER ABBREVIATIONS ===
- MOP = Muriate of Potash (0-0-60) - potassium source
- SOP = Sulfate of Potash (0-0-50) - potassium + sulfur
- DAP (fertilizer) = Diammonium Phosphate (18-46-0) - NOT Days After Planting!
- MAP = Monoammonium Phosphate (11-52-0)
- AS = Ammonium Sulfate (21-0-0)
- Urea = 46-0-0 - nitrogen source

=== PEST/DISEASE ABBREVIATIONS ===
- FAW = Fall Armyworm (Spodoptera frugiperda)
- BPH = Brown Planthopper
- GLH = Green Leafhopper
- BLB = Bacterial Leaf Blight
- RTV = Rice Tungro Virus
- RRSV = Rice Ragged Stunt Virus

=== MEASUREMENT ABBREVIATIONS ===
- MT/ha = Metric Tons per Hectare (Toneladang Metriko kada Hektarya)
- kg/ha = Kilograms per Hectare
- mL/L = Milliliters per Liter (para sa spray mixture)
- tbsp = Tablespoon (Kutsara)
- cc = Cubic centimeter (same as mL)

=== IMPORTANT CONTEXT RULES ===
1. When user says "XX DAP mais" - they mean the corn is XX days old
2. When user mentions a number + DAP/DAT - always interpret as growth stage
3. "DAP fertilizer" vs "XX DAP" - context determines if its the fertilizer or growth stage
4. Grain filling stage (75-100 DAP for corn) NEEDS POTASSIUM, not nitrogen
5. Vegetative stage (15-45 DAP) benefits most from NITROGEN
6. Flowering/reproductive stage benefits from PHOSPHORUS and POTASSIUM',
                'priority' => 99,
                'isSystemRule' => true,
            ],
        ];

        foreach ($defaultRules as $rule) {
            // Check if rule already exists (by name)
            $exists = self::where('usersId', $userId)
                ->where('ruleName', $rule['ruleName'])
                ->where('delete_status', 'active')
                ->exists();

            if (!$exists) {
                self::create([
                    'usersId' => $userId,
                    'ruleName' => $rule['ruleName'],
                    'ruleCategory' => $rule['ruleCategory'],
                    'ruleDescription' => $rule['ruleDescription'],
                    'rulePrompt' => $rule['rulePrompt'],
                    'priority' => $rule['priority'],
                    'isEnabled' => true,
                    'isSystemRule' => $rule['isSystemRule'],
                    'delete_status' => 'active',
                ]);
            }
        }
    }
}
