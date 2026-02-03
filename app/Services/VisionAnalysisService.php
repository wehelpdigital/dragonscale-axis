<?php

namespace App\Services;

use App\Models\AiApiSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VisionAnalysisService
{
    protected $settings;
    protected $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
        $this->loadSettings();
    }

    /**
     * Load the user's AI API settings (prefer vision-enabled default, then any vision-enabled).
     */
    protected function loadSettings()
    {
        // Try to get default provider that has vision enabled first
        $this->settings = AiApiSetting::active()
            ->forUser($this->userId)
            ->enabled()
            ->visionEnabled()
            ->default()
            ->first();

        // If no default with vision, get any active provider with vision enabled
        if (!$this->settings) {
            $this->settings = AiApiSetting::active()
                ->forUser($this->userId)
                ->enabled()
                ->visionEnabled()
                ->first();
        }
    }

    /**
     * Check if vision analysis is available.
     */
    public function isAvailable(): bool
    {
        return $this->settings && $this->settings->hasApiKey();
    }

    /**
     * Get the current provider name.
     */
    public function getProvider(): ?string
    {
        return $this->settings?->provider;
    }

    /**
     * Analyze an image and return a detailed description.
     */
    public function analyzeImage(string $imagePath, string $userDescription = ''): array
    {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'message' => 'No AI provider configured. Please configure an API key in AI Settings.',
            ];
        }

        try {
            // Read and encode the image
            if (!file_exists($imagePath)) {
                throw new \Exception('Image file not found.');
            }

            $imageData = file_get_contents($imagePath);
            $base64Image = base64_encode($imageData);
            $mimeType = mime_content_type($imagePath);

            // Build the prompt
            $prompt = $this->buildPrompt($userDescription);

            // Call the appropriate provider
            switch ($this->settings->provider) {
                case AiApiSetting::PROVIDER_CLAUDE:
                    return $this->analyzeWithClaude($base64Image, $mimeType, $prompt);
                case AiApiSetting::PROVIDER_OPENAI:
                    return $this->analyzeWithOpenAI($base64Image, $mimeType, $prompt);
                case AiApiSetting::PROVIDER_GEMINI:
                    return $this->analyzeWithGemini($base64Image, $mimeType, $prompt);
                default:
                    throw new \Exception('Unsupported provider: ' . $this->settings->provider);
            }
        } catch (\Exception $e) {
            Log::error('Vision analysis error: ' . $e->getMessage(), [
                'provider' => $this->settings?->provider,
                'userId' => $this->userId,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build the analysis prompt.
     */
    protected function buildPrompt(string $userDescription): string
    {
        $prompt = "Analyze this image in detail for a knowledge base. Provide a comprehensive description that includes:\n\n";
        $prompt .= "1. **Main Subject**: What is the primary focus of the image?\n";
        $prompt .= "2. **Visual Details**: Colors, shapes, text, objects, people, or elements visible\n";
        $prompt .= "3. **Context**: What setting, environment, or situation does this appear to be?\n";
        $prompt .= "4. **Key Information**: Any text, numbers, labels, or important data visible\n";
        $prompt .= "5. **Purpose/Use**: What might this image be used for or document?\n\n";

        if (!empty($userDescription)) {
            $prompt .= "The user provided this context: \"" . $userDescription . "\"\n\n";
            $prompt .= "Incorporate this context into your analysis and expand upon it with visual details.\n\n";
        }

        $prompt .= "Provide a detailed, searchable description that would help someone find this image when searching for related topics. Be specific and thorough.";

        return $prompt;
    }

    /**
     * Analyze image using Claude (Anthropic).
     */
    protected function analyzeWithClaude(string $base64Image, string $mimeType, string $prompt): array
    {
        $model = $this->settings->defaultModel ?: 'claude-sonnet-4-20250514';

        $response = Http::timeout(120)
            ->withHeaders([
                'x-api-key' => $this->settings->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => $this->settings->maxTokens ?: 4096,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'image',
                                'source' => [
                                    'type' => 'base64',
                                    'media_type' => $mimeType,
                                    'data' => $base64Image,
                                ],
                            ],
                            [
                                'type' => 'text',
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $content = $data['content'][0]['text'] ?? '';

            return [
                'success' => true,
                'analysis' => $content,
                'provider' => 'Claude',
                'model' => $model,
                'tokens' => $data['usage']['output_tokens'] ?? 0,
            ];
        }

        $error = $response->json();
        throw new \Exception($error['error']['message'] ?? 'Claude API error');
    }

    /**
     * Analyze image using OpenAI GPT-4 Vision.
     */
    protected function analyzeWithOpenAI(string $base64Image, string $mimeType, string $prompt): array
    {
        $model = $this->settings->defaultModel ?: 'gpt-4o';

        $headers = [
            'Authorization' => 'Bearer ' . $this->settings->apiKey,
            'Content-Type' => 'application/json',
        ];

        if ($this->settings->organizationId) {
            $headers['OpenAI-Organization'] = $this->settings->organizationId;
        }

        $response = Http::timeout(120)
            ->withHeaders($headers)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'max_tokens' => $this->settings->maxTokens ?: 4096,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $prompt,
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => 'data:' . $mimeType . ';base64,' . $base64Image,
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            return [
                'success' => true,
                'analysis' => $content,
                'provider' => 'OpenAI',
                'model' => $model,
                'tokens' => $data['usage']['completion_tokens'] ?? 0,
            ];
        }

        $error = $response->json();
        throw new \Exception($error['error']['message'] ?? 'OpenAI API error');
    }

    /**
     * Analyze image using Google Gemini.
     */
    protected function analyzeWithGemini(string $base64Image, string $mimeType, string $prompt): array
    {
        $model = $this->settings->defaultModel ?: 'gemini-1.5-flash';

        $response = Http::timeout(120)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post('https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $this->settings->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt,
                            ],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $base64Image,
                                ],
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'maxOutputTokens' => $this->settings->maxTokens ?: 4096,
                    'temperature' => $this->settings->temperature ?: 0.7,
                ],
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            return [
                'success' => true,
                'analysis' => $content,
                'provider' => 'Gemini',
                'model' => $model,
                'tokens' => $data['usageMetadata']['candidatesTokenCount'] ?? 0,
            ];
        }

        $error = $response->json();
        $errorMsg = $error['error']['message'] ?? 'Gemini API error';
        throw new \Exception($errorMsg);
    }
}
