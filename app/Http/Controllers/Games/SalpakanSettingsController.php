<?php

namespace App\Http\Controllers\Games;

use App\Models\GameOrder;
use App\Models\GamePlayer;
use App\Models\GamePlayerActivity;
use App\Models\GameSetting;
use App\Models\GameSyncState;
use App\Services\Games\SalpakanSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Games > Salpakan Tactics! > Settings.
 *
 * Analytics codes: saved straight to the game server (PUT /analytics), which
 * is the only place they live; the page shows what the game last confirmed
 * and whether the last save was accepted. The two server-side secrets are
 * write-only: typed here, sent to the game, never kept in AXIS.
 *
 * Also: how the sync is doing (each step's last run, counts, errors, "Sync
 * now"), the game server and Acumbamail set-up, and the peso rate game orders
 * are logged into /ecom-orders at.
 */
class SalpakanSettingsController extends SalpakanController
{
    /** Field => [rule, message]. Formats as the game expects them; empty clears. */
    private const ANALYTICS_RULES = [
        'meta_pixel_id' => ['regex:/^\d{5,20}$/', 'The Meta Pixel ID is a number (5 to 20 digits).'],
        'ga4_measurement_id' => ['regex:/^G-[A-Z0-9]{4,20}$/', 'The GA4 measurement ID looks like G-XXXXXXX.'],
        'gtm_container_id' => ['regex:/^GTM-[A-Z0-9]{4,12}$/', 'The Tag Manager container ID looks like GTM-XXXXXXX.'],
        'tiktok_pixel_id' => ['regex:/^[A-Z0-9]{10,40}$/', 'The TikTok Pixel ID is 10 to 40 letters and digits.'],
        'google_ads_id' => ['regex:/^AW-\d{5,20}$/', 'The Google Ads conversion ID looks like AW-123456789.'],
        'google_ads_label' => ['regex:/^[A-Za-z0-9_\-]{1,100}$/', 'The Google Ads conversion label is letters, digits, - and _.'],
    ];

    public function index(SalpakanSyncService $sync)
    {
        $states = GameSyncState::forGame(self::GAME)->active()->get()->keyBy('dataType');
        $acumbamailCounts = GamePlayer::forGame(self::GAME)->active()
            ->selectRaw('acumbamailStatus, COUNT(*) as players')
            ->groupBy('acumbamailStatus')
            ->pluck('players', 'acumbamailStatus')
            ->all();

        return view('games.salpakan.settings', [
            'gameName' => $this->gameName(),
            'analytics' => GameSetting::getJson(self::GAME, 'analytics'),
            'analyticsFetchedAt' => GameSetting::getTime(self::GAME, 'analyticsFetchedAt'),
            'push' => [
                'at' => GameSetting::getTime(self::GAME, 'analyticsPushedAt'),
                'status' => GameSetting::getValue(self::GAME, 'analyticsPushStatus'),
                'message' => GameSetting::getValue(self::GAME, 'analyticsPushMessage'),
            ],
            'states' => $states,
            'types' => GameSyncState::TYPES,
            'counts' => [
                'players' => GamePlayer::forGame(self::GAME)->active()->count(),
                'activity' => GamePlayerActivity::forGame(self::GAME)->active()->count(),
                'orders' => GameOrder::forGame(self::GAME)->active()->count(),
                'ecom' => GameOrder::forGame(self::GAME)->active()->whereNotNull('ecomOrderId')->count(),
                'ecomErrors' => GameOrder::forGame(self::GAME)->active()->whereNotNull('ecomError')->count(),
            ],
            'acumbamailCounts' => $acumbamailCounts,
            'lastRunAt' => GameSetting::getTime(self::GAME, 'lastRunAt'),
            'lastRunBy' => GameSetting::getValue(self::GAME, 'lastRunBy'),
            'server' => [
                'url' => $sync->api()->baseUrl(),
                'tokenSet' => $sync->api()->isConfigured(),
            ],
            'acumbamail' => [
                'keySet' => $sync->acumbamail()->isConfigured(),
                'listId' => (int) config('games.salpakan.acumbamail_list_id'),
                'listName' => (string) config('games.salpakan.acumbamail_list_name'),
                'doubleOptin' => (bool) config('services.acumbamail.double_optin'),
                'maxAttempts' => (int) config('services.acumbamail.max_attempts', 5),
            ],
            'rate' => $sync->usdToPhpRate(),
            'rateIsSet' => is_numeric(GameSetting::getValue(self::GAME, 'usdToPhpRate')),
            'defaultRate' => (float) config('games.salpakan.usd_to_php_rate', 58),
            'currency' => $this->currency(),
        ]);
    }

    /** Save the analytics codes to the game, and say whether it took them. */
    public function saveAnalytics(Request $request, SalpakanSyncService $sync)
    {
        $input = $request->all();
        foreach (array_keys(self::ANALYTICS_RULES) as $field) {
            $input[$field] = trim((string) ($input[$field] ?? ''));
        }
        foreach (['ga4_measurement_id', 'gtm_container_id', 'google_ads_id', 'tiktok_pixel_id'] as $field) {
            $input[$field] = strtoupper($input[$field]);
        }

        $rules = ['enabled' => 'nullable|boolean', 'ga4_api_secret' => 'nullable|string|max:200', 'meta_access_token' => 'nullable|string|max:1000',
            'clear_ga4_api_secret' => 'nullable|boolean', 'clear_meta_access_token' => 'nullable|boolean'];
        $messages = [];
        foreach (self::ANALYTICS_RULES as $field => [$rule, $message]) {
            $rules[$field] = ['nullable', 'string', $rule];
            $messages[$field . '.regex'] = $message;
        }

        $validator = Validator::make($input, $rules, $messages);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        // Every code is sent (empty clears it); a secret only when typed or cleared.
        $payload = ['enabled' => filter_var($request->input('enabled', false), FILTER_VALIDATE_BOOLEAN)];
        foreach (array_keys(self::ANALYTICS_RULES) as $field) {
            $payload[$field] = $input[$field];
        }
        foreach (['ga4_api_secret', 'meta_access_token'] as $secret) {
            if ($request->boolean('clear_' . $secret)) {
                $payload[$secret] = '';
            } elseif (trim((string) $request->input($secret)) !== '') {
                $payload[$secret] = trim((string) $request->input($secret));
            }
        }

        try {
            $result = $sync->pushAnalytics($payload);
        } catch (\Throwable $e) {
            Log::error('[Games] Salpakan analytics save failed: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Could not save the analytics codes.', 'error' => $e->getMessage()], 500);
        }

        if (! $result['ok']) {
            return response()->json([
                'success' => false,
                'message' => $result['status'] === 422
                    ? 'The game did not accept these codes: ' . $result['message']
                    : 'Not saved: ' . $result['message'],
                'errors' => $result['errors'],
            ], $result['status'] === 422 ? 422 : 502);
        }

        return response()->json(['success' => true, 'message' => $result['message'], 'data' => $result['data']]);
    }

    /** Read the analytics codes from the game again. */
    public function refreshAnalytics(SalpakanSyncService $sync)
    {
        $result = $sync->refreshAnalytics();
        $ok = in_array($result['status'] ?? null, ['ok', 'partial'], true);

        return response()->json([
            'success' => $ok,
            'message' => $result['message'] ?? 'Done.',
            'data' => GameSetting::getJson(self::GAME, 'analytics'),
        ], $ok ? 200 : 502);
    }

    /**
     * Run the whole sync now. It starts nothing new after 25 seconds (the
     * scheduled run carries on from there), and finishes what it started even
     * if the browser or a gateway gives up waiting.
     */
    public function syncNow(SalpakanSyncService $sync)
    {
        ignore_user_abort(true);
        @set_time_limit(120);

        try {
            $results = $sync->run(null, 25, 'admin');
        } catch (\Throwable $e) {
            Log::error('[Games] Salpakan sync now failed: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'The sync could not run.', 'error' => $e->getMessage()], 500);
        }

        $failed = collect($results)->where('status', 'failed')->keys()->all();

        return response()->json([
            'success' => true,
            'message' => $failed
                ? 'Sync finished with problems in: ' . implode(', ', $failed) . '.'
                : 'Sync finished.',
            'data' => $results,
        ]);
    }

    /** The peso rate game orders are logged into /ecom-orders at (blank = SALPAKAN_USD_TO_PHP_RATE). */
    public function saveRate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'usdToPhpRate' => 'nullable|numeric|min:1|max:1000',
        ], [
            'usdToPhpRate.numeric' => 'The rate is a number, like 58.25.',
            'usdToPhpRate.min' => 'The rate must be at least 1.',
            'usdToPhpRate.max' => 'The rate must be at most 1000.',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $rate = $request->filled('usdToPhpRate') ? round((float) $request->input('usdToPhpRate'), 4) : null;
        GameSetting::setValue(self::GAME, 'usdToPhpRate', $rate);

        return response()->json([
            'success' => true,
            'message' => $rate ? 'Orders from now on are logged at ₱' . number_format($rate, 2) . ' per ' . $this->currency() . '.' : 'Back to the default rate.',
        ]);
    }

    /** Let the next sync try again every player Acumbamail refused. */
    public function retryAcumbamail()
    {
        $count = GamePlayer::forGame(self::GAME)->active()
            ->where('acumbamailStatus', 'failed')
            ->update(['acumbamailStatus' => 'pending', 'acumbamailAttempts' => 0, 'acumbamailMessage' => null]);

        return response()->json([
            'success' => true,
            'message' => $count === 0 ? 'Nobody to try again.' : $count . ' ' . ($count === 1 ? 'player' : 'players') . ' will be tried again on the next sync.',
        ]);
    }
}
