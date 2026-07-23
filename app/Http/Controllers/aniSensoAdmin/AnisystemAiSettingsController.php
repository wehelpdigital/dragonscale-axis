<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsAiSetting;
use App\Support\AiKeyCipher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * AniSystem AI settings: which provider answers crop questions, under what
 * prompt and avatar, and what a question costs the client in AI Credits.
 */
class AnisystemAiSettingsController extends Controller
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $settings = AsAiSetting::current();

        $packs = DB::table('anisystem_ai_credit_packs')
            ->where('deleteStatus', 1)
            ->orderBy('sortOrder')
            ->get();

        // Usage at a glance, so the operator can see whether the pricing works.
        $usage = DB::table('anisystem_ai_messages')
            ->where('deleteStatus', 1)
            ->where('role', 'assistant')
            ->selectRaw('COUNT(*) as answers, COALESCE(SUM(tokensIn),0) as tokensIn, COALESCE(SUM(tokensOut),0) as tokensOut, COALESCE(SUM(creditsCharged),0) as credits')
            ->first();

        $creditsSold = DB::table('anisystem_ai_credit_purchases')
            ->where('deleteStatus', 1)
            ->where('status', 'active')
            ->selectRaw('COALESCE(SUM(credits),0) as credits, COALESCE(SUM(price),0) as revenue')
            ->first();

        return view('aniSensoAdmin.anisystemAi.index', compact('settings', 'packs', 'usage', 'creditsSold'))
            ->with('secretConfigured', AiKeyCipher::available());
    }

    /**
     * Save the AI configuration.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'provider' => 'required|in:claude,openai,gemini',
                'model' => 'nullable|string|max:100',
                'apiKey' => 'nullable|string|max:400',
                'assistantName' => 'required|string|max:100',
                'systemPrompt' => 'required|string|max:20000',
                'creditsPerInputK' => 'required|numeric|min:0|max:9999',
                'creditsPerOutputK' => 'required|numeric|min:0|max:9999',
                'creditsPerImage' => 'required|numeric|min:0|max:9999',
                'freeCreditsOnSignup' => 'required|integer|min:0|max:100000',
                'maxOutputTokens' => 'required|integer|min:100|max:8192',
                'temperature' => 'required|numeric|min:0|max:2',
                'isEnabled' => 'nullable|boolean',
            ], [
                'systemPrompt.required' => 'The prompt tells the AI what it may answer — it cannot be empty.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            $settings = AsAiSetting::current();
            $wantEnabled = (bool) $request->boolean('isEnabled');
            $newKey = trim((string) $request->input('apiKey'));

            // A key is only storable when both apps share the secret.
            if ($newKey !== '' && ! AiKeyCipher::available()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ANISYSTEM_AI_KEY_SECRET is not set in this app\'s .env, so the API key cannot be stored securely. Add it (identical in both apps) and try again.',
                ], 422);
            }

            $settings->fill([
                'provider' => $request->input('provider'),
                'model' => $request->input('model') ?: AsAiSetting::DEFAULT_MODELS[$request->input('provider')],
                'assistantName' => $request->input('assistantName'),
                'systemPrompt' => $request->input('systemPrompt'),
                'creditsPerInputK' => $request->input('creditsPerInputK'),
                'creditsPerOutputK' => $request->input('creditsPerOutputK'),
                'creditsPerImage' => $request->input('creditsPerImage'),
                'freeCreditsOnSignup' => $request->input('freeCreditsOnSignup'),
                'maxOutputTokens' => $request->input('maxOutputTokens'),
                'temperature' => $request->input('temperature'),
                'deleteStatus' => 1,
            ]);

            if ($newKey !== '') {
                $settings->storeApiKey($newKey);
            }

            // Refuse to switch on without a key — otherwise every client gets
            // an error instead of an answer.
            if ($wantEnabled && ! $settings->hasKey()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Add an API key before switching the AI Technician on.',
                ], 422);
            }
            $settings->isEnabled = $wantEnabled ? 1 : 0;
            $settings->save();

            return response()->json([
                'success' => true,
                'message' => 'AI settings saved.',
                'data' => ['hasKey' => $settings->hasKey(), 'isEnabled' => (bool) $settings->isEnabled],
            ]);
        } catch (\Throwable $e) {
            Log::error('AniSystem AI settings save failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Could not save: '.$e->getMessage()], 500);
        }
    }

    /**
     * Replace the assistant's avatar.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'avatar.max' => 'The avatar must be 2MB or smaller.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $file = $request->file('avatar');
            // Extension from content, never from the client filename.
            $guessed = strtolower((string) $file->guessExtension());
            $ext = in_array($guessed, ['jpg', 'jpeg', 'png', 'webp'], true) ? $guessed : 'jpg';
            $name = 'ai-avatar-'.time().'.'.$ext;

            // AniSystem serves this from its own public/storage disk.
            $dir = rtrim(config('anisystem.storage_path', 'C:\\xampp\\htdocs\\anisystem\\storage\\app\\public'), '\\/')
                .DIRECTORY_SEPARATOR.'ai';

            if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
                throw new \RuntimeException('Could not create the avatar folder.');
            }

            $file->move($dir, $name);

            $settings = AsAiSetting::current();
            $settings->avatarPath = 'ai/'.$name;
            $settings->deleteStatus = 1;
            $settings->save();

            return response()->json([
                'success' => true,
                'message' => 'Avatar updated.',
                'data' => ['path' => $settings->avatarPath],
            ]);
        } catch (\Throwable $e) {
            Log::error('AniSystem AI avatar upload failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Upload failed: '.$e->getMessage()], 500);
        }
    }

    /**
     * Save the credit packs (name, credits, price, active).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function savePacks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'packs' => 'required|array|min:1',
            'packs.*.id' => 'required|integer',
            'packs.*.packName' => 'required|string|max:100',
            'packs.*.credits' => 'required|integer|min:1|max:1000000',
            'packs.*.price' => 'required|numeric|min:0|max:1000000',
            'packs.*.description' => 'nullable|string|max:500',
            'packs.*.isActive' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            foreach ($request->input('packs') as $pack) {
                DB::table('anisystem_ai_credit_packs')
                    ->where('id', $pack['id'])
                    ->update([
                        'packName' => $pack['packName'],
                        'credits' => $pack['credits'],
                        'price' => $pack['price'],
                        'description' => $pack['description'] ?? null,
                        'isActive' => ! empty($pack['isActive']) ? 1 : 0,
                        'updated_at' => now(),
                    ]);
            }

            return response()->json(['success' => true, 'message' => 'Credit packs saved.']);
        } catch (\Throwable $e) {
            Log::error('AniSystem AI packs save failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Could not save: '.$e->getMessage()], 500);
        }
    }
}
