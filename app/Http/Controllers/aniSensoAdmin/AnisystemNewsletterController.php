<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Support\AnisystemMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * AniSystem > Newsletter (2026-10-17): anee.io's daily email, by priority and
 * by group, the same module as anee.io's own admin panel (the owner: "the
 * same module synced"). The screen is anee.io's kit (its /nl-kit files), and
 * every question it asks goes through api() here to anee.io's
 * /mother-api/newsletter/*, with the shared token, so both apps run one set
 * of rules on one set of rows. Pictures are kept here, like the website
 * pages' pictures, and anee.io's emails link to them.
 *
 * THE KIT COMES THROUGH THIS APP (2026-10-16, the owner: "the newsletter
 * screen did not load from anee.io"). The browser asked anee.io for the
 * kit's two files straight, and when that address was not the one that
 * answers, the page had nothing. kit() fetches them here, server side, from
 * ANISYSTEM_URL and then from https://anee.io, keeps the last good copy, and
 * serves them from this app's own address; api() falls back the same way.
 */
class AnisystemNewsletterController extends Controller
{
    /** anee.io's public address: the fallback when ANISYSTEM_URL does not answer. */
    private const ANEE = 'https://anee.io';

    private const KIT = ['nl.js' => 'text/javascript; charset=utf-8', 'nl.css' => 'text/css; charset=utf-8'];

    public function index()
    {
        return view('aniSensoAdmin.newsletter.index', [
            // The kit's files change with anee.io's deploys: refresh them every ten minutes.
            'kitVersion' => substr(md5(date('Y-m-d H') . floor((int) date('i') / 10)), 0, 8),
            'linked' => (string) config('services.anisystem_media.token') !== '',
        ]);
    }

    /** One of the kit's two files, from anee.io, served from here. */
    public function kit(string $file)
    {
        if (! isset(self::KIT[$file])) {
            abort(404);
        }
        $body = Cache::get('nlk:' . $file);
        $why = [];
        if (! is_string($body)) {
            foreach ($this->bases() as $base) {
                try {
                    $res = Http::timeout(15)->get($base . '/nl-kit/' . $file, ['v' => time()]);
                    if ($res->successful() && strlen($res->body()) > 1000 && ! str_contains(substr($res->body(), 0, 200), '<!doctype')) {
                        $body = $res->body();
                        Cache::put('nlk:' . $file, $body, 600);
                        Cache::forever('nlk:last:' . $file, $body);
                        break;
                    }
                    $why[] = $base . ' answered ' . $res->status();
                } catch (\Throwable $e) {
                    $why[] = $base . ' could not be reached';
                }
            }
        }
        // anee.io down: the last copy that worked is better than no screen.
        if (! is_string($body)) {
            $body = Cache::get('nlk:last:' . $file);
        }
        if (! is_string($body)) {
            $msg = 'The newsletter screen could not be fetched from anee.io (' . implode('; ', $why) . ').';
            $body = $file === 'nl.js' ? 'window.NLK_ERROR = ' . json_encode($msg) . ';' : '/* ' . str_replace('*/', '', $msg) . ' */';
        }

        return response($body, 200, ['Content-Type' => self::KIT[$file], 'Cache-Control' => 'private, max-age=600']);
    }

    public function api(Request $request, string $path)
    {
        $path = trim($path, '/');
        if ($path === 'upload') {
            return $this->upload($request);
        }
        $token = (string) config('services.anisystem_media.token');
        if ($token === '') {
            return response()->json(['success' => false, 'message' => 'This app is not linked to anee.io (ANISYSTEM_MEDIA_TOKEN is not set).'], 422);
        }
        $u = Auth::user();
        $http = Http::timeout(60)->acceptJson()->withHeaders([
            'X-Anee-Token' => $token,
            'X-Anee-Actor' => mb_substr((string) ($u->name ?? $u->email ?? 'an admin'), 0, 80),
            'X-Anee-Actor-Email' => (string) ($u->email ?? ''),
        ]);
        $body = $request->json()->all() ?: $request->except(['_token']);
        $res = null;
        $why = [];
        foreach ($this->bases() as $base) {
            try {
                $res = $request->isMethod('post')
                    ? $http->post($base . '/mother-api/newsletter/' . $path, $body)
                    : $http->get($base . '/mother-api/newsletter/' . $path, $request->query());
            } catch (\Throwable $e) {
                $why[] = $base . ' could not be reached';
                $res = null;

                continue;
            }
            // Only anee.io answers with the module's own JSON ({success: ...}); any
            // other address (an old host, a parked app) gets the next one tried.
            $j = $res->json();
            if (is_array($j) && array_key_exists('success', $j)) {
                break;
            }
            $why[] = $base . ' answered ' . $res->status();
            $res = null;
        }
        if (! $res) {
            return response()->json(['success' => false, 'message' => 'anee.io could not be reached: ' . implode('; ', $why) . '.'], 502);
        }
        $json = $res->json();
        if (! is_array($json)) {
            $json = ['success' => false, 'message' => 'anee.io answered ' . $res->status() . '.'];
        }

        return response()->json($json, $res->successful() ? 200 : ($res->status() >= 400 && $res->status() < 600 ? $res->status() : 422));
    }

    /** ANISYSTEM_URL first, then anee.io's public address. */
    private function bases(): array
    {
        $set = rtrim(trim((string) config('anisystem.url')), '/');
        if ($set !== '' && ! preg_match('#^https?://#i', $set)) {
            $set = 'https://' . $set;
        }

        return array_values(array_unique(array_filter([$set, self::ANEE])));
    }

    /** A picture for an email, kept here and served to every inbox from here. */
    private function upload(Request $request)
    {
        $file = $request->file('file');
        if (! $file || ! $file->isValid()) {
            return response()->json(['success' => false, 'message' => 'No picture came with that upload.'], 422);
        }
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
            return response()->json(['success' => false, 'message' => 'JPG, PNG or GIF: those show in every inbox.'], 422);
        }
        if ($file->getSize() > 6_000_000) {
            return response()->json(['success' => false, 'message' => 'Under 6 MB, please. Big pictures load slowly in an inbox.'], 413);
        }
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'picture';
        $path = 'anisystem/newsletter/' . date('Y/m') . '/' . Str::limit($name, 40, '') . '-' . Str::random(6) . '.' . $ext;
        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));
        $url = (string) AnisystemMedia::url(AnisystemMedia::REMOTE_PREFIX . $path);
        if (! Str::startsWith($url, ['http://', 'https://'])) {
            $url = rtrim((string) config('app.url'), '/') . '/' . ltrim($url, '/');
        }

        return response()->json(['success' => true, 'message' => 'ok', 'data' => ['url' => $url]]);
    }
}
