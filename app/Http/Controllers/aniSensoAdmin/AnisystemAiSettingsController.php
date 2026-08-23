<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AnisystemUser;
use App\Models\AsAiSetting;
use App\Services\AniSenso\CommunityAiAnswerService;
use App\Support\AiKeyCipher;
use App\Support\AnisystemMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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

            $previous = AsAiSetting::current()->avatarPath;

            /*
             * Where the face goes, and therefore whose /storage serves it.
             *
             * This used to write into AniSystem's own storage directory over
             * the filesystem. On one machine running both apps that works; on
             * the deployment it cannot — the two are separate containers, so
             * the file went nowhere while the shared database happily recorded
             * a path AniSystem had no file for. That is the broken image.
             *
             * So: if AniSystem's disk is genuinely reachable from here (a
             * local install), keep writing there and record a bare path,
             * which is AniSystem's own to serve. Otherwise the file is ours,
             * written to our public disk and marked `mm:` — the same prefix
             * every other file shared between these two apps carries, which
             * both sides already know how to resolve.
             *
             * The directory is never created: its existence is the test.
             */
            $aniRoot = rtrim((string) config('anisystem.storage_path'), '\\/');
            $aniReachable = $aniRoot !== '' && is_dir($aniRoot) && is_writable($aniRoot);

            if ($aniReachable) {
                $dir = $aniRoot.DIRECTORY_SEPARATOR.'ai';
                if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
                    throw new \RuntimeException('Could not create the avatar folder.');
                }
                $file->move($dir, $name);
                $path = 'ai/'.$name;
            } else {
                Storage::disk('public')->putFileAs('ai', $file, $name);
                $path = AnisystemMedia::REMOTE_PREFIX.'ai/'.$name;
            }

            $settings = AsAiSetting::current();
            $settings->avatarPath = $path;
            $settings->deleteStatus = 1;
            $settings->save();

            $this->forgetAvatar($previous);
            // The AI answers community questions under its own account; that
            // face is this one, or the discussions show initials while the
            // chat shows a portrait.
            $this->syncPersonaAvatar($settings);

            return response()->json([
                'success' => true,
                'message' => 'Avatar updated.',
                // The URL as well as the path: only this side knows which of
                // the two disks the file just landed on.
                'data' => ['path' => $settings->avatarPath, 'url' => AnisystemMedia::url($settings->avatarPath)],
            ]);
        } catch (\Throwable $e) {
            Log::error('AniSystem AI avatar upload failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Upload failed: '.$e->getMessage()], 500);
        }
    }

    /**
     * Give the community's AI Technician account the same face.
     *
     * A member reading a discussion sees the persona's user row, not the AI
     * settings, so the two have to be told the same thing. Best effort: a
     * failure here must not fail an upload that has already succeeded.
     */
    private function syncPersonaAvatar(AsAiSetting $settings): void
    {
        try {
            $persona = AnisystemUser::where('email', CommunityAiAnswerService::PERSONA_EMAIL)->first();
            if ($persona) {
                $persona->avatarPath = $settings->avatarPath;
                $persona->save();
            }
        } catch (\Throwable $e) {
            Log::warning('AI persona avatar sync failed: '.$e->getMessage());
        }
    }

    /** Take the old face off whichever disk was holding it. */
    private function forgetAvatar(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        try {
            if (AnisystemMedia::isOurs($path)) {
                Storage::disk('public')->delete(ltrim(substr($path, strlen(AnisystemMedia::REMOTE_PREFIX)), '/'));

                return;
            }

            $aniRoot = rtrim((string) config('anisystem.storage_path'), '\\/');
            $file = $aniRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, ltrim($path, '/'));
            if ($aniRoot !== '' && is_file($file)) {
                @unlink($file);
            }
        } catch (\Throwable $e) {
            // An avatar nobody points at any more is litter, not a failure.
            Log::warning('Old AI avatar could not be removed: '.$e->getMessage());
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
