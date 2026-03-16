<?php

namespace App\Http\Controllers\Recommendations;

use App\Http\Controllers\Controller;
use App\Models\RecomAccessTag;
use App\Models\RecomApiSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RecommendationSettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index()
    {
        // Get or create GLOBAL settings for each provider (not per-user)
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
                // Create global setting for this provider
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

        // Get access tags (these remain per-user)
        $accessTags = RecomAccessTag::active()
            ->forUser(Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('recommendations.settings.index', compact(
            'settings',
            'providerLabels',
            'providerIcons',
            'providerColors',
            'modelsByProvider',
            'accessTags'
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $setting = RecomApiSetting::active()
            ->global()
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
                'requestsPerMinute' => $request->requestsPerMinute ?? 60,
                'tokensPerMinute' => $request->tokensPerMinute ?? 100000,
                'isActive' => $request->boolean('isActive', false),
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
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Recommendation API Settings Update Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save settings.',
            ], 500);
        }
    }

    /**
     * Test API connection for a provider.
     */
    public function testConnection(Request $request, $provider)
    {
        $setting = RecomApiSetting::active()
            ->global()
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
                'message' => 'API key not configured.',
            ], 400);
        }

        try {
            $result = match ($provider) {
                RecomApiSetting::PROVIDER_CLAUDE => $this->testClaudeApi($setting),
                RecomApiSetting::PROVIDER_OPENAI => $this->testOpenAiApi($setting),
                RecomApiSetting::PROVIDER_GEMINI => $this->testGeminiApi($setting),
                default => ['success' => false, 'message' => 'Unknown provider.'],
            };

            // Update test status
            $setting->update([
                'lastTestedAt' => now(),
                'lastTestStatus' => $result['success'] ? 'success' : 'failed',
                'lastTestError' => $result['success'] ? null : ($result['message'] ?? 'Connection failed'),
            ]);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Recommendation API Test Error: ' . $e->getMessage());

            $setting->update([
                'lastTestedAt' => now(),
                'lastTestStatus' => 'failed',
                'lastTestError' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test Claude API connection.
     */
    private function testClaudeApi($setting): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $setting->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model' => $setting->defaultModel ?? 'claude-3-5-sonnet-20241022',
            'max_tokens' => 50,
            'messages' => [
                ['role' => 'user', 'content' => 'Say "Connection successful!" in exactly 2 words.']
            ],
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'Claude API connected successfully!',
                'response' => $response->json()['content'][0]['text'] ?? 'OK',
            ];
        }

        return [
            'success' => false,
            'message' => 'Claude API error: ' . ($response->json()['error']['message'] ?? 'Unknown error'),
        ];
    }

    /**
     * Test OpenAI API connection.
     */
    private function testOpenAiApi($setting): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $setting->apiKey,
            'Content-Type' => 'application/json',
        ];

        if ($setting->organizationId) {
            $headers['OpenAI-Organization'] = $setting->organizationId;
        }

        $response = Http::withHeaders($headers)
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $setting->defaultModel ?? 'gpt-4o-mini',
                'max_tokens' => 50,
                'messages' => [
                    ['role' => 'user', 'content' => 'Say "Connection successful!" in exactly 2 words.']
                ],
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'OpenAI API connected successfully!',
                'response' => $response->json()['choices'][0]['message']['content'] ?? 'OK',
            ];
        }

        return [
            'success' => false,
            'message' => 'OpenAI API error: ' . ($response->json()['error']['message'] ?? 'Unknown error'),
        ];
    }

    /**
     * Test Gemini API connection.
     */
    private function testGeminiApi($setting): array
    {
        $model = $setting->defaultModel ?? 'gemini-1.5-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $setting->apiKey;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($url, [
            'contents' => [
                ['parts' => [['text' => 'Say "Connection successful!" in exactly 2 words.']]]
            ],
            'generationConfig' => [
                'maxOutputTokens' => 50,
            ],
        ]);

        if ($response->successful()) {
            $text = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? 'OK';
            return [
                'success' => true,
                'message' => 'Gemini API connected successfully!',
                'response' => $text,
            ];
        }

        return [
            'success' => false,
            'message' => 'Gemini API error: ' . ($response->json()['error']['message'] ?? 'Unknown error'),
        ];
    }

    /**
     * Set a provider as the default.
     */
    public function setDefault(Request $request, $provider)
    {
        // Remove default from all global providers
        RecomApiSetting::active()
            ->global()
            ->update(['isDefault' => false]);

        // Set new default
        $setting = RecomApiSetting::active()
            ->global()
            ->forProvider($provider)
            ->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Settings not found.',
            ], 404);
        }

        $setting->update(['isDefault' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Default provider updated successfully.',
            'data' => [
                'provider' => $provider,
                'label' => $setting->provider_label,
            ],
        ]);
    }

    /**
     * Get active default provider.
     */
    public function getActiveProvider()
    {
        $setting = RecomApiSetting::active()
            ->global()
            ->default()
            ->enabled()
            ->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'No active default provider configured.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'provider' => $setting->provider,
                'label' => $setting->provider_label,
                'model' => $setting->defaultModel,
            ],
        ]);
    }

    // ==================== ACCESS TAGS ====================

    /**
     * Get all access tags for the user.
     */
    public function getAccessTags()
    {
        $tags = RecomAccessTag::active()
            ->forUser(Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'tagName' => $tag->tagName,
                    'expirationLength' => $tag->expirationLength,
                    'expirationLengthHuman' => $tag->expiration_length_human,
                    'description' => $tag->description,
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
            'expirationLength' => 'required|integer|min:1|max:3650',
            'description' => 'nullable|string|max:1000',
        ], [
            'tagName.required' => 'Tag name is required.',
            'tagName.max' => 'Tag name cannot exceed 255 characters.',
            'expirationLength.required' => 'Expiration length is required.',
            'expirationLength.min' => 'Expiration must be at least 1 day.',
            'expirationLength.max' => 'Expiration cannot exceed 3650 days (10 years).',
            'description.max' => 'Description cannot exceed 1000 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tag = RecomAccessTag::create([
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
            Log::error('Recommendation Access Tag Create Error: ' . $e->getMessage());
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
        $tag = RecomAccessTag::active()
            ->forUser(Auth::id())
            ->where('id', $id)
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
                'expirationLengthHuman' => $tag->expiration_length_human,
                'description' => $tag->description,
            ],
        ]);
    }

    /**
     * Update an access tag.
     */
    public function updateAccessTag(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'tagName' => 'required|string|max:255',
            'expirationLength' => 'required|integer|min:1|max:3650',
            'description' => 'nullable|string|max:1000',
        ], [
            'tagName.required' => 'Tag name is required.',
            'tagName.max' => 'Tag name cannot exceed 255 characters.',
            'expirationLength.required' => 'Expiration length is required.',
            'expirationLength.min' => 'Expiration must be at least 1 day.',
            'expirationLength.max' => 'Expiration cannot exceed 3650 days (10 years).',
            'description.max' => 'Description cannot exceed 1000 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $tag = RecomAccessTag::active()
            ->forUser(Auth::id())
            ->where('id', $id)
            ->first();

        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Access tag not found.',
            ], 404);
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
            Log::error('Recommendation Access Tag Update Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update access tag.',
            ], 500);
        }
    }

    /**
     * Soft delete an access tag.
     */
    public function destroyAccessTag($id)
    {
        $tag = RecomAccessTag::active()
            ->forUser(Auth::id())
            ->where('id', $id)
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
            Log::error('Recommendation Access Tag Delete Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete access tag.',
            ], 500);
        }
    }
}
