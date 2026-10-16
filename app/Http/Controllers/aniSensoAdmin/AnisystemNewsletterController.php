<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Support\AnisystemMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
 */
class AnisystemNewsletterController extends Controller
{
    public function index()
    {
        $base = rtrim((string) config('anisystem.url'), '/');

        return view('aniSensoAdmin.newsletter.index', [
            'aneeBase' => $base,
            // The kit's files change with anee.io's deploys: refresh them every hour.
            'kitVersion' => substr(md5(date('Y-m-d H')), 0, 8),
            'linked' => $base !== '' && (string) config('services.anisystem_media.token') !== '',
        ]);
    }

    public function api(Request $request, string $path)
    {
        $path = trim($path, '/');
        if ($path === 'upload') {
            return $this->upload($request);
        }
        $token = (string) config('services.anisystem_media.token');
        $base = rtrim((string) config('anisystem.url'), '/');
        if ($token === '' || $base === '') {
            return response()->json(['success' => false, 'message' => 'This app is not linked to anee.io (ANISYSTEM_URL / ANISYSTEM_MEDIA_TOKEN).'], 422);
        }
        $u = Auth::user();
        $http = Http::timeout(60)->acceptJson()->withHeaders([
            'X-Anee-Token' => $token,
            'X-Anee-Actor' => mb_substr((string) ($u->name ?? $u->email ?? 'an admin'), 0, 80),
            'X-Anee-Actor-Email' => (string) ($u->email ?? ''),
        ]);
        $url = $base . '/mother-api/newsletter/' . $path;
        try {
            $res = $request->isMethod('post')
                ? $http->post($url, $request->json()->all() ?: $request->all())
                : $http->get($url, $request->query());
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'anee.io could not be reached: ' . $e->getMessage()], 502);
        }
        $json = $res->json();
        if (! is_array($json)) {
            $json = ['success' => false, 'message' => 'anee.io answered ' . $res->status() . '.'];
        }

        return response()->json($json, $res->successful() ? 200 : ($res->status() >= 400 && $res->status() < 600 ? $res->status() : 422));
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
