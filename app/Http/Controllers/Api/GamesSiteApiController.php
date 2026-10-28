<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GamePayment;
use App\Models\GameSiteGame;
use App\Services\Games\GamePaymentException;
use App\Services\Games\GamePayments;
use App\Services\Games\GamesSiteSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * /api/games-site/v1: what the studio website (wehelpdigital/game-website)
 * reads and sends, with its bearer token (GamesSiteToken).
 *
 *   GET  /site                       the website's settings and the games it features
 *   POST /checkouts/{token}          open the payment for a checkout link (once)
 *   GET  /checkouts/{token}          the payment as it stands now
 *   POST /checkouts/{token}/gcash    the buyer's GCash receipt (proof, reference)
 *
 * The buyer's own address (ip) is sent by the website; everything else about
 * a payment comes from the game server and this app.
 */
class GamesSiteApiController extends Controller
{
    public function __construct(private GamePayments $payments) {}

    public function site(): JsonResponse
    {
        $games = GameSiteGame::onSite()->get()->map(fn (GameSiteGame $g) => [
            'key' => $g->gameKey,
            'slug' => $g->slug,
            'name' => $g->name,
            'tagline' => $g->tagline,
            'summary' => $g->summary,
            'description' => $g->description,
            'genre' => $g->genre,
            'status' => $g->status,
            'playUrl' => $g->playUrl,
            'accent' => $g->accent,
            'logo' => self::url($g->logoImage),
            'hero' => self::url($g->heroImage),
            'platforms' => array_values(array_filter((array) $g->platforms, 'is_array')),
            'features' => array_values(array_filter((array) $g->features, 'is_array')),
            'screenshots' => array_values(array_map(fn ($s) => ['url' => self::url($s['url'] ?? null), 'caption' => (string) ($s['caption'] ?? '')],
                array_filter((array) $g->screenshots, fn ($s) => is_array($s) && ! empty($s['url'])))),
        ])->values();

        $site = GamesSiteSettings::site();

        return response()->json(['data' => [
            'site' => $site,
            'games' => $games,
            'payments' => ['gcashFee' => GamesSiteSettings::pay()['gcashFee'], 'gcashReady' => GamesSiteSettings::gcashReady()],
        ]]);
    }

    public function open(Request $request, string $token): JsonResponse
    {
        return $this->answer(fn () => $this->payments->open($token, $this->ip($request)));
    }

    public function show(string $token): JsonResponse
    {
        $payment = GamePayment::active()->where('checkoutToken', $token)->first();
        if (! $payment) {
            return response()->json(['message' => 'That payment link was not found.'], 404);
        }

        return response()->json(['data' => $this->payments->view($payment)]);
    }

    public function gcash(Request $request, string $token): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'proof' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:' . (int) config('games.site.proof_max_kb', 8192)],
            'reference' => ['nullable', 'string', 'max:64'],
        ], [
            'proof.required' => 'Choose the screenshot of your receipt.',
            'proof.mimetypes' => 'Upload the receipt as a picture (JPG, PNG or WebP).',
            'proof.max' => 'That picture is too big. A screenshot is enough.',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $payment = GamePayment::active()->where('checkoutToken', $token)->first();
        if (! $payment) {
            return response()->json(['message' => 'That payment link was not found.'], 404);
        }

        return $this->answer(function () use ($request, $payment) {
            $payment = $this->payments->submitGcash($payment, $request->file('proof'), $request->input('reference'), $this->ip($request));
            // The AI reads it once the website has its answer; the scheduler is the fallback.
            dispatch(fn () => app(GamePayments::class)->check(GamePayment::find($payment->id)))->afterResponse();

            return $payment;
        });
    }

    private function answer(callable $step): JsonResponse
    {
        try {
            $payment = $step();
        } catch (GamePaymentException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status);
        }

        return response()->json(['data' => $this->payments->view($payment)]);
    }

    private function ip(Request $request): ?string
    {
        $ip = (string) $request->input('ip', '');

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }

    private static function url(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        // An address; the website's own picture ("/images/..."); or a file uploaded here.
        return preg_match('#^(https?://|/)#i', $path) ? $path : Storage::disk('public')->url($path);
    }
}
