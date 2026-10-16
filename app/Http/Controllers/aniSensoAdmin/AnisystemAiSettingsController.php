<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AnisystemUser;
use App\Models\AsAiSetting;
use App\Models\AsSiteSetting;
use App\Support\AiHouseCost;
use App\Support\AiPricingModel;
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

        [$prices, $economics] = $this->priceList($packs);

        // The international face: dollar prices and the PayPal account.
        $plans = DB::table('anisystem_plans')->where('deleteStatus', 1)->orderBy('sortOrder')->get();
        $usd = self::USD_DEFAULTS;
        try {
            $set = json_decode((string) AsSiteSetting::get('prices.usd', ''), true) ?: [];
            foreach (['tiers', 'plans', 'packs'] as $g) {
                foreach ((array) ($set[$g] ?? []) as $k => $v) {
                    if (is_array($v)) {
                        foreach ($v as $kk => $vv) {
                            if (is_numeric($vv)) {
                                $usd[$g][$k][$kk] = (float) $vv;
                            }
                        }
                    } elseif (is_numeric($v)) {
                        $usd[$g][$k] = (float) $v;
                    }
                }
            }
            if (is_numeric($set['rate'] ?? null)) {
                $usd['rate'] = (float) $set['rate'];
            }
            $paypal = json_decode((string) AsSiteSetting::get('pay.paypal', ''), true) ?: [];
        } catch (\Throwable $e) {
            $paypal = [];
        }
        $addons = ['fieldpack' => ['php' => 20, 'usd' => 0.5]];
        try {
            $addons['fieldpack']['php'] = (float) AsSiteSetting::get('addons.fieldpack.php', 20);
            $addons['fieldpack']['usd'] = (float) AsSiteSetting::get('addons.fieldpack.usd', 0.5);
        } catch (\Throwable $e) {
        }

        // The pricing engine (2026-10-15): its numbers, the margin table, what the runs really cost.
        try {
            $pricing = $this->pricingEngine($prices, $settings);
        } catch (\Throwable $e) {
            Log::warning('AniSystem AI pricing engine table failed: ' . $e->getMessage());
            $pricing = null;
        }

        return view('aniSensoAdmin.anisystemAi.index', compact('settings', 'packs', 'usage', 'creditsSold', 'prices', 'economics', 'plans', 'usd', 'paypal', 'addons', 'pricing'))
            ->with('secretConfigured', AiKeyCipher::available());
    }

    /**
     * Anee's price list: the flat prices, in credits, of the analyses that
     * are not metered (anee's App\Support\AiPrices reads the same shelf),
     * and under each what the runs of the last thirty days actually cost
     * the house, so a margin can be read and a price moved.
     */
    public const PRICE_KINDS = [
        'wtp' => 'When to Plant analysis',
        'wtpVariety' => 'When to Plant analysis with a named variety',
        'what' => 'What to Plant analysis',
        'variety' => 'Variety Ranking (searches the web)',
        'protocol' => 'Crop Protocol Analysis (season plan by growth stage, searches the web)',
        'season' => 'Anee Season Report',
        'sofar' => 'Analyze So Far report',
        'compare' => 'Comparison analysis',
        'realign' => 'Realign by Anee (growth stage)',
        'builder' => 'Protocol Builder review (Anee reads a protocol the farmer wrote)',
        'satellite' => 'Satellite Analysis (Sentinel-2 and Sentinel-1 read of a drawn field, searches the web)',
        'skyweather' => 'Satellite Weather (clouds, storm tracks and the forecast, read against a lot)',
        'npk' => 'NPK Plus, analyzed by Anee (a fertilizer calculation, searches the web)',
        'npkplan' => 'NPK Plus season check (Activities, per lot)',
        'npkproto' => 'NPK Plus protocol check (Protocol Builder)',
        'auto' => 'Anee Auto, per lot (a whole season planned and kept on track by Anee)',
        'skypass' => 'Satellite Weather, a week pass (never sold under the pricing engine quote for a week)',
        'lcc' => 'LCC Checker (Anee reads rice leaf photos against the leaf color chart)',
        'landprep' => 'Land Prep Planner, per lot (Anee plans land and seed preparation per lot, around the crew)',
        'compat' => 'Compatibility Checker, the online check (Anee looks up the products the book lacks; a flat price for one product or six)',
    ];

    /**
     * The international face's prices, in US dollars (anee.io shows them
     * to every farmer outside the Philippines). Keyed like anee.io's
     * config/regions.php `usd`; saved to the shelf as `prices.usd`.
     */
    public const USD_DEFAULTS = [
        'tiers' => ['libreAnee' => ['month' => 1.49, 'year' => 14.99], 'solo' => ['month' => 5, 'year' => 45], 'owner' => ['month' => 12, 'year' => 120]],
        'plans' => ['monthly' => 9.99, 'season' => 24.99, 'annual' => 69.99],
        'packs' => ['starter' => 1.99, 'farmer' => 8.99, 'season' => 17.99],
        'rate' => 58,
    ];

    // The owner's price list of 2026-10-16 (anee.io's AiPrices::DEFAULTS twin).
    public const PRICE_DEFAULTS = [
        'wtp' => 100, 'wtpVariety' => 150, 'what' => 50, 'variety' => 100, 'protocol' => 150, 'season' => 100, 'sofar' => 50, 'compare' => 30, 'realign' => 30, 'builder' => 100,
        'satellite' => 100, 'skyweather' => 20, 'npk' => 80, 'npkplan' => 25, 'npkproto' => 20, 'auto' => 1000, 'skypass' => 50, 'lcc' => 30, 'landprep' => 50, 'compat' => 80,
    ];

    private function priceList($packs): array
    {
        $set = json_decode((string) AsSiteSetting::get('ai.prices', ''), true) ?: [];
        $prices = self::PRICE_DEFAULTS;
        foreach ($set as $k => $v) {
            if (isset($prices[$k]) && is_numeric($v) && (int) $v >= 1) {
                $prices[$k] = (int) $v;
            }
        }

        // What a credit sells for: the cheapest and the dearest pack.
        $perCredit = collect($packs)->filter(fn ($p) => (int) $p->credits > 0 && (int) ($p->isActive ?? 1) === 1)
            ->map(fn ($p) => (float) $p->price / (int) $p->credits);
        $low = (float) ($perCredit->min() ?? 0.75);
        $high = (float) ($perCredit->max() ?? 0.99);

        $rows = collect();
        try {
            $rows = DB::table('as_ai_usage')
                ->where('created_at', '>=', now()->subDays(30))
                ->selectRaw('kind, COUNT(*) as runs, AVG(tokensIn) as tin, AVG(tokensOut) as tout, AVG(meteredCredits) as metered, SUM(searched) as searched, MAX(provider) as provider, MAX(model) as model')
                ->groupBy('kind')->get()->keyBy('kind');
        } catch (\Throwable $e) {
            // anee has not deployed the usage table yet: the list without the meter.
        }

        $economics = [];
        foreach (self::PRICE_KINDS as $kind => $name) {
            $r = $rows[$kind] ?? null;
            $runs = (int) ($r->runs ?? 0);
            $cost = $runs ? AiHouseCost::pesos($r->provider, $r->model, (int) round($r->tin), (int) round($r->tout), (int) $r->searched > 0) : null;
            $economics[$kind] = [
                'name' => $name,
                'price' => $prices[$kind],
                'default' => self::PRICE_DEFAULTS[$kind],
                'pesoLow' => $prices[$kind] * $low,
                'pesoHigh' => $prices[$kind] * $high,
                'runs' => $runs,
                'tokensIn' => $runs ? (int) round($r->tin) : null,
                'tokensOut' => $runs ? (int) round($r->tout) : null,
                'metered' => $runs ? (float) $r->metered : null,
                'searched' => $runs ? (int) $r->searched : 0,
                'houseCost' => $cost,
                'margin' => ($cost && $cost > 0) ? ($prices[$kind] * $low) / $cost : null,
            ];
        }

        return [$prices, $economics];
    }

    /** Save Anee's price list to the shelf anee reads. */
    public function savePrices(Request $request)
    {
        $rules = [];
        foreach (array_keys(self::PRICE_KINDS) as $k) {
            $rules['prices.' . $k] = 'required|integer|min:1|max:100000';
        }
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }
        try {
            $clean = [];
            foreach (array_keys(self::PRICE_KINDS) as $k) {
                $clean[$k] = (int) $request->input('prices.' . $k);
            }
            AsSiteSetting::put('ai.prices', json_encode($clean));
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'The settings shelf is not there yet — anee.io has to deploy its migration first.'], 422);
        }

        return response()->json(['success' => true, 'message' => "Anee's price list saved. anee.io reads it on the next run."]);
    }

    /** Save the add-on prices anee.io sells (the field helpers pack, a month since 2026-10-16). */
    public function saveAddons(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fieldpack.php' => 'required|numeric|min:1|max:100000',
            'fieldpack.usd' => 'required|numeric|min:0.5|max:10000',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }
        try {
            AsSiteSetting::put('addons.fieldpack.php', (string) round((float) $request->input('fieldpack.php'), 2));
            AsSiteSetting::put('addons.fieldpack.usd', (string) round((float) $request->input('fieldpack.usd'), 2));
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Could not save: ' . $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Add-on prices saved. anee.io reads them on the next page.']);
    }

    /**
     * Save the international prices (US dollars) and the PayPal account
     * anee.io shows farmers outside the Philippines.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveUsd(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'usd' => 'required|array',
            'usd.tiers' => 'nullable|array',
            'usd.tiers.*.month' => 'nullable|numeric|min:0|max:100000',
            'usd.tiers.*.year' => 'nullable|numeric|min:0|max:100000',
            'usd.plans' => 'nullable|array',
            'usd.plans.*' => 'nullable|numeric|min:0|max:100000',
            'usd.packs' => 'nullable|array',
            'usd.packs.*' => 'nullable|numeric|min:0|max:100000',
            'usd.rate' => 'nullable|numeric|min:1|max:1000',
            'paypal' => 'nullable|array',
            'paypal.email' => 'nullable|email|max:190',
            'paypal.link' => 'nullable|url|max:500',
            'paypal.name' => 'nullable|string|max:190',
            'paypal.instructions' => 'nullable|string|max:2000',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }
        try {
            $usd = ['tiers' => [], 'plans' => [], 'packs' => []];
            foreach ((array) $request->input('usd.tiers', []) as $tier => $row) {
                foreach (['month', 'year'] as $period) {
                    if (is_numeric($row[$period] ?? null)) {
                        $usd['tiers'][preg_replace('/[^a-z0-9_-]/i', '', $tier)][$period] = round((float) $row[$period], 2);
                    }
                }
            }
            foreach (['plans', 'packs'] as $g) {
                foreach ((array) $request->input('usd.' . $g, []) as $k => $v) {
                    if (is_numeric($v)) {
                        $usd[$g][preg_replace('/[^a-z0-9_-]/i', '', $k)] = round((float) $v, 2);
                    }
                }
            }
            if (is_numeric($request->input('usd.rate'))) {
                $usd['rate'] = round((float) $request->input('usd.rate'), 2);
            }
            AsSiteSetting::put('prices.usd', json_encode($usd));
            $paypal = [];
            foreach (['email', 'link', 'name', 'instructions'] as $k) {
                $paypal[$k] = trim((string) $request->input('paypal.' . $k, ''));
            }
            AsSiteSetting::put('pay.paypal', json_encode($paypal));
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Could not save: ' . $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'International prices saved. anee.io reads them on the next page.']);
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
     * The pricing engine for the owner (anee's App\Support\AiPricing, read
     * through its twin App\Support\AiPricingModel): the settings, every way
     * a credit is bought, each tool's cost model with its price at 1, 5, 20
     * and 50 units against today's flat price, what the runs of the last 90
     * days really cost and earned (as_ai_usage), and the metered chat.
     */
    private function pricingEngine(array $prices, AsAiSetting $ai): array
    {
        $s = AiPricingModel::settings();
        $provider = (string) $ai->provider;
        $model = (string) $ai->effectiveModel();
        $channels = AiPricingModel::channels();
        $ppc = AiPricingModel::pesoPerCredit($channels);
        [$pin, $pout, $fee] = AiPricingModel::modelRates($provider, $model);

        // What the runs of the last 90 days really cost, per kind (successful runs only: a failed run writes no row).
        $measured = [];
        $rows = DB::table('as_ai_usage')->where('created_at', '>=', now()->subDays(90))->get(['kind', 'provider', 'model', 'tokensIn', 'tokensOut', 'searched', 'credits']);
        foreach ($rows->groupBy('kind') as $kind => $group) {
            $costs = [];
            $margins = [];
            $under = 0;
            foreach ($group as $r) {
                $c = AiPricingModel::runCost($r->provider, $r->model, (int) $r->tokensIn, (int) $r->tokensOut, (bool) $r->searched);
                $costs[] = $c;
                if ((float) $r->credits > 0 && $c > 0) {
                    $m = (float) $r->credits * $ppc / $c;
                    $margins[] = $m;
                    $under += $m < $s['minMargin'] ? 1 : 0;
                }
            }
            sort($costs);
            sort($margins);
            $pick = fn (array $a, float $q) => $a ? $a[(int) floor($q * (count($a) - 1))] : null;
            $measured[$kind] = [
                'runs' => count($costs), 'median' => $pick($costs, 0.5), 'p90' => $pick($costs, 0.9),
                'charged' => $group->where('credits', '>', 0)->avg('credits'),
                'marginMedian' => $pick($margins, 0.5), 'marginLow' => $margins ? $margins[0] : null, 'under' => $under,
                'searched' => (int) $group->sum('searched'),
            ];
        }

        $kinds = [];
        $order = array_unique(array_merge(array_keys(self::PRICE_KINDS), array_keys($s['kinds'])));
        foreach ($order as $kind) {
            $k = AiPricingModel::kind($kind);
            if (! $k) {
                continue;
            }
            $flatOne = (int) ($prices[$kind] ?? 0);
            $sizes = [1];
            if ($k['unit'] !== '') {
                $sizes = [];
                foreach ([1, 5, 20, 50] as $n) {
                    $sizes[] = max((int) $k['min'], min((int) $k['max'] ?: 999, $n));
                }
                $sizes = array_values(array_unique($sizes));
            }
            $typical = AiPricingModel::quote($kind, [], $flatOne, $provider, $model, $ppc);
            $kinds[$kind] = [
                'name' => self::PRICE_KINDS[$kind] ?? AiPricingModel::NAMES[$kind] ?? $kind,
                'unit' => $k['unit'], 'unitWords' => AiPricingModel::UNIT_WORDS[$k['unit']] ?? [$k['unit'], $k['unit'] . 's'],
                'model' => $k, 'flat' => $flatOne, 'perUnitPrice' => ! empty($k['perUnitPrice']),
                'typical' => $typical,
                'sizes' => array_map(fn ($n) => ['units' => $n] + AiPricingModel::quote($kind, ['units' => $n], $flatOne, $provider, $model, $ppc), $sizes),
                // The extra blocks a run may add (Land Prep's variety lookup, When to Plant's named variety), priced at one unit.
                'opts' => array_map(fn ($opt) => AiPricingModel::quote($kind, ['units' => 1, $opt => true], $flatOne, $provider, $model, $ppc),
                    array_combine(array_keys((array) $k['opts']), array_keys((array) $k['opts']))),
                'measured' => $measured[$kind] ?? null,
            ];
        }

        // The metered chat: what a thousand tokens sell for at the floor over what they cost, and the last 90 days.
        $costInK = $pin / 1000 * $s['usdRate'];
        $costOutK = $pout / 1000 * $s['usdRate'];
        $chatRows = DB::table('anisystem_ai_messages')->where('deleteStatus', 1)->where('role', 'assistant')->where('created_at', '>=', now()->subDays(90))
            ->selectRaw('COUNT(*) as answers, COALESCE(SUM(tokensIn),0) as tin, COALESCE(SUM(tokensOut),0) as tout, COALESCE(SUM(creditsCharged),0) as credits')->first();
        $chatCost = AiPricingModel::runCost($provider, $model, (int) $chatRows->tin, (int) $chatRows->tout, false);
        $chat = [
            'perInputK' => (float) $ai->creditsPerInputK, 'perOutputK' => (float) $ai->creditsPerOutputK, 'perImage' => (float) $ai->creditsPerImage,
            'inMargin' => $costInK > 0 ? (float) $ai->creditsPerInputK * $ppc / $costInK : null,
            'outMargin' => $costOutK > 0 ? (float) $ai->creditsPerOutputK * $ppc / $costOutK : null,
            'okInputK' => ceil($s['minMargin'] * $costInK / $ppc * 10) / 10, 'okOutputK' => ceil($s['minMargin'] * $costOutK / $ppc * 10) / 10,
            'answers' => (int) $chatRows->answers, 'tokensIn' => (int) $chatRows->tin, 'tokensOut' => (int) $chatRows->tout, 'credits' => (float) $chatRows->credits,
            'cost' => $chatCost, 'margin' => $chatCost > 0 ? (float) $chatRows->credits * $ppc / $chatCost : null,
        ];

        return [
            'settings' => $s, 'defaults' => AiPricingModel::DEFAULTS, 'channels' => $channels, 'ppc' => $ppc, 'ppcAbroad' => AiPricingModel::pesoPerCreditAbroad($channels),
            'provider' => $provider, 'model' => $model, 'rates' => ['in' => $pin, 'out' => $pout, 'search' => $fee], 'family' => AiPricingModel::familyRates($provider, $model),
            'kinds' => $kinds, 'chat' => $chat,
            'skypassOpens' => AiPricingModel::skypassOpens(), 'editable' => AiPricingModel::EDITABLE,
        ];
    }

    /**
     * Save the pricing engine's numbers to the shelf anee.io prices by
     * (as_site_settings `ai.pricing`). Only what differs from the defaults
     * is kept, so a default anee.io improves later still reaches the owner.
     */
    public function savePricing(Request $request)
    {
        $rules = [
            'on' => 'required|boolean',
            'flatFloor' => 'nullable|boolean',
            'target' => 'required|numeric|min:1|max:50',
            'minMargin' => 'required|numeric|min:1|max:50',
            'maxMargin' => 'required|numeric|min:1|max:100',
            'pesoPerCredit' => 'nullable|numeric|min:0.01|max:100',
            'usdRate' => 'required|numeric|min:1|max:1000',
            'prices' => 'nullable|array',
            'prices.*' => 'nullable|numeric|min:0|max:100000',
            'kinds' => 'nullable|array',
            'kinds.*' => 'array',
            'kinds.*.*' => 'nullable|numeric|min:0|max:10000000',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }
        if ((float) $request->input('target') < (float) $request->input('minMargin')) {
            return response()->json(['success' => false, 'message' => 'The target margin cannot sit under the floor.'], 422);
        }
        $out = $this->pricingDiff($request);
        try {
            AsSiteSetting::put(AiPricingModel::KEY, json_encode($out));
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Could not save: ' . $e->getMessage()], 422);
        }
        AiPricingModel::forget();

        return response()->json(['success' => true, 'message' => 'Pricing engine saved. anee.io quotes by it on the next run.']);
    }

    /** What the form says that differs from the defaults: the JSON the shelf keeps. */
    private function pricingDiff(Request $request): array
    {
        $d = AiPricingModel::DEFAULTS;
        $out = ['on' => $request->boolean('on')];
        if ($request->boolean('flatFloor') !== (bool) $d['flatFloor']) {
            $out['flatFloor'] = $request->boolean('flatFloor');
        }
        foreach (['target', 'minMargin', 'maxMargin', 'usdRate'] as $k) {
            $v = round((float) $request->input($k), 4);
            if ($v != (float) $d[$k]) {
                $out[$k] = $v;
            }
        }
        if ($request->filled('pesoPerCredit')) {
            $out['pesoPerCredit'] = round((float) $request->input('pesoPerCredit'), 4);
        }
        foreach (array_keys($d['prices']) as $k) {
            $v = $request->input('prices.' . $k);
            if ($v !== null && $v !== '' && is_numeric($v) && ($d['prices'][$k] === null || (float) $v != (float) $d['prices'][$k])) {
                $out['prices'][$k] = (float) $v;
            }
        }
        foreach ((array) $request->input('kinds', []) as $kind => $row) {
            if (! isset($d['kinds'][$kind]) || ! is_array($row)) {
                continue;
            }
            $base = $d['kinds'][$kind] + ['min' => 1, 'free' => 1, 'units' => 1, 'max' => 1, 'baseIn' => 0, 'baseOut' => 0, 'unitIn' => 0, 'unitOut' => 0, 'outCap' => 0,
                'search' => 0, 'om' => 0, 'omUnit' => 0, 'maps' => 0, 'mapsUnit' => 0, 'mail' => 0, 'ee' => 0, 'usd' => 0, 'unitUsd' => 0, 'useObserve' => 0, 'useAsk' => 0];
            foreach (AiPricingModel::EDITABLE as $f) {
                if ($f === 'units' && ($base['unit'] ?? '') === '') {
                    continue;   // a one size tool has no size to set
                }
                $v = $row[$f] ?? null;
                if ($v !== null && $v !== '' && is_numeric($v) && (float) $v != (float) $base[$f]) {
                    $out['kinds'][$kind][$f] = (float) $v;
                }
            }
        }

        return $out;
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
