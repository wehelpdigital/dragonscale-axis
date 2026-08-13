<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Where AniSystem's uploads actually live.
 *
 * AniSystem runs on a container whose filesystem is wiped on every deploy, so
 * a photo saved on Tuesday was gone by Thursday and the note that pointed at
 * it showed nothing. This app has durable storage, so it keeps the files and
 * hands back a URL; AniSystem stores only that.
 *
 * Authenticated by a shared secret rather than a user session: the caller is
 * a server, not a person, and it has no session here.
 */
class AnisystemMediaController extends Controller
{
    /**
     * Folders AniSystem is allowed to write into, so a caller cannot invent
     * paths. Named after what the file is for, and kept in step with the
     * folders AniSystem actually passes — an unknown one lands in "misc"
     * rather than being refused, because losing a photo over a folder name is
     * a worse outcome than a slightly untidy shelf.
     */
    private const FOLDERS = [
        'notes', 'maps', 'drawings', 'board', 'activities', 'misc',
        // What the app sends today, folder by folder.
        'schedule-notes', 'schedule-activities', 'schedule-attachments',
        'schedule-doc-entries', 'schedule-post-harvest', 'schedule-protocols',
        'ai-photos', 'community', 'avatars', 'post-harvest',
    ];

    private const MAX_BYTES = 20_000_000;   // 20MB — far above any camera photo we accept

    public function store(Request $request)
    {
        if (! $this->authorised($request)) {
            // Says whether a token is configured here at all, never what it
            // is. Without this, a 401 is the same answer whether the caller
            // sent the wrong token or this app was deployed without one —
            // and those need very different fixes.
            return response()->json([
                'success' => false,
                'message' => 'Not authorised.',
                'configured' => config('services.anisystem_media.token') !== '',
            ], 401);
        }

        $folder = (string) $request->input('folder', 'misc');
        if (! in_array($folder, self::FOLDERS, true)) {
            $folder = 'misc';
        }

        [$binary, $ext] = $this->readPayload($request);
        if ($binary === null) {
            return response()->json(['success' => false, 'message' => 'No readable image in the request.'], 422);
        }
        if (strlen($binary) > self::MAX_BYTES) {
            return response()->json(['success' => false, 'message' => 'That file is too large.'], 413);
        }

        // Namespaced by the schedule when the caller says which, so one farm's
        // files stay together and can be cleaned up as a unit later.
        $scope = preg_replace('~[^0-9]~', '', (string) $request->input('scope', '')) ?: 'shared';
        // AniSystem reads meaning out of some filenames — a saved map, a team
        // drawing — so a caller may ask for a prefix. Letters and dashes only:
        // the rest of the name is ours.
        $prefix = substr(preg_replace('~[^A-Za-z-]~', '', (string) $request->input('prefix', '')), 0, 12);
        $path = 'anisystem/' . $folder . '/' . $scope . '/' . $prefix . Str::random(24) . '.' . $ext;

        Storage::disk('public')->put($path, $binary);

        return response()->json(['success' => true, 'data' => [
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'bytes' => strlen($binary),
        ]]);
    }

    /** Remove a file AniSystem has finished with (a superseded drawing, say). */
    public function destroy(Request $request)
    {
        if (! $this->authorised($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Not authorised.',
                'configured' => config('services.anisystem_media.token') !== '',
            ], 401);
        }

        $path = (string) $request->input('path');
        // Only inside our own tree, and no climbing out of it.
        if (! Str::startsWith($path, 'anisystem/') || str_contains($path, '..')) {
            return response()->json(['success' => false, 'message' => 'Not a path this API owns.'], 422);
        }

        Storage::disk('public')->delete($path);

        return response()->json(['success' => true]);
    }

    /**
     * A file arrives either as multipart (a photo the browser uploaded) or as
     * a data URL (a canvas the browser drew). Both end up as bytes here.
     *
     * @return array{0: ?string, 1: string} the bytes and the extension to use
     */
    private function readPayload(Request $request): array
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            if (! $file->isValid()) {
                return [null, ''];
            }
            $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');

            return [file_get_contents($file->getRealPath()), $this->safeExt($ext)];
        }

        // Video arrives this way too when the caller already has the bytes —
        // a clip it compressed itself rather than a browser upload.
        $dataUrl = (string) $request->input('image', '');
        if (! preg_match('~^data:(?:image|video)/(png|jpe?g|webp|gif|mp4|webm);base64,~i', $dataUrl, $m)) {
            return [null, ''];
        }
        $binary = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);

        return [$binary === false ? null : $binary, $this->safeExt(strtolower($m[1]))];
    }

    /** Never take an extension on trust — it decides how a browser treats the file. */
    private function safeExt(string $ext): string
    {
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;

        return in_array($ext, ['png', 'jpg', 'webp', 'gif', 'mp4', 'webm'], true) ? $ext : 'png';
    }

    private function authorised(Request $request): bool
    {
        $expected = (string) config('services.anisystem_media.token');
        if ($expected === '') {
            return false;                 // unconfigured means closed, not open
        }
        $given = (string) ($request->bearerToken() ?: $request->header('X-Api-Key', ''));

        return $given !== '' && hash_equals($expected, $given);
    }
}
