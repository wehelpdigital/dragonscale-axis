<?php

namespace App\Http\Controllers\AiTechnician;

use App\Http\Controllers\Controller;
use App\Models\AiApiSetting;
use App\Models\AiChatAvatarSetting;
use App\Models\AiCurrencySetting;
use App\Models\AiImageSearchSetting;
use App\Models\AiTechnicianAccessTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AiSettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index()
    {
        $userId = Auth::id();

        // Get or create settings for each provider
        $providers = [
            AiApiSetting::PROVIDER_CLAUDE,
            AiApiSetting::PROVIDER_OPENAI,
            AiApiSetting::PROVIDER_GEMINI,
        ];

        $settings = [];
        foreach ($providers as $provider) {
            $setting = AiApiSetting::active()
                                ->forProvider($provider)
                ->first();

            if (!$setting) {
                // Create default setting for this provider (global - no user)
                $setting = AiApiSetting::create([
                    'usersId' => null, // Global setting
                    'provider' => $provider,
                    'isActive' => false,
                    'isDefault' => $provider === AiApiSetting::PROVIDER_CLAUDE, // Claude as default
                    'delete_status' => 'active',
                ]);
            }

            $settings[$provider] = $setting;
        }

        $providerLabels = AiApiSetting::getProviderLabels();
        $providerIcons = AiApiSetting::getProviderIcons();
        $providerColors = AiApiSetting::getProviderColors();
        $modelsByProvider = AiApiSetting::getModelsByProvider();

        // Get access tags for this user
        $accessTags = AiTechnicianAccessTag::active()
                        ->orderBy('created_at', 'desc')
            ->get();

        // Get image search settings
        $imageSearchSettings = AiImageSearchSetting::getOrCreate();
        $imageSearchProviders = AiImageSearchSetting::getProviderOptions();

        // Get currency settings
        $currencySettings = AiCurrencySetting::getOrCreate();

        // Get avatar settings
        $avatarSettings = AiChatAvatarSetting::getOrCreate();

        return view('ai-technician.settings', compact(
            'settings',
            'providerLabels',
            'providerIcons',
            'providerColors',
            'modelsByProvider',
            'accessTags',
            'imageSearchSettings',
            'imageSearchProviders',
            'currencySettings',
            'avatarSettings'
        ));
    }

    /**
     * Update API settings for a provider.
     */
    public function update(Request $request, $provider)
    {
        $validator = Validator::make($request->all(), [
            'apiKey' => 'nullable|string|max:500',
            'organizationId' => 'nullable|string|max:255',
            'defaultModel' => 'nullable|string|max:100',
            'maxTokens' => 'nullable|integer|min:1|max:200000',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'requestsPerMinute' => 'nullable|integer|min:1|max:10000',
            'tokensPerMinute' => 'nullable|integer|min:1|max:10000000',
            'isActive' => 'nullable|boolean',
            'visionEnabled' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $setting = AiApiSetting::active()
                        ->forProvider($provider)
            ->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Settings not found.',
            ], 404);
        }

        try {
            $updateData = [
                'organizationId' => $request->organizationId,
                'defaultModel' => $request->defaultModel,
                'maxTokens' => $request->maxTokens ?? 4096,
                'temperature' => $request->temperature ?? 0.7,
                'requestsPerMinute' => $request->requestsPerMinute,
                'tokensPerMinute' => $request->tokensPerMinute,
                'isActive' => $request->boolean('isActive', false),
                'visionEnabled' => $request->boolean('visionEnabled', false),
            ];

            // Only update API key if provided (not empty)
            if ($request->filled('apiKey')) {
                $updateData['apiKey'] = $request->apiKey;
            }

            $setting->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Settings saved successfully.',
                'data' => [
                    'provider' => $provider,
                    'isActive' => $setting->isActive,
                    'hasApiKey' => $setting->hasApiKey(),
                    'maskedApiKey' => $setting->masked_api_key,
                    'statusBadge' => $setting->status_badge,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AI Settings update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save settings. Please try again.',
            ], 500);
        }
    }

    /**
     * Set a provider as the default.
     */
    public function setDefault($provider)
    {
        $userId = Auth::id();

        $setting = AiApiSetting::active()
                        ->forProvider($provider)
            ->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Settings not found.',
            ], 404);
        }

        try {
            // Remove default from all other providers
            AiApiSetting::active()
                                ->update(['isDefault' => false]);

            // Set this one as default
            $setting->update(['isDefault' => true]);

            return response()->json([
                'success' => true,
                'message' => ucfirst($provider) . ' set as default AI provider.',
            ]);
        } catch (\Exception $e) {
            Log::error('AI Settings set default error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to set default provider.',
            ], 500);
        }
    }

    /**
     * Test API connection for a provider.
     */
    public function testConnection($provider)
    {
        $setting = AiApiSetting::active()
                        ->forProvider($provider)
            ->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Settings not found.',
            ], 404);
        }

        if (!$setting->hasApiKey()) {
            return response()->json([
                'success' => false,
                'message' => 'API key is not configured.',
            ], 400);
        }

        try {
            $result = $this->performApiTest($setting);

            $setting->update([
                'lastTestedAt' => now(),
                'lastTestStatus' => $result['success'] ? AiApiSetting::STATUS_SUCCESS : AiApiSetting::STATUS_FAILED,
                'lastTestError' => $result['success'] ? null : $result['error'],
            ]);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Connection successful! API is working.',
                    'data' => [
                        'statusBadge' => $setting->fresh()->status_badge,
                        'testedAt' => $setting->lastTestedAt->format('Y-m-d H:i:s'),
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection failed: ' . $result['error'],
                    'data' => [
                        'statusBadge' => $setting->fresh()->status_badge,
                    ],
                ], 400);
            }
        } catch (\Exception $e) {
            $setting->update([
                'lastTestedAt' => now(),
                'lastTestStatus' => AiApiSetting::STATUS_FAILED,
                'lastTestError' => $e->getMessage(),
            ]);

            Log::error('AI API test error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'data' => [
                    'statusBadge' => $setting->fresh()->status_badge,
                ],
            ], 500);
        }
    }

    /**
     * Perform the actual API test based on provider.
     */
    private function performApiTest(AiApiSetting $setting): array
    {
        switch ($setting->provider) {
            case AiApiSetting::PROVIDER_CLAUDE:
                return $this->testClaudeApi($setting);
            case AiApiSetting::PROVIDER_OPENAI:
                return $this->testOpenAiApi($setting);
            case AiApiSetting::PROVIDER_GEMINI:
                return $this->testGeminiApi($setting);
            default:
                return ['success' => false, 'error' => 'Unknown provider'];
        }
    }

    /**
     * Test Claude (Anthropic) API.
     */
    private function testClaudeApi(AiApiSetting $setting): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-api-key' => $setting->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => $setting->defaultModel ?: 'claude-3-5-haiku-20241022',
                    'max_tokens' => 10,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Say "test" only.']
                    ],
                ]);

            if ($response->successful()) {
                return ['success' => true];
            }

            $error = $response->json('error.message') ?? $response->body();
            return ['success' => false, 'error' => $error];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Test OpenAI API.
     */
    private function testOpenAiApi(AiApiSetting $setting): array
    {
        try {
            $headers = [
                'Authorization' => 'Bearer ' . $setting->apiKey,
                'Content-Type' => 'application/json',
            ];

            if ($setting->organizationId) {
                $headers['OpenAI-Organization'] = $setting->organizationId;
            }

            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $setting->defaultModel ?: 'gpt-3.5-turbo',
                    'max_tokens' => 10,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Say "test" only.']
                    ],
                ]);

            if ($response->successful()) {
                return ['success' => true];
            }

            $error = $response->json('error.message') ?? $response->body();
            return ['success' => false, 'error' => $error];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Test Gemini (Google) API.
     */
    private function testGeminiApi(AiApiSetting $setting): array
    {
        try {
            $model = $setting->defaultModel ?: 'gemini-1.5-flash';
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $setting->apiKey;

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($url, [
                    'contents' => [
                        ['parts' => [['text' => 'Say "test" only.']]]
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => 10,
                    ],
                ]);

            if ($response->successful()) {
                return ['success' => true];
            }

            $error = $response->json('error.message') ?? $response->body();
            return ['success' => false, 'error' => $error];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get the active default API setting for use in AI operations.
     */
    public function getActiveProvider()
    {
        $setting = AiApiSetting::active()
                        ->enabled()
            ->default()
            ->first();

        if (!$setting) {
            // Fallback to any enabled provider
            $setting = AiApiSetting::active()
                                ->enabled()
                ->first();
        }

        if (!$setting || !$setting->hasApiKey()) {
            return response()->json([
                'success' => false,
                'message' => 'No active AI provider configured.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'provider' => $setting->provider,
                'model' => $setting->defaultModel,
                'maxTokens' => $setting->maxTokens,
                'temperature' => $setting->temperature,
            ],
        ]);
    }

    // ==================== IMAGE SEARCH SETTINGS METHODS ====================

    /**
     * Update image search settings.
     * AI images use Gemini, web images use Serper API.
     */
    public function updateImageSearchSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'maxImagesPerRequest' => 'nullable|integer|min:2|max:6',
            'isEnabled' => 'nullable|boolean',
            'apiKey' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $settings = AiImageSearchSetting::getOrCreate();

            $updateData = [
                'provider' => AiImageSearchSetting::PROVIDER_SERPER,
                'maxImagesPerRequest' => $request->maxImagesPerRequest ?? 4,
                'isEnabled' => $request->boolean('isEnabled', true),
            ];

            // Only update API key if provided (not empty)
            if ($request->filled('apiKey')) {
                $updateData['apiKey'] = $request->apiKey;
            }

            $settings->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Image search settings saved successfully.',
                'data' => [
                    'provider' => 'serper',
                    'isEnabled' => $settings->isEnabled,
                    'maxImagesPerRequest' => $settings->maxImagesPerRequest,
                    'hasSerperKey' => !empty($settings->apiKey),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Image search settings update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save settings. Please try again.',
            ], 500);
        }
    }

    /**
     * Test Serper API connection.
     */
    public function testSerperApi(Request $request)
    {
        $userId = Auth::id();
        $settings = AiImageSearchSetting::getOrCreate();

        // Use provided API key or existing one
        $apiKey = $request->filled('apiKey') ? $request->apiKey : $settings->apiKey;

        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'No Serper API key provided. Please enter your API key first.',
            ], 400);
        }

        try {
            // Test the Serper API with a simple image search
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-API-KEY' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://google.serper.dev/images', [
                    'q' => 'corn plant',
                    'num' => 1,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $imageCount = count($data['images'] ?? []);

                return response()->json([
                    'success' => true,
                    'message' => "Serper API is working! Found {$imageCount} image(s) in test search.",
                ]);
            }

            $errorBody = $response->json();
            $errorMessage = $errorBody['message'] ?? $errorBody['error'] ?? 'Unknown error';

            return response()->json([
                'success' => false,
                'message' => 'Serper API error: ' . $errorMessage,
            ], 400);

        } catch (\Exception $e) {
            Log::error('Serper API test error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test image generation with Gemini API.
     */
    public function testImageSearchApi()
    {
        $userId = Auth::id();

        // Check if Gemini is configured
        $geminiSetting = AiApiSetting::active()
                        ->forProvider(AiApiSetting::PROVIDER_GEMINI)
            ->first();

        if (!$geminiSetting || !$geminiSetting->hasApiKey()) {
            return response()->json([
                'success' => false,
                'message' => 'Gemini API is not configured. Please set up Gemini in the API Settings tab first.',
            ], 400);
        }

        try {
            // Test with a simple agricultural image generation
            $result = $this->testGeminiImageGeneration($geminiSetting);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Gemini image generation is working! Images will be AI-generated when requested.',
                    'data' => [
                        'note' => 'Images are generated by AI, not searched from the web.',
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);

        } catch (\Exception $e) {
            Log::error('Image generation test error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test Gemini image generation capability.
     */
    private function testGeminiImageGeneration(AiApiSetting $setting): array
    {
        // Test if Gemini API is responding
        $model = 'gemini-2.0-flash-exp';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $setting->apiKey;

        $response = Http::timeout(30)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, [
                'contents' => [
                    ['parts' => [['text' => 'Say "Image generation ready" in 3 words or less.']]]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 10,
                ],
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'Gemini API is ready for image generation.',
            ];
        }

        $error = $response->json('error.message') ?? $response->body();
        return [
            'success' => false,
            'message' => 'Gemini API error: ' . $error,
        ];
    }

    // ==================== ACCESS TAGS METHODS ====================

    /**
     * Get all access tags for the user.
     */
    public function getAccessTags()
    {
        $tags = AiTechnicianAccessTag::active()
                        ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'tagName' => $tag->tagName,
                    'expirationLength' => $tag->expirationLength,
                    'expirationLengthHuman' => $tag->expiration_length_human,
                    'description' => $tag->description,
                    'createdAt' => $tag->created_at->format('M d, Y H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $tags,
        ]);
    }

    /**
     * Store a new access tag.
     */
    public function storeAccessTag(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tagName' => 'required|string|max:255',
            'expirationLength' => 'required|integer|min:1|max:3650', // Max ~10 years
            'description' => 'nullable|string|max:1000',
        ], [
            'tagName.required' => 'Tag name is required.',
            'tagName.max' => 'Tag name must not exceed 255 characters.',
            'expirationLength.required' => 'Expiration length is required.',
            'expirationLength.min' => 'Expiration length must be at least 1 day.',
            'expirationLength.max' => 'Expiration length must not exceed 3650 days.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $tag = AiTechnicianAccessTag::create([
                'usersId' => Auth::id(),
                'tagName' => $request->tagName,
                'expirationLength' => $request->expirationLength,
                'description' => $request->description,
                'delete_status' => 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Access tag created successfully.',
                'data' => [
                    'id' => $tag->id,
                    'tagName' => $tag->tagName,
                    'expirationLength' => $tag->expirationLength,
                    'expirationLengthHuman' => $tag->expiration_length_human,
                    'description' => $tag->description,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Access tag creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create access tag.',
            ], 500);
        }
    }

    /**
     * Get a single access tag.
     */
    public function getAccessTag($id)
    {
        $tag = AiTechnicianAccessTag::where('id', $id)
                        ->where('delete_status', 'active')
            ->first();

        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Access tag not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $tag->id,
                'tagName' => $tag->tagName,
                'expirationLength' => $tag->expirationLength,
                'description' => $tag->description,
            ],
        ]);
    }

    /**
     * Update an access tag.
     */
    public function updateAccessTag(Request $request, $id)
    {
        $tag = AiTechnicianAccessTag::where('id', $id)
                        ->where('delete_status', 'active')
            ->first();

        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Access tag not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'tagName' => 'required|string|max:255',
            'expirationLength' => 'required|integer|min:1|max:3650',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $tag->update([
                'tagName' => $request->tagName,
                'expirationLength' => $request->expirationLength,
                'description' => $request->description,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Access tag updated successfully.',
                'data' => [
                    'id' => $tag->id,
                    'tagName' => $tag->tagName,
                    'expirationLength' => $tag->expirationLength,
                    'expirationLengthHuman' => $tag->expiration_length_human,
                    'description' => $tag->description,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Access tag update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update access tag.',
            ], 500);
        }
    }

    /**
     * Delete an access tag (soft delete).
     */
    public function destroyAccessTag($id)
    {
        $tag = AiTechnicianAccessTag::where('id', $id)
                        ->where('delete_status', 'active')
            ->first();

        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Access tag not found.',
            ], 404);
        }

        try {
            $tag->update(['delete_status' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => 'Access tag deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Access tag delete error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete access tag.',
            ], 500);
        }
    }

    /**
     * Get currency settings (for AJAX).
     */
    public function getCurrencySettings()
    {
        $settings = AiCurrencySetting::getOrCreate();

        return response()->json([
            'success' => true,
            'data' => [
                'usdToPhpRate' => (float) $settings->usdToPhpRate,
                'formattedRate' => $settings->formatted_rate,
                'lastUpdate' => $settings->lastRateUpdate ? $settings->lastRateUpdate->format('Y-m-d H:i:s') : null,
                'lastUpdateAgo' => $settings->last_update_ago,
                'autoUpdate' => $settings->autoUpdate,
            ],
        ]);
    }

    /**
     * Update currency settings.
     */
    public function updateCurrencySettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'usdToPhpRate' => 'nullable|numeric|min:1|max:1000',
            'autoUpdate' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $settings = AiCurrencySetting::getOrCreate();

            if ($request->has('usdToPhpRate')) {
                $settings->usdToPhpRate = $request->usdToPhpRate;
            }

            if ($request->has('autoUpdate')) {
                $settings->autoUpdate = $request->boolean('autoUpdate');
            }

            $settings->save();

            return response()->json([
                'success' => true,
                'message' => 'Currency settings updated successfully.',
                'data' => [
                    'usdToPhpRate' => (float) $settings->usdToPhpRate,
                    'formattedRate' => $settings->formatted_rate,
                    'autoUpdate' => $settings->autoUpdate,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Currency settings update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update currency settings.',
            ], 500);
        }
    }

    /**
     * Refresh exchange rate from API.
     */
    public function refreshExchangeRate()
    {
        try {
            $settings = AiCurrencySetting::getOrCreate();
            $success = $settings->refreshRate();

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Exchange rate updated successfully.',
                    'data' => [
                        'usdToPhpRate' => (float) $settings->usdToPhpRate,
                        'formattedRate' => $settings->formatted_rate,
                        'lastUpdate' => $settings->lastRateUpdate->format('Y-m-d H:i:s'),
                        'lastUpdateAgo' => $settings->last_update_ago,
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch exchange rate from API. Please try again.',
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Exchange rate refresh error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error refreshing exchange rate: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ==================== AVATAR SETTINGS METHODS ====================

    /**
     * Get avatar settings.
     */
    public function getAvatarSettings()
    {
        $settings = AiChatAvatarSetting::getOrCreate();

        return response()->json([
            'success' => true,
            'data' => [
                'avatarUrl' => $settings->avatar_url,
                'displayName' => $settings->displayName,
                'useCustomAvatar' => $settings->useCustomAvatar,
                'hasCustomAvatar' => $settings->hasCustomAvatar(),
            ],
        ]);
    }

    /**
     * Update avatar settings (upload new avatar or update display name).
     */
    public function updateAvatarSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'displayName' => 'nullable|string|max:100',
            'useCustomAvatar' => 'nullable|boolean',
            'avatar' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048', // 2MB max
        ], [
            'avatar.image' => 'The file must be an image.',
            'avatar.mimes' => 'Avatar must be a JPEG, PNG, GIF, or WebP image.',
            'avatar.max' => 'Avatar size must not exceed 2MB.',
            'displayName.max' => 'Display name must not exceed 100 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $userId = Auth::id();
            $settings = AiChatAvatarSetting::getOrCreate();

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');

                // Delete old avatar if exists
                $settings->deleteOldAvatar();

                // Generate unique filename
                $filename = 'avatar_' . $userId . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs(
                    AiChatAvatarSetting::getStoragePath($userId),
                    $filename,
                    'public'
                );

                $settings->avatarPath = $path;
                $settings->avatarFilename = $file->getClientOriginalName();
                $settings->useCustomAvatar = true;
            }

            // Update display name if provided
            if ($request->has('displayName')) {
                $settings->displayName = $request->displayName ?: 'AI Technician';
            }

            // Update useCustomAvatar toggle if provided (without new upload)
            if ($request->has('useCustomAvatar') && !$request->hasFile('avatar')) {
                $settings->useCustomAvatar = $request->boolean('useCustomAvatar');
            }

            $settings->save();

            return response()->json([
                'success' => true,
                'message' => 'Avatar settings updated successfully.',
                'data' => [
                    'avatarUrl' => $settings->avatar_url,
                    'displayName' => $settings->displayName,
                    'useCustomAvatar' => $settings->useCustomAvatar,
                    'hasCustomAvatar' => $settings->hasCustomAvatar(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Avatar settings update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update avatar settings.',
            ], 500);
        }
    }

    /**
     * Delete custom avatar and revert to default.
     */
    public function deleteAvatar()
    {
        try {
            $settings = AiChatAvatarSetting::getOrCreate();

            // Delete the avatar file
            $settings->deleteOldAvatar();

            // Reset to defaults
            $settings->update([
                'avatarPath' => null,
                'avatarFilename' => null,
                'useCustomAvatar' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Avatar removed. Using default avatar.',
                'data' => [
                    'avatarUrl' => $settings->avatar_url,
                    'useCustomAvatar' => false,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Avatar delete error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete avatar.',
            ], 500);
        }
    }
}
