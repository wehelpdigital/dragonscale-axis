<?php

namespace App\Http\Controllers\Recommendations;

use App\Http\Controllers\Controller;
use App\Models\AiChatAvatarSetting;
use App\Models\RecomAccessTag;
use App\Models\RecomApiSetting;
use App\Models\RecomCropBreed;
use App\Models\RecomRecommendation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RecommendationGenerateController extends Controller
{
    /**
     * Display the recommendations list page with settings tabs.
     */
    public function index()
    {
        $userId = Auth::id();

        // Get recommendations
        $recommendations = RecomRecommendation::active()
            ->forUser($userId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get or create GLOBAL settings for each provider (for API Settings tab)
        $providers = [
            RecomApiSetting::PROVIDER_CLAUDE,
            RecomApiSetting::PROVIDER_OPENAI,
            RecomApiSetting::PROVIDER_GEMINI,
        ];

        $settings = [];
        foreach ($providers as $provider) {
            $setting = RecomApiSetting::active()
                ->global()
                ->forProvider($provider)
                ->first();

            if (!$setting) {
                $setting = RecomApiSetting::create([
                    'usersId' => null, // Global setting
                    'provider' => $provider,
                    'isActive' => false,
                    'isDefault' => $provider === RecomApiSetting::PROVIDER_CLAUDE,
                    'delete_status' => 'active',
                ]);
            }

            $settings[$provider] = $setting;
        }

        $providerLabels = RecomApiSetting::getProviderLabels();
        $providerIcons = RecomApiSetting::getProviderIcons();
        $providerColors = RecomApiSetting::getProviderColors();
        $modelsByProvider = RecomApiSetting::getModelsByProvider();

        // Get access tags for this user
        $accessTags = RecomAccessTag::active()
            ->forUser($userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('recommendations.generate.index', compact(
            'recommendations',
            'settings',
            'providerLabels',
            'providerIcons',
            'providerColors',
            'modelsByProvider',
            'accessTags'
        ));
    }

    /**
     * Display the create recommendation page.
     */
    public function create()
    {
        // Use global avatar settings (not user-specific) so the AI avatar is consistent
        // across all users and modules. Avatar is configured in AI Technician Settings.
        $avatarSettings = AiChatAvatarSetting::getOrCreate();

        return view('recommendations.generate.create', compact('avatarSettings'));
    }

    /**
     * Store a newly created recommendation.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'questionnaire_data' => 'nullable|array',
        ], [
            'title.required' => 'Please enter a title for the recommendation.',
            'title.max' => 'Title cannot exceed 255 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $recommendation = RecomRecommendation::create([
                'usersId' => Auth::id(),
                'title' => $request->title,
                'questionnaire_data' => $request->questionnaire_data,
                'status' => RecomRecommendation::STATUS_DRAFT,
                'delete_status' => 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Recommendation created successfully.',
                'data' => [
                    'id' => $recommendation->id,
                    'title' => $recommendation->title,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create recommendation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Soft delete a recommendation.
     */
    public function destroy($id)
    {
        $recommendation = RecomRecommendation::active()
            ->forUser(Auth::id())
            ->where('id', $id)
            ->first();

        if (!$recommendation) {
            return response()->json([
                'success' => false,
                'message' => 'Recommendation not found.',
            ], 404);
        }

        try {
            $recommendation->update(['delete_status' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => 'Recommendation deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete recommendation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * AI-powered variety recommendation based on user preferences.
     */
    public function aiRecommendVarieties(Request $request)
    {
        // Get GLOBAL default AI setting (not per-user)
        $aiSetting = RecomApiSetting::active()
            ->global()
            ->enabled()
            ->where(function ($q) {
                $q->where('isDefault', true)
                    ->orWhereNotNull('apiKey');
            })
            ->orderBy('isDefault', 'desc')
            ->first();

        if (!$aiSetting || !$aiSetting->hasApiKey()) {
            return response()->json([
                'success' => false,
                'message' => 'AI API settings are not configured. Please contact the administrator.',
                'needsSetup' => true,
            ], 400);
        }

        // Get user inputs
        $freeText = $request->input('freeText', '');
        $budget = $request->input('budget');
        $farmSize = $request->input('farmSize');
        $farmUnit = $request->input('farmUnit', 'hectares');
        $province = $request->input('province', '');
        $municipality = $request->input('municipality', '');
        $barangay = $request->input('barangay', '');
        $croppingSeason = $request->input('croppingSeason');
        $protection = $request->input('protection', []);
        $cropType = $request->input('cropType');
        $breedType = $request->input('breedType');
        $cornType = $request->input('cornType');

        // Get available varieties from database
        $query = RecomCropBreed::active()->enabled();

        if ($cropType === 'palay' || $cropType === 'rice') {
            $query->forCrop('rice');
            if ($breedType) {
                $query->forBreedType($breedType);
            }
        } elseif ($cropType === 'corn') {
            $query->forCrop('corn');
            if ($cornType) {
                $query->forCornType($cornType);
            }
        }

        $varieties = $query->orderBy('name')->get([
            'id', 'name', 'cropType', 'breedType', 'cornType',
            'manufacturer', 'potentialYield', 'maturityDays',
            'geneProtection', 'characteristics', 'relatedInformation',
            'imagePath'
        ]);

        if ($varieties->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No varieties found for the selected crop type. Please add some varieties to the Crop Breeds library first.',
            ], 404);
        }

        // Build the AI prompt
        $prompt = $this->buildVarietyRecommendationPrompt(
            $freeText, $budget, $farmSize, $farmUnit, $province, $municipality, $barangay,
            $croppingSeason, $protection, $varieties
        );

        try {
            // Call AI API based on provider
            $aiResponse = $this->callAiApi($aiSetting, $prompt);

            if (!$aiResponse['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $aiResponse['error'] ?? 'Failed to get AI recommendation.',
                ], 500);
            }

            // Parse AI response to extract recommended variety IDs
            $recommendations = $this->parseAiRecommendations($aiResponse['content'], $varieties);

            return response()->json([
                'success' => true,
                'recommendations' => $recommendations,
                'aiAnalysis' => $aiResponse['content'],
            ]);

        } catch (\Exception $e) {
            Log::error('AI Variety Recommendation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while getting AI recommendations: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build the AI prompt for variety recommendation.
     */
    private function buildVarietyRecommendationPrompt($freeText, $budget, $farmSize, $farmUnit, $province, $municipality, $barangay, $croppingSeason, $protection, $varieties)
    {
        $varietyList = $varieties->map(function ($v) {
            $info = "ID:{$v->id} | Name: {$v->name}";
            if ($v->manufacturer) $info .= " | Manufacturer: {$v->manufacturer}";
            if ($v->potentialYield) $info .= " | Yield: {$v->potentialYield}";
            if ($v->maturityDays) $info .= " | Maturity: {$v->maturityDays}";
            if ($v->geneProtection) {
                $genes = is_array($v->geneProtection) ? implode(', ', $v->geneProtection) : $v->geneProtection;
                $info .= " | Protection: {$genes}";
            }
            if ($v->characteristics) $info .= " | Characteristics: " . substr($v->characteristics, 0, 200);
            return $info;
        })->implode("\n");

        $userPreferences = [];
        if (!empty($freeText)) {
            $userPreferences[] = "User's specific request: \"{$freeText}\"";
        }
        if ($budget && $budget !== 'any') {
            $budgetLabels = ['low' => 'Low budget (economical options)', 'medium' => 'Medium budget', 'high' => 'Premium/High budget'];
            $userPreferences[] = "Budget preference: " . ($budgetLabels[$budget] ?? $budget);
        }
        // Farm size information
        if ($farmSize && $farmSize > 0) {
            $sizeText = $farmSize . ' ' . ($farmUnit === 'sqm' ? 'square meters' : 'hectares');
            $userPreferences[] = "Farm size: {$sizeText}";
        }
        // Farm location information
        $locationParts = [];
        if (!empty($barangay)) $locationParts[] = "Barangay {$barangay}";
        if (!empty($municipality)) $locationParts[] = $municipality;
        if (!empty($province)) $locationParts[] = $province;
        if (!empty($locationParts)) {
            $userPreferences[] = "Farm location: " . implode(', ', $locationParts) . ", Philippines";
        }
        // Cropping season
        if ($croppingSeason) {
            $seasonLabels = [
                'wet' => 'Wet season (Tag-ulan, June - November)',
                'dry' => 'Dry season (Tag-init, December - May)',
                'transition' => 'Transition/Tri-crop season (between wet and dry seasons)'
            ];
            $userPreferences[] = "Cropping season: " . ($seasonLabels[$croppingSeason] ?? $croppingSeason);
        }
        if (!empty($protection) && !in_array('none', $protection)) {
            $protLabels = ['drought' => 'Drought tolerance', 'pest' => 'Pest resistance', 'disease' => 'Disease resistance', 'lodging' => 'Lodging resistance', 'flood' => 'Flood tolerance'];
            $protNeeds = array_map(fn($p) => $protLabels[$p] ?? $p, $protection);
            $userPreferences[] = "Protection needs: " . implode(', ', $protNeeds);
        }

        $preferencesText = !empty($userPreferences) ? implode("\n", $userPreferences) : "No specific preferences provided.";

        return <<<PROMPT
Ikaw ay isang Smart Technician - isang bata at magalang na agricultural expert na tumutulong sa mga magsasakang Pilipino na pumili ng pinakamainam na variety para sa kanilang pangangailangan.

MAHALAGA: Ang lahat ng "reason" at "summary" ay dapat nakasulat sa TAGALOG gamit ang magalang na pananalita (po/opo). English lang para sa technical terms na walang Tagalog equivalent (hal. variety names, scientific terms, gene names). Maging natural at conversational tulad ng isang batang technician na kumakausap sa mga magsasaka.

## Mga Pangangailangan ng Magsasaka:
{$preferencesText}

## Mga Available na Varieties sa Database:
{$varietyList}

## Ang Iyong Gawain:
Suriin ang mga pangangailangan ng magsasaka at irekomenda ang TOP 5 pinakamainam na varieties mula sa listahan sa itaas. Para sa bawat rekomendasyon:

1. Tingnan kung gaano katugma ang bawat variety sa mga pangangailangan ng magsasaka
2. Unahin ang mga varieties na tumutugma sa maraming criteria
3. Kung may free text ang magsasaka, bigyang-pansin ang kanilang mga partikular na alalahanin
4. Kung may lokasyon, isaalang-alang ang klima ng lugar (hal. mga lugar na madalas bagyuhin ay kailangan ng lodging resistance)
5. Kung may cropping season, irekomenda ang mga varieties na angkop sa season na iyon (wet season = flood/disease tolerance, dry season = drought tolerance)
6. Kung may farm size, isaalang-alang ang dami ng binhi na kakailanganin

## Response Format:
Sumagot ng JSON object sa ganitong eksaktong format (walang markdown, raw JSON lang):
{
  "recommendations": [
    {
      "id": <variety_id>,
      "rank": 1,
      "matchScore": <0-100>,
      "reason": "<Maikling 1-2 pangungusap sa TAGALOG kung bakit ito ang nirerekomenda, gamit ang 'po'. Hal: 'Maganda po ito para sa inyong bukid dahil matibay sa tagtuyot at mataas ang ani na hanggang 8 MT/ha.'>"
    },
    ...hanggang 5 na rekomendasyon
  ],
  "summary": "<Maikling 2-3 pangungusap sa TAGALOG na buod ng rekomendasyon para sa magsasaka, gamit ang 'po'. Maging friendly at encouraging. Hal: 'Base po sa inyong mga pangangailangan, ang mga variety na ito ang pinakaangkop para sa inyong bukid. Lahat po sila ay may magandang ani at matibay sa mga karaniwang problema sa inyong lugar.'>"
}

Mahalaga: Ang mga variety na ilalagay ay dapat galing LANG sa listahan sa itaas. Gamitin ang tamang ID numbers mula sa database.
PROMPT;
    }

    /**
     * Call the AI API based on provider.
     */
    private function callAiApi(RecomApiSetting $setting, string $prompt): array
    {
        $timeout = 60; // 60 seconds timeout

        switch ($setting->provider) {
            case RecomApiSetting::PROVIDER_CLAUDE:
                return $this->callClaudeApi($setting, $prompt, $timeout);

            case RecomApiSetting::PROVIDER_OPENAI:
                return $this->callOpenAiApi($setting, $prompt, $timeout);

            case RecomApiSetting::PROVIDER_GEMINI:
                return $this->callGeminiApi($setting, $prompt, $timeout);

            default:
                return ['success' => false, 'error' => 'Unsupported AI provider.'];
        }
    }

    /**
     * Call Claude API.
     */
    private function callClaudeApi(RecomApiSetting $setting, string $prompt, int $timeout): array
    {
        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-api-key' => $setting->apiKey,
                    'anthropic-version' => '2023-06-01',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => $setting->defaultModel ?? 'claude-3-5-sonnet-20241022',
                    'max_tokens' => $setting->maxTokens ?? 2048,
                    'temperature' => (float) ($setting->temperature ?? 0.7),
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['content'][0]['text'] ?? '';
                return ['success' => true, 'content' => $content];
            }

            return ['success' => false, 'error' => 'Claude API error: ' . $response->body()];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Claude API exception: ' . $e->getMessage()];
        }
    }

    /**
     * Call OpenAI API.
     */
    private function callOpenAiApi(RecomApiSetting $setting, string $prompt, int $timeout): array
    {
        try {
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $setting->apiKey,
            ];

            if ($setting->organizationId) {
                $headers['OpenAI-Organization'] = $setting->organizationId;
            }

            $response = Http::timeout($timeout)
                ->withHeaders($headers)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $setting->defaultModel ?? 'gpt-4o-mini',
                    'max_tokens' => $setting->maxTokens ?? 2048,
                    'temperature' => (float) ($setting->temperature ?? 0.7),
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '';
                return ['success' => true, 'content' => $content];
            }

            return ['success' => false, 'error' => 'OpenAI API error: ' . $response->body()];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'OpenAI API exception: ' . $e->getMessage()];
        }
    }

    /**
     * Call Gemini API.
     */
    private function callGeminiApi(RecomApiSetting $setting, string $prompt, int $timeout): array
    {
        try {
            $model = $setting->defaultModel ?? 'gemini-1.5-flash';
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $setting->apiKey;

            $response = Http::timeout($timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]]
                    ],
                    'generationConfig' => [
                        'temperature' => (float) ($setting->temperature ?? 0.7),
                        'maxOutputTokens' => $setting->maxTokens ?? 2048,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                return ['success' => true, 'content' => $content];
            }

            return ['success' => false, 'error' => 'Gemini API error: ' . $response->body()];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Gemini API exception: ' . $e->getMessage()];
        }
    }

    /**
     * Parse AI recommendations and match with varieties.
     */
    private function parseAiRecommendations(string $aiContent, $varieties): array
    {
        // Try to extract JSON from the response
        $jsonMatch = preg_match('/\{[\s\S]*"recommendations"[\s\S]*\}/', $aiContent, $matches);

        if (!$jsonMatch) {
            // If no JSON found, return empty with AI summary
            return [
                'items' => [],
                'summary' => $aiContent,
            ];
        }

        try {
            $parsed = json_decode($matches[0], true);

            if (!isset($parsed['recommendations']) || !is_array($parsed['recommendations'])) {
                return [
                    'items' => [],
                    'summary' => $aiContent,
                ];
            }

            $recommendations = [];
            foreach ($parsed['recommendations'] as $rec) {
                $varietyId = $rec['id'] ?? null;
                if (!$varietyId) continue;

                $variety = $varieties->firstWhere('id', $varietyId);
                if (!$variety) continue;

                $recommendations[] = [
                    'id' => $variety->id,
                    'name' => $variety->name,
                    'manufacturer' => $variety->manufacturer,
                    'potentialYield' => $variety->potentialYield,
                    'maturityDays' => $variety->maturityDays,
                    'imagePath' => $variety->imagePath,
                    'rank' => $rec['rank'] ?? count($recommendations) + 1,
                    'matchScore' => $rec['matchScore'] ?? 80,
                    'reason' => $rec['reason'] ?? 'Recommended based on your preferences.',
                ];
            }

            return [
                'items' => $recommendations,
                'summary' => $parsed['summary'] ?? 'Based on your preferences, here are the recommended varieties.',
            ];

        } catch (\Exception $e) {
            Log::error('Failed to parse AI recommendations: ' . $e->getMessage());
            return [
                'items' => [],
                'summary' => $aiContent,
            ];
        }
    }
}
